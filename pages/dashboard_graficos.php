<?php
// DASHBOARD FORNECEDORES (SEM LIMITES + FILTRO EMPRESA)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

// --- 1. FILTROS ---
$where_pedidos = "WHERE 1=1";
$params_pedidos = [];

$where_contratos = "WHERE 1=1";
$params_contratos = [];

// A. DATAS
$dt_ini = $_GET['dt_ini'] ?? date('Y-01-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-12-31');

if (!empty($dt_ini)) { 
    $where_pedidos .= " AND p.data_pedido >= ?"; $params_pedidos[] = $dt_ini; 
    $where_contratos .= " AND data_contrato >= ?"; $params_contratos[] = $dt_ini;
}
if (!empty($dt_fim)) { 
    $where_pedidos .= " AND p.data_pedido <= ?"; $params_pedidos[] = $dt_fim; 
    $where_contratos .= " AND data_contrato <= ?"; $params_contratos[] = $dt_fim;
}

// B. EMPRESA (NOVO!)
$filtro_emp = $_GET['filtro_emp'] ?? '';
if (!empty($filtro_emp)) { 
    $where_pedidos .= " AND p.empresa_id = ?"; $params_pedidos[] = $filtro_emp;
    // Contratos geralmente não têm vínculo direto com 'Empresa Pagadora' na estrutura simples, 
    // então filtramos apenas os pedidos. Se contratos tiverem esse vínculo, adicionamos aqui.
}

// C. OBRA
$filtro_obra = $_GET['filtro_obra'] ?? '';
if (!empty($filtro_obra)) { 
    $where_pedidos .= " AND p.obra_id = ?"; $params_pedidos[] = $filtro_obra;
    $where_contratos .= " AND fornecedor_id IN (SELECT DISTINCT fornecedor_id FROM pedidos WHERE obra_id = ?)";
    $params_contratos[] = $filtro_obra;
}

// D. FORNECEDOR
$filtro_forn = $_GET['filtro_forn'] ?? '';
if (!empty($filtro_forn)) { 
    $where_pedidos .= " AND p.fornecedor_id = ?"; $params_pedidos[] = $filtro_forn;
    $where_contratos .= " AND fornecedor_id = ?"; $params_contratos[] = $filtro_forn;
}

// E. PAGAMENTO
$filtro_pag = $_GET['filtro_pag'] ?? '';
if (!empty($filtro_pag)) { 
    $where_pedidos .= " AND p.forma_pagamento = ?"; $params_pedidos[] = $filtro_pag;
}

// --- 2. KPIs ---
$sql_kpi_ped = "SELECT SUM(valor_bruto_pedido) as total, COUNT(DISTINCT fornecedor_id) as qtd_forn FROM pedidos p $where_pedidos";
$stmt = $pdo->prepare($sql_kpi_ped);
$stmt->execute($params_pedidos);
$kpi_ped = $stmt->fetch(PDO::FETCH_ASSOC);
$total_gasto = $kpi_ped['total'] ?? 0;
$qtd_fornecedores = $kpi_ped['qtd_forn'] ?? 0;

$sql_kpi_cont = "SELECT SUM(valor) as total FROM contratos $where_contratos";
$stmt = $pdo->prepare($sql_kpi_cont);
$stmt->execute($params_contratos);
$total_contratos = $stmt->fetchColumn() ?? 0;


// --- 3. DADOS GRÁFICOS ---

// G1: RANKING FORNECEDORES (SEM LIMITES!)
$sql_forn = "SELECT f.nome, SUM(p.valor_bruto_pedido) as total
             FROM pedidos p
             JOIN fornecedores f ON p.fornecedor_id = f.id
             $where_pedidos
             GROUP BY f.id 
             ORDER BY total DESC"; // Removi o LIMIT
$stmt = $pdo->prepare($sql_forn);
$stmt->execute($params_pedidos);
$dados_forn = $stmt->fetchAll(PDO::FETCH_ASSOC);

