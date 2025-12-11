<?php
// CENTRAL DE IMPORTAÇÕES (5 ABAS - INCLUINDO CLIENTES IMOBILIÁRIOS)
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // 10 minutos
ini_set('memory_limit', '1024M');

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";
$aba_ativa = "empresas"; // Padrão

// --- FUNÇÕES AUXILIARES ---
function limpaValor($v) {
    if(!isset($v) || $v === '') return 0;
    $v = preg_replace('/[^\d,.-]/', '', $v); 
    $v = str_replace('.', '', $v); 
    $v = str_replace(',', '.', $v);
    return (float)$v;
}
function dataSQL($v) {
    $v = trim($v ?? '');
    if(empty($v) || $v == '-' || $v == '0') return null;
    // Formato DD/MM/AAAA
    if(strpos($v, '/') !== false) { 
        $p = explode('/', $v); 
        if(count($p)==3) return "{$p[2]}-{$p[1]}-{$p[0]}"; 
    }
    // Formato Excel Numérico
    if(is_numeric($v) && $v > 20000) return date('Y-m-d', ($v - 25569) * 86400);
    return null;
}

// --- PROCESSAMENTO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $acao = $_POST['acao'];
    $aba_ativa = $_POST['aba_origem']; 
    $dados = $_POST['dados'] ?? '';
    $linhas = array_filter(explode("\n", $dados), 'trim');

    try {
        // =================================================================
        // 1. IMPORTAR EMPRESAS
        // =================================================================
        if ($acao == 'importar_empresas') {
            $sql = "INSERT INTO empresas (codigo, nome) VALUES (?, ?) ON DUPLICATE KEY UPDATE nome = VALUES(nome)";
            $stmt = $pdo->prepare($sql);
            $count = 0;
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<2) continue;
                $cod = trim($c[0]); $nm = trim($c[1]);
                if(empty($cod) || stripos($cod,'COD')!==false) continue;
                $stmt->execute([$cod, $nm]);
                $count++;
            }
            $msg = "<div class='alert alert-success'>✅ $count Empresas processadas!</div>";
        }
        elseif ($acao == 'limpar_empresas') {
            $pdo->query("SET FOREIGN_KEY_CHECKS=0");
            $pdo->query("TRUNCATE TABLE empresas");
            $pdo->query("INSERT IGNORE INTO empresas (id, nome, codigo) VALUES (1, 'EMPRESA GERAL', '000')");
            $pdo->query("SET FOREIGN_KEY_CHECKS=1");
            $msg = "<div class='alert alert-warning'>🗑️ Empresas zeradas!</div>";
        }

        // =================================================================
        // 2. IMPORTAR OBRAS
        // =================================================================
        elseif ($acao == 'importar_obras') {
            $check = $pdo->prepare("SELECT id FROM obras WHERE codigo = ?");
            $ins = $pdo->prepare("INSERT INTO obras (codigo, nome, empresa_id) VALUES (?, ?, NULL)");
            $upd = $pdo->prepare("UPDATE obras SET nome = ? WHERE id = ?");
            $novas = 0; $upds = 0;
            
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<2) continue;
                $cod = strtoupper(trim($c[0])); $nm = trim($c[1]);
                if(empty($cod) || stripos($cod,'COD')!==false) continue;

                $check->execute([$cod]);
                if($id = $check->fetchColumn()) { $upd->execute([$nm, $id]); $upds++; }
                else { $ins->execute([$cod, $nm]); $novas++; }
            }
            $msg = "<div class='alert alert-success'>✅ Obras: $novas criadas, $upds atualizadas.</div>";
        }
        elseif ($acao == 'limpar_obras') {
            $pdo->query("SET FOREIGN_KEY_CHECKS=0");
            $pdo->query("TRUNCATE TABLE obras");
            $pdo->query("INSERT IGNORE INTO obras (id, nome, codigo) VALUES (1, 'OBRA GERAL', '000')");
            $pdo->query("SET FOREIGN_KEY_CHECKS=1");
            $msg = "<div class='alert alert-warning'>🗑️ Obras zeradas!</div>";
        }

        // =================================================================
        // 3. IMPORTAR CONTRATOS (FORNECEDORES)
        // =================================================================
        elseif ($acao == 'importar_contratos') {
            $mapaForn = [];
            $q = $pdo->query("SELECT id, nome FROM fornecedores");
            while($r=$q->fetch()) $mapaForn[strtoupper(trim($r['nome']))] = $r['id'];

            $stmt = $pdo->prepare("INSERT INTO contratos (fornecedor_id, responsavel, valor, data_contrato) VALUES (?, ?, ?, ?)");
            $ok = 0; $err = 0;

            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<3) continue;
                $nm = strtoupper(trim($c[0]));
                if(stripos($nm, 'FORNECEDOR')!==false) continue; 

                $id_forn = $mapaForn[$nm] ?? null;
                if($id_forn) {
                    $resp = trim($c[1]);
                    $vlr = limpaValor($c[2]);
                    $dt = dataSQL($c[3] ?? '');
                    $stmt->execute([$id_forn, $resp, $vlr, $dt]);
                    $ok++;
                } else { $err++; }
            }
            $msg = "<div class='alert alert-success'>✅ $ok Contratos importados ($err não encontrados).</div>";
        }

        // =================================================================
        // 4. IMPORTAÇÃO MESTRE (PEDIDOS)
        // =================================================================
        elseif ($acao == 'importar_mestre') {
            // Lógica completa V18 mantida
            $mapaObras = []; $q=$pdo->query("SELECT id, codigo FROM obras WHERE codigo IS NOT NULL");
            while($r=$q->fetch()) $mapaObras[strtoupper(trim($r['codigo']))] = $r['id'];
            
            $mapaEmpresas = []; $q=$pdo->query("SELECT id, codigo FROM empresas WHERE codigo IS NOT NULL");
            while($r=$q->fetch()) $mapaEmpresas[strtoupper(trim($r['codigo']))] = $r['id'];

            $cacheAux = ['fornecedores'=>[], 'materiais'=>[], 'compradores'=>[]];
            function getIdRapido($pdo, $tab, $nome, &$cache) {
                $nome = strtoupper(trim($nome??'')); if(strlen($nome)<2) $nome="ND";
                if(isset($cache[$tab][$nome])) return $cache[$tab][$nome];
                $s=$pdo->prepare("SELECT id FROM $tab WHERE nome=? LIMIT 1"); $s->execute([$nome]);
                if($r=$s->fetch()){ $cache[$tab][$nome]=$r['id']; return $r['id']; }
                $pdo->prepare("INSERT INTO $tab (nome) VALUES (?)")->execute([$nome]);
                return $pdo->lastInsertId();
            }

            $sqlInsert = $pdo->prepare("INSERT INTO pedidos (obra_id, empresa_id, numero_of, comprador_id, data_pedido, data_entrega, historia, fornecedor_id, material_id, qtd_pedida, valor_unitario, valor_bruto_pedido, qtd_recebida, valor_total_rec, dt_baixa, forma_pagamento, cotacao) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $ok=0; $err=0;
            $pdo->beginTransaction();
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<5) continue;
                $codEmp = strtoupper(trim($c[0]));
                $codObra = strtoupper(trim($c[1]));
                if(stripos($codEmp, 'COD') !== false) continue;

                $idObra = $mapaObras[$codObra] ?? null;
                $idEmp = $mapaEmpresas[$codEmp] ?? null;

                if(!$idObra) { $err++; continue; }

                $of = trim($c[2]); 
                $comp = getIdRapido($pdo, 'compradores', $c[3], $cacheAux);
                $dtp = dataSQL($c[4]);
                $dte = dataSQL($c[5]);
                $hist = trim($c[6]);
                $forn = getIdRapido($pdo, 'fornecedores', $c[7], $cacheAux);
                $mat = getIdRapido($pdo, 'materiais', $c[8], $cacheAux);
                $qtd = limpaValor($c[9]);
                $unit = limpaValor($c[10]);
                $bruto = limpaValor($c[11]);
                $rec = limpaValor($c[12]);
                $vlr_rec = limpaValor($c[14]);
                $baixa = dataSQL($c[16]);
                $pgto = trim($c[17]);
                $cot = trim($c[18]);

                $sqlInsert->execute([$idObra, $idEmp, $of, $comp, $dtp, $dte, $hist, $forn, $mat, $qtd, $unit, $bruto, $rec, $vlr_rec, $baixa, $pgto, $cot]);
                $ok++;
            }
            $pdo->commit();
            $msg = "<div class='alert alert-success'>✅ <b>$ok</b> Pedidos importados!</div>";
        }
        elseif ($acao == 'limpar_pedidos') {
            $pdo->query("TRUNCATE TABLE pedidos");
            $msg = "<div class='alert alert-warning'>🗑️ Pedidos limpos!</div>";
        }

        // =================================================================
        // 5. IMPORTAR CLIENTES IMOBILIÁRIOS (NOVO!)
        // =================================================================
        elseif ($acao == 'importar_clientes_imob') {
            // ORDEM CONFIRMADA (11 Colunas):
            // 0:DT_INI | 1:DT_FIM | 2:DT_CONTRATO | 3:RAZAO_SOCIAL | 
            // 4:VENCIMENTO | 5:RECEBIMENTO | 6:VLR_PAGO | 7:TOTAL_ORIGINAL | 
            // 8:POSRECTO_FORNECEDOR (CÓDIGO) | 9:CLIENTE | 10:CPF

            $pdo->beginTransaction();
            $novos_clientes = 0;
            $novas_vendas = 0;
            $parcelas_proc = 0;

            // Prepared Statements
            $stmtBuscaCli = $pdo->prepare("SELECT id FROM clientes_imob WHERE nome = ? LIMIT 1");
            $stmtInsCli = $pdo->prepare("INSERT INTO clientes_imob (nome, cpf) VALUES (?, ?)");
            
            $stmtBuscaVenda = $pdo->prepare("SELECT id FROM vendas_imob WHERE codigo_compra = ? LIMIT 1");
            $stmtInsVenda = $pdo->prepare("INSERT INTO vendas_imob (cliente_id, codigo_compra, nome_casa, nome_empresa, data_inicio, data_fim, data_contrato, valor_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtUpdVenda = $pdo->prepare("UPDATE vendas_imob SET nome_empresa=?, data_inicio=?, data_fim=?, data_contrato=? WHERE id=?");

            $stmtBuscaParc = $pdo->prepare("SELECT id FROM parcelas_imob WHERE venda_id = ? AND data_vencimento = ? LIMIT 1");
            $stmtInsParc = $pdo->prepare("INSERT INTO parcelas_imob (venda_id, numero_parcela, data_vencimento, valor_parcela, data_pagamento, valor_pago) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtUpdParc = $pdo->prepare("UPDATE parcelas_imob SET data_pagamento=?, valor_pago=? WHERE id=?");

            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c) < 9) continue; 

                // MAPEAMENTO
                $dt_ini   = dataSQL($c[0]);
                $dt_fim   = dataSQL($c[1]);
                $dt_con   = dataSQL($c[2]);
                $empresa  = trim($c[3]);  // RAZÃO SOCIAL
                
                $dt_venc  = dataSQL($c[4]);
                $dt_pag   = dataSQL($c[5]);
                $vlr_pag  = limpaValor($c[6]); // RECEBIMENTO
                $vlr_orig = limpaValor($c[7]); // TOTAL ORIGINAL
                
                $cod_cont = trim($c[8]); // POSRECTO_FORNECEDOR -> CÓDIGO DO CONTRATO
                $nome_cli = strtoupper(trim($c[9])); // CLIENTE
                $cpf_cli  = trim($c[10] ?? ''); // CPF

                if(empty($cod_cont) || empty($nome_cli) || stripos($nome_cli, 'CLIENTE')!==false) continue;

                // 1. CLIENTE
                $stmtBuscaCli->execute([$nome_cli]);
                if($row = $stmtBuscaCli->fetch()) {
                    $cli_id = $row['id'];
                } else {
                    $stmtInsCli->execute([$nome_cli, $cpf_cli]);
                    $cli_id = $pdo->lastInsertId();
                    $novos_clientes++;
                }

                // 2. VENDA
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
                if($dt_venc) {
                    $stmtBuscaParc->execute([$venda_id, $dt_venc]);
                    if($rowParc = $stmtBuscaParc->fetch()) {
                        // Atualiza se tiver pagamento novo
                        if($vlr_pag > 0 || $dt_pag != null) {
                            $stmtUpdParc->execute([$dt_pag, $vlr_pag, $rowParc['id']]);
                        }
                    } else {
                        // Cria nova
                        $stmtInsParc->execute([$venda_id, 0, $dt_venc, $vlr_orig, $dt_pag, $vlr_pag]);
                    }
                    $parcelas_proc++;
                }
            }
            
            // Recalcula total do contrato
            $pdo->query("UPDATE vendas_imob v SET valor_total = (SELECT SUM(valor_parcela) FROM parcelas_imob p WHERE p.venda_id = v.id)");

            $pdo->commit();
            $msg = "<div class='alert alert-success'>
                        ✅ <b>Importação Concluída!</b><br>
                        - Clientes: $novos_clientes<br>
                        - Contratos: $novas_vendas<br>
                        - Parcelas: $parcelas_proc
                    </div>";
        }
        
        elseif ($acao == 'limpar_imob') {
            $pdo->query("SET FOREIGN_KEY_CHECKS=0");
            $pdo->query("TRUNCATE TABLE parcelas_imob");
            $pdo->query("TRUNCATE TABLE vendas_imob");
            $pdo->query("TRUNCATE TABLE clientes_imob");
            $pdo->query("SET FOREIGN_KEY_CHECKS=1");
            $msg = "<div class='alert alert-warning'>🗑️ Módulo Imobiliário zerado!</div>";
        }

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Erro crítico: ".$e->getMessage()."</div>";
    }
}
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-primary"><i class="bi bi-cloud-arrow-up-fill"></i> CENTRAL DE IMPORTAÇÕES</h3>
        <div><?php echo $msg; ?></div>
    </div>

    <ul class="nav nav-tabs" id="tabsImport" role="tablist">
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='empresas'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-empresas">1. 🏢 Empresas</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='obras'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-obras">2. 🏗️ Obras</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='contratos'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-contratos">3. 📄 Contratos</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='pedidos'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-pedidos">4. 📦 Pedidos (Mestre)</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='imob'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-imob">5. 🏠 Clientes Imob</button></li>
    </ul>

    <div class="tab-content p-4 border border-top-0 bg-white shadow-sm">
        
        <div class="tab-pane fade <?php echo $aba_ativa=='empresas'?'show active':''; ?>" id="tab-empresas">
            <div class="alert alert-info py-2">Cole: <b>COD EMP | RAZÃO SOCIAL</b></div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_empresas">
                <input type="hidden" name="aba_origem" value="empresas">
                <textarea name="dados" class="form-control mb-3" rows="10"></textarea>
                <div class="d-flex gap-2"><button class="btn btn-primary w-100">SALVAR EMPRESAS</button><button type="submit" name="acao" value="limpar_empresas" class="btn btn-outline-danger" onclick="return confirm('Zerar tudo?')">LIMPAR</button></div>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='obras'?'show active':''; ?>" id="tab-obras">
            <div class="alert alert-info py-2">Cole: <b>COD OBRA | NOME OBRA</b></div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_obras">
                <input type="hidden" name="aba_origem" value="obras">
                <textarea name="dados" class="form-control mb-3" rows="10"></textarea>
                <div class="d-flex gap-2"><button class="btn btn-primary w-100">SALVAR OBRAS</button><button type="submit" name="acao" value="limpar_obras" class="btn btn-outline-danger" onclick="return confirm('Zerar tudo?')">LIMPAR</button></div>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='contratos'?'show active':''; ?>" id="tab-contratos">
            <div class="alert alert-info py-2">Cole: <b>FORNECEDOR | RESPONSÁVEL | VALOR | DATA</b></div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_contratos">
                <input type="hidden" name="aba_origem" value="contratos">
                <textarea name="dados" class="form-control mb-3" rows="10"></textarea>
                <button class="btn btn-warning w-100 fw-bold">PROCESSAR CONTRATOS</button>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='pedidos'?'show active':''; ?>" id="tab-pedidos">
            <div class="alert alert-dark py-2">Cole as 19 colunas (Códigos apenas)</div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_mestre">
                <input type="hidden" name="aba_origem" value="pedidos">
                <textarea name="dados" class="form-control mb-3" rows="15" style="font-family: monospace; font-size: 11px;"></textarea>
                <div class="d-flex gap-2"><button class="btn btn-dark w-100 fw-bold">🚀 PROCESSAR GERAL</button><button type="submit" name="acao" value="limpar_pedidos" class="btn btn-outline-danger" onclick="return confirm('Apagar tudo?')">LIMPAR TUDO</button></div>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='imob'?'show active':''; ?>" id="tab-imob">
            <div class="alert alert-primary py-2 small">
                <b>COLE AS 12 COLUNAS DO FINANCEIRO:</b><br>
                <code>DT INICIO | DT FIM | DT CONTRATO | COD EMP (Venda) | RAZÃO (Obra) | VENCIMENTO | RECEBIMENTO | VALOR PAGO | VALOR ORIGINAL | ... | CLIENTE | CPF</code>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_clientes_imob">
                <input type="hidden" name="aba_origem" value="imob">
                <textarea name="dados" class="form-control mb-3" rows="15" placeholder="Cole os dados do Excel aqui..." style="font-family: monospace; font-size: 11px;"></textarea>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary w-100 fw-bold">🚀 IMPORTAR FINANCEIRO IMOB</button>
                    <button type="submit" name="acao" value="limpar_imob" class="btn btn-outline-danger" onclick="return confirm('TEM CERTEZA? Vai apagar todos os clientes e parcelas!')">LIMPAR TUDO</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>