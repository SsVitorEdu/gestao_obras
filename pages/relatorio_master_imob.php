<?php
// RELATÓRIO MESTRE IMOBILIÁRIO (FINANCEIRO GERAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

// --- 1. FILTROS ---
$where = "WHERE 1=1";
$params = [];

// Data (Padrão: Mês atual)
$dt_tipo = $_GET['dt_tipo'] ?? 'vencimento'; // vencimento ou pagamento
$dt_ini = $_GET['dt_ini'] ?? date('Y-m-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-m-t');

if (!empty($dt_ini)) { 
    $coluna_data = ($dt_tipo == 'pagamento') ? 'p.data_pagamento' : 'p.data_vencimento';
    $where .= " AND $coluna_data >= ?"; 
    $params[] = $dt_ini; 
}
if (!empty($dt_fim)) { 
    $coluna_data = ($dt_tipo == 'pagamento') ? 'p.data_pagamento' : 'p.data_vencimento';
    $where .= " AND $coluna_data <= ?"; 
    $params[] = $dt_fim; 
}

// Filtro por Empresa (Empreendimento)
$filtro_empresa = $_GET['filtro_empresa'] ?? '';
if (!empty($filtro_empresa)) {
    $where .= " AND v.nome_empresa LIKE ?";
    $params[] = "%$filtro_empresa%";
}

// Filtro por Status
$filtro_status = $_GET['filtro_status'] ?? '';
// A lógica do status é feita no PHP ou via SQL complexo. Vamos filtrar no PHP para ser mais flexível visualmente, 
// mas para performance em grandes bancos, o ideal seria no SQL.

// --- 2. CONSULTA GERAL ---
$sql = "SELECT 
            p.*,
            v.codigo_compra,
            v.nome_casa,
            v.nome_empresa,
            c.nome as nome_cliente,
            c.cpf
        FROM parcelas_imob p
        JOIN vendas_imob v ON p.venda_id = v.id
        JOIN clientes_imob c ON v.cliente_id = c.id
        $where
        ORDER BY p.data_vencimento ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 3. CÁLCULOS DOS KPI's (INDICADORES) ---
$total_recebido = 0;
$total_a_receber = 0;
$total_atrasado = 0;
$hoje = date('Y-m-d');

foreach ($dados as $d) {
    $vlr_orig = $d['valor_parcela'];
    $vlr_pago = $d['valor_pago'];
    $venc = $d['data_vencimento'];
    
    // Soma Recebido
    $total_recebido += $vlr_pago;

    // Lógica Atrasado vs A Receber
    if ($vlr_pago < $vlr_orig) {
        $saldo = $vlr_orig - $vlr_pago;
        
        if ($venc < $hoje) {
            $total_atrasado += $saldo;
        } else {
            $total_a_receber += $saldo;
        }
    }
}

$total_geral = $total_recebido + $total_a_receber + $total_atrasado;
$perc_recebido = ($total_geral > 0) ? ($total_recebido / $total_geral) * 100 : 0;

