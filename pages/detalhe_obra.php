<?php
// DETALHE DA OBRA (COM EDIÇÃO, EXCLUSÃO E NOVO PEDIDO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id_obra = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT nome, codigo FROM obras WHERE id = ?");
$stmt->execute([$id_obra]);
$header = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$header) die("<div class='alert alert-danger'>Obra não encontrada!</div>");

$lista_fornecedores = $pdo->query("SELECT DISTINCT id, nome FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$lista_empresas = $pdo->query("SELECT DISTINCT id, nome FROM empresas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$lista_materiais = $pdo->query("SELECT id, nome FROM materiais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$lista_compradores = $pdo->query("SELECT id, nome FROM compradores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$sql_forn_filtro = "SELECT DISTINCT f.id, f.nome FROM pedidos p JOIN fornecedores f ON p.fornecedor_id = f.id WHERE p.obra_id = ? ORDER BY f.nome";
$stmtForn = $pdo->prepare($sql_forn_filtro);
$stmtForn->execute([$id_obra]);
$filtro_fornecedores = $stmtForn->fetchAll(PDO::FETCH_ASSOC);

$sql_emp_filtro = "SELECT DISTINCT e.id, e.nome FROM pedidos p JOIN empresas e ON p.empresa_id = e.id WHERE p.obra_id = ? ORDER BY e.nome";
$stmtEmp = $pdo->prepare($sql_emp_filtro);
$stmtEmp->execute([$id_obra]);
$filtro_empresas = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

$sql_pag = "SELECT DISTINCT forma_pagamento FROM pedidos WHERE obra_id = ? AND forma_pagamento != '' ORDER BY forma_pagamento";
$stmtPag = $pdo->prepare($sql_pag);
$stmtPag->execute([$id_obra]);
$lista_pagamentos = $stmtPag->fetchAll(PDO::FETCH_ASSOC);


$where = "WHERE p.obra_id = ?";
$params = [$id_obra];

if (!empty($_GET['dt_ini'])) { $where .= " AND p.data_pedido >= ?"; $params[] = $_GET['dt_ini']; }
if (!empty($_GET['dt_fim'])) { $where .= " AND p.data_pedido <= ?"; $params[] = $_GET['dt_fim']; }
if (!empty($_GET['filtro_of'])) { $where .= " AND p.numero_of LIKE ?"; $params[] = "%" . $_GET['filtro_of'] . "%"; }
if (!empty($_GET['filtro_forn'])) { $where .= " AND p.fornecedor_id = ?"; $params[] = $_GET['filtro_forn']; }
if (!empty($_GET['filtro_emp']))  { $where .= " AND p.empresa_id = ?";    $params[] = $_GET['filtro_emp']; }
if (!empty($_GET['filtro_pag']))  { $where .= " AND p.forma_pagamento = ?"; $params[] = $_GET['filtro_pag']; }

$sql_itens = "SELECT p.*, 
                f.nome as fornecedor, 
                m.nome as material, 
                c.nome as comprador,
                e.nome as nome_empresa_linha
              FROM pedidos p
              LEFT JOIN empresas e ON p.empresa_id = e.id
              LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
              LEFT JOIN materiais m ON p.material_id = m.id
              LEFT JOIN compradores c ON p.comprador_id = c.id
              $where 
              ORDER BY p.data_pedido DESC";

$itens = $pdo->prepare($sql_itens);
$itens->execute($params);
$lista = $itens->fetchAll(PDO::FETCH_ASSOC);
$total_bruto = 0;
$total_pago = 0;
foreach($lista as $l) {
    $total_bruto += $l['valor_bruto_pedido'];
    $total_pago  += $l['valor_total_rec'];
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.2.2/css/fixedColumns.bootstrap5.min.css">

<style>
    .table-xs th, .table-xs td { font-size: 10px; padding: 4px; white-space: nowrap; vertical-align: middle; }
    .col-longa { white-space: normal !important; min-width: 150px; max-width: 300px; }
    .bg-filtros { background-color: #f1f4f9; border-bottom: 1px solid #dce1e6; }
    .label-filtro { font-size: 0.75rem; font-weight: bold; color: #555; margin-bottom: 2px; }
    .btn-action { padding: 2px 6px; font-size: 10px; margin-right: 2px; }
    /* Ajuste para os botões do DataTables ficarem bonitos */
    .dt-buttons .btn { margin-right: 5px; font-weight: bold; }
</style>

<div class="container-fluid px-3 pt-3">
    
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'salvo'): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-check-circle-fill"></i> Pedido salvo com sucesso!
                <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'excluido'): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-trash-fill"></i> Pedido excluído com sucesso!
                <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary p-2 fs-6"><?php echo $header['codigo']; ?></span>
            <h4 class="text-dark fw-bold m-0"><?php echo $header['nome']; ?></h4>
        </div>
        
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm shadow-sm fw-bold" onclick="novoPedido()">
                <i class="bi bi-plus-lg"></i> Novo Pedido
            </button>
            <a href="index.php?page=obras" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
            <a href="index.php?page=importar_geral" class="btn btn-warning btn-sm shadow-sm fw-bold"><i class="bi bi-database-add"></i> Importar</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body bg-filtros py-3 rounded">
            <form method="GET" class="row g-2">
                <input type="hidden" name="page" value="detalhe_obra">
                <input type="hidden" name="id" value="<?php echo $id_obra; ?>">

                <div class="col-md-2"><label class="label-filtro">Início</label><input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $_GET['dt_ini'] ?? ''; ?>"></div>
                <div class="col-md-2"><label class="label-filtro">Fim</label><input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $_GET['dt_fim'] ?? ''; ?>"></div>
                
                <div class="col-md-2">
                    <label class="label-filtro">Empresa</label>
                    <select name="filtro_emp" class="form-select form-select-sm"><option value="">-- Todas --</option><?php foreach($filtro_empresas as $e): ?><option value="<?php echo $e['id']; ?>" <?php echo (isset($_GET['filtro_emp']) && $_GET['filtro_emp'] == $e['id']) ? 'selected' : ''; ?>><?php echo substr($e['nome'], 0, 20); ?></option><?php endforeach; ?></select>
                </div>
                
                <div class="col-md-2">
                    <label class="label-filtro">Fornecedor</label>
                    <select name="filtro_forn" class="form-select form-select-sm"><option value="">-- Todas --</option><?php foreach($filtro_fornecedores as $f): ?><option value="<?php echo $f['id']; ?>" <?php echo (isset($_GET['filtro_forn']) && $_GET['filtro_forn'] == $f['id']) ? 'selected' : ''; ?>><?php echo substr($f['nome'], 0, 20); ?></option><?php endforeach; ?></select>
                </div>

                <div class="col-md-2">
                    <label class="label-filtro">Pagamento</label>
                    <select name="filtro_pag" class="form-select form-select-sm"><option value="">-- Todas --</option><?php foreach($lista_pagamentos as $pg): ?><option value="<?php echo $pg['forma_pagamento']; ?>" <?php echo (isset($_GET['filtro_pag']) && $_GET['filtro_pag'] == $pg['forma_pagamento']) ? 'selected' : ''; ?>><?php echo $pg['forma_pagamento']; ?></option><?php endforeach; ?></select>
                </div>

                <div class="col-md-1"><label class="label-filtro">OF</label><input type="text" name="filtro_of" class="form-control form-control-sm" placeholder="Nº" value="<?php echo $_GET['filtro_of'] ?? ''; ?>"></div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel"></i></button>
                </div>

                <div class="col-md-12 d-flex gap-3 align-items-center justify-content-end mt-2">
                    <div class="bg-white border px-3 py-1 rounded shadow-sm"><small class="text-muted d-block" style="font-size: 10px;">TOTAL PEDIDO</small><span class="text-dark fw-bold fs-6">R$ <?php echo number_format($total_bruto, 2, ',', '.'); ?></span></div>
                    <div class="bg-white border-bottom border-4 border-success px-3 py-1 rounded shadow-sm"><small class="text-muted d-block" style="font-size: 10px;">TOTAL EXECUTADO</small><span class="text-success fw-bold fs-6">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></span></div>
                </div>
            </form>

            <hr class="my-2 border-secondary opacity-25">
            <div class="d-flex align-items-center gap-2">
                <span class="small fw-bold text-muted"><i class="bi bi-download"></i> Exportar Relatório:</span>
                <div id="area_botoes"></div>
            </div>
            </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover table-xs w-100" id="tabelaDetalhes">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>AÇÕES</th>
                            <th>EMPREEDIMENTO</th> <th>OF</th> <th>COMPRADOR</th> <th>DATA ped</th> <th>DATA ent</th>
                            <th>HISTORIA</th> <th>FORNECEDOR</th> <th>material</th>
                            <th>QTD PEDIDO</th> <th>VLR UNIT</th> <th>VLR BRUTO</th>
                            <th class="text-success">QTD REC</th> <th class="text-danger">QTD SALDO</th>
                            <th class="text-success">VLR REC</th> <th class="text-danger">VLR SALDO</th>
                            <th>DT BAIXA</th> <th>FORMA PGTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lista as $i): 
                            $saldo_qtd = $i['qtd_pedida'] - $i['qtd_recebida'];
                            $saldo_vlr = $i['valor_bruto_pedido'] - $i['valor_total_rec'];
                            $dados_json = htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="text-center" style="white-space: nowrap;">
                                <button class="btn btn-primary btn-action" onclick="editarPedido(<?php echo $dados_json; ?>)" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-danger btn-action" onclick="excluirPedido(<?php echo $i['id']; ?>)" title="Excluir"><i class="bi bi-trash"></i></button>
                            </td>

                            <td class="fw-bold text-primary"><?php echo substr($i['nome_empresa_linha'] ?? '-', 0, 15); ?></td>
                            <td><?php echo $i['numero_of']; ?></td>
                            <td><?php echo substr($i['comprador'], 0, 10); ?></td>
                            <td><?php echo $i['data_pedido'] ? date('d/m/y', strtotime($i['data_pedido'])) : '-'; ?></td>
                            <td><?php echo $i['data_entrega'] ? date('d/m/y', strtotime($i['data_entrega'])) : '-'; ?></td>
                            <td class="col-longa"><?php echo mb_strimwidth($i['historia'] ?? '', 0, 30, "..."); ?></td>
                            <td><?php echo mb_strimwidth($i['fornecedor'], 0, 15, "..."); ?></td>
                            <td class="col-longa fw-bold text-dark"><?php echo $i['material']; ?></td>
                            <td class="text-end"><?php echo number_format($i['qtd_pedida'], 2, ',', '.'); ?></td>
                            <td class="text-end"><?php echo number_format($i['valor_unitario'], 2, ',', '.'); ?></td>
                            <td class="text-end fw-bold"><?php echo number_format($i['valor_bruto_pedido'], 2, ',', '.'); ?></td>
                            <td class="text-end text-success"><?php echo number_format($i['qtd_recebida'], 2, ',', '.'); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($saldo_qtd, 2, ',', '.'); ?></td>
                            <td class="text-end text-success fw-bold bg-light"><?php echo number_format($i['valor_total_rec'], 2, ',', '.'); ?></td>
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
</div>

<div class="modal fade" id="modalNovoPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" id="modalHeader">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-plus-circle"></i> Novo Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/salvar_pedido_obra.php" method="POST" id="formPedido">
                <div class="modal-body bg-light">
                    <input type="hidden" name="id" id="pedido_id">
                    <input type="hidden" name="obra_id" value="<?php echo $id_obra; ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Número OF *</label>
                            <input type="text" name="numero_of" id="numero_of" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Data Pedido *</label>
                            <input type="date" name="data_pedido" id="data_pedido" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Previsão Entrega</label>
                            <input type="date" name="data_entrega" id="data_entrega" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Comprador</label>
                            <select name="comprador_id" id="comprador_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach($lista_compradores as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['nome']; ?></option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fornecedor *</label>
                            <select name="fornecedor_id" id="fornecedor_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach($lista_fornecedores as $f): ?><option value="<?php echo $f['id']; ?>"><?php echo $f['nome']; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Empresa Pagadora *</label>
                            <select name="empresa_id" id="empresa_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach($lista_empresas as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo $e['nome']; ?></option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Material (Produto) *</label>
                            <select name="material_id" id="material_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach($lista_materiais as $m): ?><option value="<?php echo $m['id']; ?>"><?php echo $m['nome']; ?></option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">História / Detalhes</label>
                            <textarea name="historia" id="historia" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Qtd Pedida</label>
                            <input type="text" name="qtd_pedida" id="qtd_pedida" class="form-control" placeholder="0,00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Valor Unitário</label>
                            <input type="text" name="valor_unitario" id="valor_unitario" class="form-control" placeholder="0,00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Forma Pagamento</label>
                            <input type="text" name="forma_pagamento" id="forma_pagamento" class="form-control">
                        </div>

                        <hr class="my-2">
                        <h6 class="text-primary small fw-bold">Informações de Recebimento</h6>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Qtd Recebida</label>
                            <input type="text" name="qtd_recebida" id="qtd_recebida" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Valor Total Rec.</label>
                            <input type="text" name="valor_total_rec" id="valor_total_rec" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Data Baixa</label>
                            <input type="date" name="dt_baixa" id="dt_baixa" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold" id="btnSalvar">SALVAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.2.2/js/dataTables.fixedColumns.min.js"></script>

<script>
// --- FUNÇÕES DE AÇÃO ---
function novoPedido() {
    $('#formPedido')[0].reset(); 
    $('#pedido_id').val(''); 
    $('#modalTitle').html('<i class="bi bi-plus-circle"></i> Novo Pedido');
    $('#modalHeader').removeClass('bg-warning').addClass('bg-success');
    $('#btnSalvar').text('SALVAR');
    $('#modalNovoPedido').modal('show');
}

function editarPedido(dados) {
    $('#pedido_id').val(dados.id);
    $('#numero_of').val(dados.numero_of);
    $('#data_pedido').val(dados.data_pedido);
    $('#data_entrega').val(dados.data_entrega);
    $('#comprador_id').val(dados.comprador_id);
    $('#fornecedor_id').val(dados.fornecedor_id);
    $('#empresa_id').val(dados.empresa_id);
    $('#material_id').val(dados.material_id);
    $('#historia').val(dados.historia);
    $('#qtd_pedida').val(dados.qtd_pedida);
    $('#valor_unitario').val(dados.valor_unitario);
    $('#forma_pagamento').val(dados.forma_pagamento);
    $('#qtd_recebida').val(dados.qtd_recebida);
    $('#valor_total_rec').val(dados.valor_total_rec);
    $('#dt_baixa').val(dados.dt_baixa);

    $('#modalTitle').html('<i class="bi bi-pencil-square"></i> Editar Pedido #' + dados.id);
    $('#modalHeader').removeClass('bg-success').addClass('bg-warning text-dark');
    $('#btnSalvar').text('ATUALIZAR');
    $('#modalNovoPedido').modal('show');
}

function excluirPedido(id) {
    if(confirm('Tem certeza que deseja excluir este pedido?')) {
        window.location.href = 'actions/excluir_pedido_obra.php?id=' + id + '&obra_id=<?php echo $id_obra; ?>';
    }
}

// --- CONFIGURAÇÃO DA TABELA ---
$(document).ready(function() {
    var nomeObra = "<?php echo $header['nome']; ?>";
    var dataHoje = new Date().toLocaleDateString('pt-BR');

    // NUNCA use 'var table =' se for usar a variável dentro do initComplete.
    // O DataTables gerencia isso internamente com 'this'.
    $('#tabelaDetalhes').DataTable({
        scrollY: '60vh',
        scrollX: true,
        scrollCollapse: true,
        paging: true,
        pageLength: 50,
        fixedColumns: { left: 2 },
        // 'dom' define a estrutura. 'B' cria os botões (invisíveis a princípio se quiser esconder, mas aqui vamos deixar padrão)
        dom: 'Bfrtip', 
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                className: 'btn btn-success btn-sm fw-bold',
                title: 'Relatório - ' + nomeObra + ' - ' + dataHoje,
                exportOptions: { columns: ':visible:not(:eq(0))' }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm fw-bold ms-2',
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'Relatório - ' + nomeObra,
                messageTop: 'Gerado em: ' + dataHoje,
                exportOptions: { columns: ':visible:not(:eq(0))' },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 7;
                    doc.styles.tableHeader.fontSize = 8;
                    try {
                        var colCount = doc.content[1].table.body[0].length;
                        doc.content[1].table.widths = Array(colCount).fill('*');
                    } catch (e) {}
                }
            }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" },
        
        // CORREÇÃO CRÍTICA AQUI:
        initComplete: function () {
            // Usamos 'this.api()' porque a variável 'table' ainda não existe nesse momento
            var api = this.api();
            
            // Pega os botões gerados e move fisicamente para a sua DIV personalizada
            api.buttons().container().appendTo( $('#area_botoes') );
        }
    });
});
</script>