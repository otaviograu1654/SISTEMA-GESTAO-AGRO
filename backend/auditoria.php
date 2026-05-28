<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auditoria.php';

exigirLogin();

if (!usuarioEhDesenvolvedor() && !usuarioEhFazendeiro()) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

garantirTabelaAuditoria($pdo);

$filtroAcao = trim($_GET['acao'] ?? '');
$filtroEntidade = trim($_GET['entidade'] ?? '');
$filtroUsuario = trim($_GET['usuario'] ?? '');
$params = [];
$where = [];

if ($filtroAcao !== '') {
    $where[] = 'acao = :acao';
    $params[':acao'] = $filtroAcao;
}

if ($filtroEntidade !== '') {
    $where[] = 'entidade = :entidade';
    $params[':entidade'] = $filtroEntidade;
}

if ($filtroUsuario !== '') {
    $where[] = 'usuario_nome LIKE :usuario';
    $params[':usuario'] = '%' . $filtroUsuario . '%';
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtAuditoria = $pdo->prepare("
    SELECT *
    FROM auditoria_sistema
    {$sqlWhere}
    ORDER BY created_at DESC, id DESC
    LIMIT 150
");
$stmtAuditoria->execute($params);
$registros = $stmtAuditoria->fetchAll(PDO::FETCH_ASSOC);

$acoes = $pdo->query("SELECT DISTINCT acao FROM auditoria_sistema ORDER BY acao")->fetchAll(PDO::FETCH_COLUMN);
$entidades = $pdo->query("SELECT DISTINCT entidade FROM auditoria_sistema ORDER BY entidade")->fetchAll(PDO::FETCH_COLUMN);

layoutInicio('Auditoria');
?>

<div class="page-header">
    <h1>Auditoria</h1>
    <p>Consulte as principais acoes feitas no sistema, com usuario, data e modulo afetado.</p>
</div>

<section class="panel panel-spaced">
    <h2>Filtros</h2>

    <form method="get" class="form-grid">
        <div class="form-group">
            <label for="acao">Acao</label>
            <select id="acao" name="acao">
                <option value="">Todas</option>
                <?php foreach ($acoes as $acao): ?>
                    <option value="<?= htmlspecialchars($acao, ENT_QUOTES, 'UTF-8') ?>" <?= $filtroAcao === $acao ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acao, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="entidade">Modulo</label>
            <select id="entidade" name="entidade">
                <option value="">Todos</option>
                <?php foreach ($entidades as $entidade): ?>
                    <option value="<?= htmlspecialchars($entidade, ENT_QUOTES, 'UTF-8') ?>" <?= $filtroEntidade === $entidade ? 'selected' : '' ?>>
                        <?= htmlspecialchars($entidade, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($filtroUsuario, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit">Filtrar</button>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Registros recentes</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Usuario</th>
                    <th>Perfil</th>
                    <th>Acao</th>
                    <th>Modulo</th>
                    <th>ID</th>
                    <th>Descricao</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registros === []): ?>
                    <tr>
                        <td colspan="7">Nenhum registro de auditoria encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $registro): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime((string) $registro['created_at'])) ?></td>
                            <td><?= htmlspecialchars($registro['usuario_nome'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($registro['usuario_perfil'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($registro['acao'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($registro['entidade'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $registro['entidade_id'] !== null ? (int) $registro['entidade_id'] : '-' ?></td>
                            <td><?= htmlspecialchars($registro['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