// Listas para filtros
$lista_empresas = $pdo->query("SELECT DISTINCT nome_empresa FROM vendas_imob ORDER BY nome_empresa")->fetchAll();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<style>
    .kpi-card { border-left: 5px solid #ccc; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-icon { font-size: 2rem; opacity: 0.3; position: absolute; right: 15px; top: 15px; }
    
    .janela-rolagem { width: 98%; height: 75vh; overflow: auto; background: #f4f4f4; border: 1px solid #ccc; margin: 0 auto; }
    .mesa-gigante { width: 100%; min-width: 1400px; background-color: white; }
    
    /* Status Visual */
    .st-pago { color: #198754; font-weight: bold; background-color: #d1e7dd; }
    .st-atrasado { color: #dc3545; font-weight: bold; background-color: #f8d7da; }
    .st-aberto { color: #0d6efd; font-weight: bold; background-color: #cfe2ff; }
    .st-parcial { color: #fd7e14; font-weight: bold; background-color: #ffe5d0; }
</style>

<div class="container-fluid p-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="bi bi-graph-up-arrow text-primary"></i> RELATÓRIO MESTRE IMOBILIÁRIO</h3>
            <small class="text-muted">Visão completa de recebíveis, inadimplência e fluxo de caixa.</small>
        </div>
        <div>
            <a href="index.php?page=clientes" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card" style="border-color: #198754;">
                <div class="card-body">
                    <i class="bi bi-cash-stack kpi-icon text-success"></i>
                    <small class="text-uppercase fw-bold text-success">Total Recebido</small>
                    <h3 class="mb-0 fw-bold text-dark">R$ <?php echo number_format($total_recebido, 2, ',', '.'); ?></h3>
                    <small class="text-muted"><?php echo number_format($perc_recebido, 1); ?>% do total filtrado</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card" style="border-color: #0d6efd;">
                <div class="card-body">
                    <i class="bi bi-calendar-check kpi-icon text-primary"></i>
                    <small class="text-uppercase fw-bold text-primary">A Receber (No Prazo)</small>
                    <h3 class="mb-0 fw-bold text-dark">R$ <?php echo number_format($total_a_receber, 2, ',', '.'); ?></h3>
                    <small class="text-muted">Fluxo futuro previsto</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card" style="border-color: #dc3545;">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle-fill kpi-icon text-danger"></i>
                    <small class="text-uppercase fw-bold text-danger">Inadimplência (Atrasados)</small>
                    <h3 class="mb-0 fw-bold text-dark">R$ <?php echo number_format($total_atrasado, 2, ',', '.'); ?></h3>
                    <small class="text-danger fw-bold">Atenção requerida</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm kpi-card" style="border-color: #666;">
                <div class="card-body">
                    <i class="bi bi-bank kpi-icon text-secondary"></i>
                    <small class="text-uppercase fw-bold text-secondary">Total Geral (Filtrado)</small>
                    <h3 class="mb-0 fw-bold text-dark">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></h3>
                    <small class="text-muted">Volume total movimentado</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3 border-0 bg-light">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="relatorio_master_imob">
                
                <div class="col-md-2">
                    <label class="small fw-bold">Filtrar Data Por:</label>
                    <select name="dt_tipo" class="form-select form-select-sm">
                        <option value="vencimento" <?php echo ($dt_tipo=='vencimento')?'selected':''; ?>>📅 Vencimento</option>
                        <option value="pagamento" <?php echo ($dt_tipo=='pagamento')?'selected':''; ?>>💰 Pagamento (Baixa)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Início</label>
                    <input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $dt_ini; ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Fim</label>
                    <input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $dt_fim; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="small fw-bold">Empreendimento / Empresa</label>
                    <select name="filtro_empresa" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach($lista_empresas as $e): ?>
                            <option value="<?php echo $e['nome_empresa']; ?>" <?php echo ($filtro_empresa == $e['nome_empresa'])?'selected':''; ?>>
                                <?php echo $e['nome_empresa']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold">Status</label>
                    <select name="filtro_status" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <option value="atrasado" <?php echo ($filtro_status=='atrasado')?'selected':''; ?>>🔴 Atrasados</option>
                        <option value="pago" <?php echo ($filtro_status=='pago')?'selected':''; ?>>🟢 Pagos</option>
                        <option value="aberto" <?php echo ($filtro_status=='aberto')?'selected':''; ?>>🔵 A Vencer</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <button class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="janela-rolagem">
                <table id="tabelaMaster" class="table table-striped table-hover table-bordered mb-0 mesa-gigante">
                    <thead class="table-dark text-center sticky-top">
                        <tr>
                            <th>STATUS</th>
                            <th>VENCIMENTO</th>
                            <th>CLIENTE</th>
                            <th>EMPREENDIMENTO</th>
                            <th>PARCELA</th>
                            <th>VALOR ORIGINAL</th>
                            <th>VALOR PAGO</th>
                            <th>SALDO</th>
                            <th>DT PAGTO</th>
                            <th>CÓDIGO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dados as $row): 
                            // Lógica de Status Visual
                            $classe = ""; $status_txt = "";
                            $saldo = $row['valor_parcela'] - $row['valor_pago'];
                            
                            // Define Status
                            if ($row['valor_pago'] >= ($row['valor_parcela'] - 0.1)) {
                                $classe = "st-pago"; $status_txt = "PAGO";
                            } elseif ($row['valor_pago'] > 0) {
                                $classe = "st-parcial"; $status_txt = "PARCIAL";
                            } elseif ($row['data_vencimento'] < $hoje) {
                                $classe = "st-atrasado"; $status_txt = "ATRASADO";
                            } else {
                                $classe = "st-aberto"; $status_txt = "A VENCER";
                            }

                            // Aplica filtro de status PHP (se selecionado)
                            if (!empty($filtro_status)) {
                                if ($filtro_status == 'atrasado' && $status_txt != 'ATRASADO') continue;
                                if ($filtro_status == 'pago' && $status_txt != 'PAGO') continue;
                                if ($filtro_status == 'aberto' && $status_txt != 'A VENCER') continue;
                            }
                        ?>
                        <tr>
                            <td class="text-center small"><span class="badge <?php echo $classe; ?> w-100"><?php echo $status_txt; ?></span></td>
                            <td class="text-center fw-bold"><?php echo date('d/m/Y', strtotime($row['data_vencimento'])); ?></td>
                            <td><?php echo mb_strimwidth($row['nome_cliente'], 0, 30, "..."); ?></td>
                            <td><?php echo mb_strimwidth($row['nome_empresa'], 0, 30, "..."); ?></td>
                            <td class="text-center"><?php echo $row['numero_parcela']; ?></td>
                            
                            <td class="text-end fw-bold text-secondary">R$ <?php echo number_format($row['valor_parcela'], 2, ',', '.'); ?></td>
                            <td class="text-end fw-bold text-success">R$ <?php echo number_format($row['valor_pago'], 2, ',', '.'); ?></td>
                            <td class="text-end fw-bold text-danger">R$ <?php echo number_format($saldo > 0 ? $saldo : 0, 2, ',', '.'); ?></td>
                            
                            <td class="text-center small text-muted"><?php echo $row['data_pagamento'] ? date('d/m/Y', strtotime($row['data_pagamento'])) : '-'; ?></td>
                            <td class="text-center small"><?php echo $row['codigo_compra']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#tabelaMaster').DataTable({
        paging: true,
        pageLength: 100, // Mostra bastante linha
        ordering: false, // Deixa a ordem do SQL (Data)
        dom: 'Bfrtip',
        buttons: [
            { 
                extend: 'excel', 
                text: '<i class="bi bi-file-earmark-excel"></i> Excel', 
                className: 'btn btn-success btn-sm me-1',
                title: 'Relatório Financeiro Imobiliário'
            },
            { 
                extend: 'pdfHtml5', 
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF', 
                className: 'btn btn-danger btn-sm me-1',
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'Relatório Financeiro Imobiliário'
            },
            { 
                extend: 'print', 
                text: '<i class="bi bi-printer"></i> Imprimir', 
                className: 'btn btn-secondary btn-sm'
            }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
    });
});
</script>