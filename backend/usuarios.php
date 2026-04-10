<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function garantirTabelaUsuarios(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            perfil VARCHAR(50) NOT NULL,
            senha_hash VARCHAR(255) NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function valorAntigo(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionado(string $chave, string $valor): string
{
    return (($_POST[$chave] ?? '') === $valor) ? 'selected' : '';
}

$erro = '';
$sucesso = '';
$usuarios = [];
$resumo = [
    'total' => 0,
    'ativos' => 0,
    'administradores' => 0,
];

try {
    garantirTabelaUsuarios($pdo);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a estrutura de usuários.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $perfil = trim($_POST['perfil'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;

    if ($nome === '' || $email === '' || $perfil === '' || $senha === '') {
        $erro = 'Preencha os campos obrigatórios: nome, email, perfil e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um email válido.';
    } elseif (strlen($senha) < 4) {
        $erro = 'A senha deve ter pelo menos 4 caracteres.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, perfil, senha_hash, ativo)
                VALUES (:nome, :email, :perfil, :senha_hash, :ativo)
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':perfil' => $perfil,
                ':senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                ':ativo' => $ativo,
            ]);

            $sucesso = 'Usuário cadastrado com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Já existe um usuário cadastrado com esse email.';
            } else {
                $erro = 'Erro ao cadastrar usuário: ' . $e->getMessage();
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
                SUM(CASE WHEN perfil = 'Administrador' THEN 1 ELSE 0 END) AS administradores
            FROM usuarios
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'ativos' => (int) ($resumoDb['ativos'] ?? 0),
                'administradores' => (int) ($resumoDb['administradores'] ?? 0),
            ];
        }

        $stmtUsuarios = $pdo->query("
            SELECT id, nome, email, perfil, ativo, created_at
            FROM usuarios
            ORDER BY id DESC
        ");
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar os usuários cadastrados.';
    }
}

layoutInicio('Usuários');
?>

<div class="page-header">
    <h1>Usuários</h1>
    <p>Gerenciamento básico de acesso ao sistema.</p>
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
        <h3>Total de usuários</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Usuários ativos</h3>
        <div class="value"><?= $resumo['ativos'] ?></div>
    </div>
    <div class="card">
        <h3>Administradores</h3>
        <div class="value"><?= $resumo['administradores'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Novo usuário</h2>
        <p>Cadastre quem poderá acessar ou operar o sistema.</p>

        <form method="POST" action="">
            <div class="form-group full-width">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= valorAntigo('nome') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= valorAntigo('email') ?>" required>
            </div>

            <div class="form-group">
                <label for="perfil">Perfil</label>
                <select id="perfil" name="perfil" required>
                    <option value="">Selecione</option>
                    <option value="Administrador" <?= selecionado('perfil', 'Administrador') ?>>Administrador</option>
                    <option value="Gestor" <?= selecionado('perfil', 'Gestor') ?>>Gestor</option>
                    <option value="Operador" <?= selecionado('perfil', 'Operador') ?>>Operador</option>
                </select>
            </div>

            <div class="form-group">
                <label for="ativo">Status</label>
                <select id="ativo" name="ativo" required>
                    <option value="1" <?= selecionado('ativo', '1') ?: ((!isset($_POST['ativo'])) ? 'selected' : '') ?>>Ativo</option>
                    <option value="0" <?= selecionado('ativo', '0') ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar usuário</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Usuários cadastrados</h2>
        <p>Lista dos usuários já registrados no sistema.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5">Nenhum usuário cadastrado ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($usuario['perfil'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= ((int) $usuario['ativo'] === 1) ? 'badge-sucesso' : 'badge-erro' ?>">
                                        <?= ((int) $usuario['ativo'] === 1) ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($usuario['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php layoutFim(); ?>
