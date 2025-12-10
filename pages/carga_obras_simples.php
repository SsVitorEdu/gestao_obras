<?php
// CADASTRO DE OBRAS (VIA CÓDIGO + NOME - SEM VÍNCULO DE EMPRESA)
ini_set('display_errors', 1);
$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // AÇÃO 1: LIMPAR TUDO
    if (isset($_POST['acao']) && $_POST['acao'] == 'limpar_tudo') {
        try {
            $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->query("TRUNCATE TABLE obras");
            $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $msg = "<div class='alert alert-warning'>🗑️ Banco de Obras limpo com sucesso!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Erro ao limpar: " . $e->getMessage() . "</div>";
        }
    }

    // AÇÃO 2: PROCESSAR LISTA
    elseif (isset($_POST['dados'])) {
        $dados = $_POST['dados'];
        $linhas = array_filter(explode("\n", $dados), 'trim');
        
        // Remove duplicados da lista (mantém o último nome encontrado para o código)
        $lista_unica = [];
        foreach($linhas as $linha) {
            $cols = explode("\t", $linha);
            if(count($cols) < 2) continue;

            $cod = strtoupper(trim($cols[0])); // Código maiúsculo
            $nome = trim($cols[1]);

            // Ignora cabeçalhos
            if(empty($cod) || $cod == 'COD' || $cod == 'COD. OBRA' || $cod == 'COD OBRA') continue;

            $lista_unica[$cod] = $nome;
        }

        $novas = 0; 
        $atualizadas = 0;

        // Prepara a query
        $check = $pdo->prepare("SELECT id FROM obras WHERE codigo = ?");
        // AQUI ESTÁ A MUDANÇA: empresa_id vai como NULL
        $insert = $pdo->prepare("INSERT INTO obras (codigo, nome, empresa_id) VALUES (?, ?, NULL)");
        $update = $pdo->prepare("UPDATE obras SET nome = ? WHERE id = ?");

        $pdo->beginTransaction();

        foreach($lista_unica as $codigo => $nome) {
            // 1. Verifica se já existe
            $check->execute([$codigo]);
            $obra_existente = $check->fetch(PDO::FETCH_ASSOC);

            if ($obra_existente) {
                // JÁ EXISTE: ATUALIZA O NOME
                $update->execute([$nome, $obra_existente['id']]);
                $atualizadas++;
            } else {
                // NÃO EXISTE: CRIA NOVA (Sem empresa vinculada)
                $insert->execute([$codigo, $nome]);
                $novas++;
            }
        }

        $pdo->commit();

        $msg = "<div class='alert alert-success'>
                    <h4>✅ Obras Processadas!</h4>
                    <ul>
                        <li><b>$novas</b> novas obras criadas.</li>
                        <li><b>$atualizadas</b> obras atualizadas (nome corrigido).</li>
                    </ul>
                </div>";
    }
}
?>

<div class="container mt-4">
    <div class="card shadow border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="m-0"><i class="bi bi-cone-striped"></i> Cadastro de Obras (Pelo Código)</h4>
            
            <form method="POST" onsubmit="return confirm('ATENÇÃO: Vai apagar todas as obras! Confirma?')">
                <input type="hidden" name="acao" value="limpar_tudo">
                <button class="btn btn-danger btn-sm text-white fw-bold"><i class="bi bi-trash"></i> Limpar Banco</button>
            </form>
        </div>
        
        <div class="card-body">
            <?php echo $msg; ?>
            
            <div class="alert alert-info small">
                <b>COLE AS COLUNAS "COD OBRA" E "OBRA" DO EXCEL:</b><br>
                <code>CÓDIGO | NOME DA OBRA</code><br>
                * O sistema não vinculará nenhuma empresa aqui. Isso será feito na importação dos pedidos.
            </div>
            
            <form method="POST">
                <textarea name="dados" class="form-control mb-3" rows="15" placeholder="Cole aqui... Exemplo:
157	SHOPPING AMERICANA
158	RESIDENCIAL FLORES" style="font-family: monospace; font-size: 12px;"></textarea>
                
                <button class="btn btn-primary w-100 fw-bold btn-lg">
                    <i class="bi bi-save"></i> SALVAR OBRAS
                </button>
            </form>
        </div>
    </div>
</div>