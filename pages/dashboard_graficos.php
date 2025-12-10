<?php
// DASHBOARD BI (COM DATA DE CONTRATO INCLUSA NOS FILTROS)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); 

// --- 1. INICIALIZAÇÃO DOS FILTROS ---
$where_pedidos = "WHERE 1=1";
$params_pedidos = [];

$where_contratos = "WHERE 1=1";
$params_contratos = [];

// A. FILTRO DATA
$dt_ini = $_GET['dt_ini'] ?? date('Y-m-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-m-t');

if (!empty($dt_ini)) { 
    $where_pedidos .= " AND p.data_pedido >= ?"; 
    $params_pedidos[] = $dt_ini; 
    
    // Agora afeta contratos também!
    $where_contratos .= " AND data_contrato >= ?";
    $params_contratos[] = $dt_ini;
}
if (!empty($dt_fim)) { 
    $where_pedidos .= " AND p.data_pedido <= ?"; 
    $params_pedidos[] = $dt_fim; 
    
    // Agora afeta contratos também!
    $where_contratos .= " AND data_contrato <= ?";
    $params_contratos[] = $dt_fim;
}

// B. FILTRO OBRA
$filtro_obra = $_GET['filtro_obra'] ?? '';
if (!empty($filtro_obra)) { 
    $where_pedidos .= " AND p.obra_id = ?"; 
    $params_pedidos[] = $filtro_obra; 
    
    // Para contratos: Pega fornecedores que trabalharam nessa obra
    $where_contratos .= " AND fornecedor_id IN (SELECT DISTINCT fornecedor_id FROM pedidos WHERE obra_id = ?)";
    $params_contratos[] = $filtro_obra;
}

// C. FILTRO FORNECEDOR
$filtro_forn = $_GET['filtro_forn'] ?? '';
if (!empty($filtro_forn)) { 
    $where_pedidos .= " AND p.fornecedor_id = ?"; 
    $params_pedidos[] = $filtro_forn; 
    
    $where_contratos .= " AND fornecedor_id = ?";
    $params_contratos[] = $filtro_forn;
}

// D. FILTRO PAGAMENTO (Só afeta pedidos, contratos não tem forma de pgto no cadastro simples)
$filtro_pag = $_GET['filtro_pag'] ?? '';
if (!empty($filtro_pag)) { 
    $where_pedidos .= " AND p.forma_pagamento = ?"; 
    $params_pedidos[] = $filtro_pag; 
}


// --- 2. QUERY 1: FORNECEDORES (VLR BRUTO) ---
$sql_forn = "SELECT f.nome, SUM(p.valor_bruto_pedido) as total
             FROM pedidos p
             JOIN fornecedores f ON p.fornecedor_id = f.id
             $where_pedidos
             GROUP BY f.id 
             ORDER BY total DESC"; 
$stmt = $pdo->prepare($sql_forn);
$stmt->execute($params_pedidos);
$dados_forn = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 3. QUERY 2: FORMA DE PAGAMENTO (%) ---
$sql_pag = "SELECT forma_pagamento, SUM(valor_bruto_pedido) as total
            FROM pedidos p
            $where_pedidos AND p.forma_pagamento != ''
            GROUP BY forma_pagamento 
            ORDER BY total DESC";
$stmt = $pdo->prepare($sql_pag);
$stmt->execute($params_pedidos);
$dados_pag = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 4. QUERY 3: CONTRATOS POR RESPONSÁVEL ---
// Agora obedece Data, Obra e Fornecedor!
$sql_resp = "SELECT responsavel, SUM(valor) as total
             FROM contratos
             $where_contratos
             GROUP BY responsavel
             ORDER BY total DESC";
$stmt = $pdo->prepare($sql_resp);
$stmt->execute($params_contratos);
$dados_resp = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Listas para os Selects
$obras_list = $pdo->query("SELECT id, nome FROM obras ORDER BY nome")->fetchAll();
$forn_list = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$pag_list = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();

