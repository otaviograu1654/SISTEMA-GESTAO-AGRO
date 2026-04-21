<?php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: contas_a_pagar.php?erro=conta');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM tabelacontas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: contas_a_pagar.php?excluida=1');
    exit;
} catch (PDOException $e) {
    die('Erro ao excluir conta: ' . $e->getMessage());
}