// G2: PAGAMENTO
$sql_pag = "SELECT forma_pagamento, SUM(valor_bruto_pedido) as total
            FROM pedidos p
            $where_pedidos AND p.forma_pagamento != ''
            GROUP BY forma_pagamento 
            ORDER BY total DESC";
$stmt = $pdo->prepare($sql_pag);
$stmt->execute($params_pedidos);
$dados_pag = $stmt->fetchAll(PDO::FETCH_ASSOC);

// G3: CONTRATOS
$sql_resp = "SELECT responsavel, SUM(valor) as total
             FROM contratos
             $where_contratos
             GROUP BY responsavel
             ORDER BY total DESC";
$stmt = $pdo->prepare($sql_resp);
$stmt->execute($params_contratos);
$dados_resp = $stmt->fetchAll(PDO::FETCH_ASSOC);


// LISTAS SELECTS
$emp_list  = $pdo->query("SELECT id, nome FROM empresas ORDER BY nome")->fetchAll();
$obras_list = $pdo->query("SELECT id, nome FROM obras ORDER BY nome")->fetchAll();
$forn_list = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$pag_list = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();

// JSON
$json_forn_lbl = json_encode(array_column($dados_forn, 'nome'));
$json_forn_val = json_encode(array_column($dados_forn, 'total'));

$json_pag_lbl = json_encode(array_column($dados_pag, 'forma_pagamento'));
$json_pag_val = json_encode(array_column($dados_pag, 'total'));

$json_resp_lbl = json_encode(array_column($dados_resp, 'responsavel'));
$json_resp_val = json_encode(array_column($dados_resp, 'total'));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="container-fluid p-4 bg-light">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-dark fw-bold m-0"><i class="bi bi-graph-up-arrow text-primary"></i> ANÁLISE DE FORNECEDORES</h3>
            <span class="text-muted small">Indicadores de Compras e Contratos</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-dark fw-bold me-2"><i class="bi bi-printer"></i> IMPRIMIR</button>
            <a href="index.php?page=fornecedores" class="btn btn-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-white py-2">
            <small class="fw-bold text-primary text-uppercase ls-1">Filtros Gerais</small>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="dashboard_graficos">
                
                <div class="col-md-2"><label class="fw-bold small text-muted">Início</label><input type="date" name="dt_ini" class="form-control" value="<?php echo $dt_ini; ?>"></div>
                <div class="col-md-2"><label class="fw-bold small text-muted">Fim</label><input type="date" name="dt_fim" class="form-control" value="<?php echo $dt_fim; ?>"></div>
                
                <div class="col-md-3">
                    <label class="fw-bold small text-muted">Empresa</label>
                    <select name="filtro_emp" class="form-select">
                        <option value="">-- Todas --</option>
                        <?php foreach($emp_list as $e): ?><option value="<?php echo $e['id']; ?>" <?php echo ($filtro_emp==$e['id'])?'selected':''; ?>><?php echo substr($e['nome'],0,30); ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold small text-muted">Obra</label>
                    <select name="filtro_obra" class="form-select">
                        <option value="">-- Todas --</option>
                        <?php foreach($obras_list as $o): ?><option value="<?php echo $o['id']; ?>" <?php echo ($filtro_obra==$o['id'])?'selected':''; ?>><?php echo substr($o['nome'],0,30); ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="fw-bold small text-muted">Fornecedor</label>
                    <select name="filtro_forn" class="form-select">
                        <option value="">-- Todos --</option>
                        <?php foreach($forn_list as $f): ?><option value="<?php echo $f['id']; ?>" <?php echo ($filtro_forn==$f['id'])?'selected':''; ?>><?php echo substr($f['nome'],0,25); ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold small text-muted">Pagamento</label>
                    <select name="filtro_pag" class="form-select">
                        <option value="">-- Todas --</option>
                        <?php foreach($pag_list as $p): ?><option value="<?php echo $p['forma_pagamento']; ?>" <?php echo ($filtro_pag==$p['forma_pagamento'])?'selected':''; ?>><?php echo $p['forma_pagamento']; ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel"></i> FILTRAR</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-5 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><small class="text-uppercase fw-bold text-primary">Total Gasto</small><h2 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($total_gasto, 2, ',', '.'); ?></h2></div>
                        <div class="text-primary opacity-25"><i class="bi bi-cart-check fs-1"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-5 border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><small class="text-uppercase fw-bold text-warning">Contratos</small><h2 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($total_contratos, 2, ',', '.'); ?></h2></div>
                        <div class="text-warning opacity-25"><i class="bi bi-file-earmark-text fs-1"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-5 border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><small class="text-uppercase fw-bold text-secondary">Fornecedores</small><h2 class="fw-bold text-dark mt-1 mb-0"><?php echo $qtd_fornecedores; ?></h2></div>
                        <div class="text-secondary opacity-25"><i class="bi bi-truck fs-1"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-trophy"></i> RANKING DE GASTO POR FORNECEDOR</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyForn').slideToggle()"><i class="bi bi-eye"></i></button>
                </div>
                <div class="card-body" id="bodyForn">
                    <div style="height: <?php echo max(400, count($dados_forn) * 35 + 80); ?>px;">
                        <canvas id="chartForn"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-header bg-white py-3"><h5 class="fw-bold text-dark m-0">FORMAS DE PAGAMENTO</h5></div>
                <div class="card-body"><div style="height: 350px;"><canvas id="chartPag"></canvas></div></div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-header bg-white py-3"><h5 class="fw-bold text-dark m-0">CONTRATOS POR RESPONSÁVEL</h5></div>
                <div class="card-body"><div style="height: 350px;"><canvas id="chartResp"></canvas></div></div>
            </div>
        </div>
    </div>
