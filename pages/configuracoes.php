<?php
// pages/configuracoes.php
// TELA DE CONFIGURAÇÕES GERAIS (COM CLIENTES IMOBILIÁRIOS)

// --- CONEXÃO ---
if (!isset($pdo)) {
    $arquivo_db = __DIR__ . '/../includes/db.php';
    if (file_exists($arquivo_db)) include $arquivo_db;
    else include __DIR__ . '/../db.php';
}

$mensagem = "";

// --- LÓGICA DE CADASTRO RÁPIDO (INSERIR NOVO) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    try {
        if ($acao == 'nova_empresa') {
            $pdo->prepare("INSERT INTO empresas (nome, codigo) VALUES (?, ?)")->execute([strtoupper($_POST['nome']), $_POST['codigo']]);
            $mensagem = "✅ Empresa salva!";
        }
        elseif ($acao == 'nova_obra') {
            $emp = $_POST['empresa_id'] ?: NULL;
            $pdo->prepare("INSERT INTO obras (nome, codigo, empresa_id) VALUES (?, ?, ?)")->execute([strtoupper($_POST['nome']), $_POST['codigo'], $emp]);
            $mensagem = "✅ Obra salva!";
        }
        elseif ($acao == 'novo_fornecedor') {
            $cnpj = $_POST['cnpj_cpf'] ?? null;
            $pdo->prepare("INSERT INTO fornecedores (nome, cnpj_cpf) VALUES (?, ?)")->execute([strtoupper($_POST['nome']), $cnpj]);
            $mensagem = "✅ Fornecedor salvo!";
        }
        elseif ($acao == 'novo_material') {
            $pdo->prepare("INSERT INTO materiais (nome, unidade) VALUES (?, ?)")->execute([strtoupper($_POST['nome']), $_POST['unidade']]);
            $mensagem = "✅ Material salvo!";
        }
        elseif ($acao == 'novo_comprador') {
            $pdo->prepare("INSERT INTO compradores (nome) VALUES (?)")->execute([strtoupper($_POST['nome'])]);
            $mensagem = "✅ Comprador salvo!";
        }
        // --- NOVO: CLIENTE IMOBILIÁRIO ---
        elseif ($acao == 'novo_cliente_imob') {
            $cpf = $_POST['cpf'] ?? null;
            $pdo->prepare("INSERT INTO clientes_imob (nome, cpf) VALUES (?, ?)")->execute([strtoupper($_POST['nome']), $cpf]);
            $mensagem = "✅ Cliente salvo!";
        }
    } catch (Exception $e) { $mensagem = "❌ Erro: " . $e->getMessage(); }
}

// MENSAGEM DE EDIÇÃO
if(isset($_GET['msg']) && $_GET['msg']=='editado') {
    $mensagem = "✏️ Registro atualizado com sucesso!";
}

// --- CONSULTAS ---
$empresas = $pdo->query("SELECT * FROM empresas ORDER BY nome")->fetchAll();
$obras = $pdo->query("SELECT o.*, e.nome as nome_empresa FROM obras o LEFT JOIN empresas e ON o.empresa_id = e.id ORDER BY o.nome")->fetchAll();
$fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome LIMIT 1000")->fetchAll();
$materiais = $pdo->query("SELECT * FROM materiais ORDER BY nome LIMIT 1000")->fetchAll();
$compradores = $pdo->query("SELECT * FROM compradores ORDER BY nome")->fetchAll();
// NOVO
$clientes_imob = $pdo->query("SELECT * FROM clientes_imob ORDER BY nome")->fetchAll();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

