<?php
// ARQUIVO: actions/salvar_pedido_obra.php
require_once __DIR__ . '/../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    try {
        $id = $_POST['id'] ?? ''; // Se tiver ID é edição
        $obra_id = $_POST['obra_id']; // ID da obra atual
        
        // Campos do Formulário
        $empresa_id = $_POST['empresa_id'];
        $fornecedor_id = $_POST['fornecedor_id'];
        $comprador_id = $_POST['comprador_id'];
        $material_id = $_POST['material_id'];
        
        $of = $_POST['numero_of'];
        $dt_ped = !empty($_POST['data_pedido']) ? $_POST['data_pedido'] : null;
        $dt_ent = !empty($_POST['data_entrega']) ? $_POST['data_entrega'] : null;
        $historia = $_POST['historia'];
        $pgto = $_POST['forma_pagamento'];
        
        // Tratamento de Valores (R$ 1.000,00 -> 1000.00)
        $qtd = floatval(str_replace(',', '.', $_POST['qtd_pedida']));
        $unit = floatval(str_replace(',', '.', $_POST['valor_unitario']));
        $bruto = $qtd * $unit; 
        
        $qtd_rec = floatval(str_replace(',', '.', $_POST['qtd_recebida']));
        $vlr_rec = floatval(str_replace(',', '.', $_POST['valor_total_rec']));
        $dt_baixa = !empty($_POST['dt_baixa']) ? $_POST['dt_baixa'] : null;

        if (!empty($id)) {
            // EDITAR
            $sql = "UPDATE pedidos SET 
                    obra_id=?, empresa_id=?, fornecedor_id=?, comprador_id=?, material_id=?,
                    numero_of=?, data_pedido=?, data_entrega=?, historia=?, forma_pagamento=?,
                    qtd_pedida=?, valor_unitario=?, valor_bruto_pedido=?,
                    qtd_recebida=?, valor_total_rec=?, dt_baixa=?
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$obra_id, $empresa_id, $fornecedor_id, $comprador_id, $material_id, $of, $dt_ped, $dt_ent, $historia, $pgto, $qtd, $unit, $bruto, $qtd_rec, $vlr_rec, $dt_baixa, $id]);
        } else {
            // CRIAR NOVO
            $sql = "INSERT INTO pedidos 
                    (obra_id, empresa_id, fornecedor_id, comprador_id, material_id, numero_of, data_pedido, data_entrega, historia, forma_pagamento, qtd_pedida, valor_unitario, valor_bruto_pedido, qtd_recebida, valor_total_rec, dt_baixa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$obra_id, $empresa_id, $fornecedor_id, $comprador_id, $material_id, $of, $dt_ped, $dt_ent, $historia, $pgto, $qtd, $unit, $bruto, $qtd_rec, $vlr_rec, $dt_baixa]);
        }

        // VOLTA PARA A MESMA OBRA
        header("Location: ../index.php?page=detalhe_obra&id=$obra_id&msg=salvo");
        exit;

    } catch (Exception $e) {
        die("Erro: " . $e->getMessage());
    }
}
?>