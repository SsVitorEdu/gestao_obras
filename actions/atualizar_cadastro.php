<?php
// ATUALIZAÇÃO DE CADASTROS (COM MEMÓRIA DE ABA)
require_once __DIR__ . '/../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tipo = $_POST['tipo_tabela']; // empresas, obras, fornecedores...
    $id   = $_POST['id'];
    $nome = strtoupper(trim($_POST['nome'])); 
    
    $codigo = isset($_POST['codigo']) ? strtoupper(trim($_POST['codigo'])) : null;
    $cnpj   = isset($_POST['cnpj_cpf']) ? trim($_POST['cnpj_cpf']) : null;

    try {
        if ($tipo == 'empresas') {
            $stmt = $pdo->prepare("UPDATE empresas SET nome = ?, codigo = ? WHERE id = ?");
            $stmt->execute([$nome, $codigo, $id]);
        } 
        elseif ($tipo == 'obras') {
            $stmt = $pdo->prepare("UPDATE obras SET nome = ?, codigo = ? WHERE id = ?");
            $stmt->execute([$nome, $codigo, $id]);
        }
        elseif ($tipo == 'fornecedores') {
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome = ?, cnpj_cpf = ? WHERE id = ?");
            $stmt->execute([$nome, $cnpj, $id]);
        }
        elseif ($tipo == 'materiais') {
            $stmt = $pdo->prepare("UPDATE materiais SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
        }
        elseif ($tipo == 'compradores') {
            $stmt = $pdo->prepare("UPDATE compradores SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
        }

        // AQUI ESTÁ O TRUQUE: Devolvemos o $tipo na URL (&tab=fornecedores)
        header("Location: ../index.php?page=configuracoes&msg=editado&tab=$tipo");
        exit;

    } catch (Exception $e) {
        die("Erro ao atualizar: " . $e->getMessage());
    }
}
?>