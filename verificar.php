<?php
include 'db.php';

echo "<h1>📊 Raio-X do Banco de Dados</h1>";

// Contagem
$n_emp = $pdo->query("SELECT count(*) FROM empresas")->fetchColumn();
$n_obr = $pdo->query("SELECT count(*) FROM obras")->fetchColumn();
$n_ped = $pdo->query("SELECT count(*) FROM pedidos")->fetchColumn();

echo "<ul>";
echo "<li>Empresas cadastradas: <b>$n_emp</b></li>";
echo "<li>Obras cadastradas: <b>$n_obr</b></li>";
echo "<li>Pedidos (Itens) cadastrados: <b>$n_ped</b></li>";
echo "</ul>";

if ($n_ped > 0) {
    echo "<h3>Últimos 5 Pedidos Gravados:</h3>";
    $sql = "SELECT p.id, o.nome as obra, p.valor_bruto_pedido FROM pedidos p JOIN obras o ON p.obra_id = o.id ORDER BY p.id DESC LIMIT 5";
    $lista = $pdo->query($sql)->fetchAll();
    foreach($lista as $l) {
        echo "ID: {$l['id']} | Obra: {$l['obra']} | Valor: R$ {$l['valor_bruto_pedido']}<br>";
    }
} else {
    echo "<h2 style='color:red'>⚠️ ALERTA: A tabela de pedidos está VAZIA!</h2>";
    echo "<p>Se você importou e deu sucesso, mas está vazio aqui, significa que o comando 'ROLLBACK' foi acionado no final ou você está conectando no banco errado.</p>";
}
?>