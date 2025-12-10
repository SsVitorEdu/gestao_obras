<?php
// ATIVA DEBUG
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CONSULTA SQL
$sql = "SELECT 
            o.id, 
            o.codigo, 
            o.nome, 
            COALESCE(e.nome, 'Sem Empresa') as nome_empresa, 
            COUNT(p.id) as total_itens,
            SUM(CASE WHEN p.qtd_recebida >= p.qtd_pedida THEN 1 ELSE 0 END) as itens_concluidos
        FROM obras o 
        LEFT JOIN empresas e ON o.empresa_id = e.id
        LEFT JOIN pedidos p ON p.obra_id = o.id
        GROUP BY o.id
        ORDER BY o.codigo DESC, o.id DESC";

try {
    $obras = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if(strpos($e->getMessage(), "Unknown column 'o.codigo'") !== false) {
        echo "<div class='alert alert-warning'>⚠️ Coluna código faltando. <a href='atualizar_banco.php'>Criar agora</a>.</div>";
        exit;
    }
    echo "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
    exit;
}
?>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-body p-3">
        <div class="row align-items-center">
            
            <div class="col-md-3">
                <h4 class="m-0 text-primary fw-bold"><i class="bi bi-buildings"></i> Projetos</h4>
                <small class="text-muted" id="contador">Total: <?php echo count($obras); ?></small>
            </div>

            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" id="filtroInput" class="form-control" placeholder="🔍 Filtrar por Código, Obra ou Empresa...">
                </div>
            </div>
            
            <div class="col-md-4 text-end d-flex justify-content-end gap-2">
                <a href="index.php?page=dashboard_obras" class="btn btn-dark btn-sm fw-bold shadow-sm">
                    <i class="bi bi-bar-chart-fill"></i> Dashboard
                </a>

                <a href="index.php?page=carga_obras_simples" class="btn btn-success btn-sm shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Importar
                </a>
                <a href="index.php?page=nova_obra" class="btn btn-outline-primary btn-sm shadow-sm">
                    <i class="bi bi-plus-lg"></i> Novo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row" id="listaObras">
    <?php if(count($obras) == 0): ?>
        <div class="col-12 text-center p-5">
            <h3 class="text-muted">📭 Nenhuma obra encontrada.</h3>
        </div>
    <?php endif; ?>

    <?php foreach($obras as $obra): 
        $total = $obra['total_itens'];
        $feitos = $obra['itens_concluidos'];
        $porcentagem = ($total > 0) ? round(($feitos / $total) * 100) : 0;
        
        $cor = 'primary';
        if($porcentagem >= 50) $cor = 'warning';
        if($porcentagem >= 100) $cor = 'success';
        
        // Texto para busca (Escondido no HTML para o JS ler)
        $textoBusca = strtolower($obra['codigo'] . ' ' . $obra['nome'] . ' ' . $obra['nome_empresa']);
    ?>
    
    <div class="col-xl-3 col-md-6 mb-4 obra-item" data-busca="<?php echo $textoBusca; ?>">
        <div class="card shadow-sm h-100 border-start border-4 border-<?php echo $cor; ?> hover-effect">
            <div class="card-body">
                
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-dark rounded-pill">Cód: <?php echo $obra['codigo'] ?: 'S/N'; ?></span>
                    <span class="badge bg-<?php echo $cor; ?>"><?php echo $porcentagem; ?>%</span>
                </div>
                
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">
                    <i class="bi bi-building"></i> <?php echo substr($obra['nome_empresa'], 0, 25); ?>
                </small>

                <h5 class="card-title mt-1 text-truncate fw-bold text-dark" title="<?php echo $obra['nome']; ?>">
                    <?php echo $obra['nome']; ?>
                </h5>
                
                <div class="progress my-2" style="height: 5px;">
                    <div class="progress-bar bg-<?php echo $cor; ?>" style="width: <?php echo $porcentagem; ?>%"></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <small class="text-muted">
                        <i class="bi bi-box-seam"></i> <?php echo $total; ?> itens
                    </small>
                    <a href="index.php?page=detalhe_obra&id=<?php echo $obra['id']; ?>" class="btn btn-outline-dark btn-sm stretched-link">
                        Abrir
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="semResultados" class="alert alert-warning text-center" style="display:none;">
    <h5>😕 Nenhuma obra encontrada para essa busca.</h5>
</div>

<script>
document.getElementById('filtroInput').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase(); // O que foi digitado
    let cards = document.querySelectorAll('.obra-item'); // Todos os cards
    let visiveis = 0;

    cards.forEach(card => {
        // Pega o texto escondido que montamos no PHP
        let texto = card.getAttribute('data-busca');
        
        if(texto.includes(termo)) {
            card.style.display = ''; // Mostra (remove o display:none)
            visiveis++;
        } else {
            card.style.display = 'none'; // Esconde
        }
    });

    // Atualiza contador
    document.getElementById('contador').innerText = "Exibindo: " + visiveis;

    // Mostra aviso se não achar nada
    if(visiveis === 0) {
        document.getElementById('semResultados').style.display = 'block';
    } else {
        document.getElementById('semResultados').style.display = 'none';
    }
});
</script>

<style>
    .hover-effect:hover {
        transform: translateY(-3px);
        transition: transform 0.2s;
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>