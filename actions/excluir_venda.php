<?php
// ARQUIVO: actions/excluir_venda.php
require_once __DIR__ . '/../includes/db.php';

if (isset($_GET['id']) && isset($_GET['cliente_id'])) {
    $id = $_GET['id'];
    $cliente_id = $_GET['cliente_id'];
    
    try {
        // O banco já deleta as parcelas em cascata se configurado, mas por segurança...
        $pdo->prepare("DELETE FROM vendas_imob WHERE id = ?")->execute([$id]);
        
        header("Location: ../index.php?page=detalhe_cliente&id=$cliente_id&msg=venda_excluida");
    } catch (Exception $e) {
        die("Erro: " . $e->getMessage());
    }
}
?>