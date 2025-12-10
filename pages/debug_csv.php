<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste de Leitura CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
    <div class="card shadow p-4">
        <h3>🕵️ Teste de Leitura do CSV</h3>
        <p>Selecione o seu arquivo abaixo para vermos como ele está formatado.</p>
        
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <input type="file" name="arquivo_teste" class="form-control mb-3" required>
            <button type="submit" class="btn btn-primary">Analisar Arquivo</button>
        </form>

        <?php
        if (isset($_FILES['arquivo_teste'])) {
            $arquivo = $_FILES['arquivo_teste']['tmp_name'];
            
            if (($handle = fopen($arquivo, "r")) !== FALSE) {
                // Lê a primeira linha
                $linha1 = fgets($handle);
                rewind($handle); // Volta para o começo

                echo "<hr>";
                echo "<h5>🔍 O que tem dentro do arquivo (Texto Bruto):</h5>";
                echo "<pre style='background:#222; color:#0f0; padding:10px;'>$linha1</pre>";

                // TESTE COM PONTO E VÍRGULA
                $csv_pv = fgetcsv($handle, 0, ";");
                $colunas_pv = count($csv_pv);

                rewind($handle); // Volta para o começo
                
                // TESTE COM VÍRGULA
                $csv_v = fgetcsv($handle, 0, ",");
                $colunas_v = count($csv_v);

                echo "<h5>📊 Diagnóstico:</h5>";
                
                if ($colunas_pv > $colunas_v) {
                    echo "<div class='alert alert-success'>✅ <b>ESTÁ CERTO!</b> O arquivo usa <b>PONTO E VÍRGULA (;)</b>.<br>Detectamos <b>$colunas_pv</b> colunas.</div>";
                    echo "<b>Exemplo de leitura correta:</b><br>";
                    echo "<pre>"; print_r($csv_pv); echo "</pre>";
                } else {
                    echo "<div class='alert alert-danger'>❌ <b>ESTÁ ERRADO!</b> O arquivo usa <b>VÍRGULA (,)</b>.<br>O sistema espera PONTO E VÍRGULA (;).</div>";
                    echo "<p><b>Como corrigir no Excel:</b><br>
                    1. Vá em Salvar Como.<br>
                    2. Escolha o formato: <b>CSV (MS-DOS)</b> ou CSV (separado por ponto e vírgula).<br>
                    3. NÃO use 'CSV (UTF-8)' se o seu Excel estiver configurado para inglês.</p>";
                    
                    echo "<b>Como o sistema está tentando ler (e falhando):</b><br>";
                    echo "<pre>"; print_r($csv_pv); echo "</pre>";
                }
                
                fclose($handle);
            }
        }
        ?>
    </div>
</body>
</html>