<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auditoria.php';
require_once __DIR__ . '/db.php';
exigirPermissaoModulo('financeiro');

function garantirOrigemFinanceiro(PDO $pdo): void
{
    $stmtParceiro = $pdo->query("SHOW COLUMNS FROM financeiro LIKE 'parceiro_id'");

    if (!$stmtParceiro->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN parceiro_id INT NULL AFTER tipo");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM financeiro LIKE 'origem'");

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN origem VARCHAR(50) NULL AFTER parceiro_id");
    }
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: contas_a_pagar.php?erro=conta');
    exit;
}

try {
    garantirOrigemFinanceiro($pdo);

    $stmt = $pdo->prepare("SELECT * FROM tabelacontas WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conta) {
        header('Location: contas_a_pagar.php?erro=conta');
        exit;
    }

    if (($conta['status'] ?? '') === 'pago') {
        header('Location: contas_a_pagar.php?ja_paga=1');
        exit;
    }

    $pdo->beginTransaction();

    $stmtAtualizar = $pdo->prepare("UPDATE tabelacontas SET status = 'pago' WHERE id = :id");
    $stmtAtualizar->execute([':id' => $id]);

    $stmtFinanceiro = $pdo->prepare("
        INSERT INTO financeiro (
            tipo,
            origem,
            categoria,
            descricao,
            valor,
            data_lancamento
        ) VALUES (
            :tipo,
            :origem,
            :categoria,
            :descricao,
            :valor,
            :data_lancamento
        )
    ");

    $stmtFinanceiro->execute([
        ':tipo' => 'Despesa',
        ':origem' => 'Conta a pagar',
        ':categoria' => $conta['natureza'],
        ':descricao' => 'Pagamento de conta: ' . $conta['descricao'],
        ':valor' => $conta['valor'],
        ':data_lancamento' => date('Y-m-d'),
    ]);

    registrarAuditoria($pdo, 'Pagamento', 'Financeiro', $id, 'Conta paga: ' . $conta['descricao'] . ' - R$ ' . number_format((float) $conta['valor'], 2, ',', '.'));
    $pdo->commit();

    header('Location: contas_a_pagar.php?paga=1');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro em pagar_conta.php: ' . $e->getMessage());
    header('Location: contas_a_pagar.php?erro=pagar');
    exit;
}
