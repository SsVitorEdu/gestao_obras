<?php
// IMPORTADOR IMOBILIÁRIO (VIA ÍNDICE DE COLUNA - BLINDADO)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); 
ini_set('memory_limit', '1024M');

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

// --- PROCESSAMENTO NO SERVIDOR (PHP) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['json_dados'])) {
    
    $dados = json_decode($_POST['json_dados'], true);
    
    if (!$dados) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao receber dados.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $novos_clientes = 0;
        $novas_vendas = 0;
        $parcelas_proc = 0;

        // Prepared Statements
        $stmtBuscaCli = $pdo->prepare("SELECT id FROM clientes_imob WHERE nome = ? LIMIT 1");
        $stmtInsCli = $pdo->prepare("INSERT INTO clientes_imob (nome, cpf) VALUES (?, ?)");
        
        $stmtBuscaVenda = $pdo->prepare("SELECT id FROM vendas_imob WHERE codigo_compra = ? LIMIT 1");
        $stmtInsVenda = $pdo->prepare("INSERT INTO vendas_imob (cliente_id, codigo_compra, nome_casa, nome_empresa, data_inicio, data_fim, data_contrato, valor_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtUpdVenda = $pdo->prepare("UPDATE vendas_imob SET nome_empresa=?, data_inicio=?, data_fim=?, data_contrato=? WHERE id=?");

        $stmtBuscaParc = $pdo->prepare("SELECT id FROM parcelas_imob WHERE venda_id = ? AND data_vencimento = ? LIMIT 1");
        $stmtInsParc = $pdo->prepare("INSERT INTO parcelas_imob (venda_id, numero_parcela, data_vencimento, valor_parcela, data_pagamento, valor_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUpdParc = $pdo->prepare("UPDATE parcelas_imob SET data_pagamento=?, valor_pago=? WHERE id=?");

        foreach($dados as $d) {
            // DADOS JÁ VÊM LIMPOS DO JAVASCRIPT
            $nome_cli = strtoupper(trim($d['cliente']));
            $cod_cont = trim($d['contrato']);
            
            if(empty($nome_cli) || empty($cod_cont)) continue;

            // 1. CLIENTE (Busca ou Cria)
            $stmtBuscaCli->execute([$nome_cli]);
            if($row = $stmtBuscaCli->fetch()) {
                $cli_id = $row['id'];
            } else {
                $stmtInsCli->execute([$nome_cli, $d['cpf']]);
                $cli_id = $pdo->lastInsertId();
                $novos_clientes++;
            }

            // 2. VENDA (Busca ou Cria)
            $venda_id = null;
            $stmtBuscaVenda->execute([$cod_cont]);
            if($row = $stmtBuscaVenda->fetch()) {
                $venda_id = $row['id'];
                // Atualiza cabeçalho (Datas/Empresa)
                $stmtUpdVenda->execute([$d['empresa'], $d['dt_ini'], $d['dt_fim'], $d['dt_con'], $venda_id]);
            } else {
                // Cria Nova
                $nome_casa = "CONTRATO " . $cod_cont; // Nome provisório
                $stmtInsVenda->execute([$cli_id, $cod_cont, $nome_casa, $d['empresa'], $d['dt_ini'], $d['dt_fim'], $d['dt_con'], 0]);
                $venda_id = $pdo->lastInsertId();
                $novas_vendas++;
            }

            // 3. PARCELA (Atualiza ou Cria)
            if(!empty($d['vencimento'])) {
                $stmtBuscaParc->execute([$venda_id, $d['vencimento']]);
                
                if($rowParc = $stmtBuscaParc->fetch()) {
                    // Já existe: Atualiza pagamento se tiver valor novo
                    if($d['vlr_pago'] > 0 || !empty($d['dt_pag'])) {
                        $stmtUpdParc->execute([$d['dt_pag'], $d['vlr_pago'], $rowParc['id']]);
                    }
                } else {
                    // Nova Parcela
                    $stmtInsParc->execute([$venda_id, 0, $d['vencimento'], $d['vlr_orig'], $d['dt_pag'], $d['vlr_pago']]);
                }
                $parcelas_proc++;
            }
        }
        
        // Recalcula Valor Total dos Contratos
        $pdo->query("UPDATE vendas_imob v SET valor_total = (SELECT SUM(valor_parcela) FROM parcelas_imob p WHERE p.venda_id = v.id)");

        $pdo->commit();
        echo json_encode([
            'status' => 'sucesso', 
            'msg' => "<b>Processamento Concluído!</b><br>Clientes: $novos_clientes<br>Contratos: $novas_vendas<br>Parcelas: $parcelas_proc"
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
            <h3 class="text-success fw-bold m-0"><i class="bi bi-file-earmark-excel-fill"></i> IMPORTADOR EXCEL (.XLSX)</h3>
            <span class="text-muted">Sistema por Mapeamento de Colunas (Fixo)</span>
        </div>
        <a href="index.php?page=clientes" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body bg-light p-5 text-center">
            
            <div id="drop_zone" style="border: 3px dashed #198754; padding: 40px; border-radius: 10px; cursor: pointer; background: #fff;">
                <i class="bi bi-cloud-upload display-1 text-success opacity-50"></i>
                <h4 class="mt-3">Arraste seu arquivo <b>.XLSX</b> aqui</h4>
                <p class="text-muted">O sistema lerá as colunas automaticamente (Cliente na coluna 25, Contrato na 24, etc).</p>
                <input type="file" id="fileInput" accept=".xlsx, .xls" style="display: none;">
            </div>

            <div id="loading" class="mt-4" style="display: none;">
                <div class="spinner-border text-success" role="status"></div>
                <p class="fw-bold mt-2 text-success">Lendo arquivo e formatando datas... Aguarde.</p>
            </div>

            <div id="resultado" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
// --- CONFIGURAÇÃO DO MAPA DE COLUNAS (ÍNDICES 0-BASED) ---
// Baseado no seu arquivo:
// 0: DT INICIO | 1: DT FIM | 2: DT CONTRATO | 4: EMPRESA
// 13: VENCIMENTO | 14: DT PAGAMENTO | 17: VLR PAGO | 21: VLR ORIGINAL
// 23: COD CONTRATO | 24: CLIENTE | 25: CPF

const MAPA = {
    DT_INI: 0,
    DT_FIM: 1,
    DT_CON: 2,
    EMPRESA: 4,
    VENCIMENTO: 13,
    DT_PAG: 14,
    VLR_PAGO: 17,
    VLR_ORIG: 21,
    CONTRATO: 23,
    CLIENTE: 24,
    CPF: 25
};

// Funções de Interface
const dropZone = document.getElementById('drop_zone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#e8f5e9'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.background = '#fff'; });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault(); dropZone.style.background = '#fff';
    if(e.dataTransfer.files.length) processarArquivo(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', (e) => {
    if(fileInput.files.length) processarArquivo(fileInput.files[0]);
});

// Funções de Processamento
function processarArquivo(file) {
    document.getElementById('loading').style.display = 'block';
    document.getElementById('resultado').innerHTML = '';

    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const worksheet = workbook.Sheets[workbook.SheetNames[0]];
        
        // Pega os dados como Array de Arrays (Matriz) para usar índices
        const rows = XLSX.utils.sheet_to_json(worksheet, {header: 1, defval: ''});

        const dadosLimpos = [];

        // Começa da linha 1 (pula cabeçalho linha 0)
        for (let i = 1; i < rows.length; i++) {
            let row = rows[i];
            
            // Verifica se tem dados mínimos
            if (!row[MAPA.CLIENTE] || !row[MAPA.CONTRATO]) continue;

            // Monta objeto limpo
            dadosLimpos.push({
                dt_ini:   excelDateToJS(row[MAPA.DT_INI]),
                dt_fim:   excelDateToJS(row[MAPA.DT_FIM]),
                dt_con:   excelDateToJS(row[MAPA.DT_CON]),
                empresa:  String(row[MAPA.EMPRESA]).trim(),
                vencimento: excelDateToJS(row[MAPA.VENCIMENTO]),
                dt_pag:   excelDateToJS(row[MAPA.DT_PAG]),
                vlr_pago: limparMoeda(row[MAPA.VLR_PAGO]),
                vlr_orig: limparMoeda(row[MAPA.VLR_ORIG]),
                contrato: String(row[MAPA.CONTRATO]).trim(),
                cliente:  String(row[MAPA.CLIENTE]).trim(),
                cpf:      String(row[MAPA.CPF]).trim()
            });
        }

        if (dadosLimpos.length === 0) {
            alert('Nenhum dado válido encontrado nas colunas esperadas.');
            document.getElementById('loading').style.display = 'none';
            return;
        }

        enviarParaPHP(dadosLimpos);
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
        if(data.status === 'sucesso') resDiv.innerHTML = `<div class="alert alert-success fs-5">${data.msg}</div>`;
        else resDiv.innerHTML = `<div class="alert alert-danger">Erro: ${data.msg}</div>`;
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('resultado').innerHTML = `<div class="alert alert-danger">Erro técnico: ${error}</div>`;
    });
}

// Converte Data do Excel (Número ou Texto) para YYYY-MM-DD
function excelDateToJS(serial) {
    if (!serial) return null;
    // Se for número (Excel Serial Date)
    if (typeof serial === 'number') {
        const date = new Date(Math.round((serial - 25569) * 86400 * 1000));
        return date.toISOString().split('T')[0];
    }
    // Se for texto (dd/mm/yyyy)
    if (typeof serial === 'string' && serial.includes('/')) {
        const parts = serial.split('/');
        if(parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return null;
}

function limparMoeda(v) {
    if (typeof v === 'number') return v;
    if (!v) return 0;
    // Remove "R$", espaços e converte , para .
    let s = v.toString().replace(/[^\d,.-]/g, '').replace('.', '').replace(',', '.');
    return parseFloat(s) || 0;
}
</script>