<?php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: contas_a_pagar.php?erro=conta');
    exit;
}

try {
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
            categoria,
            descricao,
            valor,
            data_lancamento
        ) VALUES (
            :tipo,
            :categoria,
            :descricao,
            :valor,
            :data_lancamento
        )
    ");

    $stmtFinanceiro->execute([
        ':tipo' => 'Despesa',
        ':categoria' => $conta['natureza'],
        ':descricao' => 'Pagamento de conta: ' . $conta['descricao'],
        ':valor' => $conta['valor'],
        ':data_lancamento' => date('Y-m-d'),
    ]);

    $pdo->commit();

    header('Location: contas_a_pagar.php?paga=1');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Erro ao pagar conta: ' . $e->getMessage());
}
