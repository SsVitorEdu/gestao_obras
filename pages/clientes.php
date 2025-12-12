<?php
// GESTÃO DE CLIENTES (FILTROS + 3 VALORES FINANCEIROS)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. CAPTURA DE FILTROS ---
$dt_ini = $_GET['dt_ini'] ?? date('Y-01-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-12-31');
$filtro_status = $_GET['status'] ?? 'todos'; // todos, recebidos, a_receber

// --- 2. CONSTRUÇÃO DO WHERE (FILTROS) ---
$where_cond = "WHERE 1=1";
$params = [];

// Filtro de Data (Aplica ao Vencimento ou Pagamento dependendo do contexto, 
// mas para simplificar a visão geral, vamos filtrar por Vencimento no período)
if (!empty($dt_ini)) { $where_cond .= " AND p.data_vencimento >= ?"; $params[] = $dt_ini; }
if (!empty($dt_fim)) { $where_cond .= " AND p.data_vencimento <= ?"; $params[] = $dt_fim; }

// Filtro de Status
if ($filtro_status == 'recebidos') {
    $where_cond .= " AND p.valor_pago > 0"; // Teve algum pagamento
} elseif ($filtro_status == 'a_receber') {
    $where_cond .= " AND p.valor_pago < p.valor_parcela"; // Falta pagar algo
}

// --- 3. CONSULTA SQL (AGRUPADA POR CLIENTE) ---
$sql = "SELECT 
            c.id, 
            c.nome, 
            c.cpf, 
            GROUP_CONCAT(DISTINCT v.nome_casa SEPARATOR ', ') as empreendimentos,
            COUNT(DISTINCT v.id) as qtd_imoveis,
            
            -- OS 3 VALORES SOLICITADOS (Somados conforme filtro)
            SUM(p.valor_parcela) as valor_original,
            SUM(p.valor_pago) as valor_recebimento

        FROM clientes_imob c
        LEFT JOIN vendas_imob v ON v.cliente_id = c.id
        LEFT JOIN parcelas_imob p ON p.venda_id = v.id
        $where_cond
        GROUP BY c.id
        HAVING valor_original > 0 -- Só mostra quem tem conta no período
        ORDER BY c.nome ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
    exit;
}

// --- 4. CÁLCULO DOS TOTAIS GERAIS (KPIs) ---
$geral_original = 0;
$geral_recebimento = 0;
$geral_total_saldo = 0;

foreach($clientes as $c) {
    $geral_original += $c['valor_original'];
    $geral_recebimento += $c['valor_recebimento'];
}
$geral_total_saldo = $geral_original - $geral_recebimento;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="m-0 text-dark fw-bold"><i class="bi bi-people-fill text-primary"></i> Clientes & Carteira</h3>
        <span class="text-muted small">Gerenciamento Financeiro</span>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=dashboard_clientes" class="btn btn-warning btn-sm shadow-sm fw-bold"><i class="bi bi-pie-chart-fill"></i> Dashboard</a>
        <a href="index.php?page=relatorio_master_imob" class="btn btn-outline-dark btn-sm shadow-sm fw-bold"><i class="bi bi-file-earmark-bar-graph"></i> Relatório</a>
        <a href="index.php?page=importar_clientes" class="btn btn-dark btn-sm shadow-sm"><i class="bi bi-cloud-arrow-up"></i> Importar</a>
        <a href="index.php?page=configuracoes&tab=clientes" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-plus-lg"></i> Novo</a>
    </div>
</div>

