<?php
// ARQUIVO: actions/excluir_pedido_obra.php
require_once __DIR__ . '/../includes/db.php'; 

$id = $_GET['id'] ?? 0;
$obra_id = $_GET['obra_id'] ?? 0;

if ($id && $obra_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redireciona de volta com mensagem de sucesso
        header("Location: ../index.php?page=detalhe_obra&id=$obra_id&msg=excluido");
        exit;
    } catch (Exception $e) {
        die("Erro ao excluir: " . $e->getMessage());
    }
} else {
    die("ID inválido.");
}
?>