// --- PREPARA JSON ---
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
        <h3 class="text-dark fw-bold"><i class="bi bi-graph-up-arrow text-primary"></i> DASHBOARD DE ANÁLISE</h3>
        <a href="index.php?page=fornecedores" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
    </div>

    <div class="card shadow-sm mb-5 border-primary">
        <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between">
            <span><i class="bi bi-funnel"></i> FILTROS GERAIS</span>
            <span class="badge bg-white text-primary">Aplica para Pedidos E Contratos</span>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="dashboard_graficos">
                
                <div class="col-md-2"><label class="fw-bold small">Início</label><input type="date" name="dt_ini" class="form-control" value="<?php echo $dt_ini; ?>"></div>
                <div class="col-md-2"><label class="fw-bold small">Fim</label><input type="date" name="dt_fim" class="form-control" value="<?php echo $dt_fim; ?>"></div>
                
                <div class="col-md-3">
                    <label class="fw-bold small">Obra</label>
                    <select name="filtro_obra" class="form-select">
                        <option value="">-- Todas --</option>
                        <?php foreach($obras_list as $o): ?><option value="<?php echo $o['id']; ?>" <?php echo ($filtro_obra==$o['id'])?'selected':''; ?>><?php echo substr($o['nome'],0,30); ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="fw-bold small">Pagamento</label>
                    <select name="filtro_pag" class="form-select">
                        <option value="">-- Todos --</option>
                        <?php foreach($pag_list as $p): ?><option value="<?php echo $p['forma_pagamento']; ?>" <?php echo ($filtro_pag==$p['forma_pagamento'])?'selected':''; ?>><?php echo $p['forma_pagamento']; ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="fw-bold small">Fornecedor</label>
                    <select name="filtro_forn" class="form-select">
                        <option value="">-- Todos --</option>
                        <?php foreach($forn_list as $f): ?><option value="<?php echo $f['id']; ?>" <?php echo ($filtro_forn==$f['id'])?'selected':''; ?>><?php echo substr($f['nome'],0,20); ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1"><button class="btn btn-dark w-100"><i class="bi bi-search"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-success"><i class="bi bi-truck"></i> GASTO BRUTO POR FORNECEDOR</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyForn').slideToggle()"><i class="bi bi-eye"></i> Ocultar/Mostrar</button>
        </div>
        <div class="card-body" id="bodyForn">
            <div style="height: <?php echo count($dados_forn) * 30 + 100; ?>px; min-height: 400px;"> <canvas id="chartForn"></canvas>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-primary"><i class="bi bi-wallet2"></i> GASTO POR FORMA DE PAGAMENTO (%)</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyPag').slideToggle()"><i class="bi bi-eye"></i> Ocultar/Mostrar</button>
        </div>
        <div class="card-body" id="bodyPag">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <div style="height: 400px;">
                        <canvas id="chartPag"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-warning"><i class="bi bi-file-earmark-person"></i> CONTRATOS POR RESPONSÁVEL</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyResp').slideToggle()"><i class="bi bi-eye"></i> Ocultar/Mostrar</button>
        </div>
        <div class="card-body" id="bodyResp">
            <div style="height: 400px;">
                <canvas id="chartResp"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
// --- CONFIGURAÇÕES GERAIS ---
Chart.register(ChartDataLabels); 
const formataBRL = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);

// --- 1. CHART FORNECEDORES ---
new Chart(document.getElementById('chartForn'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_forn_lbl; ?>,
        datasets: [{
            label: 'Total Bruto (R$)',
            data: <?php echo $json_forn_val; ?>,
            backgroundColor: '#198754',
            borderColor: '#146c43',
            borderWidth: 1,
            barThickness: 20
        }]
    },
    options: {
        indexAxis: 'y', 
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => formataBRL(ctx.raw) } },
            datalabels: {
                anchor: 'end', align: 'end',
                formatter: (val) => formataBRL(val),
                color: '#333', font: { weight: 'bold' }
            }
        },
        scales: { x: { beginAtZero: true } }
    }
});

// --- 2. CHART PAGAMENTO ---
new Chart(document.getElementById('chartPag'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $json_pag_lbl; ?>,
        datasets: [{
            data: <?php echo $json_pag_val; ?>,
            backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => ctx.label + ': ' + formataBRL(ctx.raw) } },
            datalabels: {
                color: '#fff', font: { weight: 'bold' },
                formatter: (value, ctx) => {
                    let sum = ctx.chart._metasets[ctx.datasetIndex].total;
                    let percentage = (value * 100 / sum).toFixed(1) + "%";
                    return percentage; 
                }
            },
            legend: { position: 'right' }
        }
    }
});

// --- 3. CHART RESPONSÁVEL ---
new Chart(document.getElementById('chartResp'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_resp_lbl; ?>,
        datasets: [{
            label: 'Total em Contratos',
            data: <?php echo $json_resp_val; ?>,
            backgroundColor: '#ffc107',
            borderColor: '#e0a800',
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => formataBRL(ctx.raw) } },
            datalabels: {
                anchor: 'end', align: 'top',
                formatter: (val) => formataBRL(val),
                color: '#555'
            }
        }
    }
});
</script>