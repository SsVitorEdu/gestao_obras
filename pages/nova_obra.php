<?php
// Buscas para preencher os <select>
$empresas = $pdo->query("SELECT * FROM empresas ORDER BY nome")->fetchAll();
$obras = $pdo->query("SELECT * FROM obras ORDER BY nome")->fetchAll();
$fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome")->fetchAll();
$materiais = $pdo->query("SELECT * FROM materiais ORDER BY nome")->fetchAll();
$compradores = $pdo->query("SELECT * FROM compradores ORDER BY nome")->fetchAll();
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Novo Lançamento Manual</h5>
    </div>
    <div class="card-body">
        <form action="pages/salvar_pedido.php" method="POST">
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Empresa</label>
                    <select name="empresa_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach($empresas as $e) echo "<option value='{$e['id']}'>{$e['nome']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Obra / Projeto</label>
                    <select name="obra_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach($obras as $o) echo "<option value='{$o['id']}'>{$o['nome']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Comprador</label>
                    <select name="comprador_id" class="form-select">
                        <option value="">Selecione...</option>
                        <?php foreach($compradores as $c) echo "<option value='{$c['id']}'>{$c['nome']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Fornecedor</label>
                    <select name="fornecedor_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach($fornecedores as $f) echo "<option value='{$f['id']}'>{$f['nome']}</option>"; ?>
                    </select>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Material</label>
                    <select name="material_id" class="form-select" required>
                         <option value="">Selecione...</option>
                         <?php foreach($materiais as $m) echo "<option value='{$m['id']}'>{$m['nome']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Data Pedido</label>
                    <input type="date" name="data_pedido" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Previsão Entrega</label>
                    <input type="date" name="data_entrega" class="form-control">
                </div>
            </div>

            <div class="row mb-3 bg-light p-3 rounded">
                <div class="col-md-4">
                    <label>Quantidade Pedida</label>
                    <input type="number" step="0.01" name="qtd_pedida" id="qtd" class="form-control" oninput="calcularTotal()">
                </div>
                <div class="col-md-4">
                    <label>Valor Unitário (R$)</label>
                    <input type="number" step="0.01" name="valor_unitario" id="vlr" class="form-control" oninput="calcularTotal()">
                </div>
                <div class="col-md-4">
                    <label>Valor Bruto (Calculado)</label>
                    <input type="text" id="total" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold;">
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100">Salvar Lançamento</button>
        </form>
    </div>
</div>

<script>
function calcularTotal() {
    let qtd = document.getElementById('qtd').value || 0;
    let vlr = document.getElementById('vlr').value || 0;
    let total = qtd * vlr;
    document.getElementById('total').value = "R$ " + total.toFixed(2);
}
</script>