</div>

<script>
const fmtBRL = (val) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(val);
const fmtCompact = (val) => new Intl.NumberFormat('pt-BR', { notation: "compact", compactDisplay: "short" }).format(val);

Chart.register(ChartDataLabels);
Chart.defaults.font.family = "'Segoe UI', sans-serif";

// --- G1: FORNECEDORES (SEM LIMITES) ---
new Chart(document.getElementById('chartForn'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_forn_lbl; ?>,
        datasets: [{
            label: 'Total Gasto',
            data: <?php echo $json_forn_val; ?>,
            backgroundColor: '#0d6efd',
            borderRadius: 4,
            barPercentage: 0.8 // Barras um pouco mais grossas
        }]
    },
    options: {
        indexAxis: 'y', 
        responsive: true,
        maintainAspectRatio: false, // Permite altura customizada
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (c) => fmtBRL(c.raw) } },
            datalabels: {
                anchor: 'end', align: 'end',
                formatter: (val) => fmtCompact(val),
                color: '#333', font: { weight: 'bold' }
            }
        },
        scales: { x: { grid: { display: false } } }
    }
});

// --- G2: PAGAMENTO ---
new Chart(document.getElementById('chartPag'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $json_pag_lbl; ?>,
        datasets: [{
            data: <?php echo $json_pag_val; ?>,
            backgroundColor: ['#198754', '#ffc107', '#0dcaf0', '#dc3545', '#6610f2', '#fd7e14'],
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            datalabels: { color: '#fff', font: { weight: 'bold' }, formatter: (val, ctx) => {
                let sum = ctx.chart._metasets[ctx.datasetIndex].total;
                return (val * 100 / sum).toFixed(0) + "%";
            }}
        }
    }
});

// --- G3: CONTRATOS ---
new Chart(document.getElementById('chartResp'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_resp_lbl; ?>,
        datasets: [{
            label: 'Total Contratado',
            data: <?php echo $json_resp_val; ?>,
            backgroundColor: '#ffc107',
            borderRadius: 4,
            barPercentage: 0.5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (c) => fmtBRL(c.raw) } },
            datalabels: { anchor: 'end', align: 'top', formatter: (val) => fmtCompact(val), color: '#333', font: { weight: 'bold' } }
        },
        scales: { y: { grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
    }
});
</script>

<style>
@media print {
    .btn, form, a { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    canvas { max-width: 100% !important; height: auto !important; page-break-inside: avoid; }
}
</style>