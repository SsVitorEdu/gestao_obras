<?php
// IMPORTADOR FINANCEIRO IMOBILIÁRIO (VERSÃO FINAL LIMPA)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); 
ini_set('memory_limit', '1024M');

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

// --- FUNÇÕES DE CORREÇÃO ---
function limpaValor($v) {
    if(is_numeric($v)) return (float)$v;
    if(!isset($v) || trim($v) === '') return 0.00;
    $v = preg_replace('/[^\d,.-]/', '', $v); 
    $v = str_replace('.', '', $v); 
    $v = str_replace(',', '.', $v);
    return (float)$v;
}

function dataSQL($v) {
    if(empty($v) || $v == '-' || $v == 'NULL') return null;
    
    // CASO 1: Excel Serial (Número > 20000)
    // O Excel conta dias desde 1900. O PHP conta segundos desde 1970.
    if(is_numeric($v) && $v > 20000) {
        // Ajuste matemático preciso para datas do Excel
        $unix_date = ($v - 25569) * 86400;
        return gmdate("Y-m-d", $unix_date); // gmdate usa UTC para não voltar 1 dia
    }
    
    $v = trim($v);
    // CASO 2: Data Texto (DD/MM/AAAA)
    if(strpos($v, '/') !== false) { 
        $p = explode('/', $v); 
        if(count($p)==3) return "{$p[2]}-{$p[1]}-{$p[0]}"; 
    }
    
    return null; 
}

