<?php
// DETALHE DO FORNECEDOR (VERSÃO FINAL: MOLDURA + BLOCO DE CONCRETO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id_forn = $_GET['id'] ?? 0;

// 1. DADOS
$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
$stmt->execute([$id_forn]);
$fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$fornecedor) die("Fornecedor não encontrado!");

// 2. FILTROS
$where = "WHERE p.fornecedor_id = ?";
$params = [$id_forn];
if (!empty($_GET['dt_ini'])) { $where .= " AND p.data_pedido >= ?"; $params[] = $_GET['dt_ini']; }
if (!empty($_GET['dt_fim'])) { $where .= " AND p.data_pedido <= ?"; $params[] = $_GET['dt_fim']; }
if (!empty($_GET['filtro_obra'])) { $where .= " AND p.obra_id = ?"; $params[] = $_GET['filtro_obra']; }

// 3. CONSULTA
$sql_itens = "SELECT p.*, o.nome as nome_obra, o.codigo as cod_obra, m.nome as material, c.nome as nome_comprador
              FROM pedidos p
              LEFT JOIN obras o ON p.obra_id = o.id
              LEFT JOIN materiais m ON p.material_id = m.id
              LEFT JOIN compradores c ON p.comprador_id = c.id
              $where ORDER BY p.data_pedido DESC";
$itens = $pdo->prepare($sql_itens);
$itens->execute($params);
$lista = $itens->fetchAll(PDO::FETCH_ASSOC);

// 4. TOTAIS
$consumo_acumulado = 0;
foreach($lista as $l) { $consumo_acumulado += $l['valor_bruto_pedido']; }
$valor_contrato = 0; 
$saldo_contrato = $valor_contrato - $consumo_acumulado;
$obras_filtro = $pdo->query("SELECT DISTINCT o.id, o.nome FROM pedidos p JOIN obras o ON p.obra_id = o.id WHERE p.fornecedor_id = $id_forn ORDER BY o.nome")->fetchAll();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<style>
    /* 1. MOLDURA (A CAIXA COM A BARRA DE ROLAGEM) */
    .moldura-tabela {
        width: 100%;
        max-width: 85vw; /* Respeita o menu lateral */
        height: 65vh;    /* Altura fixa */
        overflow: auto;  /* A BARRA APARECE AQUI */
        border: 1px solid #ccc;
        background: #fff;
        display: block;
    }

    /* Estilo da Barra (Cinza e Visível) */
    .moldura-tabela::-webkit-scrollbar { width: 18px; height: 18px; }
    .moldura-tabela::-webkit-scrollbar-track { background: #f0f0f0; }
    .moldura-tabela::-webkit-scrollbar-thumb { background-color: #999; border: 3px solid #f0f0f0; border-radius: 8px; }
    .moldura-tabela::-webkit-scrollbar-thumb:hover { background-color: #666; }

    /* 2. TABELA SOLDA (BLOCO ÚNICO) */
    #tabelaDetalhes {
        /* FORÇA BRUTA: Largura fixa de 2500px. Garante que vaze da tela. */
        min-width: 2500px !important; 
        width: 2500px !important; 
        
        margin: 0;
        border-collapse: collapse;
        
        /* O CIMENTO: Isso trava as colunas no lugar */
        table-layout: fixed !important; 
    }

    /* 3. CÉLULAS E CABEÇALHO (DESTRAVADOS) */
    #tabelaDetalhes th, #tabelaDetalhes td {
        white-space: nowrap;
        padding: 8px 10px;
        font-size: 12px;
        border: 1px solid #ddd;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis; /* Corta texto longo com ... para não quebrar o layout */
        
        /* GARANTIA ABSOLUTA QUE NADA FLUTUA */
        position: static !important; 
        transform: none !important;
        z-index: auto !important;
    }

    /* Ajuste manual de largura de colunas importantes */
    /* Como usamos table-layout: fixed, definimos quem precisa de mais espaço */
    #tabelaDetalhes th:nth-child(1) { width: 350px; } /* Obra */
    #tabelaDetalhes th:nth-child(5) { width: 500px; } /* Material */

    /* Cor do Cabeçalho */
    #tabelaDetalhes thead th {
        background-color: #212529 !important;
        color: white !important;
        text-align: center;
        border-bottom: 2px solid #000;
    }
</style>

