<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auditoria.php';
require_once __DIR__ . '/db.php';
exigirPermissaoModulo('financeiro');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: contas_a_pagar.php?erro=conta');
    exit;
}

try {
    $stmtConta = $pdo->prepare("SELECT descricao FROM tabelacontas WHERE id = :id LIMIT 1");
    $stmtConta->execute([':id' => $id]);
    $conta = $stmtConta->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("DELETE FROM tabelacontas WHERE id = :id");
    $stmt->execute([':id' => $id]);
    registrarAuditoria($pdo, 'Exclusao', 'Financeiro', $id, 'Conta excluida: ' . ($conta['descricao'] ?? 'ID ' . $id));

    header('Location: contas_a_pagar.php?excluida=1');
    exit;
} catch (PDOException $e) {
    error_log('Erro em excluir_conta.php: ' . $e->getMessage());
    header('Location: contas_a_pagar.php?erro=excluir');
    exit;
}
