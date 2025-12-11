<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id_forn = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
$stmt->execute([$id_forn]);
$fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$fornecedor) die("Fornecedor não encontrado!");

try {
    $stmtContratos = $pdo->prepare("SELECT * FROM contratos WHERE fornecedor_id = ? ORDER BY data_contrato DESC");
    $stmtContratos->execute([$id_forn]);
    $contratos = $stmtContratos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $contratos = []; }

$valor_contrato_total = 0;
foreach($contratos as $c) { $valor_contrato_total += $c['valor']; }

$where = "WHERE p.fornecedor_id = ?";
$params = [$id_forn];

if (!empty($_GET['dt_ini'])) { $where .= " AND p.data_pedido >= ?"; $params[] = $_GET['dt_ini']; }
if (!empty($_GET['dt_fim'])) { $where .= " AND p.data_pedido <= ?"; $params[] = $_GET['dt_fim']; }
if (!empty($_GET['filtro_obra'])) { $where .= " AND p.obra_id = ?"; $params[] = $_GET['filtro_obra']; }
if (!empty($_GET['filtro_pag'])) { $where .= " AND p.forma_pagamento = ?"; $params[] = $_GET['filtro_pag']; }
if (!empty($_GET['filtro_of'])) { $where .= " AND p.numero_of LIKE ?"; $params[] = "%" . $_GET['filtro_of'] . "%"; }

$sql_itens = "SELECT p.*, 
                o.nome as nome_obra, 
                o.codigo as cod_obra, 
                m.nome as material, 
                c.nome as nome_comprador
              FROM pedidos p
              LEFT JOIN obras o ON p.obra_id = o.id
              LEFT JOIN materiais m ON p.material_id = m.id
              LEFT JOIN compradores c ON p.comprador_id = c.id
              $where ORDER BY p.data_pedido DESC";

$itens = $pdo->prepare($sql_itens);
$itens->execute($params);
$lista = $itens->fetchAll(PDO::FETCH_ASSOC);

$consumo_acumulado = 0;
foreach($lista as $l) { $consumo_acumulado += $l['valor_bruto_pedido']; }
$saldo_contrato = $valor_contrato_total - $consumo_acumulado;

$obras_filtro = $pdo->query("SELECT DISTINCT o.id, o.nome FROM pedidos p JOIN obras o ON p.obra_id = o.id WHERE p.fornecedor_id = $id_forn ORDER BY o.nome")->fetchAll();
$pagamentos_filtro = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE fornecedor_id = $id_forn AND forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.2.2/css/fixedColumns.bootstrap5.min.css">

