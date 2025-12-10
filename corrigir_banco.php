<?php
// SCRIPT DE CURA DO BANCO DE DADOS
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'includes/db.php';

echo "<h1>🔧 Reparando Banco de Dados...</h1><hr>";

function adicionarColuna($pdo, $tabela, $coluna, $tipo) {
    try {
        // Verifica se a coluna já existe
        $stmt = $pdo->query("SHOW COLUMNS FROM $tabela LIKE '$coluna'");
        if ($stmt->fetch()) {
            echo "<p style='color:blue'>ℹ️ Coluna <b>$tabela.$coluna</b> já existe. (OK)</p>";
        } else {
            // Se não existe, cria
            $pdo->exec("ALTER TABLE $tabela ADD COLUMN $coluna $tipo");
            echo "<p style='color:green'>✅ Coluna <b>$tabela.$coluna</b> criada com sucesso!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Erro ao criar $tabela.$coluna: " . $e->getMessage() . "</p>";
    }
}

// 1. CORRIGE TABELA OBRAS
adicionarColuna($pdo, 'obras', 'codigo', 'VARCHAR(50) AFTER id');

// 2. CORRIGE TABELA PEDIDOS (Adiciona todas as colunas novas)
adicionarColuna($pdo, 'pedidos', 'numero_of', 'VARCHAR(50)');
adicionarColuna($pdo, 'pedidos', 'comprador_id', 'INT');
adicionarColuna($pdo, 'pedidos', 'data_pedido', 'DATE');
adicionarColuna($pdo, 'pedidos', 'data_entrega', 'DATE');
adicionarColuna($pdo, 'pedidos', 'qtd_pedida', 'DECIMAL(15,2)');
adicionarColuna($pdo, 'pedidos', 'valor_unitario', 'DECIMAL(15,2)');
adicionarColuna($pdo, 'pedidos', 'valor_bruto_pedido', 'DECIMAL(15,2)');
adicionarColuna($pdo, 'pedidos', 'qtd_recebida', 'DECIMAL(15,2)');
adicionarColuna($pdo, 'pedidos', 'valor_total_rec', 'DECIMAL(15,2)');

echo "<hr><h3>🎉 Processo Finalizado!</h3>";
echo "<p>Agora os erros 'Unknown column' e 'Undefined array key' devem sumir.</p>";
echo "<a href='index.php?page=configuracoes' class='btn btn-primary'>Voltar para Configurações</a>";
?>