<div class="container-fluid px-3 pt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-dark fw-bold m-0"><?php echo htmlspecialchars($fornecedor['nome']); ?></h4>
        <a href="index.php?page=fornecedores" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="card shadow-sm mb-3 border-0 bg-light">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="detalhe_fornecedor">
                <input type="hidden" name="id" value="<?php echo $id_forn; ?>">
                <div class="col-md-2"><label class="small fw-bold">Data Início</label><input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $_GET['dt_ini'] ?? ''; ?>"></div>
                <div class="col-md-2"><label class="small fw-bold">Data Fim</label><input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $_GET['dt_fim'] ?? ''; ?>"></div>
                <div class="col-md-3"><select name="filtro_obra" class="form-select form-select-sm"><option value="">-- Todas as Obras --</option><?php foreach($obras_filtro as $o): ?><option value="<?php echo $o['id']; ?>" <?php echo (isset($_GET['filtro_obra']) && $_GET['filtro_obra'] == $o['id']) ? 'selected' : ''; ?>><?php echo substr($o['nome'], 0, 30); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">FILTRAR</button></div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4"><div class="card shadow-sm border-start border-5 border-secondary"><div class="card-body py-2"><small class="text-muted fw-bold">VALOR CONTRATO</small><h4 class="text-secondary m-0">R$ <?php echo number_format($valor_contrato, 2, ',', '.'); ?></h4></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm border-start border-5 border-primary"><div class="card-body py-2"><small class="text-muted fw-bold">CONSUMO ACUMULADO</small><h4 class="text-primary m-0">R$ <?php echo number_format($consumo_acumulado, 2, ',', '.'); ?></h4></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm border-start border-5 border-<?php echo ($saldo_contrato < 0) ? 'danger' : 'success'; ?>"><div class="card-body py-2"><small class="text-muted fw-bold">SALDO DO CONTRATO</small><h4 class="<?php echo ($saldo_contrato < 0) ? 'text-danger' : 'text-success'; ?> m-0">R$ <?php echo number_format($saldo_contrato, 2, ',', '.'); ?></h4></div></div></div>
    </div>

    <div class="alert alert-secondary py-1 small">
        <i class="bi bi-layout-three-columns"></i> <b>Modo Bloco Sólido:</b> O cabeçalho é parte da tabela e deve rolar junto com a barra cinza.
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="moldura-tabela">
                <table class="table table-striped table-hover" id="tabelaDetalhes">
                    <thead>
                        <tr>
                            <th>OBRA</th>
                            <th>OF</th>
                            <th>COMPRADOR</th>
                            <th>DATA PED</th>
                            <th>MATERIAL</th>
                            <th>QTD PEDIDO</th>
                            <th>VLR UNIT</th>
                            <th>VLR BRUTO</th>
                            <th>QTD REC</th>
                            <th>QTD SALDO</th>
                            <th>VLR TOT REC</th>
                            <th>VLR SALDO</th>
                            <th>DT BAIXA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lista as $i): 
                            $saldo_qtd = $i['qtd_pedida'] - $i['qtd_recebida'];
                            $saldo_vlr = $i['valor_bruto_pedido'] - $i['valor_total_rec'];
                        ?>
                        <tr>
                            <td title="<?php echo $i['nome_obra']; ?>"><?php echo $i['nome_obra']; ?></td>
                            <td><?php echo $i['numero_of']; ?></td>
                            <td><?php echo $i['nome_comprador'] ?? ''; ?></td>
                            <td><?php echo $i['data_pedido'] ? date('d/m/y', strtotime($i['data_pedido'])) : '-'; ?></td>
                            <td class="fw-bold" title="<?php echo $i['material']; ?>"><?php echo $i['material']; ?></td>
                            <td class="text-end"><?php echo number_format($i['qtd_pedida'], 2, ',', '.'); ?></td>
                            <td class="text-end"><?php echo number_format($i['valor_unitario'], 2, ',', '.'); ?></td>
                            <td class="text-end fw-bold"><?php echo number_format($i['valor_bruto_pedido'], 2, ',', '.'); ?></td>
                            <td class="text-end text-success"><?php echo number_format($i['qtd_recebida'], 2, ',', '.'); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($saldo_qtd, 2, ',', '.'); ?></td>
                            <td class="text-end text-success fw-bold"><?php echo number_format($i['valor_total_rec'], 2, ',', '.'); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($saldo_vlr, 2, ',', '.'); ?></td>
                            <td><?php echo $i['dt_baixa'] ? date('d/m/y', strtotime($i['dt_baixa'])) : '-'; ?></td>
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
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tabelaDetalhes').DataTable({
        paging: true,
        pageLength: 50,
        
        // --- BLINDAGEM MÁXIMA ---
        scrollX: false, // DESLIGADO para não separar o cabeçalho
        scrollY: false, 
        fixedHeader: false, // DESLIGADO para não flutuar o cabeçalho
        
        autoWidth: false, // IMPEDE O JS DE MEXER NA LARGURA
        
        buttons: [ { extend: 'excel', className: 'btn btn-success btn-sm me-1', text: 'Excel' } ],
        dom: 'Bfrtip',
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
    });
});
</script>