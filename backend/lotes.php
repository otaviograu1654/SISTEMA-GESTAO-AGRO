<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function garantirTabelaLotes(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL UNIQUE,
            descricao VARCHAR(255),
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function valorAntigoLote(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionadoLote(string $chave, string $valor, string $padrao = ''): string
{
    $atual = $_POST[$chave] ?? $padrao;

    return $atual === $valor ? 'selected' : '';
}

function formatarDataLote(?string $data): string
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
$lotes = [];
$resumo = [
    'total' => 0,
    'ativos' => 0,
    'inativos' => 0,
];

try {
    garantirTabelaLotes($pdo);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a estrutura de lotes.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'alterar_status') {
        $loteId = (int) ($_POST['lote_id'] ?? 0);
        $novoStatus = ($_POST['novo_status'] ?? '1') === '0' ? 0 : 1;

        if ($loteId <= 0) {
            $erro = 'Lote nao encontrado.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE lotes SET ativo = :ativo WHERE id = :id");
                $stmt->execute([
                    ':ativo' => $novoStatus,
                    ':id' => $loteId,
                ]);

                $sucesso = $novoStatus === 1 ? 'Lote ativado com sucesso.' : 'Lote desativado com sucesso.';
            } catch (PDOException $e) {
                $erro = 'Nao foi possivel alterar o status do lote.';
            }
        }
    } else {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;

    if ($nome === '') {
        $erro = 'Informe o nome do lote.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO lotes (nome, descricao, ativo)
                VALUES (:nome, :descricao, :ativo)
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':ativo' => $ativo,
            ]);

            $sucesso = 'Lote cadastrado com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Já existe um lote cadastrado com esse nome.';
            } else {
                error_log('Erro em lotes.php ao cadastrar: ' . $e->getMessage());
                $erro = 'Nao foi possivel cadastrar o lote agora.';
            }
        }
    }
    }
}

if ($erro === '') {
    try {
        $stmtResumo = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativos,
                SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) AS inativos
            FROM lotes
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'ativos' => (int) ($resumoDb['ativos'] ?? 0),
                'inativos' => (int) ($resumoDb['inativos'] ?? 0),
            ];
        }

        $stmtLotes = $pdo->query("
            SELECT id, nome, descricao, ativo, created_at
            FROM lotes
            ORDER BY nome ASC, id ASC
        ");
        $lotes = $stmtLotes->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar os lotes cadastrados.';
    }
}

layoutInicio('Lotes');
?>

<div class="page-header">
    <h1>Lotes</h1>
    <p>Cadastre os lotes usados para organizar o rebanho.</p>
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
        <h3>Total de lotes</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Ativos</h3>
        <div class="value"><?= $resumo['ativos'] ?></div>
    </div>
    <div class="card">
        <h3>Inativos</h3>
        <div class="value"><?= $resumo['inativos'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Novo lote</h2>
        <p>Cadastre um lote para seleção no animal.</p>

        <form method="POST" action="">
            <input type="hidden" name="acao" value="cadastrar">

            <div class="form-group full-width">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= valorAntigoLote('nome') ?>" required>
            </div>

            <div class="form-group">
                <label for="ativo">Status</label>
                <select id="ativo" name="ativo" required>
                    <option value="1" <?= selecionadoLote('ativo', '1', '1') ?>>Ativo</option>
                    <option value="0" <?= selecionadoLote('ativo', '0', '1') ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="3"><?= valorAntigoLote('descricao') ?></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar lote</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Uso nos animais</h2>
        <p>Animais cadastrados e editados passam a usar estes lotes como opção de seleção.</p>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Cadastro de animais</th>
                        <td>Lotes ativos aparecem como opção de seleção.</td>
                    </tr>
                    <tr>
                        <th>Organização</th>
                        <td>Ajuda a separar o rebanho por manejo, idade, finalidade ou local.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel panel-spaced">
    <h2>Lotes cadastrados</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Cadastro</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lotes)): ?>
                    <tr>
                        <td colspan="5">Nenhum lote cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lotes as $lote): ?>
                        <tr>
                            <td><?= htmlspecialchars($lote['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($lote['descricao'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= ((int) $lote['ativo'] === 1) ? 'badge-sucesso' : 'badge-erro' ?>">
                                    <?= ((int) $lote['ativo'] === 1) ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(formatarDataLote($lote['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="POST" action="" class="form-inline">
                                    <input type="hidden" name="acao" value="alterar_status">
                                    <input type="hidden" name="lote_id" value="<?= (int) $lote['id'] ?>">
                                    <input type="hidden" name="novo_status" value="<?= ((int) $lote['ativo'] === 1) ? '0' : '1' ?>">
                                    <button type="submit" class="btn-tabela">
                                        <?= ((int) $lote['ativo'] === 1) ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
