<?php
// IMPORTADOR MESTRE BLINDADO (MAPEAMENTO POR NOME DE COLUNA)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
ini_set('memory_limit', '1024M');

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

// --- FUNÇÕES DE LIMPEZA ---
function limpaValor($v) {
    if (is_numeric($v)) return (float)$v;
    if (!isset($v) || trim($v) === '') return 0.00;
    // Remove tudo que não é número, ponto ou vírgula
    $v = preg_replace('/[^\d,.-]/', '', $v); 
    $v = str_replace('.', '', $v); 
    $v = str_replace(',', '.', $v);
    return (float)$v;
}

function dataSQL($v) {
    if (empty($v) || $v == '-' || $v == 'NULL') return null;
    
    // Excel Serial (Número puro > 20000)
    if (is_numeric($v) && $v > 20000) return date('Y-m-d', ($v - 25569) * 86400); 
    
    $v = trim($v);
    // Formato Brasileiro (DD/MM/AAAA)
    if (strpos($v, '/') !== false) { 
        $p = explode('/', $v); 
        if(count($p)==3) return "{$p[2]}-{$p[1]}-{$p[0]}"; 
    }
    // Formato Internacional (AAAA-MM-DD)
    if (strpos($v, '-') !== false) {
        return date('Y-m-d', strtotime($v));
    }
    return null;
}

// CACHES
$cache_obras = [];
$cache_empresas = [];
$cache_forn = [];
$cache_mat = [];
$cache_comp = [];

function getId($pdo, $tabela, $campo, $valor, &$cache) {
    $chave = strtoupper(trim($valor ?? ''));
    if(empty($chave) || $chave == '0') return null;
    if(isset($cache[$chave])) return $cache[$chave];

    $stmt = $pdo->prepare("SELECT id FROM $tabela WHERE $campo = ? LIMIT 1");
    $stmt->execute([$chave]);
    $id = $stmt->fetchColumn();

    if($id) {
        $cache[$chave] = $id;
        return $id;
    }

    $stmtIns = $pdo->prepare("INSERT INTO $tabela ($campo) VALUES (?)");
    $stmtIns->execute([$chave]);
    $novoId = $pdo->lastInsertId();
    $cache[$chave] = $novoId;
    return $novoId;
}

