<?php
// GESTÃO DE FORNECEDORES (DASHBOARD: BRUTO vs EXECUTADO vs SALDO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. BUSCA OPÇÕES DE PAGAMENTO ---
$pagamentos_filtro = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll();

// --- 2. PREPARAÇÃO DOS FILTROS ---
$where_pedidos = "WHERE 1=1";
$params = [];

// Filtro de Data
$dt_ini = $_GET['dt_ini'] ?? '';
$dt_fim = $_GET['dt_fim'] ?? '';
if (!empty($dt_ini)) { $where_pedidos .= " AND p.data_pedido >= ?"; $params[] = $dt_ini; }
if (!empty($dt_fim)) { $where_pedidos .= " AND p.data_pedido <= ?"; $params[] = $dt_fim; }

// Filtro Forma de Pagamento
$filtro_pag = $_GET['filtro_pag'] ?? '';
if (!empty($filtro_pag)) { 
    $where_pedidos .= " AND p.forma_pagamento = ?"; 
    $params[] = $filtro_pag; 
}

// --- 3. CONSULTA PRINCIPAL (AGRUPADA POR FORNECEDOR) ---
// Agora somamos também o VALOR RECEBIDO (valor_total_rec)
$sql = "SELECT 
            f.id, 
            f.nome, 
            f.cnpj_cpf,
            COUNT(p.id) as qtd_pedidos,
            SUM(p.valor_bruto_pedido) as total_bruto,
            SUM(p.valor_total_rec) as total_recebido
        FROM fornecedores f
        JOIN pedidos p ON p.fornecedor_id = f.id
        $where_pedidos
        GROUP BY f.id
        ORDER BY total_bruto DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 4. CÁLCULO DOS TOTAIS GLOBAIS (BASEADO NOS FILTROS) ---
$global_bruto = 0;
$global_rec = 0;

foreach($lista as $f) { 
    $global_bruto += $f['total_bruto']; 
    $global_rec   += $f['total_recebido'];
}

// Saldo = O que foi pedido MENOS o que foi recebido
$global_saldo = $global_bruto - $global_rec;
?>

<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-dark fw-bold m-0"><i class="bi bi-truck-front-fill"></i> FORNECEDORES</h3>
            <span class="text-muted small">Acompanhamento de Pedidos e Entregas</span>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=lista_geral" class="btn btn-outline-primary fw-bold">
                <i class="bi bi-list-columns-reverse"></i> RELATÓRIO MESTRE
            </a>
            <a href="index.php?page=dashboard_graficos" class="btn btn-dark fw-bold shadow-sm">
                <i class="bi bi-pie-chart-fill"></i> ANÁLISE GRÁFICA
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body py-3">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="fornecedores">
                
                <div class="col-md-2">
                    <label class="fw-bold small text-muted">Data Início</label>
                    <input type="date" name="dt_ini" class="form-control" value="<?php echo $dt_ini; ?>">
                </div>
                <div class="col-md-2">
                    <label class="fw-bold small text-muted">Data Fim</label>
                    <input type="date" name="dt_fim" class="form-control" value="<?php echo $dt_fim; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="fw-bold small text-muted">Forma de Pagamento</label>
                    <select name="filtro_pag" class="form-select">
                        <option value="">-- Todas --</option>
                        <?php foreach($pagamentos_filtro as $p): ?>
                            <option value="<?php echo $p['forma_pagamento']; ?>" <?php echo ($filtro_pag == $p['forma_pagamento']) ? 'selected' : ''; ?>>
                                <?php echo $p['forma_pagamento']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="fw-bold small text-muted">Buscar por Nome</label>
                    <input type="text" id="filtroTexto" class="form-control" placeholder="Digite para filtrar cards...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel"></i> FILTRAR</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-5 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fw-bold small">VALOR BRUTO (TOTAL PEDIDO)</span>
                            <h3 class="text-primary fw-bold mb-0">R$ <?php echo number_format($global_bruto, 2, ',', '.'); ?></h3>
                        </div>
                        <div class="text-primary opacity-50"><i class="bi bi-cart-plus fs-1"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-5 border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fw-bold small">VLR TOT REC (EXECUTADO)</span>
                            <h3 class="text-success fw-bold mb-0">R$ <?php echo number_format($global_rec, 2, ',', '.'); ?></h3>
                        </div>
                        <div class="text-success opacity-50"><i class="bi bi-check-circle fs-1"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-start border-5 border-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted fw-bold small">VLR SALDO (A EXECUTAR)</span>
                            <h3 class="text-danger fw-bold mb-0">
                                R$ <?php echo number_format($global_saldo, 2, ',', '.'); ?>
                            </h3>
                        </div>
                        <div class="text-danger opacity-50"><i class="bi bi-exclamation-circle fs-1"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3 text-secondary border-bottom pb-2">Parceiros Encontrados: <b><?php echo count($lista); ?></b></h5>
    
    <div class="row" id="containerCards">
        <?php foreach($lista as $f): 
            $saldo_forn = $f['total_bruto'] - $f['total_recebido'];
        ?>
        <div class="col-xl-3 col-md-6 mb-4 item-card" data-nome="<?php echo strtolower($f['nome']); ?>">
            <div class="card shadow-sm h-100 border-top border-4 border-dark hover-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-dark border">ID: <?php echo $f['id']; ?></span>
                        <span class="badge bg-info text-dark"><?php echo $f['qtd_pedidos']; ?> Pedidos</span>
                    </div>
                    
                    <h6 class="card-title fw-bold text-dark text-truncate mb-1" title="<?php echo $f['nome']; ?>">
                        <?php echo $f['nome']; ?>
                    </h6>
                    <small class="text-muted mb-2"><?php echo $f['cnpj_cpf'] ?? '-'; ?></small>
                    
                    <div class="mt-auto pt-2 border-top small">
                        <div class="d-flex justify-content-between">
                            <span>Bruto:</span>
                            <span class="fw-bold text-primary">R$ <?php echo number_format($f['total_bruto'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Exec:</span>
                            <span class="fw-bold text-success">R$ <?php echo number_format($f['total_recebido'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="fw-bold">Saldo:</span>
                            <span class="fw-bold text-danger">R$ <?php echo number_format($saldo_forn, 2, ',', '.'); ?></span>
                        </div>
                    </div>

                    <a href="index.php?page=detalhe_fornecedor&id=<?php echo $f['id']; ?>" class="btn btn-outline-dark btn-sm w-100 mt-2 fw-bold">
                        <i class="bi bi-eye"></i> ABRIR DETALHES
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if(count($lista) == 0): ?>
            <div class="col-12 text-center py-5">
                <h3 class="text-muted"><i class="bi bi-inbox"></i> Nenhum fornecedor encontrado com estes filtros.</h3>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.getElementById('filtroTexto').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase();
    let cards = document.querySelectorAll('.item-card');
    
    cards.forEach(card => {
        let nome = card.getAttribute('data-nome');
        if (nome.includes(termo)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<style>
    .hover-card { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15)!important; }
</style>