<?php
// ARQUIVO: actions/excluir_contrato.php
require_once __DIR__ . '/../includes/db.php'; 

// Verifica se recebeu o ID do contrato e o ID do fornecedor (para voltar na página certa)
if (isset($_GET['id']) && isset($_GET['id_forn'])) {
    
    $id = $_GET['id'];
    $id_forn = $_GET['id_forn'];

    try {
        $stmt = $pdo->prepare("DELETE FROM contratos WHERE id = ?");
        $stmt->execute([$id]);

        // Volta para a tela do fornecedor com mensagem
        header("Location: ../index.php?page=detalhe_fornecedor&id=$id_forn&msg=excluido");
        exit;

    } catch (Exception $e) {
        die("Erro ao excluir contrato: " . $e->getMessage());
    }
} else {
    // Se tentar acessar direto sem ID
    header("Location: ../index.php");
    exit;
}
?>