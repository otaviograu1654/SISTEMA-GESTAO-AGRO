<?php

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$descricao = trim($_POST['descricao'] ?? '');
$valor = $_POST['valor'] ?? 0;
$data_vencimento = $_POST['data_vencimento'] ?? '';
$natureza = trim($_POST['natureza'] ?? '');
$prioridade = $_POST['prioridade'] ?? 'baixa';

if (empty($descricao) || empty($data_vencimento) || $valor <= 0) {
    die("Eroo: Preencha todos os campos obrigatórios corretamente");
}
try {

$sql = "INSERT INTO tabelacontas 
(descricao,valor,data_vencimento,natureza,prioridade)
VALUES 
(:descricao,:valor,:data_vencimento,:natureza,:prioridade)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':descricao'       => $descricao,
    ':valor'           => $valor,
    ':data_vencimento' => $data_vencimento,
    ':natureza'        => $natureza,
    ':prioridade'      => $prioridade  
]);

header("Location: contas_a_pagar.php?sucesso=1");
exit();
} catch (PDOException $e) {
    die("Erro ao salvar no banco de dados: " . $e->getMessage());
}    
} else {
    header("Location: contas_a_pagar");
    exit();
}