<style>
    .tabela-excel thead th { background-color: #f8f9fa; color: #333; border-bottom: 2px solid #ccc; }
    .filter-select { width: 100%; font-size: 11px; margin-top: 5px; border: 1px solid #ccc; padding: 2px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark"><i class="bi bi-gear-fill"></i> Configurações</h3>
        <?php if($mensagem): ?><div class="alert alert-success py-1 px-3 m-0"><?php echo $mensagem; ?></div><?php endif; ?>
    </div>

    <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-empresas">🏢 Empresas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-obras">🏗️ Obras</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fornecedores">🚛 Fornecedores</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-clientes">🏠 Clientes (Imob)</button></li> <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-materiais">📦 Materiais</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-compradores">👤 Compradores</button></li>
    </ul>

    <div class="tab-content">
        
        <div class="tab-pane fade show active" id="tab-empresas">
            <div class="card mb-3 p-2 bg-light">
                <form method="POST" class="row g-2 align-items-center">
                    <input type="hidden" name="acao" value="nova_empresa">
                    <div class="col-auto"><input type="text" name="codigo" class="form-control form-control-sm" placeholder="Código"></div>
                    <div class="col-auto"><input type="text" name="nome" class="form-control form-control-sm" placeholder="Razão Social" required></div>
                    <div class="col-auto"><button class="btn btn-primary btn-sm">Criar Nova</button></div>
                </form>
            </div>
            <table class="table table-bordered table-hover table-sm tabela-excel" style="width:100%">
                <thead><tr><th>Cód</th><th>Empresa</th><th width="50">Editar</th></tr><tr class="filters-row"><th></th><th></th><th></th></tr></thead>
                <tbody>
                    <?php foreach($empresas as $e): ?>
                    <tr>
                        <td><?php echo $e['codigo']; ?></td>
                        <td><?php echo $e['nome']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 btn-editar" 
                                data-id="<?php echo $e['id']; ?>" 
                                data-nome="<?php echo $e['nome']; ?>" 
                                data-codigo="<?php echo $e['codigo']; ?>" 
                                data-tipo="empresas"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-obras">
            <div class="card mb-3 p-2 bg-light">
                <form method="POST" class="row g-2 align-items-center">
                    <input type="hidden" name="acao" value="nova_obra">
                    <div class="col-auto"><input type="text" name="codigo" class="form-control form-control-sm" placeholder="Cód"></div>
                    <div class="col-auto"><input type="text" name="nome" class="form-control form-control-sm" placeholder="Obra" required></div>
                    <div class="col-auto"><button class="btn btn-warning btn-sm">Criar Nova</button></div>
                </form>
            </div>
            <table class="table table-bordered table-hover table-sm tabela-excel" style="width:100%">
                <thead><tr><th>Cód</th><th>Obra</th><th width="50">Editar</th></tr><tr class="filters-row"><th></th><th></th><th></th></tr></thead>
                <tbody>
                    <?php foreach($obras as $o): ?>
                    <tr>
                        <td><?php echo $o['codigo']; ?></td>
                        <td><?php echo $o['nome']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 btn-editar" 
                                data-id="<?php echo $o['id']; ?>" 
                                data-nome="<?php echo $o['nome']; ?>" 
                                data-codigo="<?php echo $o['codigo']; ?>" 
                                data-tipo="obras"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-fornecedores">
            <div class="card mb-3 p-2 bg-light">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="acao" value="novo_fornecedor">
                    <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome do Fornecedor" required>
                    <input type="text" name="cnpj_cpf" class="form-control form-control-sm" placeholder="CNPJ / CPF" style="width: 150px;">
                    <button class="btn btn-info btn-sm text-white">Criar Novo</button>
                </form>
            </div>
            <table class="table table-bordered table-hover table-sm tabela-excel" style="width:100%">
                <thead>
                    <tr><th>Fornecedor</th><th width="150">CNPJ / CPF</th><th width="50">Editar</th></tr>
                    <tr class="filters-row"><th></th><th></th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach($fornecedores as $f): ?>
                    <tr>
                        <td><?php echo $f['nome']; ?></td>
                        <td><?php echo $f['cnpj_cpf']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 btn-editar" 
                                data-id="<?php echo $f['id']; ?>" 
                                data-nome="<?php echo $f['nome']; ?>" 
                                data-doc="<?php echo $f['cnpj_cpf']; ?>"
                                data-tipo="fornecedores"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-clientes">
            <div class="card mb-3 p-2 bg-light">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="acao" value="novo_cliente_imob">
                    <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome do Cliente" required>
                    <input type="text" name="cpf" class="form-control form-control-sm" placeholder="CPF / CNPJ" style="width: 150px;">
                    <button class="btn btn-dark btn-sm text-white">Adicionar Cliente</button>
                </form>
            </div>
            <table class="table table-bordered table-hover table-sm tabela-excel" style="width:100%">
                <thead>
                    <tr><th>Cliente</th><th width="150">CPF / CNPJ</th><th width="50">Editar</th></tr>
                    <tr class="filters-row"><th></th><th></th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach($clientes_imob as $c): ?>
                    <tr>
                        <td><?php echo $c['nome']; ?></td>
                        <td><?php echo $c['cpf']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 btn-editar" 
                                data-id="<?php echo $c['id']; ?>" 
                                data-nome="<?php echo $c['nome']; ?>" 
                                data-doc="<?php echo $c['cpf']; ?>"
                                data-tipo="clientes_imob"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-materiais">
            <div class="card mb-3 p-2 bg-light">
                <form method="POST" class="row g-2">
                    <input type="hidden" name="acao" value="novo_material">
                    <div class="col-auto"><input type="text" name="nome" class="form-control form-control-sm" placeholder="Material" required></div>
                    <div class="col-auto"><input type="text" name="unidade" class="form-control form-control-sm" placeholder="Un" size="5"></div>
                    <div class="col-auto"><button class="btn btn-success btn-sm">Salvar</button></div>
                </form>
            </div>
            <table class="table table-bordered table-hover table-sm tabela-excel" style="width:100%">
                <thead><tr><th>Material</th><th>Unidade</th><th width="50">Editar</th></tr><tr class="filters-row"><th></th><th></th><th></th></tr></thead>
                <tbody>
                    <?php foreach($materiais as $m): ?>
                    <tr>
                        <td><?php echo $m['nome']; ?></td>
                        <td><?php echo $m['unidade']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 btn-editar" 
                                data-id="<?php echo $m['id']; ?>" 
                                data-nome="<?php echo $m['nome']; ?>" 
                                data-tipo="materiais"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-compradores">
            <div class="card mb-3 p-2 bg-light">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="acao" value="novo_comprador">
                    <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome" required>
                    <button class="btn btn-secondary btn-sm">Salvar</button>
                </form>
            </div>
            <table class="table table-bordered table-hover table-sm tabela-excel" style="width:100%">
                <thead><tr><th>Comprador</th><th width="50">Editar</th></tr><tr class="filters-row"><th></th><th></th></tr></thead>
                <tbody>
                    <?php foreach($compradores as $c): ?>
                    <tr>
                        <td><?php echo $c['nome']; ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 btn-editar" 
                                data-id="<?php echo $c['id']; ?>" 
                                data-nome="<?php echo $c['nome']; ?>" 
                                data-tipo="compradores"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark">✏️ Editar Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/atualizar_cadastro.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="tipo_tabela" id="edit_tipo">
                    
                    <div class="mb-3" id="div_codigo">
                        <label class="form-label fw-bold">Código:</label>
                        <input type="text" name="codigo" id="edit_codigo" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome / Descrição:</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>

                    <div class="mb-3" id="div_doc" style="display:none;">
                        <label class="form-label fw-bold">CPF / CNPJ:</label>
                        <input type="text" name="doc" id="edit_doc" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">Salvar Alterações</button>
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

<script>
$(document).ready(function() {
    $('.tabela-excel').DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        orderCellsTop: true,
        buttons: [ { extend: 'excel', className: 'btn btn-success btn-sm' } ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" },
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                var header = $(column.header());
                if(header.text() === "Editar" || header.text().includes("CPF")) return; 
                
                var filterRow = header.closest('thead').find('.filters-row th').eq(column.index());
                var select = $('<select class="filter-select"><option value="">Todos</option></select>')
                    .appendTo(filterRow)
                    .on('change', function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });
                column.data().unique().sort().each(function (d, j) {
                    var texto = $('<div>').html(d).text();
                    if(texto) select.append('<option value="' + texto + '">' + texto + '</option>');
                });
            });
        }
    });

    // --- LÓGICA DO EDITAR ---
    $(document).on('click', '.btn-editar', function() {
        var id = $(this).data('id');
        var nome = $(this).data('nome');
        var codigo = $(this).data('codigo');
        var doc = $(this).data('doc'); // CPF ou CNPJ
        var tipo = $(this).data('tipo');

        $('#edit_id').val(id);
        $('#edit_nome').val(nome);
        $('#edit_tipo').val(tipo);

        // Reset
        $('#div_codigo').hide();
        $('#div_doc').hide();

        if (tipo === 'empresas' || tipo === 'obras') {
            $('#div_codigo').show();
            $('#edit_codigo').val(codigo);
        } 
        else if (tipo === 'fornecedores') {
            $('#div_doc').show();
            $('#edit_doc').val(doc).attr('name', 'cnpj_cpf'); // Nome do campo pro PHP
        }
        else if (tipo === 'clientes_imob') {
            $('#div_doc').show();
            $('#edit_doc').val(doc).attr('name', 'cpf'); // Nome do campo pro PHP
        }

        var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
        modal.show();
    });

    // Memória de Aba
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const tabButton = document.querySelector(`button[data-bs-target="#tab-${activeTab}"]`);
        if (tabButton) { const tabInstance = new bootstrap.Tab(tabButton); tabInstance.show(); }
    }
});
</script>