// --- PROCESSAMENTO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['json_dados'])) {
    
    $dados = json_decode($_POST['json_dados'], true);
    
    if (!$dados) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao decodificar arquivo.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $novos_clientes = 0;
        $novas_vendas = 0;
        $parcelas_proc = 0;

        // Prepared Statements
        $stmtBuscaCli = $pdo->prepare("SELECT id FROM clientes_imob WHERE nome = ? LIMIT 1");
        $stmtInsCli   = $pdo->prepare("INSERT INTO clientes_imob (nome, cpf) VALUES (?, ?)");
        
        $stmtBuscaVenda = $pdo->prepare("SELECT id FROM vendas_imob WHERE codigo_compra = ? LIMIT 1");
        $stmtInsVenda   = $pdo->prepare("INSERT INTO vendas_imob (cliente_id, codigo_compra, nome_casa, nome_empresa, data_inicio, data_fim, data_contrato, valor_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtUpdVenda   = $pdo->prepare("UPDATE vendas_imob SET nome_empresa=?, data_inicio=?, data_fim=?, data_contrato=? WHERE id=?");

        // Busca Parcela (Evita duplicar se já existir no mesmo dia com mesmo valor)
        $stmtBuscaParc = $pdo->prepare("SELECT id FROM parcelas_imob WHERE venda_id = ? AND data_vencimento = ? AND ABS(valor_parcela - ?) < 0.1 LIMIT 1");
        $stmtInsParc   = $pdo->prepare("INSERT INTO parcelas_imob (venda_id, numero_parcela, data_vencimento, valor_parcela, data_pagamento, valor_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUpdParc   = $pdo->prepare("UPDATE parcelas_imob SET data_pagamento=?, valor_pago=? WHERE id=?");

        foreach($dados as $d) {
            $nome_cli = strtoupper(trim($d['cliente']));
            $cod_cont = trim($d['contrato']); // POSRECTO_FORNECEDOR
            
            // Se não tiver nome ou contrato, pula (linha vazia ou lixo)
            if(empty($nome_cli) || empty($cod_cont)) continue;

            // 1. CLIENTE
            $stmtBuscaCli->execute([$nome_cli]);
            if($row = $stmtBuscaCli->fetch()) {
                $cli_id = $row['id'];
            } else {
                $stmtInsCli->execute([$nome_cli, $d['cpf']]);
                $cli_id = $pdo->lastInsertId();
                $novos_clientes++;
            }

            // 2. VENDA (CONTRATO)
            $venda_id = null;
            $stmtBuscaVenda->execute([$cod_cont]);
            if($row = $stmtBuscaVenda->fetch()) {
                $venda_id = $row['id'];
                // Atualiza dados gerais do contrato
                $stmtUpdVenda->execute([
                    $d['empresa'], dataSQL($d['dt_ini']), dataSQL($d['dt_fim']), dataSQL($d['dt_rel']), $venda_id
                ]);
            } else {
                $nome_casa = "CONTRATO " . $cod_cont;
                $stmtInsVenda->execute([
                    $cli_id, $cod_cont, $nome_casa, $d['empresa'], 
                    dataSQL($d['dt_ini']), dataSQL($d['dt_fim']), dataSQL($d['dt_rel']), 0
                ]);
                $venda_id = $pdo->lastInsertId();
                $novas_vendas++;
            }

            // 3. PARCELA
            $dt_venc = dataSQL($d['vencimento']);
            $vlr_orig = limpaValor($d['vlr_orig']); // TOTAL ORIGINAL
            
            // Só processa se tiver data de vencimento e valor > 0
            if($dt_venc && $vlr_orig > 0) {
                
                // Verifica se essa parcela já existe
                $stmtBuscaParc->execute([$venda_id, $dt_venc, $vlr_orig]);
                
                $dt_pag = dataSQL($d['dt_pag']); // DATA RECEBIMENTO
                $vlr_pago = limpaValor($d['vlr_pago']); // TOTAL LIQUIDO RECEBIDO

                if($rowParc = $stmtBuscaParc->fetch()) {
                    // JÁ EXISTE: ATUALIZA O PAGAMENTO (BAIXA)
                    // Só atualiza se vier informação de pagamento nova
                    if($vlr_pago > 0 || !empty($dt_pag)) {
                        $stmtUpdParc->execute([$dt_pag, $vlr_pago, $rowParc['id']]);
                    }
                } else {
                    // NÃO EXISTE: INSERE NOVA PARCELA
                    $stmtInsParc->execute([$venda_id, 0, $dt_venc, $vlr_orig, $dt_pag, $vlr_pago]);
                }
                $parcelas_proc++;
            }
        }
        
        // Atualiza Valor Total do Contrato (Soma das parcelas)
        $pdo->query("UPDATE vendas_imob v SET valor_total = (SELECT SUM(valor_parcela) FROM parcelas_imob p WHERE p.venda_id = v.id)");
        
        $pdo->commit();

        echo json_encode([
            'status' => 'sucesso', 
            'msg' => "<b>Sucesso!</b><br>Clientes: $novos_clientes<br>Contratos: $novas_vendas<br>Parcelas Processadas: $parcelas_proc"
        ]);

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
    }
    exit;
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-success fw-bold m-0"><i class="bi bi-file-earmark-excel-fill"></i> IMPORTADOR DE CLIENTES</h3>
            <span class="text-muted">Importação do Arquivo Simplificado (12 Colunas)</span>
        </div>
        <a href="index.php?page=clientes" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-5 text-center">
            
            <div id="drop_zone" style="border: 3px dashed #198754; padding: 40px; border-radius: 10px; cursor: pointer; background: #f8fff9;">
                <i class="bi bi-cloud-upload display-1 text-success opacity-50"></i>
                <h4 class="mt-3">Arraste o arquivo <b>.XLSX</b> aqui</h4>
                <p class="text-muted">O sistema vai ler automaticamente as colunas: "TOTAL ORIGINAL", "CLIENTE", etc.</p>
                <input type="file" id="fileInput" accept=".xlsx, .xls, .csv" style="display: none;">
            </div>

            <div id="loading" class="mt-4" style="display: none;">
                <div class="spinner-border text-success" role="status"></div>
                <p class="fw-bold mt-2 text-success">Lendo arquivo e mapeando colunas...</p>
                <div id="log_msg" class="text-muted small"></div>
            </div>

            <div id="resultado" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('drop_zone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#e8f5e9'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.background = '#f8fff9'; });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault(); dropZone.style.background = '#f8fff9';
    if(e.dataTransfer.files.length) processarArquivo(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', (e) => {
    if(fileInput.files.length) processarArquivo(fileInput.files[0]);
});

