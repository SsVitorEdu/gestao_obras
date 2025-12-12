<?php
// GESTÃO DE OBRAS (COM IMPORTADOR MESTRE VINCULADO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. CARREGAR LISTAS PARA OS FILTROS ---
$lista_fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$lista_pagamentos = $pdo->query("SELECT DISTINCT forma_pagamento FROM pedidos WHERE forma_pagamento != '' ORDER BY forma_pagamento")->fetchAll(PDO::FETCH_ASSOC);

// --- 2. CAPTURA DE FILTROS ---
$dt_ini = $_GET['dt_ini'] ?? '';
$dt_fim = $_GET['dt_fim'] ?? '';
$filtro_forn = $_GET['filtro_forn'] ?? '';
$filtro_pag = $_GET['filtro_pag'] ?? '';
$filtro_ordem = $_GET['ordem'] ?? 'cod_desc';

// --- 3. CONSTRUÇÃO DA CONSULTA SQL ---
$where_pedidos = "WHERE 1=1";
$params = [];

if (!empty($dt_ini)) { $where_pedidos .= " AND p.data_pedido >= ?"; $params[] = $dt_ini; }
if (!empty($dt_fim)) { $where_pedidos .= " AND p.data_pedido <= ?"; $params[] = $dt_fim; }
if (!empty($filtro_forn)) { $where_pedidos .= " AND p.fornecedor_id = ?"; $params[] = $filtro_forn; }
if (!empty($filtro_pag)) { $where_pedidos .= " AND p.forma_pagamento = ?"; $params[] = $filtro_pag; }

// Definição da Ordem
switch ($filtro_ordem) {
    case 'cod_asc':    $sql_order = "o.codigo ASC"; break;
    case 'nome_asc':   $sql_order = "o.nome ASC"; break;
    case 'valor_desc': $sql_order = "total_gasto DESC"; break;
    case 'progresso':  $sql_order = "(itens_concluidos / NULLIF(total_itens, 0)) DESC"; break;
    default:           $sql_order = "o.codigo DESC";
}

// Query Principal
$sql = "SELECT 
            o.id, 
            o.codigo, 
            o.nome, 
            COALESCE(e.nome, 'Sem Empresa') as nome_empresa, 
            
            -- Contagens filtradas
            COUNT(p.id) as total_itens,
            SUM(CASE WHEN p.qtd_recebida >= p.qtd_pedida THEN 1 ELSE 0 END) as itens_concluidos,
            SUM(p.valor_bruto_pedido) as total_gasto

        FROM obras o 
        LEFT JOIN empresas e ON o.empresa_id = e.id
        LEFT JOIN pedidos p ON p.obra_id = o.id 
        $where_pedidos 
        
        GROUP BY o.id
        HAVING (total_itens > 0 OR '$dt_ini' = '') 
        ORDER BY $sql_order";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
    exit;
}

// --- 4. CÁLCULO DOS KPIs ---
$total_obras_listadas = count($obras);
$kpi_investido = 0;
$kpi_itens = 0;
$kpi_concluidos = 0;