// --- PROCESSAMENTO AJAX ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lote_dados'])) {
    
    $dados = json_decode($_POST['lote_dados'], true);
    if (!$dados) { echo json_encode(['status'=>'erro', 'msg'=>'Dados vazios ou inválidos']); exit; }

    try {
        $pdo->beginTransaction();
        $processados = 0;

        // Statements
        $stmtCheckEmp = $pdo->prepare("SELECT id FROM empresas WHERE codigo = ? LIMIT 1");
        $stmtInsEmp   = $pdo->prepare("INSERT INTO empresas (codigo, nome) VALUES (?, ?)");
        
        $stmtCheckObra = $pdo->prepare("SELECT id FROM obras WHERE codigo = ? LIMIT 1");
        $stmtInsObra   = $pdo->prepare("INSERT INTO obras (codigo, nome, empresa_id) VALUES (?, ?, ?)");

        $stmtInsPed = $pdo->prepare("INSERT INTO pedidos (obra_id, empresa_id, numero_of, comprador_id, data_pedido, data_entrega, historia, fornecedor_id, material_id, qtd_pedida, valor_unitario, valor_bruto_pedido, qtd_recebida, valor_total_rec, dt_baixa, forma_pagamento, cotacao) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        foreach($dados as $d) {
            // O JSON JÁ VEM COM CHAVES NOMEADAS DO JAVASCRIPT
            // ex: $d['of'], $d['fornecedor'], etc.

            $cod_empresa = trim($d['empresa_cod'] ?? '');
            $nome_empresa = strtoupper(trim($d['empresa_nome'] ?? ''));
            
            $cod_obra = trim($d['obra_cod'] ?? '');
            $nome_obra = strtoupper(trim($d['obra_nome'] ?? ''));
            
            // Validação mínima
            if(empty($cod_obra) && empty($nome_obra)) continue; 

            // 1. EMPRESA
            $id_empresa = null;
            if(!empty($cod_empresa)) {
                if(isset($cache_empresas[$cod_empresa])) {
                    $id_empresa = $cache_empresas[$cod_empresa];
                } else {
                    $stmtCheckEmp->execute([$cod_empresa]);
                    if($id = $stmtCheckEmp->fetchColumn()) {
                        $id_empresa = $id;
                    } else {
                        // Se não tem nome, usa o código
                        $nm = !empty($nome_empresa) ? $nome_empresa : "EMPRESA $cod_empresa";
                        $stmtInsEmp->execute([$cod_empresa, $nm]);
                        $id_empresa = $pdo->lastInsertId();
                    }
                    $cache_empresas[$cod_empresa] = $id_empresa;
                }
            }

            // 2. OBRA
            $id_obra = null;
            $chave_obra = $cod_obra . '_' . $nome_obra; // Cache composto
            
            if(isset($cache_obras[$chave_obra])) {
                $id_obra = $cache_obras[$chave_obra];
            } else {
                // Tenta achar pelo código primeiro
                $found = false;
                if(!empty($cod_obra)) {
                    $stmtCheckObra->execute([$cod_obra]);
                    if($id = $stmtCheckObra->fetchColumn()) {
                        $id_obra = $id;
                        $found = true;
                    }
                }
                
                if(!$found) {
                    $nm = !empty($nome_obra) ? $nome_obra : "OBRA $cod_obra";
                    $cod = !empty($cod_obra) ? $cod_obra : 'S/N';
                    $stmtInsObra->execute([$cod, $nm, $id_empresa]);
                    $id_obra = $pdo->lastInsertId();
                }
                $cache_obras[$chave_obra] = $id_obra;
            }

            // 3. VÍNCULOS
            $id_comprador = getId($pdo, 'compradores', 'nome', $d['comprador'], $cache_comp);
            $id_fornecedor = getId($pdo, 'fornecedores', 'nome', $d['fornecedor'], $cache_forn);
            $id_material = getId($pdo, 'materiais', 'nome', $d['material'], $cache_mat);

            // 4. PEDIDO
            $stmtInsPed->execute([
                $id_obra, 
                $id_empresa, 
                $d['of'], 
                $id_comprador, 
                dataSQL($d['dt_pedido']), 
                dataSQL($d['dt_entrega']), 
                $d['historia'], 
                $id_fornecedor, 
                $id_material, 
                limpaValor($d['qtd']), 
                limpaValor($d['unitario']), 
                limpaValor($d['total_bruto']), 
                limpaValor($d['qtd_rec']), 
                limpaValor($d['vlr_rec']), 
                dataSQL($d['dt_baixa']), 
                $d['pagamento'] ?? '',
                $d['cotacao'] ?? ''
            ]);
            $processados++;
        }

        $pdo->commit();
        echo json_encode(['status'=>'sucesso', 'qtd'=>$processados]);

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status'=>'erro', 'msg'=>$e->getMessage()]);
    }
    exit;
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-primary fw-bold"><i class="bi bi-robot"></i> IMPORTADOR MESTRE V2.0</h3>
            <span class="text-muted">Scanner Automático de Cabeçalho (Ignora lixo inicial)</span>
        </div>
        <a href="index.php?page=obras" class="btn btn-outline-dark fw-bold">VOLTAR</a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-body p-5 text-center">
            
            <div id="drop_zone" style="border: 3px dashed #0d6efd; padding: 50px; border-radius: 15px; cursor: pointer; background: #f8f9fa;">
                <i class="bi bi-file-earmark-excel display-1 text-primary opacity-50"></i>
                <h4 class="mt-3">Arraste o <b>obras 2.xlsx</b> aqui</h4>
                <p class="text-muted">O sistema vai encontrar a linha do cabeçalho "COD. EMPR" sozinho.</p>
                <input type="file" id="fileInput" accept=".xlsx, .xls, .csv" style="display: none;">
            </div>

            <div id="progress_area" class="mt-4" style="display: none;">
                <h5 id="status_text" class="fw-bold text-dark">Lendo arquivo...</h5>
                <div class="progress" style="height: 25px;">
                    <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%">0%</div>
                </div>
            </div>

            <div id="resultado_final" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('drop_zone');
const fileInput = document.getElementById('fileInput');
const progressArea = document.getElementById('progress_area');
const progressBar = document.getElementById('progress_bar');
const statusText = document.getElementById('status_text');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#e9ecef'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.background = '#f8f9fa'; });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault(); dropZone.style.background = '#f8f9fa';
    if(e.dataTransfer.files.length) processarArquivo(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', (e) => {
    if(fileInput.files.length) processarArquivo(fileInput.files[0]);
});