function processarArquivo(file) {
    document.getElementById('loading').style.display = 'block';
    document.getElementById('resultado').innerHTML = '';
    document.getElementById('log_msg').innerText = "Iniciando leitura...";

    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.SheetNames[0];
        // Lê tudo como matriz para podermos achar o cabeçalho
        const rows = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], {header: 1, defval: ''});

        // 1. LOCALIZAR A LINHA DE CABEÇALHO
        let headerIndex = -1;
        // Procura palavras chave que você confirmou
        for(let i=0; i < Math.min(rows.length, 20); i++) {
            const linha = JSON.stringify(rows[i]).toUpperCase();
            if(linha.includes("TOTAL ORIGINAL") && linha.includes("CLIENTE")) {
                headerIndex = i;
                break;
            }
        }

        if(headerIndex === -1) {
            alert('ERRO: Não encontrei as colunas "TOTAL ORIGINAL" e "CLIENTE" no início do arquivo.');
            document.getElementById('loading').style.display = 'none';
            return;
        }

        document.getElementById('log_msg').innerText = "Cabeçalho encontrado na linha " + (headerIndex+1) + ". Processando dados...";

        // 2. MAPEAR COLUNAS (ÍNDICES DINÂMICOS)
        const headerRow = rows[headerIndex].map(c => String(c).trim().toUpperCase());
        const map = {};

        headerRow.forEach((col, idx) => {
            if(col.includes("DATA INICIO")) map['DT_INI'] = idx;
            else if(col.includes("DATA FIM")) map['DT_FIM'] = idx;
            else if(col.includes("RELATORIO")) map['DT_REL'] = idx;
            else if(col.includes("RAZA") || col.includes("SOCIAL")) map['EMPRESA'] = idx;
            
            else if(col.includes("VENCIMENTO")) map['VENCIMENTO'] = idx;
            else if(col.includes("DE RECEBIMENTO")) map['DT_PAG'] = idx;
            
            else if(col.includes("LIQUIDO RECEBIDO")) map['VLR_PAGO'] = idx;
            else if(col.includes("TOTAL ORIGINAL")) map['VLR_ORIG'] = idx;
            
            else if(col.includes("POSRECTO_FORNECEDOR")) map['CONTRATO'] = idx;
            else if(col === "CLIENTE") map['CLIENTE'] = idx;
            else if(col.includes("CPF") || col.includes("CNPJ")) map['CPF'] = idx;
        });

        // 3. EXTRAIR DADOS
        const dadosFinais = [];
        for(let i = headerIndex + 1; i < rows.length; i++) {
            let r = rows[i];
            
            // Só pega se tiver Valor Original (garantia que é uma parcela)
            if(!r[map['VLR_ORIG']] && !r[map['CLIENTE']]) continue;

            dadosFinais.push({
                dt_ini:     excelDateToJS(r[map['DT_INI']]),
                dt_fim:     excelDateToJS(r[map['DT_FIM']]),
                dt_rel:     excelDateToJS(r[map['DT_REL']]),
                empresa:    String(r[map['EMPRESA']] ?? '').trim(),
                
                vencimento: excelDateToJS(r[map['VENCIMENTO']]),
                dt_pag:     excelDateToJS(r[map['DT_PAG']]),
                
                vlr_pago:   limparMoeda(r[map['VLR_PAGO']]),
                vlr_orig:   limparMoeda(r[map['VLR_ORIG']]),
                
                contrato:   String(r[map['CONTRATO']] ?? '').trim(),
                cliente:    String(r[map['CLIENTE']] ?? '').trim(),
                cpf:        String(r[map['CPF']] ?? '').trim()
            });
        }

        document.getElementById('log_msg').innerText = dadosFinais.length + " linhas válidas encontradas. Enviando para o banco...";
        enviarParaPHP(dadosFinais);
    };
    reader.readAsArrayBuffer(file);
}

function enviarParaPHP(dados) {
    const formData = new FormData();
    formData.append('json_dados', JSON.stringify(dados));

    fetch('pages/importar_clientes.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';
        const resDiv = document.getElementById('resultado');
        if(data.status === 'sucesso') resDiv.innerHTML = `<div class="alert alert-success fs-5 shadow-sm">${data.msg}</div>`;
        else resDiv.innerHTML = `<div class="alert alert-danger">Erro: ${data.msg}</div>`;
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';
        alert('Erro de conexão: ' + error);
    });
}

// Helpers
function excelDateToJS(serial) {
    if (!serial) return null;
    if (typeof serial === 'number') {
        // Correção de Fuso: Adiciona alguns segundos para garantir que caia no dia certo ao converter
        const date = new Date(Math.round((serial - 25569) * 86400 * 1000) + (12*60*60*1000)); 
        return date.toISOString().split('T')[0];
    }
    if (typeof serial === 'string' && serial.includes('/')) {
        const parts = serial.split('/');
        if(parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return null;
}

function limparMoeda(v) {
    if (typeof v === 'number') return v;
    if (!v) return 0;
    let s = v.toString().replace(/[^\d,.-]/g, '').replace('.', '').replace(',', '.');
    return parseFloat(s) || 0;
}
</script>