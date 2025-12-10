<?php
// MODO IMPORTAÇÃO MESTRE (V18 - SÓ CÓDIGOS, SEM NOMES, SEM COLUNA 'TODOS')
ini_set('display_errors', 1);
set_time_limit(600); 
ini_set('memory_limit', '1024M'); 

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";

// 1. CARREGA MAPAS (Cache: Código -> ID)
$mapaObras = [];
$sql = $pdo->query("SELECT id, codigo FROM obras WHERE codigo IS NOT NULL");
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $mapaObras[strtoupper(trim($row['codigo']))] = $row['id'];
}

$mapaEmpresas = [];
$sql = $pdo->query("SELECT id, codigo FROM empresas WHERE codigo IS NOT NULL");
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $mapaEmpresas[strtoupper(trim($row['codigo']))] = $row['id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dados'])) {
    
    $dados = $_POST['dados'];
    $linhas = array_filter(explode("\n", $dados), 'trim');
    
    $sucessos = 0;
    $erros = 0;
    
    // Funções de Limpeza
    function limpaValor($v) {
        if(!isset($v) || $v === '') return 0;
        $v = preg_replace('/[^\d,.-]/', '', $v); 
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            $v = str_replace('.', '', $v); $v = str_replace(',', '.', $v);
        } elseif (strpos($v, ',') !== false) { $v = str_replace(',', '.', $v); }
        return (float)$v;
    }
    
    function dataSQL($v) {
        $v = trim($v);
        if(empty($v) || $v == '-' || $v == '0') return null;
        if(is_numeric($v) && $v > 20000) return date('Y-m-d', ($v - 25569) * 86400);
        if(strpos($v, '/') !== false) {
            $p = explode('/', $v); if(count($p) == 3) return "{$p[2]}-{$p[1]}-{$p[0]}";
        }
        return null;
    }

    $cacheAux = ['fornecedores'=>[], 'materiais'=>[], 'compradores'=>[]];

    function getIdRapido($pdo, $tab, $nome, &$cache) {
        $nome = strtoupper(trim($nome ?? ''));
        if(empty($nome) || $nome=='-' || strlen($nome)<2) $nome="ND";
        if(isset($cache[$tab][$nome])) return $cache[$tab][$nome];
        
        $s=$pdo->prepare("SELECT id FROM $tab WHERE nome=? LIMIT 1");
        $s->execute([$nome]);
        if($r=$s->fetch()){ $cache[$tab][$nome]=$r['id']; return $r['id']; }
        
        try {
            $pdo->prepare("INSERT INTO $tab (nome) VALUES (?)")->execute([$nome]);
            $id = $pdo->lastInsertId();
            $cache[$tab][$nome] = $id;
            return $id;
        } catch (Exception $e) { return 1; }
    }

    $pdo->beginTransaction();

    try {
        $sqlInsert = $pdo->prepare("INSERT INTO pedidos 
            (obra_id, empresa_id, numero_of, comprador_id, data_pedido, data_entrega, historia, 
             fornecedor_id, material_id, qtd_pedida, valor_unitario, valor_bruto_pedido,
             qtd_recebida, valor_total_rec, dt_baixa, forma_pagamento, cotacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach($linhas as $i => $linha) {
            $cols = explode("\t", $linha);
            if(count($cols) < 5) continue; 

            // 1. COD EMPRESA (Coluna 0)
            $cod_emp = strtoupper(trim($cols[0]));
            $empresa_id = $mapaEmpresas[$cod_emp] ?? null;

            // 2. COD OBRA (Coluna 1)
            $cod_obra = strtoupper(trim($cols[1] ?? '')); 
            $obra_id = $mapaObras[$cod_obra] ?? null;

            // Pula cabeçalho ou linha inválida
            if(strpos($cod_emp, 'COD') !== false || $cod_obra == '') continue;

            if(!$obra_id) {
                $erros++; // Obra não encontrada
                continue;
            }

            /* --- MAPEAMENTO V18 (19 COLUNAS - SEM NOMES, SEM TODOS) ---
               0: COD EMPR
               1: COD OBRA
               2: OF
               3: COMPRADOR
               4: DATA PED
               5: DATA ENT
               6: HISTORIA
               7: FORNECEDOR
               8: MATERIAL
               9: QTD PED
               10: UNIT
               11: BRUTO
               12: REC
               13: SALDO QTD (Pula)
               14: VLRTOTREC
               15: SALDO VLR (Pula)
               16: DTBAIXA
               17: FORMA PAG
               18: COTAÇÃO
            */
            
            $of         = trim($cols[2] ?? ''); 
            $nm_comp    = trim($cols[3] ?? '');
            $dt_ped     = dataSQL($cols[4] ?? '');
            $dt_ent     = dataSQL($cols[5] ?? '');
            $historia   = trim($cols[6] ?? '');
            $nm_forn    = trim($cols[7] ?? '');
            $nm_mat     = trim($cols[8] ?? '');
            
            $qtd        = limpaValor($cols[9] ?? 0);
            $unit       = limpaValor($cols[10] ?? 0);
            $bruto      = limpaValor($cols[11] ?? 0);
            $qtd_rec    = limpaValor($cols[12] ?? 0);
            // Pula 13 (Saldo Qtd)
            $vlr_rec    = limpaValor($cols[14] ?? 0); 
            // Pula 15 (Saldo Vlr)
            
            $dt_baixa   = dataSQL($cols[16] ?? '');
            $forma      = trim($cols[17] ?? '');
            $cotacao    = trim($cols[18] ?? '');

            $id_forn = getIdRapido($pdo, 'fornecedores', $nm_forn, $cacheAux);
            $id_mat  = getIdRapido($pdo, 'materiais', $nm_mat, $cacheAux);
            $id_comp = getIdRapido($pdo, 'compradores', $nm_comp, $cacheAux);

            $sqlInsert->execute([
                $obra_id, $empresa_id, $of, $id_comp, $dt_ped, $dt_ent, $historia,
                $id_forn, $id_mat, $qtd, $unit, $bruto,
                $qtd_rec, $vlr_rec, $dt_baixa, $forma, $cotacao
            ]);
            $sucessos++;
        }

        $pdo->commit();
        $msg = "<div class='alert alert-success'>✅ <b>$sucessos</b> itens importados! (Erros: $erros)</div>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Erro Crítico: ".$e->getMessage()."</div>";
    }
}
?>

<div class="container mt-4">
    <div class="card shadow border-dark">
        <div class="card-header bg-dark text-white"><h4><i class="bi bi-box-seam"></i> IMPORTAÇÃO (V18 - Só Códigos)</h4></div>
        <div class="card-body">
            <?php echo $msg; ?>
            <div class="alert alert-info small">
                <b>COLE AS 19 COLUNAS (Sem nomes de empresa/obra e Sem 'Todos'):</b><br>
                <code>COD EMP | COD OBRA | OF | COMP | DT | DT | HIST | FORN | MAT | QTD | UNIT | BRUTO | REC | SALDO | VLRTOT | SALDO$ | BAIXA | PAG | COT</code>
            </div>
            <form method="POST"><textarea name="dados" class="form-control mb-3" rows="15" placeholder="Cole aqui..."></textarea><button class="btn btn-dark w-100">PROCESSAR</button></form>
        </div>
    </div>
</div>