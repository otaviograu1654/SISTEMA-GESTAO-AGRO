<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auditoria.php';
require_once __DIR__ . '/db.php';
exigirPermissaoModulo('financeiro');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$descricao = trim($_POST['descricao'] ?? '');
$valor = $_POST['valor'] ?? 0;
$data_vencimento = $_POST['data_vencimento'] ?? '';
$natureza = trim($_POST['natureza'] ?? '');
$prioridade = $_POST['prioridade'] ?? 'baixa';

if (empty($descricao) || empty($data_vencimento) || !is_numeric($valor) || (float) $valor <= 0) {
    header("Location: contas_a_pagar.php?erro=campos");
    exit();
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

registrarAuditoria($pdo, 'Criacao', 'Financeiro', (int) $pdo->lastInsertId(), 'Conta a pagar criada: ' . $descricao);

header("Location: contas_a_pagar.php?sucesso=1");
exit();
} catch (PDOException $e) {
    error_log('Erro em salvar_conta.php: ' . $e->getMessage());
    header("Location: contas_a_pagar.php?erro=salvar");
    exit();
}    
} else {
    header("Location: contas_a_pagar.php");
    exit();
}
