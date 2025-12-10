<?php
// IMPORTADOR DE CONTRATOS (VINCULA PELO NOME DO FORNECEDOR)
ini_set('display_errors', 1);
$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";

// Função para limpar dinheiro (R$ 1.000,00 -> 1000.00)
function limpaDinheiro($valor) {
    if (!$valor) return 0;
    $valor = preg_replace('/[^\d,.-]/', '', $valor); // Tira R$ e espaços
    $valor = str_replace('.', '', $valor); // Tira ponto de milhar
    $valor = str_replace(',', '.', $valor); // Troca vírgula por ponto
    return (float)$valor;
}

// Função para data (dd/mm/yyyy -> yyyy-mm-dd)
function dataSQL($data) {
    if (!$data) return null;
    $data = trim($data);
    if (strpos($data, '/') !== false) {
        $p = explode('/', $data);
        if (count($p) == 3) return "{$p[2]}-{$p[1]}-{$p[0]}";
    }
    // Tenta formato Excel numérico
    if (is_numeric($data) && $data > 20000) {
        return date('Y-m-d', ($data - 25569) * 86400);
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dados'])) {
    $linhas = array_filter(explode("\n", $_POST['dados']), 'trim');
    $sucessos = 0;
    $erros = 0;
    $nao_encontrados = "";

    // 1. Carrega Fornecedores na Memória (Nome -> ID)
    $mapaFornecedores = [];
    $sql = $pdo->query("SELECT id, nome FROM fornecedores");
    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
        $mapaFornecedores[strtoupper(trim($row['nome']))] = $row['id'];
    }

    $stmt = $pdo->prepare("INSERT INTO contratos (fornecedor_id, responsavel, valor, data_contrato) VALUES (?, ?, ?, ?)");

    foreach($linhas as $linha) {
        $cols = explode("\t", $linha);
        if (count($cols) < 3) continue;

        // LEITURA DAS COLUNAS
        $nome_forn   = strtoupper(trim($cols[0])); // Coluna A: Fornecedor
        $responsavel = trim($cols[1]);             // Coluna B: Responsável
        $valor       = limpaDinheiro($cols[2]);    // Coluna C: Valor
        $data        = dataSQL($cols[3] ?? '');    // Coluna D: Data

        // Pula cabeçalho
        if ($nome_forn == 'FORNECEDOR' || $nome_forn == 'FORCEDORES') continue;

        // Busca ID
        $id_forn = $mapaFornecedores[$nome_forn] ?? null;

        if ($id_forn) {
            $stmt->execute([$id_forn, $responsavel, $valor, $data]);
            $sucessos++;
        } else {
            $erros++;
            if($erros < 10) $nao_encontrados .= "$nome_forn, ";
        }
    }

    $msg = "<div class='alert alert-info'>
                <b>Resultado:</b> $sucessos contratos importados. <br>
                <b>Não encontrados ($erros):</b> $nao_encontrados ...
            </div>";
}
?>

<div class="container mt-4">
    <div class="card shadow border-warning">
        <div class="card-header bg-warning text-dark fw-bold">
            <i class="bi bi-file-earmark-text"></i> IMPORTAR CONTRATOS
        </div>
        <div class="card-body">
            <?php echo $msg; ?>
            <div class="alert alert-secondary small">
                <b>COLE AS 4 COLUNAS DO EXCEL:</b><br>
                <code>FORNECEDOR | RESPONSÁVEL | VALOR (R$) | DATA</code><br>
                O sistema vai procurar o fornecedor pelo NOME exato.
            </div>
            <form method="POST">
                <textarea name="dados" class="form-control mb-3" rows="10" placeholder="Cole aqui..."></textarea>
                <button class="btn btn-warning w-100 fw-bold">PROCESSAR CONTRATOS</button>
            </form>
        </div>
    </div>
</div>