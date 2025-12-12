<?php
// DETALHE DO CLIENTE (LAYOUT MODERNO E COMPACTO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id_cliente = $_GET['id'] ?? 0;

// 1. DADOS DO CLIENTE
$stmt = $pdo->prepare("SELECT * FROM clientes_imob WHERE id = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cliente) {
    echo "<div class='alert alert-warning'>Cliente não encontrado. <a href='index.php?page=clientes'>Voltar</a></div>";
    exit;
}

// 2. BUSCA CONTRATOS
$stmtVendas = $pdo->prepare("SELECT * FROM vendas_imob WHERE cliente_id = ? ORDER BY id DESC");
$stmtVendas->execute([$id_cliente]);
$vendas = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<style>
    /* LAYOUT LIMPO (Sem mesa gigante) */
    .container-fluid { max-width: 1600px; margin: 0 auto; }

    /* CORES DE STATUS (SUAVES) */
    .bg-soft-green { background-color: #d1e7dd !important; color: #0f5132; } /* Pago */
    .bg-soft-warning { background-color: #fff3cd !important; color: #664d03; } /* Parcial */
    .bg-soft-danger { background-color: #f8d7da !important; color: #842029; } /* Atrasado */
    .bg-soft-light { background-color: #fff !important; color: #555; } /* A Vencer */

    /* TABELA COMPACTA PROFISSIONAL */
    .tabela-parcelas th { 
        background-color: #343a40; 
        color: white; 
        text-align: center; 
        font-size: 10px; 
        padding: 6px; 
        text-transform: uppercase; 
    }
    .tabela-parcelas td { 
        vertical-align: middle; 
        padding: 4px 8px; 
        font-size: 12px; 
        border-color: #dee2e6; 
    }

    /* CABEÇALHO CONTRATO (Mais compacto) */
    .card-contrato { border: 1px solid #0d6efd; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .header-contrato { background: linear-gradient(to right, #0d6efd, #0a58ca); color: white; padding: 10px 15px; }
    
    /* BADGES DE RESUMO */
    .badge-resumo { background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; margin-right: 5px; border: 1px solid rgba(255,255,255,0.1); }
    
    .btn-action-header { color: white; border: 1px solid rgba(255,255,255,0.5); font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; transition: 0.2s; }
    .btn-action-header:hover { background: rgba(255,255,255,0.2); color: white; text-decoration: none; }
</style>

<div class="container-fluid px-3 pt-3"> 
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-primary fw-bold m-0"><i class="bi bi-person-vcard"></i> <?php echo htmlspecialchars($cliente['nome']); ?></h4>
            <span class="badge bg-light text-dark border mt-1">DOC: <?php echo $cliente['cpf'] ?? '-'; ?></span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm fw-bold shadow-sm" onclick="novoContrato()">
                <i class="bi bi-plus-circle"></i> Novo Contrato
            </button>
            <a href="index.php?page=central_importacoes&tab=imob" class="btn btn-warning btn-sm shadow-sm fw-bold"><i class="bi bi-cloud-upload"></i> Importar</a>
            <a href="index.php?page=clientes" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <?php if(empty($vendas)): ?>
        <div class="alert alert-light border text-center p-5 text-muted">
            <h4><i class="bi bi-folder-plus"></i> Nenhum contrato registrado.</h4>
            <p>Clique em "Novo Contrato" ou use a importação.</p>
        </div>
    <?php endif; ?>

    <?php foreach($vendas as $venda): 
        $stmtParc = $pdo->prepare("SELECT * FROM parcelas_imob WHERE venda_id = ? ORDER BY data_vencimento ASC");
        $stmtParc->execute([$venda['id']]);
        $parcelas = $stmtParc->fetchAll(PDO::FETCH_ASSOC);
        
        // CÁLCULOS
        $hoje = date('Y-m-d');
        $total_pago = 0; 
        $total_orig = 0;
        
        $qtd_total = count($parcelas);
        $qtd_pagas = 0;
        $qtd_atrasadas = 0;
        $qtd_abertas = 0;

        foreach($parcelas as $p) { 
            $total_pago += $p['valor_pago']; 
            $total_orig += $p['valor_parcela'];
            
            if ($p['valor_pago'] >= ($p['valor_parcela'] - 0.1)) {
                $qtd_pagas++;
            } elseif ($p['data_vencimento'] < $hoje && $p['valor_pago'] < $p['valor_parcela']) {
                $qtd_atrasadas++;
            } else {
                $qtd_abertas++;
            }
        }
        
        $perc_pagas = ($qtd_total > 0) ? round(($qtd_pagas / $qtd_total) * 100) : 0;
        $jsonVenda = htmlspecialchars(json_encode($venda), ENT_QUOTES, 'UTF-8');
    ?>
    
    <div class="card card-contrato" id="venda_<?php echo $venda['id']; ?>">
        <div class="header-contrato">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white p-1 rounded text-primary fs-4 shadow-sm"><i class="bi bi-building"></i></div>
                    <div>
                        <h5 class="m-0 fw-bold" style="font-size: 1rem;"><?php echo $venda['nome_casa'] ?: 'Nome do Empreendimento (Não informado)'; ?></h5>
                        <small class="opacity-75" style="font-size: 0.75rem;"><?php echo $venda['nome_empresa'] ?: 'Empresa Vendedora'; ?></small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-white text-primary shadow-sm mb-1">COD: <?php echo $venda['codigo_compra']; ?></span>
                    <div class="d-flex justify-content-end gap-1">
                        <button class="btn-action-header" onclick="editarContrato(<?php echo $jsonVenda; ?>)" title="Editar">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <a href="actions/excluir_venda.php?id=<?php echo $venda['id']; ?>&cli=<?php echo $id_cliente; ?>" 
                           class="btn-action-header text-danger border-danger bg-white" 
                           onclick="return confirm('ATENÇÃO: Vai apagar o contrato e TODAS as parcelas! Confirma?')" title="Excluir">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="d-flex flex-wrap align-items-center pt-2 border-top border-white border-opacity-25 gap-2">
                <span class="badge-resumo"><i class="bi bi-list-ol"></i> <?php echo $qtd_total; ?> Total</span>
                <span class="badge-resumo bg-success bg-opacity-75 border-0"><i class="bi bi-check-circle"></i> <?php echo $qtd_pagas; ?> Pagas</span>
                <?php if($qtd_atrasadas > 0): ?>
                    <span class="badge-resumo bg-danger bg-opacity-75 border-0"><i class="bi bi-exclamation-triangle"></i> <?php echo $qtd_atrasadas; ?> Atrasadas</span>
                <?php endif; ?>
                
                <div class="progress ms-2" style="width: 80px; height: 6px; background-color: rgba(255,255,255,0.3);">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $perc_pagas; ?>%"></div>
                </div>
                <small class="ms-1 text-white fw-bold" style="font-size: 0.7rem;"><?php echo $perc_pagas; ?>%</small>

                <div class="ms-auto bg-warning text-dark px-2 py-0 rounded shadow-sm">
                    <small class="fw-bold text-uppercase" style="font-size: 0.65rem;">Total Contrato:</small>
                    <span class="fw-bold" style="font-size: 0.85rem;">R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover mb-0 tabela-parcelas table-sm">
                    <thead class="sticky-top">
                        <tr>
                            <th width="60">AÇÃO</th>
                            <th width="50">#</th>
                            <th width="100">STATUS</th>
                            <th width="100">VENCIMENTO</th>
                            <th width="120">ORIGINAL</th>
                            <th width="100">DT PAGTO</th>
                            <th width="120">VALOR PAGO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($parcelas as $p): 
                            $hoje = date('Y-m-d');
                            $classe = "bg-soft-light"; 
                            $status_txt = "ABERTO";
                            $icone = '<i class="bi bi-circle text-muted"></i>';

                            if($p['valor_pago'] >= ($p['valor_parcela'] - 0.1) && $p['valor_parcela'] > 0) { 
                                $classe = "bg-soft-green"; 
                                $status_txt = "PAGO"; 
                                $icone = '<i class="bi bi-check-circle-fill text-success"></i>';
                            } 
                            elseif($p['data_vencimento'] < $hoje && $p['valor_pago'] < $p['valor_parcela']) { 
                                $classe = "bg-soft-danger"; 
                                $status_txt = "VENCIDO";
                                $icone = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
                            }
                            elseif($p['valor_pago'] > 0) { 
                                $classe = "bg-soft-warning"; 
                                $status_txt = "PARCIAL";
                                $icone = '<i class="bi bi-pie-chart-fill text-warning"></i>';
                            }

                            $jsonParc = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="<?php echo $classe; ?>">
                            <td class="text-center bg-white">
                                <button class="btn btn-sm btn-light border py-0 px-1" onclick="editarParcela(<?php echo $jsonParc; ?>)" title="Editar"><i class="bi bi-pencil" style="font-size: 10px;"></i></button>
                                <a href="actions/excluir_parcela.php?id=<?php echo $p['id']; ?>&cli=<?php echo $id_cliente; ?>" 
                                   class="btn btn-sm btn-light border py-0 px-1 text-danger" 
                                   onclick="return confirm('Excluir?')" title="Excluir"><i class="bi bi-x" style="font-size: 10px;"></i></a>
                            </td>
                            <td class="text-center fw-bold text-muted"><?php echo $p['numero_parcela']; ?></td>
                            <td class="text-center small fw-bold" style="font-size: 0.7rem;">
                                <?php echo $icone . ' ' . $status_txt; ?>
                            </td>
                            <td class="text-center"><?php echo date('d/m/y', strtotime($p['data_vencimento'])); ?></td>
                            <td class="text-end fw-bold text-secondary">R$ <?php echo number_format($p['valor_parcela'], 2, ',', '.'); ?></td>
                            <td class="text-center text-muted small"><?php echo $p['data_pagamento'] ? date('d/m/y', strtotime($p['data_pagamento'])) : '-'; ?></td>
                            <td class="text-end fw-bold text-dark">R$ <?php echo number_format($p['valor_pago'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="bg-light p-2 border-top d-flex justify-content-between align-items-center">
                <button class="btn btn-xs btn-outline-primary fw-bold" onclick="novaParcela(<?php echo $venda['id']; ?>)">
                    <i class="bi bi-plus-lg"></i> Add Parcela
                </button>
                <div class="small fw-bold text-secondary">
                    Total Pago: <span class="text-success fs-6">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="modalVenda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold" id="tituloModalVenda">Dados do Contrato</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/salvar_venda.php" method="POST">
                <div class="modal-body bg-light">
                    <input type="hidden" name="id" id="venda_id">
                    <input type="hidden" name="cliente_id" value="<?php echo $id_cliente; ?>">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="small fw-bold">Código</label><input type="text" name="codigo_compra" id="venda_codigo" class="form-control form-control-sm" required></div>
                        <div class="col-md-9"><label class="small fw-bold">Empreendimento</label><input type="text" name="nome_casa" id="venda_casa" class="form-control form-control-sm" required></div>
                        <div class="col-md-12"><label class="small fw-bold">Empresa</label><input type="text" name="nome_empresa" id="venda_empresa" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="small fw-bold">Data Contrato</label><input type="date" name="data_contrato" id="venda_dt_con" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="small fw-bold">Início</label><input type="date" name="data_inicio" id="venda_dt_ini" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="small fw-bold">Fim</label><input type="date" name="data_fim" id="venda_dt_fim" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="small fw-bold text-success">Valor Total</label><input type="text" name="valor_total" id="venda_valor" class="form-control form-control-sm" required></div>
                    </div>
                </div>
                <div class="modal-footer py-1"><button type="submit" class="btn btn-primary btn-sm fw-bold">SALVAR</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalParcela" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2"><h6 class="modal-title">Parcela</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="actions/salvar_parcela.php" method="POST">
                <div class="modal-body p-3">
                    <input type="hidden" name="id" id="parc_id">
                    <input type="hidden" name="venda_id" id="parc_venda_id">
                    <input type="hidden" name="cliente_id" value="<?php echo $id_cliente; ?>">
                    <div class="row g-2">
                        <div class="col-4"><label class="small fw-bold">Nº</label><input type="number" name="numero_parcela" id="parc_num" class="form-control form-control-sm" required></div>
                        <div class="col-8"><label class="small fw-bold">Vencimento</label><input type="date" name="data_vencimento" id="parc_venc" class="form-control form-control-sm" required></div>
                        <div class="col-12"><label class="small fw-bold">Valor (R$)</label><input type="text" name="valor_parcela" id="parc_valor" class="form-control form-control-sm" required></div>
                        <hr class="my-2"><h6 class="text-success small fw-bold m-0">Pagamento</h6>
                        <div class="col-6"><label class="small fw-bold">Data</label><input type="date" name="data_pagamento" id="parc_dt_pag" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="small fw-bold">Pago (R$)</label><input type="text" name="valor_pago" id="parc_vlr_pag" class="form-control form-control-sm"></div>
                    </div>
                </div>
                <div class="modal-footer py-1"><button class="btn btn-success btn-sm w-100 fw-bold">SALVAR</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
const fmtMoney = (v) => v ? new Intl.NumberFormat('pt-BR', {minimumFractionDigits: 2}).format(v) : '';

function novoContrato() {
    $('#venda_id').val(''); $('#venda_codigo').val(''); $('#venda_casa').val(''); $('#venda_empresa').val('');
    $('#venda_valor').val(''); $('#venda_dt_con').val(''); $('#venda_dt_ini').val(''); $('#venda_dt_fim').val('');
    $('#tituloModalVenda').text('Novo Contrato');
    new bootstrap.Modal(document.getElementById('modalVenda')).show();
}
function editarContrato(d) {
    $('#venda_id').val(d.id); $('#venda_codigo').val(d.codigo_compra);
    $('#venda_casa').val(d.nome_casa); $('#venda_empresa').val(d.nome_empresa);
    $('#venda_valor').val(fmtMoney(d.valor_total));
    $('#venda_dt_con').val(d.data_contrato); $('#venda_dt_ini').val(d.data_inicio); $('#venda_dt_fim').val(d.data_fim);
    $('#tituloModalVenda').text('Editar Contrato');
    new bootstrap.Modal(document.getElementById('modalVenda')).show();
}
function novaParcela(vendaId) {
    $('#parc_id').val(''); $('#parc_venda_id').val(vendaId);
    $('#parc_num').val(''); $('#parc_venc').val(''); $('#parc_valor').val(''); 
    $('#parc_dt_pag').val(''); $('#parc_vlr_pag').val('');
    new bootstrap.Modal(document.getElementById('modalParcela')).show();
}
function editarParcela(d) {
    $('#parc_id').val(d.id); $('#parc_venda_id').val(d.venda_id);
    $('#parc_num').val(d.numero_parcela); $('#parc_venc').val(d.data_vencimento);
    $('#parc_valor').val(fmtMoney(d.valor_parcela));
    $('#parc_dt_pag').val(d.data_pagamento); $('#parc_vlr_pag').val(fmtMoney(d.valor_pago));
    new bootstrap.Modal(document.getElementById('modalParcela')).show();
}
</script>