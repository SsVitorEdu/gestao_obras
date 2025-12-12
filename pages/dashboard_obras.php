<?php
// DASHBOARD OBRAS (COM PERCENTUAL NA FORMA DE PAGAMENTO)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); 

// --- 1. FILTROS ---
$where = "WHERE 1=1";
$params = [];

$dt_ini = $_GET['dt_ini'] ?? date('Y-01-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-12-31');

if (!empty($dt_ini)) { $where .= " AND p.data_pedido >= ?"; $params[] = $dt_ini; }
if (!empty($dt_fim)) { $where .= " AND p.data_pedido <= ?"; $params[] = $dt_fim; }

$filtro_emp = $_GET['filtro_emp'] ?? '';
if (!empty($filtro_emp)) { $where .= " AND p.empresa_id = ?"; $params[] = $filtro_emp; }

$filtro_obra = $_GET['filtro_obra'] ?? '';
if (!empty($filtro_obra)) { $where .= " AND p.obra_id = ?"; $params[] = $filtro_obra; }

$filtro_forn = $_GET['filtro_forn'] ?? '';
if (!empty($filtro_forn)) { $where .= " AND p.fornecedor_id = ?"; $params[] = $filtro_forn; }

$filtro_pag = $_GET['filtro_pag'] ?? '';
if (!empty($filtro_pag)) { $where .= " AND p.forma_pagamento = ?"; $params[] = $filtro_pag; }


// --- 2. CONSULTAS ---
// G1: MENSAL
$sql_mes = "SELECT DATE_FORMAT(p.data_pedido, '%m/%Y') as label, SUM(p.valor_bruto_pedido) as total
            FROM pedidos p JOIN obras o ON p.obra_id = o.id $where
            GROUP BY YEAR(p.data_pedido), MONTH(p.data_pedido) ORDER BY p.data_pedido ASC"; 
$stmt = $pdo->prepare($sql_mes); $stmt->execute($params); $dados_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// G2: OBRA
$sql_obra = "SELECT o.nome, SUM(p.valor_bruto_pedido) as total
             FROM pedidos p JOIN obras o ON p.obra_id = o.id $where
             GROUP BY o.id ORDER BY total DESC";
$stmt = $pdo->prepare($sql_obra); $stmt->execute($params); $dados_obra = $stmt->fetchAll(PDO::FETCH_ASSOC);

// G3: EMPREENDIMENTO
$sql_emp = "SELECT e.nome, SUM(p.valor_bruto_pedido) as total
            FROM pedidos p JOIN empresas e ON p.empresa_id = e.id $where
            GROUP BY e.id ORDER BY total DESC";
$stmt = $pdo->prepare($sql_emp); $stmt->execute($params); $dados_emp = $stmt->fetchAll(PDO::FETCH_ASSOC);

// G4: VOLUME
$sql_vol = "SELECT o.nome, COUNT(DISTINCT p.numero_of) as total
            FROM pedidos p JOIN obras o ON p.obra_id = o.id $where
            GROUP BY o.id ORDER BY total DESC";
$stmt = $pdo->prepare($sql_vol); $stmt->execute($params); $dados_vol = $stmt->fetchAll(PDO::FETCH_ASSOC);

// G5: PAGAMENTO
$sql_pagto = "SELECT p.forma_pagamento as nome, SUM(p.valor_bruto_pedido) as total
              FROM pedidos p $where AND p.forma_pagamento != ''
              GROUP BY p.forma_pagamento ORDER BY total DESC";
$stmt = $pdo->prepare($sql_pagto); $stmt->execute($params); $dados_pagto = $stmt->fetchAll(PDO::FETCH_ASSOC);


// LISTAS
$obras_list = $pdo->query("SELECT id, nome FROM obras ORDER BY nome")->fetchAll();
$emp_list   = $pdo->query("SELECT id, nome FROM empresas ORDER BY nome")->fetchAll();
$forn_list  = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$pag_list   = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();

// JSON
$json_mes_lbl = json_encode(array_column($dados_mes, 'label'));
$json_mes_val = json_encode(array_column($dados_mes, 'total'));

$json_obra_lbl = json_encode(array_column($dados_obra, 'nome'));
$json_obra_val = json_encode(array_column($dados_obra, 'total'));

$json_emp_lbl = json_encode(array_column($dados_emp, 'nome'));
$json_emp_val = json_encode(array_column($dados_emp, 'total'));

$json_vol_lbl = json_encode(array_column($dados_vol, 'nome'));
$json_vol_val = json_encode(array_column($dados_vol, 'total'));

