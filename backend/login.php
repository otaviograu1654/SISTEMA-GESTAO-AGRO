<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

function colunaLoginExiste(PDO $pdo, string $coluna): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = :coluna
    ");
    $stmt->execute([':coluna' => $coluna]);

    return (int) $stmt->fetchColumn() > 0;
}

function garantirTabelaLoginUsuarios(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            perfil VARCHAR(50) NOT NULL,
            senha_hash VARCHAR(255) NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            criado_por_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaLoginExiste($pdo, 'ativo')) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }

    if (!colunaLoginExiste($pdo, 'criado_por_id')) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN criado_por_id INT NULL AFTER ativo");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuario_permissoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            modulo VARCHAR(50) NOT NULL,
            permitido TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY usuario_modulo (usuario_id, modulo),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )
    ");
}

function garantirAdminInicial(PDO $pdo): void
{
    $total = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

    if ($total > 0) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, perfil, senha_hash, ativo)
        VALUES (:nome, :email, :perfil, :senha_hash, 1)
    ");
    $stmt->execute([
        ':nome' => 'Desenvolvedor',
        ':email' => 'admin@sga.local',
        ':perfil' => 'Desenvolvedor',
        ':senha_hash' => password_hash('admin123', PASSWORD_DEFAULT),
    ]);
}

function migrarAdminInicialParaDesenvolvedor(PDO $pdo): void
{
    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET nome = CASE WHEN nome = 'Administrador' THEN 'Desenvolvedor' ELSE nome END,
            perfil = 'Desenvolvedor'
        WHERE email = 'admin@sga.local'
          AND perfil = 'Administrador'
    ");
    $stmt->execute();
}

function redirectSeguro(string $redirect): string
{
    $permitidos = [
        'dashboard.php',
        'animais.php',
        'parceiros.php',
        'racas.php',
        'lotes.php',
        'usuarios.php',
        'pesagens.php',
        'vacinacao.php',
        'producao_leite.php',
        'estoque.php',
        'plano_contas.php',
        'compras.php',
        'vendas.php',
        'lancamentos_vista.php',
        'contas_a_pagar.php',
        'fluxo_caixa.php',
        'suporte.php',
    ];

    return in_array($redirect, $permitidos, true) ? $redirect : 'dashboard.php';
}

$erro = '';
$redirect = redirectSeguro($_GET['redirect'] ?? 'dashboard.php');

try {
    garantirTabelaLoginUsuarios($pdo);
    garantirAdminInicial($pdo);
    migrarAdminInicialParaDesenvolvedor($pdo);
} catch (PDOException $e) {
    $erro = 'Nao foi possivel preparar o login.';
}

if (usuarioLogado()) {
    header('Location: ' . $redirect);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $erro === '') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    $redirect = redirectSeguro($_POST['redirect'] ?? 'dashboard.php');

    if ($email === '' || $senha === '') {
        $erro = 'Informe email e senha.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, nome, email, perfil, senha_hash, ativo
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || (int) $usuario['ativo'] !== 1 || !password_verify($senha, $usuario['senha_hash'])) {
            $erro = 'Email ou senha invalidos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            header('Location: ' . $redirect);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuaria - Login</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #eef4f0;
        }

        .login-panel {
            width: min(420px, calc(100% - 32px));
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 12px 35px rgba(31, 122, 63, 0.14);
        }

        .login-panel h1 {
            margin-bottom: 8px;
            color: #1f7a3f;
        }

        .login-panel p {
            margin-bottom: 20px;
            color: #5f6b76;
        }

        .login-panel form {
            display: grid;
            gap: 14px;
        }
    </style>
</head>
<body>
    <main class="login-panel">
        <h1>SGA Pecuaria</h1>
        <p>Entre para acessar o sistema da fazenda.</p>

        <?php if ($erro !== ''): ?>
            <div class="mensagem erro mensagem-bloco">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group full-width">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <div class="form-group full-width">
                <button type="submit">Entrar</button>
            </div>
        </form>

        <p class="help">Primeiro acesso do desenvolvedor: admin@sga.local / admin123. Depois cadastre os fazendeiros e usuarios reais.</p>
        <p class="help">Esqueci minha senha: solicite redefinicao ao responsavel pelo sistema.</p>
    </main>
</body>
</html>
