<?php
// LISTA GERAL (LAYOUT PROFISSIONAL: SCROLL NATIVO + COLUNA FIXA)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); 

// --- 1. FILTROS ---
$where_pedidos = "WHERE 1=1"; 
$where_contratos = "WHERE 1=1";
$params_pedidos = [];
$params_contratos = [];

// Filtro Data
if (!empty($_GET['dt_ini'])) { 
    $where_pedidos .= " AND p.data_pedido >= ?"; 
    $params_pedidos[] = $_GET['dt_ini']; 
}
if (!empty($_GET['dt_fim'])) { 
    $where_pedidos .= " AND p.data_pedido <= ?"; 
    $params_pedidos[] = $_GET['dt_fim']; 
}

// Filtro Fornecedor
if (!empty($_GET['filtro_forn'])) { 
    $where_pedidos .= " AND p.fornecedor_id = ?"; 
    $params_pedidos[] = $_GET['filtro_forn'];
    
    $where_contratos .= " AND c.fornecedor_id = ?";
    $params_contratos[] = $_GET['filtro_forn'];
}

// Filtro Obra
if (!empty($_GET['filtro_obra'])) { 
    $where_pedidos .= " AND p.obra_id = ?"; 
    $params_pedidos[] = $_GET['filtro_obra']; 
}

// Filtro Forma de Pagamento
if (!empty($_GET['filtro_pag'])) { 
    $where_pedidos .= " AND p.forma_pagamento = ?"; 
    $params_pedidos[] = $_GET['filtro_pag']; 
}

// Filtro OF
if (!empty($_GET['filtro_of'])) { 
    $where_pedidos .= " AND p.numero_of LIKE ?"; 
    $params_pedidos[] = "%" . $_GET['filtro_of'] . "%"; 
}

// =========================================================================
// CÁLCULOS GLOBAIS
// =========================================================================

// A. SOMA CONTRATOS
$sql_soma_contratos = "SELECT SUM(valor) FROM contratos c $where_contratos";
$stmt = $pdo->prepare($sql_soma_contratos);
$stmt->execute($params_contratos);
$valor_contrato_total = $stmt->fetchColumn() ?: 0;

// B. SOMA PEDIDOS
$sql_soma_pedidos = "SELECT SUM(valor_bruto_pedido) FROM pedidos p $where_pedidos";
$stmt = $pdo->prepare($sql_soma_pedidos);
$stmt->execute($params_pedidos);
$consumo_acumulado = $stmt->fetchColumn() ?: 0;

// C. SALDO
$saldo_geral = $valor_contrato_total - $consumo_acumulado;


// =========================================================================
// BUSCA DE DADOS
// =========================================================================

// Contratos (Lista para o card retrátil)
$sql_lista_contratos = "SELECT c.*, f.nome as nome_fornecedor 
                        FROM contratos c 
                        LEFT JOIN fornecedores f ON c.fornecedor_id = f.id 
                        $where_contratos 
                        ORDER BY c.valor DESC LIMIT 2000";
$stmt = $pdo->prepare($sql_lista_contratos);
$stmt->execute($params_contratos);
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pedidos (Lista Principal)
$sql_lista_pedidos = "SELECT p.*, 
                        o.nome as nome_obra, 
                        o.codigo as cod_obra, 
                        m.nome as material, 
                        c.nome as nome_comprador,
                        f.nome as nome_fornecedor,
                        f.cnpj_cpf
                      FROM pedidos p
                      LEFT JOIN obras o ON p.obra_id = o.id
                      LEFT JOIN materiais m ON p.material_id = m.id
                      LEFT JOIN compradores c ON p.comprador_id = c.id
                      LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                      $where_pedidos 
                      ORDER BY p.data_pedido DESC LIMIT 5000";

$stmt = $pdo->prepare($sql_lista_pedidos);
$stmt->execute($params_pedidos);
$lista_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdowns
$obras_filtro = $pdo->query("SELECT id, nome FROM obras ORDER BY nome")->fetchAll();
$forn_filtro = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll();
$pag_filtro = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.2.2/css/fixedColumns.bootstrap5.min.css">