function processarArquivo(file) {
    dropZone.style.display = 'none';
    progressArea.style.display = 'block';
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.SheetNames[0];
        // Ler como matriz bruta (array de arrays) para achar o cabeçalho
        const rows = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], {header: 1, defval: ''});

        // 1. ENCONTRAR LINHA DE CABEÇALHO
        let headerIndex = -1;
        
        // Procura palavras chave na linha
        for(let i=0; i < Math.min(rows.length, 20); i++) {
            const linhaStr = JSON.stringify(rows[i]).toUpperCase();
            if(linhaStr.includes("OF") && (linhaStr.includes("EMPREEDIMENTO") || linhaStr.includes("OBRA"))) {
                headerIndex = i;
                break;
            }
        }

        if(headerIndex === -1) {
            alert('ERRO: Não encontrei a linha de cabeçalho (OF, EMPREEDIMENTO...) nas primeiras 20 linhas.');
            location.reload();
            return;
        }

        // 2. MAPEAR COLUNAS (INDICES)
        const header = rows[headerIndex].map(c => String(c).trim().toUpperCase());
        const map = {};
        
        header.forEach((col, idx) => {
            if(col.includes("COD. EMPR")) map['COD_EMP'] = idx;
            else if(col.includes("EMPREEDIMENTO")) map['NOME_EMP'] = idx;
            else if(col.includes("COD .BRA") || col.includes("COD. OBR")) map['COD_OBRA'] = idx;
            else if(col === "OBRA") map['NOME_OBRA'] = idx;
            else if(col === "OF") map['OF'] = idx;
            else if(col.includes("COMPRADOR")) map['COMPRADOR'] = idx;
            else if(col.includes("DATA PED")) map['DT_PED'] = idx;
            else if(col.includes("DATA ENT")) map['DT_ENT'] = idx;
            else if(col.includes("HISTORIA")) map['HIST'] = idx;
            else if(col.includes("FORNECEDOR")) map['FORN'] = idx;
            else if(col.includes("MATERIAL")) map['MAT'] = idx;
            else if(col.includes("QUANTIDADE PEDIDO")) map['QTD'] = idx;
            else if(col.includes("VALOR UNITARIO")) map['UNIT'] = idx;
            else if(col.includes("VALOR BRUTO")) map['TOTAL'] = idx;
            else if(col.includes("QUANTIDADE RECEBIDA")) map['QTD_REC'] = idx;
            else if(col.includes("VLRTOTREC")) map['VLR_REC'] = idx;
            else if(col.includes("DTBAIXA")) map['DT_BAIXA'] = idx;
            else if(col.includes("FORMA DE PAGAMENTO")) map['PAGTO'] = idx;
            else if(col.includes("COTAÇÃO")) map['COT'] = idx;
        });

        // 3. PROCESSAR DADOS REAIS
        const dadosLimpos = [];
        
        for(let i = headerIndex + 1; i < rows.length; i++) {
            const row = rows[i];
            // Verifica se tem OF ou Obra pra não pegar linha vazia
            if(!row[map['OF']] && !row[map['COD_OBRA']]) continue;

            dadosLimpos.push({
                empresa_cod:  row[map['COD_EMP']] ?? '',
                empresa_nome: row[map['NOME_EMP']] ?? '',
                obra_cod:     row[map['COD_OBRA']] ?? '',
                obra_nome:    row[map['NOME_OBRA']] ?? '',
                of:           row[map['OF']] ?? '',
                comprador:    row[map['COMPRADOR']] ?? '',
                dt_pedido:    row[map['DT_PED']] ?? '',
                dt_entrega:   row[map['DT_ENT']] ?? '',
                historia:     row[map['HIST']] ?? '',
                fornecedor:   row[map['FORN']] ?? '',
                material:     row[map['MAT']] ?? '',
                qtd:          row[map['QTD']] ?? 0,
                unitario:     row[map['UNIT']] ?? 0,
                total_bruto:  row[map['TOTAL']] ?? 0,
                qtd_rec:      row[map['QTD_REC']] ?? 0,
                vlr_rec:      row[map['VLR_REC']] ?? 0,
                dt_baixa:     row[map['DT_BAIXA']] ?? '',
                pagamento:    row[map['PAGTO']] ?? '',
                cotacao:      row[map['COT']] ?? ''
            });
        }

        // 4. ENVIAR EM LOTES
        const totalLinhas = dadosLimpos.length;
        const tamanhoLote = 200;
        let indexAtual = 0;
        let totalImportado = 0;

        statusText.innerText = `Cabeçalho encontrado na linha ${headerIndex+1}. ${totalLinhas} registros válidos. Importando...`;

        function enviarLote() {
            if (indexAtual >= totalLinhas) {
                progressBar.style.width = '100%';
                progressBar.innerText = 'CONCLUÍDO';
                progressBar.classList.remove('progress-bar-animated');
                
                document.getElementById('resultado_final').innerHTML = `
                    <div class="alert alert-success fs-5 shadow-sm">
                        ✅ <b>Sucesso!</b> ${totalImportado} registros importados.
                        <br><a href="index.php?page=obras" class="btn btn-success mt-2">Ir para Obras</a>
                    </div>`;
                return;
            }

            const lote = dadosLimpos.slice(indexAtual, indexAtual + tamanhoLote);
            const formData = new FormData();
            formData.append('lote_dados', JSON.stringify(lote));

            fetch('pages/importar_mestre_xlsx.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(resp => {
                if(resp.status === 'sucesso') {
                    totalImportado += resp.qtd;
                    indexAtual += tamanhoLote;
                    
                    const perc = Math.round((indexAtual / totalLinhas) * 100);
                    progressBar.style.width = (perc > 100 ? 100 : perc) + '%';
                    progressBar.innerText = (perc > 100 ? 100 : perc) + '%';
                    
                    enviarLote();
                } else {
                    alert('Erro no servidor: ' + resp.msg);
                }
            })
            .catch(err => {
                alert('Erro de conexão: ' + err);
            });
        }

        enviarLote();
    };
    reader.readAsArrayBuffer(file);
}
</script>