<div class="card shadow-sm mb-4 border-0 bg-light">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="clientes">
            
            <div class="col-auto">
                <span class="fw-bold text-muted small"><i class="bi bi-funnel"></i> FILTRAR:</span>
            </div>
            
            <div class="col-md-2">
                <input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $dt_ini; ?>">
            </div>
            <div class="col-auto text-muted small">até</div>
            <div class="col-md-2">
                <input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $dt_fim; ?>">
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm fw-bold text-<?php echo ($filtro_status=='recebidos'?'success':($filtro_status=='a_receber'?'danger':'dark')); ?>">
                    <option value="todos" <?php echo $filtro_status=='todos'?'selected':''; ?>>📋 Todos</option>
                    <option value="recebidos" <?php echo $filtro_status=='recebidos'?'selected':''; ?>>✅ Já Recebidos</option>
                    <option value="a_receber" <?php echo $filtro_status=='a_receber'?'selected':''; ?>>📅 A Receber</option>
                </select>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">ATUALIZAR</button>
            </div>

            <div class="col ms-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="filtroInput" class="form-control border-start-0" placeholder="Buscar nome, CPF...">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-5 border-secondary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-secondary" style="font-size: 0.7rem;">VALOR ORIGINAL (CARTEIRA)</small>
                        <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($geral_original, 2, ',', '.'); ?></h4>
                    </div>
                    <div class="text-secondary opacity-25"><i class="bi bi-bank fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-start border-5 border-success h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-success" style="font-size: 0.7rem;">VALOR RECEBIMENTO (PAGO)</small>
                        <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($geral_recebimento, 2, ',', '.'); ?></h4>
                    </div>
                    <div class="text-success opacity-25"><i class="bi bi-cash-coin fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-start border-5 border-primary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-primary" style="font-size: 0.7rem;">VALOR TOTAL (A RECEBER/SALDO)</small>
                        <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($geral_total_saldo, 2, ',', '.'); ?></h4>
                    </div>
                    <div class="text-primary opacity-25"><i class="bi bi-wallet2 fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="listaClientes">
    
    <?php if(empty($clientes)): ?>
        <div class="col-12 text-center p-5">
            <h3 class="text-muted"><i class="bi bi-person-x"></i> Nenhum cliente encontrado neste período/status.</h3>
        </div>
    <?php endif; ?>

    <?php foreach($clientes as $c): 
        $saldo = $c['valor_original'] - $c['valor_recebimento'];
        $cor = ($saldo > 0) ? 'primary' : 'success'; // Azul se deve, Verde se quitou
        $textoBusca = strtolower($c['nome'] . ' ' . $c['cpf'] . ' ' . $c['empreendimentos']);
    ?>
    
    <div class="col-xl-3 col-md-6 mb-4 item-cliente" data-busca="<?php echo $textoBusca; ?>">
        <div class="card shadow-sm h-100 border-top border-4 border-<?php echo $cor; ?> hover-effect">
            <div class="card-body d-flex flex-column p-3">
                
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-light text-dark border">ID: <?php echo $c['id']; ?></span>
                    <?php if($c['qtd_imoveis'] > 0): ?>
                        <span class="badge bg-info text-dark"><?php echo $c['qtd_imoveis']; ?> Unid.</span>
                    <?php endif; ?>
                </div>
                
                <h6 class="card-title mt-1 fw-bold text-dark text-truncate" title="<?php echo $c['nome']; ?>">
                    <?php echo $c['nome']; ?>
                </h6>
                <div class="mb-3 text-muted small">
                    <i class="bi bi-house-door"></i> <?php echo mb_strimwidth($c['empreendimentos'], 0, 30, '...'); ?>
                </div>

                <div class="bg-light rounded p-2 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted" style="font-size: 10px;">ORIGINAL</small>
                        <span class="fw-bold text-secondary small">R$ <?php echo number_format($c['valor_original'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted" style="font-size: 10px;">RECEBIMENTO</small>
                        <span class="fw-bold text-success small">R$ <?php echo number_format($c['valor_recebimento'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="border-top my-1"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted fw-bold" style="font-size: 10px;">TOTAL (SALDO)</small>
                        <span class="fw-bold text-primary small">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></span>
                    </div>
                </div>
                
                <a href="index.php?page=detalhe_cliente&id=<?php echo $c['id']; ?>" class="btn btn-outline-dark btn-sm w-100 mt-auto fw-bold stretched-link">
                    VER FINANCEIRO
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
document.getElementById('filtroInput').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase(); 
    let cards = document.querySelectorAll('.item-cliente');
    
    cards.forEach(card => {
        let texto = card.getAttribute('data-busca');
        if(texto.includes(termo)) {
            card.style.display = ''; 
        } else {
            card.style.display = 'none'; 
        }
    });
});
</script>

<style>
    .hover-effect { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-effect:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1)!important; }
</style>