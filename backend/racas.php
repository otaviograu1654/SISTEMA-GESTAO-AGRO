<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function garantirTabelaRacas(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS racas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL UNIQUE,
            descricao VARCHAR(255),
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function valorAntigoRaca(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionadoRaca(string $chave, string $valor, string $padrao = ''): string
{
    $atual = $_POST[$chave] ?? $padrao;

    return $atual === $valor ? 'selected' : '';
}

function formatarDataRaca(?string $data): string
{
    if (!$data) {
        return '--';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return $data;
    }

    return date('d/m/Y', $timestamp);
}

$erro = '';
$sucesso = '';
$racas = [];
$resumo = [
    'total' => 0,
    'ativas' => 0,
    'inativas' => 0,
];

try {
    garantirTabelaRacas($pdo);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a estrutura de raças.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;

    if ($nome === '') {
        $erro = 'Informe o nome da raça.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO racas (nome, descricao, ativo)
                VALUES (:nome, :descricao, :ativo)
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':ativo' => $ativo,
            ]);

            $sucesso = 'Raça cadastrada com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Já existe uma raça cadastrada com esse nome.';
            } else {
                error_log('Erro em racas.php ao cadastrar: ' . $e->getMessage());
                $erro = 'Nao foi possivel cadastrar a raca agora.';
            }
        }
    }
}

if ($erro === '') {
    try {
        $stmtResumo = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativas,
                SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) AS inativas
            FROM racas
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'ativas' => (int) ($resumoDb['ativas'] ?? 0),
                'inativas' => (int) ($resumoDb['inativas'] ?? 0),
            ];
        }

        $stmtRacas = $pdo->query("
            SELECT id, nome, descricao, ativo, created_at
            FROM racas
            ORDER BY nome ASC, id ASC
        ");
        $racas = $stmtRacas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar as raças cadastradas.';
    }
}

layoutInicio('Raças');
?>

<div class="page-header">
    <h1>Raças</h1>
    <p>Cadastre as raças que serão usadas nos próximos passos do cadastro de animais.</p>
</div>

<?php if ($erro !== ''): ?>
    <div class="mensagem erro mensagem-bloco">
        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($sucesso !== ''): ?>
    <div class="mensagem sucesso mensagem-bloco">
        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="cards">
    <div class="card">
        <h3>Total de raças</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Ativas</h3>
        <div class="value"><?= $resumo['ativas'] ?></div>
    </div>
    <div class="card">
        <h3>Inativas</h3>
        <div class="value"><?= $resumo['inativas'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Nova raça</h2>
        <p>Cadastre uma raça para padronizar os animais posteriormente.</p>

        <form method="POST" action="">
            <div class="form-group full-width">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= valorAntigoRaca('nome') ?>" required>
            </div>

            <div class="form-group">
                <label for="ativo">Status</label>
                <select id="ativo" name="ativo" required>
                    <option value="1" <?= selecionadoRaca('ativo', '1', '1') ?>>Ativa</option>
                    <option value="0" <?= selecionadoRaca('ativo', '0', '1') ?>>Inativa</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="3"><?= valorAntigoRaca('descricao') ?></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar raça</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Uso nos animais</h2>
        <p>As raças ativas ficam disponíveis para padronizar o cadastro do rebanho.</p>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Cadastro de animais</th>
                        <td>Raças ativas aparecem como opção de seleção.</td>
                    </tr>
                    <tr>
                        <th>Padronização</th>
                        <td>Evita nomes duplicados ou digitados de formas diferentes.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel panel-spaced">
    <h2>Raças cadastradas</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Cadastro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($racas)): ?>
                    <tr>
                        <td colspan="4">Nenhuma raça cadastrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($racas as $raca): ?>
                        <tr>
                            <td><?= htmlspecialchars($raca['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($raca['descricao'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= ((int) $raca['ativo'] === 1) ? 'badge-sucesso' : 'badge-erro' ?>">
                                    <?= ((int) $raca['ativo'] === 1) ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(formatarDataRaca($raca['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
