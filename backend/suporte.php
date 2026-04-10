<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function garantirTabelaSuporte(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS suporte_chamados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_contato VARCHAR(150) NOT NULL,
            email_contato VARCHAR(150) NOT NULL,
            assunto VARCHAR(150) NOT NULL,
            mensagem TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Aberto',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function valorAntigo(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

$erro = '';
$sucesso = '';
$chamados = [];
$resumo = [
    'total' => 0,
    'abertos' => 0,
    'hoje' => 0,
];

try {
    garantirTabelaSuporte($pdo);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a estrutura de suporte.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $nomeContato = trim($_POST['nome_contato'] ?? '');
    $emailContato = trim($_POST['email_contato'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($nomeContato === '' || $emailContato === '' || $assunto === '' || $mensagem === '') {
        $erro = 'Preencha os campos obrigatórios: nome, email, assunto e mensagem.';
    } elseif (!filter_var($emailContato, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um email válido.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO suporte_chamados (
                    nome_contato,
                    email_contato,
                    assunto,
                    mensagem
                ) VALUES (
                    :nome_contato,
                    :email_contato,
                    :assunto,
                    :mensagem
                )
            ");

            $stmt->execute([
                ':nome_contato' => $nomeContato,
                ':email_contato' => $emailContato,
                ':assunto' => $assunto,
                ':mensagem' => $mensagem,
            ]);

            $sucesso = 'Chamado enviado com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            $erro = 'Erro ao registrar chamado: ' . $e->getMessage();
        }
    }
}

if ($erro === '') {
    try {
        $stmtResumo = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Aberto' THEN 1 ELSE 0 END) AS abertos,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS hoje
            FROM suporte_chamados
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'abertos' => (int) ($resumoDb['abertos'] ?? 0),
                'hoje' => (int) ($resumoDb['hoje'] ?? 0),
            ];
        }

        $stmtChamados = $pdo->query("
            SELECT id, nome_contato, email_contato, assunto, mensagem, status, created_at
            FROM suporte_chamados
            ORDER BY id DESC
        ");
        $chamados = $stmtChamados->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar os chamados de suporte.';
    }
}

layoutInicio('Suporte');
?>

<div class="page-header">
    <h1>Suporte</h1>
    <p>Ajuda, contato e registro de dúvidas do sistema.</p>
</div>

<?php if ($erro !== ''): ?>
    <div class="mensagem erro" style="display: block; margin-bottom: 16px;">
        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($sucesso !== ''): ?>
    <div class="mensagem sucesso" style="display: block; margin-bottom: 16px;">
        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="cards">
    <div class="card">
        <h3>Total de chamados</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Chamados abertos</h3>
        <div class="value"><?= $resumo['abertos'] ?></div>
    </div>
    <div class="card">
        <h3>Chamados hoje</h3>
        <div class="value"><?= $resumo['hoje'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Abrir chamado</h2>
        <p>Envie sua dúvida, dificuldade ou solicitação de ajuste.</p>

        <form method="POST" action="">
            <div class="form-group full-width">
                <label for="nome_contato">Nome</label>
                <input type="text" id="nome_contato" name="nome_contato" value="<?= valorAntigo('nome_contato') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="email_contato">Email</label>
                <input type="email" id="email_contato" name="email_contato" value="<?= valorAntigo('email_contato') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="assunto">Assunto</label>
                <input type="text" id="assunto" name="assunto" value="<?= valorAntigo('assunto') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="mensagem">Mensagem</label>
                <textarea id="mensagem" name="mensagem" rows="5" required><?= valorAntigo('mensagem') ?></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit">Enviar chamado</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Dúvidas frequentes</h2>
        <p>Orientações rápidas para os fluxos mais comuns do sistema.</p>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Como cadastrar um animal?</th>
                        <td>Use a tela de animais e preencha os dados obrigatórios de identificação.</td>
                    </tr>
                    <tr>
                        <th>Onde lançar uma venda?</th>
                        <td>Acesse a tela de vendas e registre a operação como receita.</td>
                    </tr>
                    <tr>
                        <th>Como registrar vacinação?</th>
                        <td>Abra a tela de vacinação, escolha o animal e salve a aplicação.</td>
                    </tr>
                    <tr>
                        <th>O que fazer se faltar uma categoria financeira?</th>
                        <td>Faça um lançamento financeiro com a nova categoria para ela aparecer no plano de contas.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="panel" style="margin-top: 24px;">
    <h2>Chamados registrados</h2>
    <p>Histórico dos chamados abertos no sistema.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Contato</th>
                    <th>Email</th>
                    <th>Assunto</th>
                    <th>Status</th>
                    <th>Mensagem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chamados)): ?>
                    <tr>
                        <td colspan="6">Nenhum chamado cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($chamados as $chamado): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($chamado['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($chamado['nome_contato'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($chamado['email_contato'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($chamado['assunto'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= $chamado['status'] === 'Aberto' ? 'badge-alerta' : 'badge-sucesso' ?>">
                                    <?= htmlspecialchars($chamado['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= nl2br(htmlspecialchars($chamado['mensagem'], ENT_QUOTES, 'UTF-8')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php layoutFim(); ?>
