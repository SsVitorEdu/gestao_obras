<?php
// Consulta para agrupar valores por Obra
$sql = "SELECT 
            o.nome as nome_obra, 
            SUM(p.valor_bruto_pedido) as total_teorico, 
            SUM(p.valor_total_rec) as total_real 
        FROM pedidos p 
        JOIN obras o ON p.obra_id = o.id 
        GROUP BY o.id";
$dados_grafico = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Prepara arrays para o JavaScript
$labels = [];
$teorico = [];
$real = [];

foreach($dados_grafico as $d) {
    $labels[] = $d['nome_obra'];
    $teorico[] = $d['total_teorico'];
    $real[] = $d['total_real'];
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Comparativo Financeiro: Teórico vs Realizado</h6>
    </div>
    <div class="card-body">
        <div class="chart-bar">
            <canvas id="myBarChart"></canvas>
        </div>
        <hr>
        <small>Este gráfico compara o <b>Valor Bruto do Pedido</b> (quanto deveríamos pagar) com o <b>Valor Total Recuperado/Pago</b> (quanto saiu do caixa).</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var ctx = document.getElementById("myBarChart");
var myBarChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels); ?>,
    datasets: [
        {
            label: "Valor Teórico (Pedido)",
            backgroundColor: "#4e73df", // Azul
            data: <?php echo json_encode($teorico); ?>,
        },
        {
            label: "Valor Real (Pago)",
            backgroundColor: "#1cc88a", // Verde
            data: <?php echo json_encode($real); ?>,
        }
    ]
  },
  options: {
    maintainAspectRatio: false,
    scales: {
        y: { beginAtZero: true }
    }
  }
});
</script>