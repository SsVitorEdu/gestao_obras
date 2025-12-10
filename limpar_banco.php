<?php
include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Limpeza Total</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-danger d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow p-5 text-center">
        <h1 class="text-danger">⚠️ PERIGO</h1>
        <p>Isso vai apagar <b>TODOS</b> os dados do sistema.</p>
        
        <form method="POST">
            <button type="submit" name="limpar" class="btn btn-dark btn-lg">CONFIRMAR LIMPEZA TOTAL</button>
        </form>
        <br>
        <a href="index.php" class="btn btn-light">Cancelar</a>

        <?php
        if(isset($_POST['limpar'])) {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $pdo->exec("TRUNCATE TABLE pedidos");
                $pdo->exec("TRUNCATE TABLE obras");
                $pdo->exec("TRUNCATE TABLE empresas");
                $pdo->exec("TRUNCATE TABLE fornecedores");
                $pdo->exec("TRUNCATE TABLE compradores");
                $pdo->exec("TRUNCATE TABLE materiais");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo "<div class='alert alert-success mt-3'>✅ TUDO LIMPO! <a href='index.php?page=carga_dados'>Ir para Carga de Dados</a></div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-warning mt-3'>Erro: ".$e->getMessage()."</div>";
            }
        }
        ?>
    </div>
</body>
</html>