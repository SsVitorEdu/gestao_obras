<?php
// GESTÃO DE FORNECEDORES (FINANCEIRO + BOTÃO LISTA GERAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. CARREGAR LISTAS PARA FILTROS ---
$lista_obras = $pdo->query("SELECT id, nome FROM obras ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$lista_pagamentos = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll(PDO::FETCH_ASSOC);

// --- 2. CAPTURA DE FILTROS ---
$dt_ini = $_GET['dt_ini'] ?? date('Y-01-01');
$dt_fim = $_GET['dt_fim'] ?? date('Y-12-31');
$filtro_obra = $_GET['filtro_obra'] ?? '';
$filtro_pag = $_GET['filtro_pag'] ?? '';

// --- 3. CONSULTA SQL INTELIGENTE ---
$where = "WHERE 1=1";
$params = [];

if (!empty($dt_ini)) { $where .= " AND p.data_pedido >= ?"; $params[] = $dt_ini; }
if (!empty($dt_fim)) { $where .= " AND p.data_pedido <= ?"; $params[] = $dt_fim; }
if (!empty($filtro_obra)) { $where .= " AND p.obra_id = ?"; $params[] = $filtro_obra; }
if (!empty($filtro_pag)) { $where .= " AND p.forma_pagamento = ?"; $params[] = $filtro_pag; }

$sql = "SELECT 
            f.id, 
            f.nome, 
            f.cnpj_cpf, 
            
            -- CÁLCULOS FINANCEIROS (PEDIDOS)
            SUM(p.valor_bruto_pedido) as total_pedido,
            SUM(p.valor_total_rec) as total_executado,
            
            -- Última movimentação
            MAX(p.data_pedido) as ultimo_pedido

        FROM fornecedores f
        LEFT JOIN pedidos p ON p.fornecedor_id = f.id
        $where
        GROUP BY f.id
        HAVING total_pedido > 0
        ORDER BY total_pedido DESC"; 

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
    exit;
}

// --- 4. CÁLCULO DOS KPIs GERAIS ---
$kpi_bruto = 0;
$kpi_executado = 0;
$kpi_saldo = 0;

foreach($fornecedores as $f) {
    $kpi_bruto += $f['total_pedido'];
    $kpi_executado += $f['total_executado'];
}
$kpi_saldo = $kpi_bruto - $kpi_executado;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="m-0 text-dark fw-bold"><i class="bi bi-truck text-primary"></i> Fornecedores</h4>
        <small class="text-muted">Controle de Pedidos e Execução</small>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=dashboard_graficos" class="btn btn-warning btn-sm shadow-sm fw-bold">
            <i class="bi bi-pie-chart-fill"></i> Dashboard
        </a>

        <a href="index.php?page=lista_geral" class="btn btn-secondary btn-sm shadow-sm fw-bold">
            <i class="bi bi-list-ul"></i> Lista Geral
        </a>

        <a href="index.php?page=importar_mestre_xlsx" class="btn btn-dark btn-sm shadow-sm">
            <i class="bi bi-cloud-arrow-up"></i> Importar
        </a>
        
        <a href="index.php?page=configuracoes&tab=fornecedores" class="btn btn-outline-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg"></i> Novo
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4 border-0 bg-light">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="fornecedores">
            
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Início</label>
                <input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $dt_ini; ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Fim</label>
                <input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $dt_fim; ?>">
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted">Obra</label>
                <select name="filtro_obra" class="form-select form-select-sm">
                    <option value="">-- Todas --</option>
                    <?php foreach($lista_obras as $o): ?>
                        <option value="<?php echo $o['id']; ?>" <?php echo ($filtro_obra == $o['id']) ? 'selected' : ''; ?>>
                            <?php echo substr($o['nome'], 0, 30); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted">Pagamento</label>
                <select name="filtro_pag" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach($lista_pagamentos as $p): ?>
                        <option value="<?php echo $p['forma_pagamento']; ?>" <?php echo ($filtro_pag == $p['forma_pagamento']) ? 'selected' : ''; ?>>
                            <?php echo $p['forma_pagamento']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100 fw-bold px-3">FILTRAR</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-5 border-primary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-primary" style="font-size: 0.7rem;">VALOR BRUTO (PEDIDO)</small>
                        <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($kpi_bruto, 2, ',', '.'); ?></h4>
                    </div>
                    <div class="text-primary opacity-25"><i class="bi bi-cart-check fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-start border-5 border-success h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-success" style="font-size: 0.7rem;">VLR TOT REC (EXECUTADO)</small>
                        <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($kpi_executado, 2, ',', '.'); ?></h4>
                    </div>
                    <div class="text-success opacity-25"><i class="bi bi-check-circle fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-start border-5 border-danger h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-danger" style="font-size: 0.7rem;">VLR SALDO (A EXECUTAR)</small>
                        <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($kpi_saldo, 2, ',', '.'); ?></h4>
                    </div>
                    <div class="text-danger opacity-25"><i class="bi bi-hourglass-split fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="listaFornecedores">
    
    <?php if(empty($fornecedores)): ?>
        <div class="col-12 text-center py-5">
            <h4 class="text-muted"><i class="bi bi-inbox"></i> Nenhum fornecedor encontrado com estes filtros.</h4>
        </div>
    <?php endif; ?>

    <?php foreach($fornecedores as $f): 
        $saldo = $f['total_pedido'] - $f['total_executado'];
        $cor = ($saldo > 0) ? 'warning' : 'success';
        $textoBusca = strtolower($f['nome'] . ' ' . $f['cnpj_cpf']);
    ?>
    
    <div class="col-xl-3 col-md-6 mb-4 item-forn" data-busca="<?php echo $textoBusca; ?>">
        <div class="card shadow-sm h-100 border-top border-4 border-<?php echo $cor; ?> hover-effect">
            <div class="card-body d-flex flex-column p-3">
                
                <div class="mb-3" style="height: 50px;">
                    <h6 class="card-title fw-bold text-dark text-truncate mb-1" title="<?php echo $f['nome']; ?>">
                        <?php echo $f['nome']; ?>
                    </h6>
                    <span class="badge bg-light text-muted border"><?php echo $f['cnpj_cpf'] ?: 'S/ DOC'; ?></span>
                </div>

                <div class="bg-light rounded p-2 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted" style="font-size: 10px;">PEDIDO</small>
                        <span class="fw-bold text-primary small">R$ <?php echo number_format($f['total_pedido'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted" style="font-size: 10px;">EXECUTADO</small>
                        <span class="fw-bold text-success small">R$ <?php echo number_format($f['total_executado'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="border-top my-1"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted fw-bold" style="font-size: 10px;">SALDO</small>
                        <span class="fw-bold text-danger small">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></span>
                    </div>
                </div>

                <a href="index.php?page=detalhe_fornecedor&id=<?php echo $f['id']; ?>" class="btn btn-outline-dark btn-sm fw-bold w-100 mt-auto stretched-link">
                    VER DETALHES
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mb-4">
    <input type="text" id="filtroInput" class="form-control" placeholder="🔍 Digite para buscar um fornecedor na tela...">
</div>

<script>
document.getElementById('filtroInput').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase(); 
    let cards = document.querySelectorAll('.item-forn');
    cards.forEach(card => {
        let texto = card.getAttribute('data-busca');
        if(texto.includes(termo)) { card.style.display = ''; } else { card.style.display = 'none'; }
    });
});
</script>

<style>
    .hover-effect { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-effect:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1)!important; }
</style>