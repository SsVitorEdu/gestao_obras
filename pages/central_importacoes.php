<?php
// CENTRAL DE IMPORTAÇÕES (TUDO EM UM)
ini_set('display_errors', 1);
set_time_limit(600); // 10 minutos de limite
ini_set('memory_limit', '1024M');

$db_path = __DIR__ . '/../includes/db.php';
if (file_exists($db_path)) include $db_path;
else include __DIR__ . '/../db.php';

$msg = "";
$aba_ativa = "empresas"; // Aba padrão

// --- PROCESSAMENTO DOS FORMULÁRIOS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $acao = $_POST['acao'];
    $aba_ativa = $_POST['aba_origem']; // Mantém a aba aberta após salvar
    $dados = $_POST['dados'] ?? '';
    $linhas = array_filter(explode("\n", $dados), 'trim');

    try {
        // =================================================================
        // 1. IMPORTAR EMPRESAS
        // =================================================================
        if ($acao == 'limpar_empresas') {
            $pdo->query("SET FOREIGN_KEY_CHECKS=0");
            $pdo->query("TRUNCATE TABLE empresas");
            $pdo->query("INSERT IGNORE INTO empresas (id, nome, codigo) VALUES (1, 'EMPRESA GERAL', '000')");
            $pdo->query("SET FOREIGN_KEY_CHECKS=1");
            $msg = "<div class='alert alert-warning'>🗑️ Empresas limpas!</div>";
        }
        elseif ($acao == 'importar_empresas') {
            $lista_unica = [];
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<2) continue;
                $cod = trim($c[0]); $nm = trim($c[1]);
                if(empty($cod) || $cod=='COD' || $cod=='COD. EMP') continue;
                $lista_unica[$cod] = $nm;
            }
            $sql = "INSERT INTO empresas (codigo, nome) VALUES (?, ?) ON DUPLICATE KEY UPDATE nome = VALUES(nome)";
            $stmt = $pdo->prepare($sql);
            $count=0;
            foreach($lista_unica as $c => $n) { $stmt->execute([$c, $n]); $count++; }
            $msg = "<div class='alert alert-success'>✅ $count empresas processadas!</div>";
        }

        // =================================================================
        // 2. IMPORTAR OBRAS
        // =================================================================
        elseif ($acao == 'limpar_obras') {
            $pdo->query("SET FOREIGN_KEY_CHECKS=0");
            $pdo->query("TRUNCATE TABLE obras");
            $pdo->query("INSERT IGNORE INTO obras (id, nome, codigo) VALUES (1, 'OBRA GERAL', '000')");
            $pdo->query("SET FOREIGN_KEY_CHECKS=1");
            $msg = "<div class='alert alert-warning'>🗑️ Obras limpas!</div>";
        }
        elseif ($acao == 'importar_obras') {
            $lista_unica = [];
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<2) continue;
                $cod = strtoupper(trim($c[0])); $nm = trim($c[1]);
                if(empty($cod) || $cod=='COD' || $cod=='COD OBRA') continue;
                $lista_unica[$cod] = $nm;
            }
            // Lógica: Verifica se existe. Se sim atualiza nome, se não cria (sem empresa vinculada)
            $check = $pdo->prepare("SELECT id FROM obras WHERE codigo = ?");
            $ins = $pdo->prepare("INSERT INTO obras (codigo, nome, empresa_id) VALUES (?, ?, NULL)");
            $upd = $pdo->prepare("UPDATE obras SET nome = ? WHERE id = ?");
            $novas=0; $upds=0;
            foreach($lista_unica as $c => $n) {
                $check->execute([$c]);
                if($id = $check->fetchColumn()) { $upd->execute([$n, $id]); $upds++; }
                else { $ins->execute([$c, $n]); $novas++; }
            }
            $msg = "<div class='alert alert-success'>✅ Obras: $novas novas, $upds atualizadas.</div>";
        }

        // =================================================================
        // 3. IMPORTAR CONTRATOS
        // =================================================================
        elseif ($acao == 'importar_contratos') {
            // Mapa de fornecedores
            $mapaForn = [];
            $q = $pdo->query("SELECT id, nome FROM fornecedores");
            while($r=$q->fetch()) $mapaForn[strtoupper(trim($r['nome']))] = $r['id'];

            $stmt = $pdo->prepare("INSERT INTO contratos (fornecedor_id, responsavel, valor, data_contrato) VALUES (?, ?, ?, ?)");
            $ok=0; $err=0;
            
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<3) continue;
                $nm = strtoupper(trim($c[0]));
                $resp = trim($c[1]);
                // Limpa valor
                $vlr = str_replace(['R$','.',','], ['','','.'], $c[2]); 
                // Data
                $dt = null;
                if(strpos($c[3]??'', '/')!==false) { $p=explode('/', $c[3]); $dt="{$p[2]}-{$p[1]}-{$p[0]}"; }
                
                if(isset($mapaForn[$nm])) { $stmt->execute([$mapaForn[$nm], $resp, (float)$vlr, $dt]); $ok++; }
                else $err++;
            }
            $msg = "<div class='alert alert-success'>✅ $ok contratos importados ($err não encontrados).</div>";
        }

        // =================================================================
        // 4. IMPORTAÇÃO MESTRE (V18 - PEDIDOS)
        // =================================================================
        elseif ($acao == 'limpar_pedidos') {
            $pdo->query("TRUNCATE TABLE pedidos");
            $msg = "<div class='alert alert-warning'>🗑️ Pedidos limpos!</div>";
        }
        elseif ($acao == 'importar_mestre') {
            // Carrega Mapas
            $mapaObras = []; 
            $q=$pdo->query("SELECT id, codigo FROM obras WHERE codigo IS NOT NULL");
            while($r=$q->fetch()) $mapaObras[strtoupper(trim($r['codigo']))] = $r['id'];
            
            $mapaEmpresas = [];
            $q=$pdo->query("SELECT id, codigo FROM empresas WHERE codigo IS NOT NULL");
            while($r=$q->fetch()) $mapaEmpresas[strtoupper(trim($r['codigo']))] = $r['id'];

            // Auxiliares
            $cacheAux = ['fornecedores'=>[], 'materiais'=>[], 'compradores'=>[]];
            function getIdRapido($pdo, $tab, $nome, &$cache) {
                $nome = strtoupper(trim($nome??'')); if(strlen($nome)<2) $nome="ND";
                if(isset($cache[$tab][$nome])) return $cache[$tab][$nome];
                $s=$pdo->prepare("SELECT id FROM $tab WHERE nome=? LIMIT 1"); $s->execute([$nome]);
                if($r=$s->fetch()){ $cache[$tab][$nome]=$r['id']; return $r['id']; }
                $pdo->prepare("INSERT INTO $tab (nome) VALUES (?)")->execute([$nome]);
                return $pdo->lastInsertId();
            }
            function limpaV($v){ return (float)str_replace(['.',','], ['','.'], preg_replace('/[^\d,.-]/','',$v)); }
            function dataS($v){ 
                $v=trim($v); 
                if(strpos($v,'/')!==false){ $p=explode('/',$v); return "{$p[2]}-{$p[1]}-{$p[0]}"; }
                if(is_numeric($v) && $v>20000) return date('Y-m-d',($v-25569)*86400);
                return null;
            }

            $sqlInsert = $pdo->prepare("INSERT INTO pedidos (obra_id, empresa_id, numero_of, comprador_id, data_pedido, data_entrega, historia, fornecedor_id, material_id, qtd_pedida, valor_unitario, valor_bruto_pedido, qtd_recebida, valor_total_rec, dt_baixa, forma_pagamento, cotacao) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $ok=0; $err=0;
            $pdo->beginTransaction();
            foreach($linhas as $l) {
                $c = explode("\t", $l);
                if(count($c)<5) continue;
                $codEmp = strtoupper(trim($c[0]));
                $codObra = strtoupper(trim($c[1]));
                if(strpos($codEmp,'COD')!==false) continue;

                $idObra = $mapaObras[$codObra] ?? null;
                $idEmp = $mapaEmpresas[$codEmp] ?? null;

                if(!$idObra) { $err++; continue; }

                $idForn = getIdRapido($pdo, 'fornecedores', $c[7]??'', $cacheAux);
                $idMat = getIdRapido($pdo, 'materiais', $c[8]??'', $cacheAux);
                $idComp = getIdRapido($pdo, 'compradores', $c[3]??'', $cacheAux);

                $sqlInsert->execute([
                    $idObra, $idEmp, $c[2], $idComp, dataS($c[4]), dataS($c[5]), $c[6],
                    $idForn, $idMat, limpaV($c[9]), limpaV($c[10]), limpaV($c[11]),
                    limpaV($c[12]), limpaV($c[14]), dataS($c[16]), $c[17], $c[18]
                ]);
                $ok++;
            }
            $pdo->commit();
            $msg = "<div class='alert alert-success'>✅ <b>$ok</b> Pedidos importados. ($err erros de obra).</div>";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Erro: ".$e->getMessage()."</div>";
    }
}
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-primary"><i class="bi bi-cloud-arrow-up-fill"></i> Central de Importações</h3>
        <div><?php echo $msg; ?></div>
    </div>

    <ul class="nav nav-tabs" id="tabImport" role="tablist">
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='empresas'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-empresas">1. 🏢 Empresas</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='obras'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-obras">2. 🏗️ Obras</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='contratos'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-contratos">3. 📄 Contratos</button></li>
        <li class="nav-item"><button class="nav-link <?php echo $aba_ativa=='pedidos'?'active':''; ?>" data-bs-toggle="tab" data-bs-target="#tab-pedidos">4. 📦 Pedidos (Mestre)</button></li>
    </ul>

    <div class="tab-content p-4 border border-top-0 bg-white shadow-sm">
        
        <div class="tab-pane fade <?php echo $aba_ativa=='empresas'?'show active':''; ?>" id="tab-empresas">
            <div class="alert alert-info py-2">Cole: <b>COD EMP | RAZÃO SOCIAL</b></div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_empresas">
                <input type="hidden" name="aba_origem" value="empresas">
                <textarea name="dados" class="form-control mb-3" rows="10" placeholder="001	GDA NEGOCIOS..."></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">SALVAR EMPRESAS</button>
                    <button type="submit" name="acao" value="limpar_empresas" class="btn btn-outline-danger" onclick="return confirm('Zerar empresas?')">LIMPAR</button>
                </div>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='obras'?'show active':''; ?>" id="tab-obras">
            <div class="alert alert-info py-2">Cole: <b>COD OBRA | NOME OBRA</b> (O sistema verifica código para não duplicar)</div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_obras">
                <input type="hidden" name="aba_origem" value="obras">
                <textarea name="dados" class="form-control mb-3" rows="10" placeholder="157	SHOPPING..."></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">SALVAR OBRAS</button>
                    <button type="submit" name="acao" value="limpar_obras" class="btn btn-outline-danger" onclick="return confirm('Zerar obras?')">LIMPAR</button>
                </div>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='contratos'?'show active':''; ?>" id="tab-contratos">
            <div class="alert alert-info py-2">Cole: <b>FORNECEDOR | RESPONSÁVEL | VALOR | DATA</b></div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_contratos">
                <input type="hidden" name="aba_origem" value="contratos">
                <textarea name="dados" class="form-control mb-3" rows="10" placeholder="Cola aqui..."></textarea>
                <button type="submit" class="btn btn-warning w-100">PROCESSAR CONTRATOS</button>
            </form>
        </div>

        <div class="tab-pane fade <?php echo $aba_ativa=='pedidos'?'show active':''; ?>" id="tab-pedidos">
            <div class="alert alert-dark py-2 small">
                <b>LAYOUT 19 COLUNAS (Sem nomes, só códigos):</b><br>
                <code>COD EMP | COD OBRA | OF | COMP | DT | DT | HIST | FORN | MAT | QTD | UNIT | BRUTO | REC | SALDO | VLRTOT | SALDO$ | BAIXA | PAG | COT</code>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="importar_mestre">
                <input type="hidden" name="aba_origem" value="pedidos">
                <textarea name="dados" class="form-control mb-3" rows="15" style="font-family: monospace; font-size: 11px;"></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">🚀 PROCESSAR GERAL</button>
                    <button type="submit" name="acao" value="limpar_pedidos" class="btn btn-outline-danger" onclick="return confirm('TEM CERTEZA? Vai apagar todos os pedidos do sistema!')">LIMPAR TUDO</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>