<?php
// DETALHE DO CLIENTE (FICHA FINANCEIRA IMOBILIÁRIA)
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

// 2. BUSCA AS VENDAS (CONTRATOS) DO CLIENTE
$stmtVendas = $pdo->prepare("SELECT * FROM vendas_imob WHERE cliente_id = ? ORDER BY id DESC");
$stmtVendas->execute([$id_cliente]);
$vendas = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<style>
    /* 1. LAYOUT PRINCIPAL */
    .janela-rolagem {
        width: 98%; 
        height: 85vh; 
        overflow-x: auto; 
        overflow-y: auto; 
        background: #f4f4f4; 
        border: 1px solid #ccc;
        margin: 0 auto;
    }
    .mesa-gigante { 
        width: 100%; 
        min-width: 1000px; /* Garante que não esprema em telas pequenas */
        padding: 20px; 
        background-color: white; 
    }

    /* 2. TABELA DE PARCELAS */
    .tabela-parcelas th { 
        background-color: #212529; 
        color: white; 
        text-align: center; 
        font-size: 12px; 
        padding: 8px;
    }
    .tabela-parcelas td { 
        vertical-align: middle; 
        padding: 6px 10px; 
        font-size: 13px; 
        border-color: #dee2e6; 
    }

    /* 3. CORES DE STATUS (A Lógica Solicitada) */
    .status-verde { background-color: #d1e7dd !important; color: #0f5132; font-weight: bold; } /* Pago Total */
    .status-laranja { background-color: #ffecb5 !important; color: #664d03; font-weight: bold; } /* Parcial */
    .status-branco { background-color: #fff !important; } /* Aberto */

    /* 4. CABEÇALHO DO CONTRATO (CARD AZUL) */
    .card-contrato { 
        border: 1px solid #0d6efd; 
        margin-bottom: 30px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
    }
    .header-contrato { 
        background-color: #0d6efd; 
        color: white; 
        padding: 15px; 
    }
    
    /* Caixinhas de informação no cabeçalho */
    .info-box { 
        background: rgba(255,255,255,0.15); 
        padding: 5px 12px; 
        border-radius: 4px; 
        font-size: 0.85rem; 
        margin-right: 10px; 
        display: inline-block;
        margin-bottom: 5px;
    }
    .info-label { font-size: 0.7rem; opacity: 0.8; display: block; text-transform: uppercase; }
    .info-value { font-weight: bold; font-size: 0.95rem; }
</style>

<div class="container-fluid p-0"> 
    <div class="janela-rolagem">
        <div class="mesa-gigante">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="text-dark fw-bold m-0"><i class="bi bi-person-vcard"></i> <?php echo htmlspecialchars($cliente['nome']); ?></h3>
                    <span class="badge bg-secondary fs-6 mt-1">DOC: <?php echo $cliente['cpf'] ?? '-'; ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?page=central_importacoes&tab=imob" class="btn btn-warning shadow-sm fw-bold"><i class="bi bi-cloud-upload"></i> Importar/Atualizar</a>
                    <a href="index.php?page=clientes" class="btn btn-outline-dark"><i class="bi bi-arrow-left"></i> Voltar</a>
                </div>
            </div>

            <?php if(empty($vendas)): ?>
                <div class="alert alert-light border text-center p-5 text-muted">
                    <h4><i class="bi bi-folder-x"></i> Nenhum contrato imobiliário encontrado.</h4>
                    <p>Vá em "Central de Importações" > Aba "Clientes Imob" para carregar os dados.</p>
                </div>
            <?php endif; ?>

            <?php foreach($vendas as $venda): 
                // Busca parcelas desta venda
                $stmtParc = $pdo->prepare("SELECT * FROM parcelas_imob WHERE venda_id = ? ORDER BY data_vencimento ASC");
                $stmtParc->execute([$venda['id']]);
                $parcelas = $stmtParc->fetchAll(PDO::FETCH_ASSOC);
                
                // Totais para o rodapé da tabela
                $total_pago_contrato = 0;
                $total_original_contrato = 0;
                foreach($parcelas as $p) { 
                    $total_pago_contrato += $p['valor_pago']; 
                    $total_original_contrato += $p['valor_parcela'];
                }
            ?>
            
            <div class="card card-contrato">
                <div class="header-contrato">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="m-0 fw-bold"><i class="bi bi-house-door-fill"></i> <?php echo $venda['nome_casa'] ?: 'Imóvel sem nome'; ?></h4>
                            <small class="opacity-75 fs-6"><?php echo $venda['nome_empresa']; ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-primary fs-6 shadow-sm">CÓDIGO: <?php echo $venda['codigo_compra']; ?></span>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="info-box">
                            <span class="info-label">Data Contrato</span>
                            <span class="info-value"><?php echo $venda['data_contrato'] ? date('d/m/Y', strtotime($venda['data_contrato'])) : '-'; ?></span>
                        </div>
                        <div class="info-box">
                            <span class="info-label">Início</span>
                            <span class="info-value"><?php echo $venda['data_inicio'] ? date('d/m/Y', strtotime($venda['data_inicio'])) : '-'; ?></span>
                        </div>
                        <div class="info-box">
                            <span class="info-label">Fim</span>
                            <span class="info-value"><?php echo $venda['data_fim'] ? date('d/m/Y', strtotime($venda['data_fim'])) : '-'; ?></span>
                        </div>
                        
                        <div class="ms-auto bg-warning text-dark px-3 py-2 rounded shadow-sm">
                            <span class="d-block small fw-bold text-uppercase" style="opacity: 0.7;">Valor Total Contrato</span>
                            <span class="fs-5 fw-bold">R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <table class="table table-bordered table-hover mb-0 tabela-parcelas">
                        <thead>
                            <tr>
                                <th width="120">DATA VENCIMENTO</th>
                                <th width="150">TOTAL ORIGINAL (R$)</th>
                                <th width="120">DATA PAGAMENTO</th>
                                <th width="150">VALOR RECEBIDO (R$)</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($parcelas as $p): 
                                // LÓGICA DE CORES
                                $classe = "status-branco";
                                $status = "ABERTO";
                                
                                if($p['valor_pago'] >= $p['valor_parcela'] && $p['valor_parcela'] > 0) {
                                    $classe = "status-verde"; 
                                    $status = "QUITADO";
                                } elseif($p['valor_pago'] > 0) {
                                    $classe = "status-laranja"; 
                                    $status = "PARCIAL";
                                }
                            ?>
                            <tr class="<?php echo $classe; ?>">
                                <td class="text-center"><?php echo $p['data_vencimento'] ? date('d/m/Y', strtotime($p['data_vencimento'])) : '-'; ?></td>
                                <td class="text-end fw-bold text-secondary">R$ <?php echo number_format($p['valor_parcela'], 2, ',', '.'); ?></td>
                                
                                <td class="text-center fw-bold">
                                    <?php echo $p['data_pagamento'] ? date('d/m/Y', strtotime($p['data_pagamento'])) : '-'; ?>
                                </td>
                                
                                <td class="text-end fw-bold text-dark">
                                    R$ <?php echo number_format($p['valor_pago'], 2, ',', '.'); ?>
                                </td>
                                
                                <td class="text-center small fw-bold"><?php echo $status; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td class="text-end fw-bold">TOTAIS:</td>
                                <td class="text-end fw-bold text-secondary">R$ <?php echo number_format($total_original_contrato, 2, ',', '.'); ?></td>
                                <td class="text-end fw-bold">RECEBIDO:</td>
                                <td class="text-end fw-bold text-success fs-6">R$ <?php echo number_format($total_pago_contrato, 2, ',', '.'); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>