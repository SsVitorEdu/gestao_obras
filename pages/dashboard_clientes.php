<?php
// DASHBOARD IMOBILIÁRIO (BARRAS LADO A LADO + FILTRO VISUAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

// --- 1. FILTROS ---
$where = "WHERE 1=1";
$params = [];

// Datas
$dt_ini = $_GET['dt_ini'] ?? date('Y-01-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-12-31');

if (!empty($dt_ini)) { 
    $where .= " AND (p.data_vencimento >= ? OR p.data_pagamento >= ?)"; 
    $params[] = $dt_ini; $params[] = $dt_ini;
}
if (!empty($dt_fim)) { 
    $where .= " AND (p.data_vencimento <= ? OR p.data_pagamento <= ?)"; 
    $params[] = $dt_fim; $params[] = $dt_fim;
}

// Filtro Empresa
$filtro_emp = $_GET['filtro_emp'] ?? '';
if (!empty($filtro_emp)) {
    $where .= " AND v.nome_empresa = ?";
    $params[] = $filtro_emp;
}

// Filtro de Status (Afeta o WHERE do banco)
$filtro_status = $_GET['filtro_status'] ?? '';
if ($filtro_status == 'pago') {
    $where .= " AND p.valor_pago > 0";
} elseif ($filtro_status == 'vencido') {
    $where .= " AND p.data_vencimento < CURDATE() AND p.valor_pago < p.valor_parcela";
} elseif ($filtro_status == 'aberto') {
    $where .= " AND p.data_vencimento >= CURDATE() AND p.valor_pago < p.valor_parcela";
}

// --- 2. CONSULTAS ---

// KPI GERAIS
$sql_kpi = "SELECT 
                SUM(p.valor_pago) as total_recebido,
                SUM(CASE WHEN p.data_vencimento < CURDATE() AND p.valor_pago < p.valor_parcela THEN (p.valor_parcela - p.valor_pago) ELSE 0 END) as total_vencido,
                SUM(CASE WHEN p.data_vencimento >= CURDATE() AND p.valor_pago < p.valor_parcela THEN (p.valor_parcela - p.valor_pago) ELSE 0 END) as total_a_receber
            FROM parcelas_imob p
            JOIN vendas_imob v ON p.venda_id = v.id
            $where";
$stmt = $pdo->prepare($sql_kpi);
$stmt->execute($params);
$kpi = $stmt->fetch(PDO::FETCH_ASSOC);

$total_recebido = $kpi['total_recebido'] ?? 0;
$total_vencido = $kpi['total_vencido'] ?? 0;
$total_a_receber = $kpi['total_a_receber'] ?? 0;
$total_geral = $total_recebido + $total_vencido + $total_a_receber;


// GRÁFICO 1: EVOLUÇÃO MENSAL
$sql_mes = "SELECT 
                DATE_FORMAT(p.data_vencimento, '%Y-%m') as mes_ref,
                DATE_FORMAT(p.data_vencimento, '%m/%Y') as mes_label,
                SUM(p.valor_pago) as recebido,
                SUM(CASE WHEN p.valor_pago < p.valor_parcela THEN (p.valor_parcela - p.valor_pago) ELSE 0 END) as pendente
            FROM parcelas_imob p
            JOIN vendas_imob v ON p.venda_id = v.id
            $where
            GROUP BY mes_ref
            ORDER BY mes_ref ASC";
$stmt = $pdo->prepare($sql_mes);
$stmt->execute($params);
$dados_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// GRÁFICO 2: POR EMPRESA
$sql_emp = "SELECT 
                v.nome_empresa,
                SUM(p.valor_pago) as recebido,
                SUM(CASE WHEN p.data_vencimento < CURDATE() AND p.valor_pago < p.valor_parcela THEN (p.valor_parcela - p.valor_pago) ELSE 0 END) as vencido,
                SUM(CASE WHEN p.data_vencimento >= CURDATE() AND p.valor_pago < p.valor_parcela THEN (p.valor_parcela - p.valor_pago) ELSE 0 END) as a_receber
            FROM parcelas_imob p
            JOIN vendas_imob v ON p.venda_id = v.id
            $where
            GROUP BY v.nome_empresa
            ORDER BY recebido DESC";
$stmt = $pdo->prepare($sql_emp);
$stmt->execute($params);
$dados_emp = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Lista para filtro
$lista_empresas = $pdo->query("SELECT DISTINCT nome_empresa FROM vendas_imob ORDER BY nome_empresa")->fetchAll();

// JSON Charts
$json_mes_lbl = json_encode(array_column($dados_mes, 'mes_label'));
$json_mes_rec = json_encode(array_column($dados_mes, 'recebido'));
$json_mes_pen = json_encode(array_column($dados_mes, 'pendente'));

