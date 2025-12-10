<?php
// DASHBOARD OBRAS (TRÊS EIXOS: OBRA, EMPREENDIMENTO, OFs)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

// --- 1. FILTROS ---
$where = "WHERE 1=1";
$params = [];

// Data
$dt_ini = $_GET['dt_ini'] ?? date('Y-m-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-m-t');
if (!empty($dt_ini)) { $where .= " AND p.data_pedido >= ?"; $params[] = $dt_ini; }
if (!empty($dt_fim)) { $where .= " AND p.data_pedido <= ?"; $params[] = $dt_fim; }

// Obra
$filtro_obra = $_GET['filtro_obra'] ?? '';
if (!empty($filtro_obra)) { $where .= " AND p.obra_id = ?"; $params[] = $filtro_obra; }

// Empreendimento (Empresa)
$filtro_emp = $_GET['filtro_emp'] ?? '';
if (!empty($filtro_emp)) { $where .= " AND p.empresa_id = ?"; $params[] = $filtro_emp; }

// Fornecedor
$filtro_forn = $_GET['filtro_forn'] ?? '';
if (!empty($filtro_forn)) { $where .= " AND p.fornecedor_id = ?"; $params[] = $filtro_forn; }

// Pagamento
$filtro_pag = $_GET['filtro_pag'] ?? '';
if (!empty($filtro_pag)) { $where .= " AND p.forma_pagamento = ?"; $params[] = $filtro_pag; }


// --- 2. DADOS GRÁFICO 1: GASTO POR OBRA (VLR BRUTO) ---
$sql_obra = "SELECT o.nome, SUM(p.valor_bruto_pedido) as total
             FROM pedidos p
             JOIN obras o ON p.obra_id = o.id
             $where
             GROUP BY o.id 
             ORDER BY total DESC";
$stmt = $pdo->prepare($sql_obra);
$stmt->execute($params);
$dados_obra = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 3. DADOS GRÁFICO 2: GASTO POR EMPREENDIMENTO (VLR BRUTO) ---
$sql_emp = "SELECT e.nome, SUM(p.valor_bruto_pedido) as total
            FROM pedidos p
            JOIN empresas e ON p.empresa_id = e.id
            $where
            GROUP BY e.id 
            ORDER BY total DESC";
$stmt = $pdo->prepare($sql_emp);
$stmt->execute($params);
$dados_emp = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 4. DADOS GRÁFICO 3: VOLUME DE PEDIDOS (OFs ÚNICAS POR OBRA) ---
// Conta quantas 'numero_of' diferentes existem para cada obra
$sql_vol = "SELECT o.nome, COUNT(DISTINCT p.numero_of) as total
            FROM pedidos p
            JOIN obras o ON p.obra_id = o.id
            $where
            GROUP BY o.id 
            ORDER BY total DESC";
$stmt = $pdo->prepare($sql_vol);
$stmt->execute($params);
$dados_vol = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Listas para os Selects (Filtros)
$obras_list = $pdo->query("SELECT id, nome FROM obras ORDER BY nome")->fetchAll();
$emp_list   = $pdo->query("SELECT id, nome FROM empresas ORDER BY nome")->fetchAll();
$forn_list  = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$pag_list   = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();

// --- PREPARA JSON ---
$json_obra_lbl = json_encode(array_column($dados_obra, 'nome'));
$json_obra_val = json_encode(array_column($dados_obra, 'total'));

$json_emp_lbl = json_encode(array_column($dados_emp, 'nome'));
$json_emp_val = json_encode(array_column($dados_emp, 'total'));