$json_pag_lbl = json_encode(array_column($dados_pagto, 'nome'));
$json_pag_val = json_encode(array_column($dados_pagto, 'total'));
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="container-fluid p-4 bg-light">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-dark fw-bold m-0"><i class="bi bi-bar-chart-line-fill text-primary"></i> INDICADORES DE OBRAS</h3>
            <span class="text-muted small">Análise completa de custos e volume</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-danger fw-bold me-2"><i class="bi bi-file-pdf"></i> PDF / IMPRIMIR</button>
            <a href="index.php?page=obras" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-white py-3">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="dashboard_obras">
                <div class="col-md-2"><label class="fw-bold small text-muted">Início</label><input type="date" name="dt_ini" class="form-control" value="<?php echo $dt_ini; ?>"></div>
                <div class="col-md-2"><label class="fw-bold small text-muted">Fim</label><input type="date" name="dt_fim" class="form-control" value="<?php echo $dt_fim; ?>"></div>
                <div class="col-md-4"><label class="fw-bold small text-muted">Obra</label><select name="filtro_obra" class="form-select"><option value="">-- Todas --</option><?php foreach($obras_list as $o): ?><option value="<?php echo $o['id']; ?>" <?php echo ($filtro_obra==$o['id'])?'selected':''; ?>><?php echo $o['nome']; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="fw-bold small text-muted">Empreendimento</label><select name="filtro_emp" class="form-select"><option value="">-- Todas --</option><?php foreach($emp_list as $e): ?><option value="<?php echo $e['id']; ?>" <?php echo ($filtro_emp==$e['id'])?'selected':''; ?>><?php echo substr($e['nome'],0,40); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="fw-bold small text-muted">Fornecedor</label><select name="filtro_forn" class="form-select"><option value="">-- Todas --</option><?php foreach($forn_list as $f): ?><option value="<?php echo $f['id']; ?>" <?php echo ($filtro_forn==$f['id'])?'selected':''; ?>><?php echo substr($f['nome'],0,25); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="fw-bold small text-muted">Pagamento</label><select name="filtro_pag" class="form-select"><option value="">-- Todas --</option><?php foreach($pag_list as $p): ?><option value="<?php echo $p['forma_pagamento']; ?>" <?php echo ($filtro_pag==$p['forma_pagamento'])?'selected':''; ?>><?php echo $p['forma_pagamento']; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><button class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel-fill"></i> ATUALIZAR GRÁFICOS</button></div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4 border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-primary text-center">COMPARATIVO DE GASTO MENSAL</h5>
            <div class="d-flex gap-1">
                <button class="btn btn-light btn-sm border" onclick="expandirGrafico('chartMes', 'EVOLUÇÃO MENSAL')" title="Expandir"><i class="bi bi-arrows-fullscreen"></i></button>
                <button class="btn btn-light btn-sm border" onclick="$('#bodyMes').slideToggle()" title="Ocultar"><i class="bi bi-eye-slash"></i></button>
            </div>
        </div>
        <div class="card-body" id="bodyMes">
            <div style="height: 400px;"><canvas id="chartMes"></canvas></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark">TOTAL POR OBRA (RANKING)</h5>
                    <div class="d-flex gap-1">
                        <button class="btn btn-light btn-sm border" onclick="expandirGrafico('chartObra', 'TOTAL POR OBRA')" title="Expandir"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button class="btn btn-light btn-sm border" onclick="$('#bodyObra').slideToggle()" title="Ocultar"><i class="bi bi-eye-slash"></i></button>
                    </div>
                </div>
                <div class="card-body" id="bodyObra">
                    <div style="height: <?php echo max(400, count($dados_obra) * 35 + 80); ?>px;"><canvas id="chartObra"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">POR EMPREENDIMENTO</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-light btn-sm border" onclick="expandirGrafico('chartEmp', 'POR EMPREENDIMENTO')"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button class="btn btn-light btn-sm border" onclick="$('#bodyEmp').slideToggle()"><i class="bi bi-eye-slash"></i></button>
                    </div>
                </div>
                <div class="card-body" id="bodyEmp"><div style="height: 350px;"><canvas id="chartEmp"></canvas></div></div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">VOLUME PEDIDOS (OFs)</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-light btn-sm border" onclick="expandirGrafico('chartVol', 'VOLUME DE PEDIDOS')"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button class="btn btn-light btn-sm border" onclick="$('#bodyVol').slideToggle()"><i class="bi bi-eye-slash"></i></button>
                    </div>
                </div>
                <div class="card-body" id="bodyVol">
                    <div style="height: <?php echo max(350, count($dados_vol) * 25 + 50); ?>px;"><canvas id="chartVol"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">FORMA DE PAGAMENTO</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-light btn-sm border" onclick="expandirGrafico('chartPag', 'FORMA DE PAGAMENTO')"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button class="btn btn-light btn-sm border" onclick="$('#bodyPag').slideToggle()"><i class="bi bi-eye-slash"></i></button>
                    </div>
                </div>
                <div class="card-body" id="bodyPag"><div style="height: 350px;"><canvas id="chartPag"></canvas></div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGrafico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title fw-bold" id="modalLabel">Visualização Expandida</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white" style="height: 80vh;">
                <canvas id="modalCanvas"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// CONFIGURAÇÕES GERAIS
Chart.register(ChartDataLabels); 
const fmtBRL = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(value);
const fmtCompact = (val) => new Intl.NumberFormat('pt-BR', { notation: "compact", compactDisplay: "short" }).format(val);

// OBJETO PARA GUARDAR OS GRÁFICOS
const charts = {};

