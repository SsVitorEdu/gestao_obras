<?php
// ARQUIVO: actions/salvar_contrato.php
require_once __DIR__ . '/../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    try {
        $id = $_POST['id'] ?? ''; 
        $fornecedor_id = $_POST['fornecedor_id'];
        $responsavel = mb_strtoupper($_POST['responsavel']); // Salva em maiúsculo
        $data = !empty($_POST['data_contrato']) ? $_POST['data_contrato'] : null;
        
        // Limpa valor (R$ 1.000,00 -> 1000.00)
        $val_str = $_POST['valor'];
        $val_str = preg_replace('/[^\d,.-]/', '', $val_str);
        $val_str = str_replace('.', '', $val_str);
        $valor = floatval(str_replace(',', '.', $val_str));

        if (!empty($id)) {
            // EDITAR
            $stmt = $pdo->prepare("UPDATE contratos SET responsavel=?, data_contrato=?, valor=? WHERE id=?");
            $stmt->execute([$responsavel, $data, $valor, $id]);
        } else {
            // NOVO
            $stmt = $pdo->prepare("INSERT INTO contratos (fornecedor_id, responsavel, data_contrato, valor) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fornecedor_id, $responsavel, $data, $valor]);
        }

        header("Location: ../index.php?page=detalhe_fornecedor&id=$fornecedor_id");
        exit;

    } catch (Exception $e) {
        die("Erro ao salvar contrato: " . $e->getMessage());
    }
}
?>