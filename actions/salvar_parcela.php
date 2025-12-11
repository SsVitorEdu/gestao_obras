<?php
// ARQUIVO: actions/salvar_parcela.php
require_once __DIR__ . '/../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $venda_id = $_POST['venda_id'];
    $cliente_id = $_POST['cliente_id'];
    
    $num = $_POST['numero_parcela'];
    $venc = $_POST['data_vencimento'];
    $val = floatval(str_replace(',', '.', $_POST['valor_parcela']));
    
    $dt_pag = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
    $val_pag = floatval(str_replace(',', '.', $_POST['valor_pago']));

    if(!empty($id)) {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE parcelas_imob SET numero_parcela=?, data_vencimento=?, valor_parcela=?, data_pagamento=?, valor_pago=? WHERE id=?");
        $stmt->execute([$num, $venc, $val, $dt_pag, $val_pag, $id]);
    } else {
        // INSERT
        $stmt = $pdo->prepare("INSERT INTO parcelas_imob (venda_id, numero_parcela, data_vencimento, valor_parcela, data_pagamento, valor_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$venda_id, $num, $venc, $val, $dt_pag, $val_pag]);
    }

    header("Location: ../index.php?page=detalhe_cliente&id=$cliente_id");
}
?>