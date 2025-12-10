<?php
// Função para verificar se existe, se não, cria e devolve o ID
function obterOuCriarId($pdo, $tabela, $valor) {
    $valor = trim($valor); // Remove espaços extras
    if(empty($valor)) return null;

    // 1. Tenta achar
    $stmt = $pdo->prepare("SELECT id FROM $tabela WHERE nome = :nome LIMIT 1");
    $stmt->execute([':nome' => $valor]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        return $resultado['id'];
    } else {
        // 2. Se não achar, cria
        $stmt = $pdo->prepare("INSERT INTO $tabela (nome) VALUES (:nome)");
        $stmt->execute([':nome' => $valor]);
        return $pdo->lastInsertId();
    }
}
?>