foreach($obras as $o) {
    $kpi_investido += $o['total_gasto'];
    $kpi_itens += $o['total_itens'];
    $kpi_concluidos += $o['itens_concluidos'];
}
$kpi_progresso = ($kpi_itens > 0) ? round(($kpi_concluidos / $kpi_itens) * 100) : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="m-0 text-dark fw-bold"><i class="bi bi-buildings text-primary"></i> Gestão de Obras</h4>
        <small class="text-muted">Visão Geral e Controle de Custos</small>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=dashboard_obras" class="btn btn-warning btn-sm shadow-sm fw-bold">
            <i class="bi bi-pie-chart-fill"></i> Dashboard
        </a>
        
        <a href="index.php?page=importar_mestre_xlsx" class="btn btn-dark btn-sm shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i> Importar Mestre
        </a>
        
        <a href="index.php?page=configuracoes&tab=obras" class="btn btn-outline-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg"></i> Nova Obra
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4 border-0 bg-light">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="obras">
            
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Início</label>
                <input type="date" name="dt_ini" class="form-control form-control-sm" value="<?php echo $dt_ini; ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Fim</label>
                <input type="date" name="dt_fim" class="form-control form-control-sm" value="<?php echo $dt_fim; ?>">
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted">Fornecedor</label>
                <select name="filtro_forn" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach($lista_fornecedores as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo ($filtro_forn == $f['id']) ? 'selected' : ''; ?>>
                            <?php echo substr($f['nome'], 0, 25); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
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
                <label class="small fw-bold text-muted">Ordenar Por</label>
                <select name="ordem" class="form-select form-select-sm">
                    <option value="cod_desc" <?php echo ($filtro_ordem=='cod_desc')?'selected':''; ?>>Mais Recentes</option>
                    <option value="valor_desc" <?php echo ($filtro_ordem=='valor_desc')?'selected':''; ?>>💰 Maior Valor</option>
                    <option value="progresso" <?php echo ($filtro_ordem=='progresso')?'selected':''; ?>>📊 Maior Progresso</option>
                    <option value="nome_asc" <?php echo ($filtro_ordem=='nome_asc')?'selected':''; ?>>Alfabetica</option>
                </select>
            </div>

            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-5 border-success h-100">
            <div class="card-body py-2">
                <small class="text-uppercase fw-bold text-success" style="font-size: 0.7rem;">Total Investido (Filtro)</small>
                <h4 class="fw-bold text-dark mt-1 mb-0">R$ <?php echo number_format($kpi_investido, 2, ',', '.'); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-5 border-primary h-100">
            <div class="card-body py-2">
                <small class="text-uppercase fw-bold text-primary" style="font-size: 0.7rem;">Volume de Itens</small>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo number_format($kpi_itens, 0, ',', '.'); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-5 border-info h-100">
            <div class="card-body py-2">
                <small class="text-uppercase fw-bold text-info" style="font-size: 0.7rem;">Obras Listadas</small>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo $total_obras_listadas; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-5 border-warning h-100">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-uppercase fw-bold text-warning" style="font-size: 0.7rem;">Progresso Médio</small>
                    <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo $kpi_progresso; ?>%</h4>
                </div>
                <div style="width: 35px; height: 35px; border-radius: 50%; background: conic-gradient(#ffc107 <?php echo $kpi_progresso; ?>%, #e9ecef 0);"></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <input type="text" id="filtroInput" class="form-control form-control-lg" placeholder="🔍 Digite para localizar uma obra na tela...">
</div>

<div class="row" id="listaObras">
    <?php foreach($obras as $obra): 
        $progresso = ($obra['total_itens'] > 0) ? round(($obra['itens_concluidos'] / $obra['total_itens']) * 100) : 0;
        
        $cor_prog = 'primary';
        if($progresso == 100) $cor_prog = 'success';
        elseif($progresso < 30) $cor_prog = 'danger';
        elseif($progresso < 70) $cor_prog = 'warning';

        $textoBusca = strtolower($obra['codigo'] . ' ' . $obra['nome'] . ' ' . $obra['nome_empresa']);
    ?>
    <div class="col-xl-3 col-md-6 mb-4 obra-item" data-busca="<?php echo $textoBusca; ?>">
        <div class="card shadow-sm h-100 border-top border-4 border-<?php echo $cor_prog; ?> hover-effect">
            <div class="card-body d-flex flex-column">
                
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-light text-dark border">CÓD: <?php echo $obra['codigo']; ?></span>
                    <?php if($progresso == 100): ?>
                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> 100%</span>
                    <?php endif; ?>
                </div>

                <h5 class="card-title fw-bold text-dark text-truncate mt-1" title="<?php echo $obra['nome']; ?>">
                    <?php echo $obra['nome']; ?>
                </h5>
                <small class="text-muted mb-3 d-block text-truncate">
                    <i class="bi bi-building"></i> <?php echo $obra['nome_empresa']; ?>
                </small>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span>Progresso (Filtro)</span>
                        <span class="text-<?php echo $cor_prog; ?>"><?php echo $progresso; ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-<?php echo $cor_prog; ?>" role="progressbar" style="width: <?php echo $progresso; ?>%"></div>
                    </div>
                    <small class="text-muted" style="font-size: 10px;">
                        <?php echo $obra['itens_concluidos']; ?> / <?php echo $obra['total_itens']; ?> itens
                    </small>
                </div>

                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block" style="font-size: 10px;">INVESTIDO (FILTRO)</small>
                        <span class="fw-bold text-dark">R$ <?php echo number_format($obra['total_gasto'], 2, ',', '.'); ?></span>
                    </div>
                    <a href="index.php?page=detalhe_obra&id=<?php echo $obra['id']; ?>" class="btn btn-outline-dark btn-sm fw-bold stretched-link">
                        ABRIR
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if(empty($obras)): ?>
        <div class="col-12 text-center py-5">
            <h4 class="text-muted"><i class="bi bi-funnel"></i> Nenhuma obra encontrada com estes filtros.</h4>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('filtroInput').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase();
    let cards = document.querySelectorAll('.obra-item');
    
    cards.forEach(card => {
        let texto = card.getAttribute('data-busca');
        if(texto.includes(termo)) {
            card.style.display = ''; 
        } else {
            card.style.display = 'none'; 
        }
    });
});
</script>

<style>
    .hover-effect { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-effect:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1)!important; }
</style>