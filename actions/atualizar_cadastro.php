<?php
// ARQUIVO: actions/atualizar_cadastro.php
require_once __DIR__ . '/../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tipo = $_POST['tipo_tabela'];
    $id   = $_POST['id'];
    $nome = strtoupper(trim($_POST['nome']));
    
    $codigo = isset($_POST['codigo']) ? strtoupper(trim($_POST['codigo'])) : null;
    $cnpj   = isset($_POST['cnpj_cpf']) ? trim($_POST['cnpj_cpf']) : null;
    $cpf    = isset($_POST['cpf']) ? trim($_POST['cpf']) : null; // Novo campo

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
        elseif ($tipo == 'clientes_imob') {
            // ATUALIZAÇÃO DO CLIENTE IMOBILIÁRIO
            $stmt = $pdo->prepare("UPDATE clientes_imob SET nome = ?, cpf = ? WHERE id = ?");
            $stmt->execute([$nome, $cpf, $id]);
        }
        elseif ($tipo == 'materiais') {
            $stmt = $pdo->prepare("UPDATE materiais SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
        }
        elseif ($tipo == 'compradores') {
            $stmt = $pdo->prepare("UPDATE compradores SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
        }

        header("Location: ../index.php?page=configuracoes&msg=editado&tab=$tipo");
        exit;

    } catch (Exception $e) {
        die("Erro ao atualizar: " . $e->getMessage());
    }
}
?>