<style>
    /* Estilo Compacto Profissional */
    .table-xs th, .table-xs td { 
        font-size: 11px; 
        padding: 5px 8px; 
        white-space: nowrap; 
        vertical-align: middle; 
    }
    
    /* Colunas Inteligentes (Texto Longo) */
    .col-longa { 
        white-space: normal !important; 
        min-width: 250px; 
        max-width: 400px; 
        color: #333;
    }
    .col-media {
        white-space: normal !important;
        min-width: 150px;
        max-width: 250px;
    }

    /* Área de Filtros */
    .bg-filtros { background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .label-filtro { font-size: 0.75rem; font-weight: bold; color: #666; margin-bottom: 2px; }
    
    /* Totais */
    .card-total-sm { padding: 10px; border-left: 4px solid #ccc; background: #fff; }
    .card-total-sm h5 { font-size: 1.1rem; margin: 0; font-weight: bold; }
    .card-total-sm small { font-size: 0.7rem; text-transform: uppercase; color: #888; font-weight: bold; }
    
    /* Contratos */
    .card-contratos { border-left: 4px solid #0d6efd; background-color: #f0f7ff; }
</style>

<div class="container-fluid px-3 pt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="text-primary fw-bold m-0"><i class="bi bi-globe-americas"></i> PAINEL GERAL DE PEDIDOS</h4>
            <small class="text-muted">Visão consolidada de todas as obras e fornecedores</small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=central_importacoes" class="btn btn-warning btn-sm shadow-sm fw-bold"><i class="bi bi-cloud-upload"></i> Importar</a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i> Home</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card-total-sm shadow-sm" style="border-color: #6c757d;">
                <small>Total Contratos (Global)</small>
                <h5 class="text-secondary">R$ <?php echo number_format($valor_contrato_total, 2, ',', '.'); ?></h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-total-sm shadow-sm" style="border-color: #0d6efd;">
                <small>Total Consumido (Filtrado)</small>
                <h5 class="text-primary">R$ <?php echo number_format($consumo_acumulado, 2, ',', '.'); ?></h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-total-sm shadow-sm" style="border-color: <?php echo ($saldo_geral < 0) ? '#dc3545' : '#198754'; ?>;">
                <small>Saldo Geral</small>
                <h5 class="<?php echo ($saldo_geral < 0) ? 'text-danger' : 'text-success'; ?>">R$ <?php echo number_format($saldo_geral, 2, ',', '.'); ?></h5>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body bg-filtros py-2 rounded">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="lista_geral">
                
                <div class="col-md-2">
                    <label class="label-filtro">Início</label>
                    <input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $_GET['dt_ini'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="label-filtro">Fim</label>
                    <input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $_GET['dt_fim'] ?? ''; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="label-filtro">Fornecedor</label>
                    <select name="filtro_forn" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach($forn_filtro as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo (isset($_GET['filtro_forn']) && $_GET['filtro_forn'] == $f['id']) ? 'selected' : ''; ?>>
                                <?php echo substr($f['nome'], 0, 20); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="label-filtro">Obra</label>
                    <select name="filtro_obra" class="form-select form-select-sm">
                        <option value="">-- Todas --</option>
                        <?php foreach($obras_filtro as $o): ?>
                            <option value="<?php echo $o['id']; ?>" <?php echo (isset($_GET['filtro_obra']) && $_GET['filtro_obra'] == $o['id']) ? 'selected' : ''; ?>>
                                <?php echo substr($o['nome'], 0, 20); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="label-filtro">Pagamento</label>
                    <select name="filtro_pag" class="form-select form-select-sm">
                        <option value="">-- Todas --</option>
                        <?php foreach($pag_filtro as $pg): ?>
                            <option value="<?php echo $pg['forma_pagamento']; ?>" <?php echo (isset($_GET['filtro_pag']) && $_GET['filtro_pag'] == $pg['forma_pagamento']) ? 'selected' : ''; ?>>
                                <?php echo $pg['forma_pagamento']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="label-filtro">OF</label>
                    <input type="text" name="filtro_of" class="form-control form-control-sm" placeholder="Nº" value="<?php echo $_GET['filtro_of'] ?? ''; ?>">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3 border-0">
        <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center">
            <small class="fw-bold text-primary"><i class="bi bi-file-earmark-text"></i> TODOS OS CONTRATOS</small>
            <button class="btn btn-sm text-primary" type="button" id="btnToggleContratos" style="font-size: 0.8rem;">
                <i class="bi bi-chevron-down"></i> Mostrar/Ocultar
            </button>
        </div>
        <div class="card-body p-0" id="areaContratos" style="display: none;">
            <?php if(empty($contratos)): ?>
                <div class="p-3 text-center text-muted small">Nenhum contrato encontrado.</div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 250px; overflow: auto;">
                    <table class="table table-sm table-bordered mb-0 table-striped">
                        <thead class="table-light sticky-top"><tr><th>Fornecedor</th><th>Responsável</th><th class="text-center">Data</th><th class="text-end">Valor</th></tr></thead>
                        <tbody>
                            <?php foreach($contratos as $c): ?>
                            <tr>
                                <td class="small fw-bold"><?php echo $c['nome_fornecedor']; ?></td>
                                <td class="small"><?php echo $c['responsavel']; ?></td>
                                <td class="text-center small"><?php echo date('d/m/Y', strtotime($c['data_contrato'])); ?></td>
                                <td class="text-end small fw-bold">R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table class="table table-striped table-bordered table-hover table-xs w-100" id="tabelaDetalhes">
                <thead class="table-dark text-center">
                    <tr>
                        <th>OBRA</th> <th>FORNECEDOR</th> <th>OF</th>
                        <th>COMPRADOR</th>
                        <th>DATA</th>
                        <th>MATERIAL / DESCRIÇÃO</th> <th>QTD</th>
                        <th>UNITÁRIO</th>
                        <th>TOTAL BRUTO</th>
                        <th>QTD REC</th>
                        <th>QTD SALDO</th>
                        <th>VLR REC</th>
                        <th>VLR SALDO</th>
                        <th>BAIXA</th>
                        <th>PAGTO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_pedidos as $i): 
                        $saldo_qtd = $i['qtd_pedida'] - $i['qtd_recebida'];
                        $saldo_vlr = $i['valor_bruto_pedido'] - $i['valor_total_rec'];
                    ?>
                    <tr>
                        <td class="fw-bold text-primary" title="<?php echo $i['nome_obra']; ?>">
                            <?php echo substr($i['nome_obra'], 0, 25); ?>
                        </td>
                        
                        <td class="col-media text-dark" title="<?php echo $i['nome_fornecedor']; ?>">
                            <?php echo mb_strimwidth($i['nome_fornecedor'], 0, 40, "..."); ?>
                        </td>
                        
                        <td class="text-center"><?php echo $i['numero_of']; ?></td>
                        <td><?php echo substr($i['nome_comprador'] ?? '', 0, 10); ?></td>
                        <td class="text-center"><?php echo $i['data_pedido'] ? date('d/m/y', strtotime($i['data_pedido'])) : '-'; ?></td>
                        
                        <td class="col-longa"><?php echo $i['material']; ?></td>
                        
                        <td class="text-end"><?php echo number_format($i['qtd_pedida'], 2, ',', '.'); ?></td>
                        <td class="text-end"><?php echo number_format($i['valor_unitario'], 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold"><?php echo number_format($i['valor_bruto_pedido'], 2, ',', '.'); ?></td>
                        <td class="text-end text-success"><?php echo number_format($i['qtd_recebida'], 2, ',', '.'); ?></td>
                        <td class="text-end text-danger"><?php echo number_format($saldo_qtd, 2, ',', '.'); ?></td>
                        <td class="text-end text-success fw-bold"><?php echo number_format($i['valor_total_rec'], 2, ',', '.'); ?></td>
                        <td class="text-end text-danger"><?php echo number_format($saldo_vlr, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $i['dt_baixa'] ? date('d/m/y', strtotime($i['dt_baixa'])) : '-'; ?></td>
                        <td><?php echo substr($i['forma_pagamento'], 0, 15); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.2.2/js/dataTables.fixedColumns.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Mostrar/Ocultar Contratos
    $('#btnToggleContratos').click(function() { $('#areaContratos').slideToggle(); });

    // 2. DataTables com Scroll Nativo e Coluna Fixa
    $('#tabelaDetalhes').DataTable({
        scrollY: '60vh',      // Altura da tabela ajustável (60% da tela)
        scrollX: true,        // Barra de rolagem horizontal
        scrollCollapse: true, // Encolhe se tiver pouco registro
        paging: true,
        pageLength: 100,      // Mostra mais registros por padrão na lista geral
        
        // A MÁGICA DA COLUNA FIXA
        fixedColumns: {
            left: 1 // A primeira coluna (OBRA) fica parada
        },
        
        buttons: [ { extend: 'excel', className: 'btn btn-success btn-sm', text: 'Baixar Excel' } ],
        dom: 'Bfrtip',
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
    });
});
</script>