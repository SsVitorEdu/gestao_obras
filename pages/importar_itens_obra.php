<?php
// MODO IMPORTAÇÃO MESTRE (V3 - LAYOUT DEFINITIVO)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // 10 minutos
ini_set('memory_limit', '1024M'); // 1GB memória

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";

// 1. CARREGA OBRAS NA MEMÓRIA (Dicionário: Código -> ID)
$mapaObras = [];
$sql = $pdo->query("SELECT id, codigo FROM obras WHERE codigo IS NOT NULL");
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $chave = strtoupper(trim($row['codigo']));
    if($chave) $mapaObras[$chave] = $row['id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dados'])) {
    
    $dados = $_POST['dados'];
    $linhas = array_filter(explode("\n", $dados), 'trim');
    
    $sucessos = 0;
    $erros = 0;
    $log_erros = "";

    // Funções de Limpeza
    function limpa($v) {
        if(!isset($v) || $v === '') return 0;
        $v = str_replace(['R$', ' ', '.'], '', $v); 
        return (float)str_replace(',', '.', $v);
    }
    
    function dataSQL($v) {
        $v = trim($v);
        if(empty($v)) return null; // Se vazio, manda NULL
        if(is_numeric($v) && $v > 20000) return date('Y-m-d', ($v - 25569) * 86400);
        if(strpos($v, '/') !== false) {
            $p = explode('/', $v);
            if(count($p) == 3) return "{$p[2]}-{$p[1]}-{$p[0]}";
        }
        return null;
    }

    // Cache local
    $cacheAux = ['fornecedores' => [], 'materiais' => [], 'compradores' => []];

    function getIdRapido($pdo, $tabela, $nome, &$cache) {
        $nome = strtoupper(trim($nome ?? ''));
        if(empty($nome) || $nome == '-') $nome = "ND";
        
        if(isset($cache[$tabela][$nome])) return $cache[$tabela][$nome];

        $s = $pdo->prepare("SELECT id FROM $tabela WHERE nome = ? LIMIT 1");
        $s->execute([$nome]);
        if($r = $s->fetch()) {
            $cache[$tabela][$nome] = $r['id'];
            return $r['id'];
        }
        
        try {
            $pdo->prepare("INSERT INTO $tabela (nome) VALUES (?)")->execute([$nome]);
            $id = $pdo->lastInsertId();
            $cache[$tabela][$nome] = $id;
            return $id;
        } catch (Exception $e) { return 1; }
    }

    $pdo->beginTransaction();

    try {
        $sqlInsert = $pdo->prepare("INSERT INTO pedidos 
            (obra_id, numero_of, comprador_id, data_pedido, data_entrega, historia, 
             fornecedor_id, material_id, qtd_pedida, valor_unitario, valor_bruto_pedido,
             qtd_recebida, valor_total_rec, dt_baixa, forma_pagamento, cotacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach($linhas as $i => $linha) {
            $cols = explode("\t", $linha);
            
            // Se tiver menos de 5 colunas preenchidas, ignora (linha vazia ou lixo)
            if(count($cols) < 5) continue; 

            // --- 1. IDENTIFICAR A OBRA (COLUNA 0) ---
            $cod_obra_excel = strtoupper(trim($cols[0])); 
            
            // Pula cabeçalho
            if(strpos($cod_obra_excel, 'COD') !== false || $cod_obra_excel == '') continue;

            $obra_id = $mapaObras[$cod_obra_excel] ?? null;

            if(!$obra_id) {
                $erros++;
                if($erros < 20) $log_erros .= "L.".($i+1).": Obra Cód '$cod_obra_excel' não achada.<br>";
                continue;
            }

            /* --- MAPEAMENTO EXATO (19 Colunas no Excel) ---
               0: COD .BRA. (Usado para achar o ID)
               1: OF
               2: COMPRADOR
               3: DATA PEDIDO
               4: DATA ENTREGA
               5: HISTORIA
               6: FORNECEDOR
               7: MATERIAL
               8: QUANTIDADE PEDIDO
               9: VALOR UNITARIO
               10: VALOR BRUTO PEDIDO
               11: QUANTIDADE RECEBIDA
               12: QUANT. SALDO (Pula)
               13: VLRTOTREC
               14: VALOR BRUTO DE SALDO (Pula)
               15: TODOS (Pula - Calculado na tela)
               16: DTBAIXA
               17: FORMA DE PAGAMENTO
               18: Cotação
            */
            
            $of         = trim($cols[1] ?? ''); 
            $nm_comp    = trim($cols[2] ?? '');
            $dt_ped     = dataSQL($cols[3] ?? '');
            $dt_ent     = dataSQL($cols[4] ?? '');
            $historia   = trim($cols[5] ?? '');
            $nm_forn    = trim($cols[6] ?? '');
            $nm_mat     = trim($cols[7] ?? '');
            
            $qtd        = limpa($cols[8] ?? 0);
            $unit       = limpa($cols[9] ?? 0);
            $bruto      = limpa($cols[10] ?? 0);
            $qtd_rec    = limpa($cols[11] ?? 0);
            // Pula 12 (Saldo Qtd)
            $vlr_rec    = limpa($cols[13] ?? 0); 
            // Pula 14 (Saldo Vlr) e 15 (Todos)
            
            $dt_baixa   = dataSQL($cols[16] ?? '');
            $forma      = trim($cols[17] ?? '');
            $cotacao    = trim($cols[18] ?? '');

            // Busca IDs Auxiliares
            $id_forn = getIdRapido($pdo, 'fornecedores', $nm_forn, $cacheAux);
            $id_mat  = getIdRapido($pdo, 'materiais', $nm_mat, $cacheAux);
            $id_comp = getIdRapido($pdo, 'compradores', $nm_comp, $cacheAux);

            $sqlInsert->execute([
                $obra_id, $of, $id_comp, $dt_ped, $dt_ent, $historia,
                $id_forn, $id_mat, $qtd, $unit, $bruto,
                $qtd_rec, $vlr_rec, $dt_baixa, $forma, $cotacao
            ]);
            $sucessos++;
        }

        $pdo->commit();
        
        $cor = ($erros > 0) ? 'warning' : 'success';
        $msg = "<div class='alert alert-$cor'>
                    <h4>Resultado da Importação:</h4>
                    <ul>
                        <li class='text-success fw-bold'>$sucessos linhas importadas com sucesso!</li>
                        <li class='text-danger fw-bold'>$erros linhas ignoradas (Código da obra não existe no sistema).</li>
                    </ul>
                    ". ($erros > 0 ? "<div style='max-height:100px; overflow:auto; background:#fff; padding:5px; font-size:11px;'>$log_erros</div>" : "") ."
                </div>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Erro Crítico: ".$e->getMessage()."</div>";
    }
}
?>

<div class="container mt-4">
    <div class="card shadow border-dark">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="m-0"><i class="bi bi-database-fill-gear"></i> IMPORTAÇÃO GERAL (V3 - Layout Novo)</h4>
            <span class="badge bg-light text-dark"><?php echo count($mapaObras); ?> Obras Identificadas</span>
        </div>
        <div class="card-body">
            
            <?php echo $msg; ?>

            <div class="alert alert-info small">
                <h6 class="fw-bold"><i class="bi bi-info-circle"></i> ORDEM DAS COLUNAS:</h6>
                Copie do Excel da coluna <b>COD .BRA.</b> até a coluna <b>Cotação</b>.<br>
                <hr class="my-1">
                <code>COD .BRA. | OF | COMPRADOR | DATA | DATA | HISTORIA | FORNECEDOR | MATERIAL | QTD PED | UNIT | BRUTO | QTD REC | (SALDO) | VLR REC | (SALDO $) | (TODOS) | DT BAIXA | FORMA PAG | COTAÇÃO</code>
            </div>

            <form method="POST">
                <textarea name="dados" class="form-control mb-3" rows="15" placeholder="Cole aqui suas 10.000 linhas..." style="font-family: monospace; font-size: 11px; white-space: pre;"></textarea>
                <button type="submit" class="btn btn-dark w-100 btn-lg fw-bold">🚀 PROCESSAR TUDO</button>
            </form>
        </div>
    </div>
</div>