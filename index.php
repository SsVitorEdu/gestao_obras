<?php
// Define caminho da raiz
define('ROOT_PATH', __DIR__);

// Conexão
$db_file = ROOT_PATH . '/includes/db.php';
if (!file_exists($db_file)) $db_file = ROOT_PATH . '/db.php';

if (file_exists($db_file)) include $db_file;
else die("<h1>Erro: Arquivo de conexão (db.php) não encontrado.</h1>");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Obras - Imoveis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        body { display: flex; min-height: 100vh; overflow-x: hidden; background-color: #f8f9fc; }
        
        /* Menu Lateral Moderno */
        .sidebar { min-width: 260px; background: #0d1b2a; color: white; min-height: 100vh; display: flex; flex-direction: column; }
        
        .sidebar a { 
            color: rgba(255,255,255,0.7); 
            text-decoration: none; 
            padding: 16px 25px; 
            display: flex; 
            align-items: center;
            gap: 10px;
            border-left: 5px solid transparent; 
            transition: all 0.3s;
            font-size: 15px;
        }
        
        .sidebar a:hover { background: #1b263b; color: white; padding-left: 30px; }
        
        .sidebar a.active { 
            background: #1b263b; 
            color: white; 
            border-left-color: #0d6efd; 
            font-weight: bold; 
        }

        .sidebar a.destaque {
            color: #ffca2c; /* Amarelo */
        }
        .sidebar a.destaque:hover {
            background: rgba(255, 202, 44, 0.1);
        }

        .content { flex: 1; padding: 30px; width: 100%; overflow: hidden; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4 class="text-center py-4 border-bottom border-secondary mb-2">
            <i class="bi bi-box-seam-fill text-primary"></i> Indicadores
        </h4>
        
        <a href="index.php?page=obras" class="<?php echo (!isset($_GET['page']) || $_GET['page']=='obras')?'active':''; ?>">
            <i class="bi bi-buildings-fill"></i> Gestão de Obras
        </a>

        <a href="index.php?page=fornecedores" class="<?php echo (isset($_GET['page']) && $_GET['page']=='fornecedores')?'active':''; ?>">
            <i class="bi bi-truck-front-fill"></i> Fornecedores
        </a>
 
        <a href="index.php?page=configuracoes" class="<?php echo (isset($_GET['page']) && $_GET['page']=='configuracoes')?'active':''; ?>">
            <i class="bi bi-gear-fill"></i> Configurações
        </a>

        <hr class="border-secondary mx-3 my-2">

        <a href="index.php?page=central_importacoes" class="destaque <?php echo (isset($_GET['page']) && $_GET['page']=='central_importacoes')?'active':''; ?>">
            <i class="bi bi-cloud-arrow-up-fill"></i> Central de Importações
        </a>

    </div>

    <div class="content">
        <?php
            $pagina = isset($_GET['page']) ? $_GET['page'] : 'obras';
            // Proteção simples contra directory traversal
            $pagina = preg_replace('/[^a-zA-Z0-9_]/', '', $pagina);
            
            $arquivo = "pages/{$pagina}.php";

            if (file_exists($arquivo)) {
                include $arquivo;
            } else {
                echo "<div class='alert alert-danger shadow-sm'>
                        <h4 class='alert-heading'><i class='bi bi-exclamation-triangle'></i> Página não encontrada!</h4>
                        <p>O arquivo <b>$arquivo</b> não existe no sistema.</p>
                      </div>";
            }
        ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

</body>
</html>