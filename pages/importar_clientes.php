<?php
// IMPORTADOR DE CLIENTES (VIA COPIAR E COLAR - 12 COLUNAS)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); 
ini_set('memory_limit', '1024M');

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";

// --- FUNÇÕES DE LIMPEZA ---
function limpaValor($v) {
    if(!isset($v) || trim($v) === '') return 0.00;
    // Remove R$, espaços e converte formato BR (1.000,00) para SQL (1000.00)
    $v = preg_replace('/[^\d,.-]/', '', $v); 
    $v = str_replace('.', '', $v); 
    $v = str_replace(',', '.', $v);
    return (float)$v;
}

function dataSQL($v) {
    $v = trim($v ?? '');
    if(empty($v) || $v == '-' || $v == '0' || $v == 'NULL') return null;
    
    // Formato BR (dd/mm/aaaa)
    if(strpos($v, '/') !== false) { 
        $p = explode('/', $v); 
        if(count($p)==3) return "{$p[2]}-{$p[1]}-{$p[0]}"; 
    }
    // Formato Excel Numérico (apenas se colar valor puro)
    if(is_numeric($v) && $v > 20000) return date('Y-m-d', ($v - 25569) * 86400);
    
    return null;
}

// --- PROCESSAMENTO DO TEXTO COLADO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dados_excel'])) {
    
    $dados_brutos = $_POST['dados_excel'];
    $linhas = explode("\n", $dados_brutos); // Quebra por linha

    try {
        $pdo->beginTransaction();
        $novos_clientes = 0;
        $novas_vendas = 0;
        $parcelas_proc = 0;

        // PREPARED STATEMENTS
        $stmtBuscaCli = $pdo->prepare("SELECT id FROM clientes_imob WHERE nome = ? LIMIT 1");
        $stmtInsCli   = $pdo->prepare("INSERT INTO clientes_imob (nome, cpf) VALUES (?, ?)");
        
        $stmtBuscaVenda = $pdo->prepare("SELECT id FROM vendas_imob WHERE codigo_compra = ? LIMIT 1");
        $stmtInsVenda   = $pdo->prepare("INSERT INTO vendas_imob (cliente_id, codigo_compra, nome_casa, nome_empresa, data_inicio, data_fim, data_contrato, valor_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtUpdVenda   = $pdo->prepare("UPDATE vendas_imob SET nome_empresa=?, data_inicio=?, data_fim=?, data_contrato=? WHERE id=?");

        // Busca Inteligente: Data + Valor (Evita mesclar parcelas diferentes do mesmo dia)
        $stmtBuscaParc = $pdo->prepare("SELECT id FROM parcelas_imob WHERE venda_id = ? AND data_vencimento = ? AND ABS(valor_parcela - ?) < 0.1 LIMIT 1");
        $stmtInsParc   = $pdo->prepare("INSERT INTO parcelas_imob (venda_id, numero_parcela, data_vencimento, valor_parcela, data_pagamento, valor_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtUpdParc   = $pdo->prepare("UPDATE parcelas_imob SET data_pagamento=?, valor_pago=? WHERE id=?");

        foreach($linhas as $l) {
            $c = explode("\t", trim($l)); // Quebra por TAB (padrão do Excel)
            
            // Verifica se tem as 12 colunas mínimas (ou perto disso)
            if(count($c) < 10) continue; 

            // --- MAPEAMENTO DAS 12 COLUNAS ---
            // 0: DT INI | 1: DT FIM | 2: DT REL | 3: COD EMP | 4: EMPRESA
            // 5: VENCIMENTO | 6: RECEBIMENTO | 7: VLR PAGO | 8: VLR ORIG
            // 9: CONTRATO (POSRECTO) | 10: CLIENTE | 11: CPF

            $dt_ini   = dataSQL($c[0]);
            $dt_fim   = dataSQL($c[1]);
            $dt_con   = dataSQL($c[2]); 
            $empresa  = trim($c[4]);    
            
            $dt_venc  = dataSQL($c[5]); 
            $dt_pag   = dataSQL($c[6]); 
            
            $vlr_pago = limpaValor($c[7]); 
            $vlr_orig = limpaValor($c[8]); 
            
            $cod_cont = trim($c[9]); 
            $nome_cli = strtoupper(trim($c[10])); 
            $cpf_cli  = trim($c[11] ?? '');

            // Validação: Ignora cabeçalhos ou linhas vazias
            if(empty($nome_cli) || empty($cod_cont) || stripos($nome_cli, 'CLIENTE') !== false) continue;

            // 1. CLIENTE
            $stmtBuscaCli->execute([$nome_cli]);
            if($row = $stmtBuscaCli->fetch()) {
                $cli_id = $row['id'];
            } else {
                $stmtInsCli->execute([$nome_cli, $cpf_cli]);
                $cli_id = $pdo->lastInsertId();
                $novos_clientes++;
            }

            // 2. VENDA (CONTRATO)
            $venda_id = null;
            $stmtBuscaVenda->execute([$cod_cont]);
            if($row = $stmtBuscaVenda->fetch()) {
                $venda_id = $row['id'];
                $stmtUpdVenda->execute([$empresa, $dt_ini, $dt_fim, $dt_con, $venda_id]);
            } else {
                $nome_casa = "CONTRATO " . $cod_cont;
                $stmtInsVenda->execute([$cli_id, $cod_cont, $nome_casa, $empresa, $dt_ini, $dt_fim, $dt_con, 0]);
                $venda_id = $pdo->lastInsertId();
                $novas_vendas++;
            }

            // 3. PARCELA
            if($dt_venc && $vlr_orig > 0) {
                // Tenta achar parcela idêntica (Dia + Valor)
                $stmtBuscaParc->execute([$venda_id, $dt_venc, $vlr_orig]);
                
                if($rowParc = $stmtBuscaParc->fetch()) {
                    // Já existe: Atualiza pagamento se houver novidade
                    if($vlr_pago > 0 || !empty($dt_pag)) {
                        $stmtUpdParc->execute([$dt_pag, $vlr_pago, $rowParc['id']]);
                    }
                } else {
                    // Nova Parcela
                    $stmtInsParc->execute([$venda_id, 0, $dt_venc, $vlr_orig, $dt_pag, $vlr_pago]);
                }
                $parcelas_proc++;
            }
        }
        
        // Recalcula valor total dos contratos
        $pdo->query("UPDATE vendas_imob v SET valor_total = (SELECT SUM(valor_parcela) FROM parcelas_imob p WHERE p.venda_id = v.id)");

        $pdo->commit();
        $msg = "<div class='alert alert-success shadow-sm border-start border-5 border-success'>
                    <h4 class='alert-heading'><i class='bi bi-check-circle-fill'></i> Dados Importados!</h4>
                    <ul class='mb-0'>
                        <li>Clientes Novos: <b>$novos_clientes</b></li>
                        <li>Contratos: <b>$novas_vendas</b></li>
                        <li>Parcelas Processadas: <b>$parcelas_proc</b></li>
                    </ul>
                </div>";

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>❌ Erro: ".$e->getMessage()."</div>";
    }
}
?>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-dark fw-bold m-0"><i class="bi bi-clipboard-data-fill text-primary"></i> IMPORTAÇÃO MANUAL</h3>
            <span class="text-muted">Copie do Excel e Cole abaixo</span>
        </div>
        <a href="index.php?page=clientes" class="btn btn-outline-dark fw-bold"><i class="bi bi-arrow-left"></i> VOLTAR</a>
    </div>

    <?php if($msg) echo $msg; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white fw-bold">
            ÁREA DE COLAGEM (CTRL+V)
        </div>
        <div class="card-body bg-light">
            
            <div class="alert alert-info py-2 small mb-3">
                <i class="bi bi-info-circle-fill"></i> <b>ORDEM DAS COLUNAS (12 Campos):</b><br>
                <code>DT INICIO | DT FIM | DT RELATORIO | COD EMP | EMPRESA | VENCIMENTO | RECEBIMENTO | VLR PAGO | TOTAL ORIG | CONTRATO | CLIENTE | CPF</code>
            </div>

            <form method="POST">
                <textarea name="dados_excel" class="form-control font-monospace mb-3" rows="15" placeholder="Clique aqui e cole seus dados..." style="white-space: pre; overflow-x: auto; font-size: 12px;"></textarea>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success fw-bold py-3">
                        <i class="bi bi-rocket-takeoff"></i> PROCESSAR DADOS COLADOS
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>