<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/animal_auditoria.php';
require_once __DIR__ . '/includes/layout.php';

garantirTabelaAuditoriaAnimal($pdo);

$erroPagina = '';
$alteracoes = [];

try {
    $stmt = $pdo->query("
        SELECT
            id,
            animal_id,
            brinco_referencia,
            nome_referencia,
            tipo_alteracao,
            descricao,
            created_at
        FROM animal_alteracoes
        ORDER BY id DESC
        LIMIT 100
    ");
    $alteracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar a auditoria dos animais.';
}

layoutInicio('Auditoria dos animais');
?>

<div class="page-header">
    <h1>Auditoria dos animais</h1>
    <p>Histórico das alterações feitas no cadastro dos animais.</p>
</div>

<div class="top-actions">
    <a href="animais.php" class="btn-link">Voltar para animais</a>
</div>

<?php if ($erroPagina !== ''): ?>
    <div class="mensagem erro mensagem-bloco">
        <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="panel">
    <h2>Últimas alterações</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Animal</th>
                    <th>Brinco</th>
                    <th>Descrição</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alteracoes)): ?>
                    <tr>
                        <td colspan="6">Nenhuma alteração registrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alteracoes as $alteracao): ?>
                        <tr>
                            <td><?= htmlspecialchars(formatarDataHoraAuditoria($alteracao['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(ucfirst((string) $alteracao['tipo_alteracao']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($alteracao['nome_referencia'] ?: 'Sem nome', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($alteracao['brinco_referencia'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($alteracao['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (!empty($alteracao['animal_id'])): ?>
                                    <a href="animal.php?id=<?= (int) $alteracao['animal_id'] ?>" class="btn-link">Ver animal</a>
                                <?php else: ?>
                                    <span class="help">Animal excluído</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