<style>
    .table-xs th, .table-xs td { font-size: 11px; padding: 5px 8px; white-space: nowrap; vertical-align: middle; }
    .col-longa { white-space: normal !important; min-width: 250px; max-width: 400px; font-weight: bold; color: #333; }
    .bg-filtros { background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .label-filtro { font-size: 0.75rem; font-weight: bold; color: #666; margin-bottom: 2px; }
    
    .card-total-sm { padding: 10px; border-left: 4px solid #ccc; background: #fff; }
    .card-total-sm h5 { font-size: 1.1rem; margin: 0; font-weight: bold; }
    .card-total-sm small { font-size: 0.7rem; text-transform: uppercase; color: #888; font-weight: bold; }
</style>

<div class="container-fluid px-3 pt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <h4 class="text-dark fw-bold m-0"><?php echo htmlspecialchars($fornecedor['nome']); ?></h4>
            <span class="badge bg-secondary"><?php echo $fornecedor['cnpj_cpf'] ?? '-'; ?></span>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=importar_contratos" class="btn btn-warning btn-sm shadow-sm fw-bold"><i class="bi bi-cloud-upload"></i> Importar Lote</a>
            <a href="index.php?page=fornecedores" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card-total-sm shadow-sm" style="border-color: #6c757d;"><small>Valor Contrato (Total)</small><h5 class="text-secondary">R$ <?php echo number_format($valor_contrato_total, 2, ',', '.'); ?></h5></div></div>
        <div class="col-md-4"><div class="card-total-sm shadow-sm" style="border-color: #0d6efd;"><small>Consumo Acumulado</small><h5 class="text-primary">R$ <?php echo number_format($consumo_acumulado, 2, ',', '.'); ?></h5></div></div>
        <div class="col-md-4"><div class="card-total-sm shadow-sm" style="border-color: <?php echo ($saldo_contrato < 0) ? '#dc3545' : '#198754'; ?>;"><small>Saldo do Contrato</small><h5 class="<?php echo ($saldo_contrato < 0) ? 'text-danger' : 'text-success'; ?>">R$ <?php echo number_format($saldo_contrato, 2, ',', '.'); ?></h5></div></div>
    </div>

    <div class="card shadow-sm mb-3 border-0">
        <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center" style="border-left: 5px solid #ffc107;">
            <div class="d-flex align-items-center gap-3">
                <small class="fw-bold text-dark"><i class="bi bi-file-earmark-text"></i> GESTÃO DE CONTRATOS</small>
                <button class="btn btn-warning btn-sm py-0 fw-bold shadow-sm" onclick="modalContrato()">
                    <i class="bi bi-plus-circle"></i> Novo
                </button>
            </div>
            <button class="btn btn-sm text-primary" type="button" id="btnToggleContratos" style="font-size: 0.8rem;">
                <i class="bi bi-chevron-down"></i> Mostrar/Ocultar
            </button>
        </div>
        <div class="card-body p-0" id="areaContratos" style="display: block;">
            <?php if(empty($contratos)): ?>
                <div class="p-3 text-center text-muted small">Nenhum contrato. Clique em "Novo" para adicionar.</div>
            <?php else: ?>
                <table class="table table-sm table-bordered mb-0 table-hover">
    <thead class="table-light">
        <tr>
            <th width="80" class="text-center">Ações</th> <th>Responsável</th>
            <th class="text-center">Data</th>
            <th class="text-end">Valor</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($contratos as $c): ?>
        <tr>
            <td class="text-center">
                <button class="btn btn-light border btn-sm py-0 text-primary" 
                    onclick='modalContrato(<?php echo json_encode($c); ?>)' title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>

                <a href="actions/excluir_contrato.php?id=<?php echo $c['id']; ?>&id_forn=<?php echo $id_forn; ?>" 
                   class="btn btn-light border btn-sm py-0 text-danger ms-1" 
                   onclick="return confirm('TEM CERTEZA? Essa ação não pode ser desfeita!');" 
                   title="Excluir">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
            <td><?php echo $c['responsavel']; ?></td>
            <td class="text-center"><?php echo date('d/m/Y', strtotime($c['data_contrato'])); ?></td>
            <td class="text-end fw-bold">R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table> 
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body bg-filtros py-2 rounded">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="detalhe_fornecedor">
                <input type="hidden" name="id" value="<?php echo $id_forn; ?>">
                <div class="col-md-2"><label class="label-filtro">Início</label><input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $_GET['dt_ini'] ?? ''; ?>"></div>
                <div class="col-md-2"><label class="label-filtro">Fim</label><input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $_GET['dt_fim'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="label-filtro">Obra</label><select name="filtro_obra" class="form-select form-select-sm"><option value="">-- Todas --</option><?php foreach($obras_filtro as $o): ?><option value="<?php echo $o['id']; ?>" <?php echo (isset($_GET['filtro_obra']) && $_GET['filtro_obra'] == $o['id']) ? 'selected' : ''; ?>><?php echo substr($o['nome'], 0, 35); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="label-filtro">OF</label><input type="text" name="filtro_of" class="form-control form-control-sm" value="<?php echo $_GET['filtro_of'] ?? ''; ?>"></div>
                <div class="col-md-2"><label class="label-filtro">Pagamento</label><select name="filtro_pag" class="form-select form-select-sm"><option value="">-- Todos --</option><?php foreach($pagamentos_filtro as $p): ?><option value="<?php echo $p['forma_pagamento']; ?>" <?php echo (isset($_GET['filtro_pag']) && $_GET['filtro_pag'] == $p['forma_pagamento']) ? 'selected' : ''; ?>><?php echo $p['forma_pagamento']; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table class="table table-striped table-bordered table-hover table-xs w-100" id="tabelaDetalhes">
                <thead class="table-dark text-center">
                    <tr>
                        <th>OBRA</th> <th>OF</th> <th>COMPRADOR</th> <th>DATA</th> <th>MATERIAL / DESCRIÇÃO</th>
                        <th>QTD</th> <th>UNITÁRIO</th> <th>TOTAL BRUTO</th>
                        <th>QTD REC</th> <th>QTD SALDO</th> <th>VLR REC</th> <th>VLR SALDO</th>
                        <th>BAIXA</th> <th>PAGTO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista as $i): 
                        $saldo_qtd = $i['qtd_pedida'] - $i['qtd_recebida'];
                        $saldo_vlr = $i['valor_bruto_pedido'] - $i['valor_total_rec'];
                    ?>
                    <tr>
                        <td class="fw-bold text-primary" title="<?php echo $i['nome_obra']; ?>"><?php echo substr($i['nome_obra'], 0, 30); ?></td>
                        <td class="text-center"><?php echo $i['numero_of']; ?></td>
                        <td><?php echo substr($i['nome_comprador'] ?? '', 0, 15); ?></td>
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
                        <td><?php echo $i['forma_pagamento']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalContrato" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold" id="tituloModal">Novo Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/salvar_contrato.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="ct_id">
                    <input type="hidden" name="fornecedor_id" value="<?php echo $id_forn; ?>">
                    
                    <div class="mb-3">
                        <label class="fw-bold">Responsável / Descrição</label>
                        <input type="text" name="responsavel" id="ct_resp" class="form-control" placeholder="Ex: CONTRATO ANUAL 2025" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="fw-bold">Data</label>
                            <input type="date" name="data_contrato" id="ct_data" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="fw-bold">Valor (R$)</label>
                            <input type="text" name="valor" id="ct_valor" class="form-control" placeholder="0,00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark fw-bold">Salvar Contrato</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.2.2/js/dataTables.fixedColumns.min.js"></script>

<script>
$(document).ready(function() {
    $('#btnToggleContratos').click(function() { $('#areaContratos').slideToggle(); });

    $('#tabelaDetalhes').DataTable({
        scrollY: '60vh', scrollX: true, scrollCollapse: true, paging: true, pageLength: 50,
        fixedColumns: { left: 1 },
        buttons: [ { extend: 'excel', className: 'btn btn-success btn-sm', text: 'Baixar Excel' } ],
        dom: 'Bfrtip',
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
    });
});

function modalContrato(dados = null) {
    if (dados) {
        $('#tituloModal').text('Editar Contrato');
        $('#ct_id').val(dados.id);
        $('#ct_resp').val(dados.responsavel);
        $('#ct_data').val(dados.data_contrato);
        $('#ct_valor').val(dados.valor.replace('.', ',')); 
    } else {
        $('#tituloModal').text('Novo Contrato');
        $('#ct_id').val('');
        $('#ct_resp').val('');
        $('#ct_data').val('');
        $('#ct_valor').val('');
    }
    new bootstrap.Modal(document.getElementById('modalContrato')).show();
}
</script>