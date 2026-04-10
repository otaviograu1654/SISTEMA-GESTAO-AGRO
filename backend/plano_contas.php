<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

$erroPagina = '';
$resumo = [
    'total_categorias' => 0,
    'categorias_receita' => 0,
    'categorias_despesa' => 0,
];
$categorias = [];

try {
    $stmtResumo = $pdo->query("
        SELECT
            COUNT(DISTINCT categoria) AS total_categorias,
            COUNT(DISTINCT CASE WHEN tipo = 'Receita' THEN categoria END) AS categorias_receita,
            COUNT(DISTINCT CASE WHEN tipo = 'Despesa' THEN categoria END) AS categorias_despesa
        FROM financeiro
        WHERE categoria IS NOT NULL
          AND categoria <> ''
    ");
    $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

    if (is_array($resumoDb)) {
        $resumo = [
            'total_categorias' => (int) ($resumoDb['total_categorias'] ?? 0),
            'categorias_receita' => (int) ($resumoDb['categorias_receita'] ?? 0),
            'categorias_despesa' => (int) ($resumoDb['categorias_despesa'] ?? 0),
        ];
    }

    $stmtCategorias = $pdo->query("
        SELECT
            categoria,
            COUNT(*) AS total_lancamentos,
            SUM(CASE WHEN tipo = 'Receita' THEN 1 ELSE 0 END) AS total_receitas,
            SUM(CASE WHEN tipo = 'Despesa' THEN 1 ELSE 0 END) AS total_despesas,
            SUM(CASE WHEN tipo = 'Receita' THEN valor ELSE 0 END) AS valor_receitas,
            SUM(CASE WHEN tipo = 'Despesa' THEN valor ELSE 0 END) AS valor_despesas
        FROM financeiro
        WHERE categoria IS NOT NULL
          AND categoria <> ''
        GROUP BY categoria
        ORDER BY categoria ASC
    ");
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar o plano de contas.';
}

function formatarMoeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

layoutInicio('Plano de contas');
?>

<div class="page-header">
    <h1>Plano de contas</h1>
    <p>Classificação das categorias financeiras já utilizadas no sistema.</p>
</div>

<?php if ($erroPagina !== ''): ?>
    <div class="mensagem erro" style="display: block; margin-bottom: 16px;">
        <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="cards">
    <div class="card">
        <h3>Total de categorias</h3>
        <div class="value"><?= $resumo['total_categorias'] ?></div>
    </div>
    <div class="card">
        <h3>Categorias de receita</h3>
        <div class="value"><?= $resumo['categorias_receita'] ?></div>
    </div>
    <div class="card">
        <h3>Categorias de despesa</h3>
        <div class="value"><?= $resumo['categorias_despesa'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Como o plano está sendo usado</h2>
        <p>As categorias abaixo são lidas diretamente da tabela financeira. Sempre que um lançamento recebe uma categoria, ele passa a compor este plano de contas.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Lançamentos</th>
                        <th>Receitas</th>
                        <th>Despesas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="4">Nenhuma categoria financeira cadastrada ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?= htmlspecialchars($categoria['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $categoria['total_lancamentos'] ?></td>
                                <td><?= (int) $categoria['total_receitas'] ?></td>
                                <td><?= (int) $categoria['total_despesas'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Resumo por categoria</h2>
        <p>Visão rápida dos valores movimentados por cada categoria.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Total em receitas</th>
                        <th>Total em despesas</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="4">Sem movimentações para resumir.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $categoria): ?>
                            <?php
                            $valorReceitas = (float) $categoria['valor_receitas'];
                            $valorDespesas = (float) $categoria['valor_despesas'];
                            $saldo = $valorReceitas - $valorDespesas;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($categoria['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatarMoeda($valorReceitas) ?></td>
                                <td><?= formatarMoeda($valorDespesas) ?></td>
                                <td><?= formatarMoeda($saldo) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php layoutFim(); ?>