$json_emp_lbl = json_encode(array_column($dados_emp, 'nome_empresa'));
$json_emp_rec = json_encode(array_column($dados_emp, 'recebido'));
$json_emp_ven = json_encode(array_column($dados_emp, 'vencido'));
$json_emp_fut = json_encode(array_column($dados_emp, 'a_receber'));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="container-fluid p-4 bg-light">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-dark fw-bold m-0"><i class="bi bi-pie-chart-fill text-success"></i> DASHBOARD FINANCEIRO</h3>
            <span class="text-muted small">Inteligência de Recebíveis</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-dark fw-bold me-2"><i class="bi bi-printer"></i> IMPRIMIR</button>
            <a href="index.php?page=clientes" class="btn btn-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-white py-3">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="dashboard_clientes">
                
                <div class="col-md-2">
                    <label class="fw-bold small text-muted">Início</label>
                    <input type="date" name="dt_ini" class="form-control" value="<?php echo $dt_ini; ?>">
                </div>
                <div class="col-md-2">
                    <label class="fw-bold small text-muted">Fim</label>
                    <input type="date" name="dt_fim" class="form-control" value="<?php echo $dt_fim; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="fw-bold small text-muted">Status</label>
                    <select name="filtro_status" id="filtro_status" class="form-select">
                        <option value="">-- Todos --</option>
                        <option value="pago" <?php echo ($filtro_status=='pago')?'selected':''; ?>>✅ Recebidos</option>
                        <option value="vencido" <?php echo ($filtro_status=='vencido')?'selected':''; ?>>🔴 Vencidos</option>
                        <option value="aberto" <?php echo ($filtro_status=='aberto')?'selected':''; ?>>📅 A Receber</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="fw-bold small text-muted">Empresa</label>
                    <select name="filtro_emp" class="form-select">
                        <option value="">-- Todas --</option>
                        <?php foreach($lista_empresas as $e): ?>
                            <option value="<?php echo $e['nome_empresa']; ?>" <?php echo ($filtro_emp == $e['nome_empresa'])?'selected':''; ?>>
                                <?php echo mb_strimwidth($e['nome_empresa'], 0, 45, "..."); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-success w-100 fw-bold"><i class="bi bi-funnel-fill"></i> ATUALIZAR</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-5 border-success h-100">
                <div class="card-body">
                    <small class="text-uppercase fw-bold text-success ls-1">Recebido</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($total_recebido, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-5 border-primary h-100">
                <div class="card-body">
                    <small class="text-uppercase fw-bold text-primary ls-1">A Receber</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($total_a_receber, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-5 border-danger h-100">
                <div class="card-body">
                    <small class="text-uppercase fw-bold text-danger ls-1">Vencido</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($total_vencido, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-5 border-secondary h-100">
                <div class="card-body">
                    <small class="text-uppercase fw-bold text-secondary ls-1">Total (Filtro)</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-bar-chart-line"></i> FLUXO MENSAL</h5>
                </div>
                <div class="card-body">
                    <div style="height: 400px;"><canvas id="chartFluxo"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-building"></i> POR EMPRESA</h5>
                </div>
                <div class="card-body">
                    <div style="height: 500px;"><canvas id="chartEmpresa"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const fmtBRL = (val) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(val);
const fmtCompact = (val) => new Intl.NumberFormat('pt-BR', { notation: "compact", compactDisplay: "short", maximumFractionDigits: 1 }).format(val);

// VARIÁVEIS PHP PARA JS
const statusFiltro = "<?php echo $filtro_status; ?>";

Chart.register(ChartDataLabels);
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.color = '#555';

// 1. FLUXO MENSAL
// Lógica de visualização: LADO A LADO (sem stack)
new Chart(document.getElementById('chartFluxo'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_mes_lbl; ?>,
        datasets: [
            {
                label: 'Recebido',
                data: <?php echo $json_mes_rec; ?>,
                backgroundColor: '#198754', // Verde
                borderRadius: 4,
                hidden: (statusFiltro === 'vencido' || statusFiltro === 'aberto'), // Esconde se filtrar só pendente
                order: 1
            },
            {
                label: 'Pendente',
                data: <?php echo $json_mes_pen; ?>,
                backgroundColor: '#6c757d', // Cinza Escuro (Melhor contraste)
                borderRadius: 4,
                hidden: (statusFiltro === 'pago'), // Esconde se filtrar só pago
                order: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: { 
                callbacks: { label: (c) => c.dataset.label + ': ' + fmtBRL(c.raw) },
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 10
            },
            datalabels: {
                display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                formatter: (val) => fmtCompact(val),
                font: { weight: 'bold' },
                anchor: 'end',
                align: 'top',
                offset: -4
            },
            legend: { position: 'top', align: 'end' }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { borderDash: [5, 5] } }
        }
    }
});

// 2. EMPRESA
new Chart(document.getElementById('chartEmpresa'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_emp_lbl; ?>,
        datasets: [
            { 
                label: 'Recebido', 
                data: <?php echo $json_emp_rec; ?>, 
                backgroundColor: '#198754', 
                barPercentage: 0.7,
                hidden: (statusFiltro === 'vencido' || statusFiltro === 'aberto') 
            },
            { 
                label: 'Futuro', 
                data: <?php echo $json_emp_fut; ?>, 
                backgroundColor: '#0d6efd', 
                barPercentage: 0.7,
                hidden: (statusFiltro === 'pago' || statusFiltro === 'vencido') 
            },
            { 
                label: 'Vencido', 
                data: <?php echo $json_emp_ven; ?>, 
                backgroundColor: '#dc3545', 
                barPercentage: 0.7,
                hidden: (statusFiltro === 'pago' || statusFiltro === 'aberto') 
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + fmtBRL(c.raw) } },
            datalabels: {
                anchor: 'end', align: 'end',
                formatter: (val) => val > 0 ? fmtCompact(val) : '',
                font: { size: 11, weight: 'bold' }
            }
        },
        scales: { x: { grid: { borderDash: [2, 2] } } }
    }
});
</script>

<style>
@media print {
    .btn, form, a { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    canvas { max-width: 100% !important; height: auto !important; page-break-inside: avoid; }
    body { background-color: white !important; }
}
</style>