$json_vol_lbl = json_encode(array_column($dados_vol, 'nome'));
$json_vol_val = json_encode(array_column($dados_vol, 'total'));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="container-fluid p-4 bg-light">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold"><i class="bi bi-bar-chart-steps text-primary"></i> DASHBOARD DE OBRAS</h3>
        <a href="index.php?page=obras" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
    </div>

    <div class="card shadow-sm mb-5 border-primary">
        <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between">
            <span><i class="bi bi-funnel"></i> FILTROS GERAIS</span>
            <span class="badge bg-white text-primary">Filtra todos os gráficos</span>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="dashboard_obras">
                
                <div class="col-md-2"><label class="fw-bold small">Início</label><input type="date" name="dt_ini" class="form-control" value="<?php echo $dt_ini; ?>"></div>
                <div class="col-md-2"><label class="fw-bold small">Fim</label><input type="date" name="dt_fim" class="form-control" value="<?php echo $dt_fim; ?>"></div>
                
                <div class="col-md-3">
                    <label class="fw-bold small">Empreendimento</label>
                    <select name="filtro_emp" class="form-select">
                        <option value="">-- Todos --</option>
                        <?php foreach($emp_list as $e): ?><option value="<?php echo $e['id']; ?>" <?php echo ($filtro_emp==$e['id'])?'selected':''; ?>><?php echo substr($e['nome'],0,30); ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold small">Fornecedor</label>
                    <select name="filtro_forn" class="form-select">
                        <option value="">-- Todos --</option>
                        <?php foreach($forn_list as $f): ?><option value="<?php echo $f['id']; ?>" <?php echo ($filtro_forn==$f['id'])?'selected':''; ?>><?php echo substr($f['nome'],0,30); ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="fw-bold small">Pagamento</label>
                    <select name="filtro_pag" class="form-select">
                        <option value="">-- Todos --</option>
                        <?php foreach($pag_list as $p): ?><option value="<?php echo $p['forma_pagamento']; ?>" <?php echo ($filtro_pag==$p['forma_pagamento'])?'selected':''; ?>><?php echo $p['forma_pagamento']; ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12 mt-2">
                    <div class="input-group">
                        <label class="input-group-text fw-bold bg-light">Filtrar Obra Específica:</label>
                        <select name="filtro_obra" class="form-select">
                            <option value="">-- Todas as Obras --</option>
                            <?php foreach($obras_list as $o): ?><option value="<?php echo $o['id']; ?>" <?php echo ($filtro_obra==$o['id'])?'selected':''; ?>><?php echo $o['nome']; ?></option><?php endforeach; ?>
                        </select>
                        <button class="btn btn-dark fw-bold px-5"><i class="bi bi-search"></i> FILTRAR</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-primary"><i class="bi bi-building"></i> VALOR BRUTO POR OBRA</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyObra').slideToggle()"><i class="bi bi-eye"></i> Ver</button>
        </div>
        <div class="card-body" id="bodyObra">
            <div style="height: <?php echo count($dados_obra) * 30 + 100; ?>px; min-height: 400px;">
                <canvas id="chartObra"></canvas>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-success"><i class="bi bi-bank"></i> VALOR BRUTO POR EMPREENDIMENTO</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyEmp').slideToggle()"><i class="bi bi-eye"></i> Ver</button>
        </div>
        <div class="card-body" id="bodyEmp">
            <div style="height: <?php echo count($dados_emp) * 30 + 100; ?>px; min-height: 400px;">
                <canvas id="chartEmp"></canvas>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-warning"><i class="bi bi-file-earmark-text"></i> TOTAL DE PEDIDOS (OFs) POR OBRA</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#bodyVol').slideToggle()"><i class="bi bi-eye"></i> Ver</button>
        </div>
        <div class="card-body" id="bodyVol">
            <div style="height: <?php echo count($dados_vol) * 30 + 100; ?>px; min-height: 400px;">
                <canvas id="chartVol"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
// --- CONFIGURAÇÕES ---
Chart.register(ChartDataLabels); 
const formataBRL = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);

const configBase = {
    indexAxis: 'y', // Barras Horizontais
    maintainAspectRatio: false,
    responsive: true,
    scales: { x: { beginAtZero: true } }
};

// --- 1. CHART OBRA ---
new Chart(document.getElementById('chartObra'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_obra_lbl; ?>,
        datasets: [{
            label: 'Valor Bruto (R$)',
            data: <?php echo $json_obra_val; ?>,
            backgroundColor: '#0d6efd',
            borderColor: '#0a58ca',
            borderWidth: 1,
            barThickness: 20
        }]
    },
    options: {
        ...configBase,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => formataBRL(ctx.raw) } },
            datalabels: {
                anchor: 'end', align: 'end',
                formatter: (val) => formataBRL(val),
                color: '#333', font: { weight: 'bold' }
            }
        }
    }
});

// --- 2. CHART EMPREENDIMENTO ---
new Chart(document.getElementById('chartEmp'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_emp_lbl; ?>,
        datasets: [{
            label: 'Valor Bruto (R$)',
            data: <?php echo $json_emp_val; ?>,
            backgroundColor: '#198754',
            borderColor: '#146c43',
            borderWidth: 1,
            barThickness: 20
        }]
    },
    options: {
        ...configBase,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => formataBRL(ctx.raw) } },
            datalabels: {
                anchor: 'end', align: 'end',
                formatter: (val) => formataBRL(val),
                color: '#333', font: { weight: 'bold' }
            }
        }
    }
});

// --- 3. CHART VOLUME (QTD) ---
new Chart(document.getElementById('chartVol'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_vol_lbl; ?>,
        datasets: [{
            label: 'Qtd de Pedidos (OFs)',
            data: <?php echo $json_vol_val; ?>,
            backgroundColor: '#ffc107',
            borderColor: '#ffca2c',
            borderWidth: 1,
            barThickness: 20
        }]
    },
    options: {
        ...configBase,
        plugins: {
            datalabels: {
                anchor: 'end', align: 'end',
                color: '#333', font: { weight: 'bold' }
            }
        }
    }
});
</script>