<?php
// CADASTRO DE EMPRESAS (COM LIMPEZA E ANTI-DUPLICIDADE)
ini_set('display_errors', 1);
$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // AÇÃO 1: LIMPAR TUDO (Zerar Tabela)
    if (isset($_POST['acao']) && $_POST['acao'] == 'limpar_tudo') {
        try {
            // Desativa verificação de chave estrangeira temporariamente para permitir limpar
            $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->query("TRUNCATE TABLE empresas");
            $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Garante que a empresa padrão (ID 1) exista para não quebrar o sistema
            $pdo->query("INSERT IGNORE INTO empresas (id, nome, codigo) VALUES (1, 'EMPRESA GERAL', '000')");
            
            $msg = "<div class='alert alert-warning'>🗑️ Tabela de Empresas limpa com sucesso!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Erro ao limpar: " . $e->getMessage() . "</div>";
        }
    }

    // AÇÃO 2: IMPORTAR LISTA
    elseif (isset($_POST['dados'])) {
        $dados = $_POST['dados'];
        $linhas = array_filter(explode("\n", $dados), 'trim');
        
        // ARRAY PARA REMOVER DUPLICADOS DA LISTA (O PULO DO GATO 😺)
        $lista_unica = [];

        foreach($linhas as $linha) {
            $cols = explode("\t", $linha);
            if(count($cols) < 2) continue;

            $cod = trim($cols[0]);
            $nome = trim($cols[1]);

            // Pula cabeçalho ou inválidos
            if(empty($cod) || empty($nome) || strtoupper($cod) == 'COD' || strtoupper($cod) == 'COD. EMPR.') continue;

            // Salva no array usando o CÓDIGO como chave.
            // Se o código se repetir, ele sobrescreve o anterior, garantindo unicidade.
            $lista_unica[$cod] = $nome;
        }

        // AGORA GRAVA NO BANCO
        $novas = 0; 
        $atualizadas = 0;
        
        $sql = "INSERT INTO empresas (codigo, nome) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE nome = VALUES(nome)";
        $stmt = $pdo->prepare($sql);

        foreach($lista_unica as $codigo => $nome) {
            $stmt->execute([$codigo, $nome]);
            if($stmt->rowCount() == 1) $novas++; 
            else $atualizadas++;
        }

        $msg = "<div class='alert alert-success'>
                    <h4>✅ Processamento Concluído!</h4>
                    <ul>
                        <li><b>".count($linhas)."</b> linhas lidas no total.</li>
                        <li><b>".count($lista_unica)."</b> empresas únicas identificadas.</li>
                        <li><b>$novas</b> criadas e <b>$atualizadas</b> atualizadas.</li>
                    </ul>
                </div>";
    }
}
?>

<div class="container mt-4">
    <div class="card shadow border-info">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h4 class="m-0"><i class="bi bi-buildings"></i> Cadastro de Empresas Mestre</h4>
            
            <form method="POST" onsubmit="return confirm('ATENÇÃO: Isso apagará todas as empresas cadastradas! Tem certeza?')">
                <input type="hidden" name="acao" value="limpar_tudo">
                <button class="btn btn-danger btn-sm text-white fw-bold"><i class="bi bi-trash"></i> Limpar Banco</button>
            </form>
        </div>
        
        <div class="card-body">
            <?php echo $msg; ?>
            
            <div class="alert alert-info small">
                <b>COLE DUAS COLUNAS DO EXCEL:</b><br>
                <code>COD EMP | RAZÃO SOCIAL</code><br>
                <br>
                <i>* Pode colar repetido à vontade! O sistema vai filtrar e deixar apenas uma de cada código.</i>
            </div>
            
            <form method="POST">
                <textarea name="dados" class="form-control mb-3" rows="15" placeholder="Cole aqui:
001	GDA NEGOCIOS
002	PURA CONSTRUTORA
001	GDA NEGOCIOS (O sistema ignora essa repetição)" style="font-family: monospace; font-size: 12px;"></textarea>
                
                <button class="btn btn-info w-100 fw-bold text-white btn-lg">
                    <i class="bi bi-save"></i> SALVAR EMPRESAS
                </button>
            </form>
        </div>
    </div>
</div>