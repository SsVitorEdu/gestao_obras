<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $cliente_id = $_POST['cliente_id'];
    
    // Tratamento de dados
    $codigo = $_POST['codigo_compra'];
    $empreendimento = mb_strtoupper($_POST['nome_casa']); // AQUI ENTRA O NOME MANUAL
    $empresa = mb_strtoupper($_POST['nome_empresa']);
    
    // Valor Monetário
    $val = $_POST['valor_total'];
    $val = str_replace('.', '', $val); // Tira ponto de milhar
    $val = str_replace(',', '.', $val); // Troca vírgula por ponto
    $valor_total = floatval($val);

    $dt_con = $_POST['data_contrato'] ?: null;
    $dt_ini = $_POST['data_inicio'] ?: null;
    $dt_fim = $_POST['data_fim'] ?: null;

    try {
        if (!empty($id)) {
            // EDITAR
            $sql = "UPDATE vendas_imob SET codigo_compra=?, nome_casa=?, nome_empresa=?, valor_total=?, data_contrato=?, data_inicio=?, data_fim=? WHERE id=?";
            $pdo->prepare($sql)->execute([$codigo, $empreendimento, $empresa, $valor_total, $dt_con, $dt_ini, $dt_fim, $id]);
        } else {
            // NOVO
            $sql = "INSERT INTO vendas_imob (cliente_id, codigo_compra, nome_casa, nome_empresa, valor_total, data_contrato, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$cliente_id, $codigo, $empreendimento, $empresa, $valor_total, $dt_con, $dt_ini, $dt_fim]);
        }
        header("Location: ../index.php?page=detalhe_cliente&id=$cliente_id&msg=sucesso");
    } catch (Exception $e) { die("Erro: " . $e->getMessage()); }
}
?>