// --- 1. MENSAL ---
charts['chartMes'] = new Chart(document.getElementById('chartMes'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_mes_lbl; ?>,
        datasets: [{ label: 'Total Gasto', data: <?php echo $json_mes_val; ?>, backgroundColor: '#082c79ff', borderRadius: 4, barPercentage: 0.6 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (c) => fmtBRL(c.raw) } },
            datalabels: { anchor: 'end', align: 'top', formatter: (val) => fmtCompact(val), color: '#333', font: { weight: 'bold' } }
        },
        scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } }
    }
});

// --- 2. OBRA ---
charts['chartObra'] = new Chart(document.getElementById('chartObra'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_obra_lbl; ?>,
        datasets: [{ label: 'Total Gasto', data: <?php echo $json_obra_val; ?>, backgroundColor: '#003585ff', barPercentage: 0.7 }]
    },
    options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            datalabels: { anchor: 'end', align: 'end', formatter: (val) => fmtCompact(val), color: '#333' }
        },
        scales: { x: { grid: { display: false } } }
    }
});

// --- 3. EMPREENDIMENTO ---
charts['chartEmp'] = new Chart(document.getElementById('chartEmp'), {
    type: 'doughnut',
    data: { labels: <?php echo $json_emp_lbl; ?>, datasets: [{ data: <?php echo $json_emp_val; ?>, backgroundColor: ['#198754', '#ffc107', '#0dcaf0', '#dc3545', '#6610f2'] }] },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, datalabels: { color: '#fff', font: { weight: 'bold' }, formatter: (val, ctx) => ((val * 100 / ctx.chart._metasets[ctx.datasetIndex].total).toFixed(0) + "%") } } }
});

// --- 4. VOLUME ---
charts['chartVol'] = new Chart(document.getElementById('chartVol'), {
    type: 'bar',
    data: { labels: <?php echo $json_vol_lbl; ?>, datasets: [{ label: 'Qtd OFs', data: <?php echo $json_vol_val; ?>, backgroundColor: '#ffca2c', barPercentage: 0.6 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'end', color: '#333', font: { weight: 'bold' } } }, scales: { x: { grid: { display: false } } } }
});

// --- 5. PAGAMENTO (ATUALIZADO: % VISUAL + R$ NO HOVER) ---
// --- 5. PAGAMENTO (CORRIGIDO) ---
charts['chartPag'] = new Chart(document.getElementById('chartPag'), {
    type: 'pie',
    data: { 
        labels: <?php echo $json_pag_lbl; ?>, 
        datasets: [{ 
            data: <?php echo $json_pag_val; ?>, 
            backgroundColor: ['#6f42c1', '#20c997', '#fd7e14', '#0d6efd', '#dc3545', '#ffc107'] 
        }] 
    },
    options: { 
        maintainAspectRatio: false, 
        plugins: { 
            legend: { position: 'bottom' }, 
            
            // TOOLTIP: Mostra o valor em Dinheiro (R$) ao passar o mouse
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) { label += ': '; }
                        label += fmtBRL(context.raw); 
                        return label;
                    }
                }
            },

            // DATALABELS: Calcula a Porcentagem (%) e exibe na fatia
            datalabels: { 
                display: true,
                color: '#fff', 
                font: { weight: 'bold', size: 12 },
                formatter: (value, ctx) => {
                    let sum = 0;
                    let dataArr = ctx.chart.data.datasets[0].data;
                    
                    // CORREÇÃO DO BUG: Força converter para Numero antes de somar
                    dataArr.forEach(data => { 
                        sum += Number(data); 
                    });

                    if (sum === 0) return "0%"; // Evita divisão por zero

                    let percentage = (value * 100 / sum).toFixed(1) + "%";
                    
                    // Só mostra se for maior que 3% para não encavalar texto
                    return (value * 100 / sum) > 3 ? percentage : ''; 
                }
            } 
        } 
    }
});

// --- FUNÇÃO MÁGICA: EXPANDIR GRÁFICO (POP-UP) ---
let modalChartInstance = null;

function expandirGrafico(chartId, titulo) {
    const sourceChart = charts[chartId];
    if (!sourceChart) { alert('Gráfico ainda carregando ou não encontrado. Tente novamente.'); return; }
    
    document.getElementById('modalLabel').innerText = titulo;
    const modalCanvas = document.getElementById('modalCanvas');
    
    if (modalChartInstance) { modalChartInstance.destroy(); }

    modalChartInstance = new Chart(modalCanvas, {
        type: sourceChart.config.type,
        data: sourceChart.config.data, 
        options: {
            ...sourceChart.config.options, 
            maintainAspectRatio: false, 
            plugins: {
                ...sourceChart.config.options.plugins,
                legend: { display: true, position: 'top' } 
            }
        }
    });

    new bootstrap.Modal(document.getElementById('modalGrafico')).show();
}
</script>

<style>
@media print { .btn, form, a { display: none !important; } canvas { max-width: 100% !important; height: auto !important; } .card { border: none !important; shadow: none !important; } }
</style>