<?php
// GESTÃO DE CLIENTES IMOBILIÁRIOS
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONSULTA: Traz Cliente + CPF + Nome da Casa (Empreendimento)
// Fazemos um LEFT JOIN para trazer o cliente mesmo que ele ainda não tenha casa vinculada
$sql = "SELECT 
            c.id, 
            c.nome, 
            c.cpf, 
            GROUP_CONCAT(DISTINCT v.nome_casa SEPARATOR ', ') as empreendimentos,
            COUNT(v.id) as qtd_imoveis,
            SUM(p.valor_pago) as total_pago_acumulado
        FROM clientes_imob c
        LEFT JOIN vendas_imob v ON v.cliente_id = c.id
        LEFT JOIN parcelas_imob p ON p.venda_id = v.id
        GROUP BY c.id
        ORDER BY c.nome ASC";

$clientes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-body p-3">
        <div class="row align-items-center">
            
            <div class="col-md-4">
                <h4 class="m-0 text-dark fw-bold"><i class="bi bi-people-fill"></i> Clientes (Imobiliário)</h4>
                <small class="text-muted" id="contador">Total: <?php echo count($clientes); ?> clientes</small>
            </div>

            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" id="filtroInput" class="form-control" placeholder="🔍 Buscar por Nome, CPF ou Empreendimento...">
                </div>
            </div>
            
            <div class="col-md-3 text-end">
                <a href="index.php?page=central_importacoes&tab=imob" class="btn btn-dark btn-sm shadow-sm">
                    <i class="bi bi-cloud-arrow-up"></i> Importar
                </a>
                <a href="index.php?page=configuracoes&tab=clientes" class="btn btn-outline-primary btn-sm shadow-sm">
                    <i class="bi bi-person-plus-fill"></i> Novo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row" id="listaClientes">
    
    <?php if(count($clientes) == 0): ?>
        <div class="col-12 text-center p-5">
            <h3 class="text-muted"><i class="bi bi-person-x"></i> Nenhum cliente cadastrado.</h3>
            <p>Vá em "Importar" para carregar a planilha financeira.</p>
        </div>
    <?php endif; ?>

    <?php foreach($clientes as $c): 
        // Define cor da borda: Azul se tem imóvel, Cinza se não tem
        $cor = (!empty($c['empreendimentos'])) ? 'primary' : 'secondary';
        
        // Texto para busca (Escondido)
        $textoBusca = strtolower($c['nome'] . ' ' . $c['cpf'] . ' ' . $c['empreendimentos']);
    ?>
    
    <div class="col-xl-3 col-md-6 mb-4 item-cliente" data-busca="<?php echo $textoBusca; ?>">
        <div class="card shadow-sm h-100 border-top border-4 border-<?php echo $cor; ?> hover-effect">
            <div class="card-body d-flex flex-column">
                
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-light text-dark border">ID: <?php echo $c['id']; ?></span>
                    <?php if($c['qtd_imoveis'] > 0): ?>
                        <span class="badge bg-success"><?php echo $c['qtd_imoveis']; ?> Unid.</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">S/ Imóvel</span>
                    <?php endif; ?>
                </div>
                
                <h5 class="card-title mt-1 text-truncate fw-bold text-dark" title="<?php echo $c['nome']; ?>">
                    <?php echo $c['nome']; ?>
                </h5>
                
                <div class="mb-3">
                    <span class="badge bg-secondary"><i class="bi bi-card-heading"></i> <?php echo $c['cpf'] ?: 'CPF N/D'; ?></span>
                </div>

                <div class="bg-light p-2 rounded mb-3 border">
                    <small class="text-muted d-block fw-bold" style="font-size: 10px;">EMPREENDIMENTO / UNIDADE</small>
                    <div class="text-primary fw-bold text-truncate">
                        <?php echo $c['empreendimentos'] ?: '<span class="text-muted fst-italic">Não vinculado</span>'; ?>
                    </div>
                </div>
                
                <div class="mt-auto pt-2 border-top">
                    <small class="text-muted d-block" style="font-size: 11px;">TOTAL PAGO (ACUMULADO)</small>
                    <h4 class="text-dark fw-bold">R$ <?php echo number_format($c['total_pago_acumulado'], 2, ',', '.'); ?></h4>
                </div>
                
                <a href="index.php?page=detalhe_cliente&id=<?php echo $c['id']; ?>" class="btn btn-outline-primary btn-sm w-100 mt-2 fw-bold stretched-link">
                    VER FINANCEIRO
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="semResultados" class="alert alert-warning text-center shadow-sm" style="display:none;">
    <h5>😕 Nenhum cliente encontrado.</h5>
</div>

<script>
document.getElementById('filtroInput').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase(); 
    let cards = document.querySelectorAll('.item-cliente');
    let visiveis = 0;

    cards.forEach(card => {
        let texto = card.getAttribute('data-busca');
        if(texto.includes(termo)) {
            card.style.display = ''; 
            visiveis++;
        } else {
            card.style.display = 'none'; 
        }
    });

    document.getElementById('contador').innerText = "Exibindo: " + visiveis;
    document.getElementById('semResultados').style.display = (visiveis === 0) ? 'block' : 'none';
});
</script>

<style>
    .hover-effect { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-effect:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1)!important; }
</style>