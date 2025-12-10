<?php
include 'db.php';

if (isset($_FILES['arquivo_csv']) && $_FILES['arquivo_csv']['error'] == 0) {
    $arquivo = $_FILES['arquivo_csv']['tmp_name'];
    
    // Abre o arquivo para leitura
    if (($handle = fopen($arquivo, "r")) !== FALSE) {
        
        // Pula a primeira linha (cabeçalho)
        fgetcsv($handle, 1000, ";"); // Verifica se o separador do seu Excel é ";" ou ","

        // Prepara a inserção
        $stmt = $pdo->prepare("INSERT INTO fornecedores_resumo (nome_fornecedor, tipo_material, responsavel, valor_contrato, consumo_acumulado, saldo) VALUES (?, ?, ?, ?, ?, ?)");

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            // Mapeie aqui as colunas do seu CSV (Ex: Coluna A = 0, B = 1, etc)
            // Baseado no seu print "RESUMO":
            // Col 1 (B): Fornecedor, Col 2 (C): Material, Col 3 (D): Responsavel
            // Col 4 (E): Valor Contrato, Col 5 (F): Consumo
            
            // Tratamento simples de moeda (remove R$, pontos e troca virgula por ponto)
            $v_contrato = str_replace(',', '.', str_replace('.', '', str_replace('R$', '', trim($data[4])))); 
            $v_consumo = str_replace(',', '.', str_replace('.', '', str_replace('R$', '', trim($data[5]))));
            $v_saldo = $v_contrato - $v_consumo;

            $stmt->execute([
                $data[1], // Fornecedor
                $data[2], // Material
                $data[3], // Responsavel
                (float)$v_contrato,
                (float)$v_consumo,
                (float)$v_saldo
            ]);
        }
        fclose($handle);
        header("Location: index.php?status=sucesso");
    }
} else {
    echo "Erro no upload.";
}
?>