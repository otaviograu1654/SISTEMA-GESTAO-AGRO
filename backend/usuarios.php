<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

exigirGerenciarUsuarios();

function colunaUsuarioExiste(PDO $pdo, string $coluna): bool
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
            criado_por_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaUsuarioExiste($pdo, 'ativo')) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }

    if (!colunaUsuarioExiste($pdo, 'criado_por_id')) {
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

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET nome = CASE WHEN nome = 'Administrador' THEN 'Desenvolvedor' ELSE nome END,
            perfil = 'Desenvolvedor'
        WHERE email = 'admin@sga.local'
          AND perfil = 'Administrador'
    ");
    $stmt->execute();
}

function valorAntigoUsuario(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionadoUsuario(string $chave, string $valor): string
{
    return (($_POST[$chave] ?? '') === $valor) ? 'selected' : '';
}

function perfisPermitidosParaCadastro(): array
{
    if (usuarioEhDesenvolvedor()) {
        return ['Fazendeiro', 'Funcionario'];
    }

    if (usuarioEhFazendeiro()) {
        return ['Funcionario'];
    }

    return [];
}

function perfisPermitidosParaEdicao(array $usuario): array
{
    if (in_array($usuario['perfil'], ['Desenvolvedor', 'Administrador'], true)) {
        return ['Desenvolvedor'];
    }

    return perfisPermitidosParaCadastro();
}

function usuarioPodeAlterarRegistro(array $usuario): bool
{
    if (usuarioEhDesenvolvedor()) {
        return true;
    }

    if (usuarioEhFazendeiro()) {
        return $usuario['perfil'] === 'Funcionario'
            && (int) ($usuario['criado_por_id'] ?? 0) === usuarioAtualId();
    }

    return false;
}

function contarDesenvolvedoresAtivos(PDO $pdo): int
{
    return (int) $pdo->query("
        SELECT COUNT(*)
        FROM usuarios
        WHERE ativo = 1
          AND perfil IN ('Desenvolvedor', 'Administrador')
    ")->fetchColumn();
}

function buscarUsuario(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, nome, email, perfil, ativo, criado_por_id
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($usuario) ? $usuario : null;
}

function permissoesPostadas(): array
{
    $permitidos = array_keys(modulosSistema());
    $selecionadas = $_POST['permissoes'] ?? [];

    if (!is_array($selecionadas)) {
        return [];
    }

    return array_values(array_intersect($permitidos, $selecionadas));
}

function salvarPermissoesUsuario(PDO $pdo, int $usuarioId, array $permissoes): void
{
    $stmt = $pdo->prepare("
        INSERT INTO usuario_permissoes (usuario_id, modulo, permitido)
        VALUES (:usuario_id, :modulo, :permitido)
        ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)
    ");

    foreach (modulosSistema() as $modulo => $rotulo) {
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':modulo' => $modulo,
            ':permitido' => in_array($modulo, $permissoes, true) ? 1 : 0,
        ]);
    }
}

function permissoesUsuario(PDO $pdo, int $usuarioId): array
{
    $stmt = $pdo->prepare("
        SELECT modulo
        FROM usuario_permissoes
        WHERE usuario_id = :usuario_id
          AND permitido = 1
        ORDER BY modulo
    ");
    $stmt->execute([':usuario_id' => $usuarioId]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function formatarPermissoesUsuario(array $permissoes): string
{
    $modulos = modulosSistema();
    $rotulos = [];

    foreach ($permissoes as $modulo) {
        if (isset($modulos[$modulo])) {
            $rotulos[] = $modulos[$modulo];
        }
    }

    return empty($rotulos) ? '-' : implode(', ', $rotulos);
}

$erro = '';
$sucesso = '';
$usuarios = [];
$perfisPermitidos = perfisPermitidosParaCadastro();
$resumo = [
    'total' => 0,
    'ativos' => 0,
    'fazendeiros' => 0,
    'funcionarios' => 0,
];

try {
    garantirTabelaUsuarios($pdo);
} catch (PDOException $e) {
    $erro = 'Nao foi possivel preparar a estrutura de usuarios.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'cadastrar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $perfil = trim($_POST['perfil'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');
        $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;
        $permissoes = $perfil === 'Funcionario' ? permissoesPostadas() : array_keys(modulosSistema());

        if ($nome === '' || $email === '' || $perfil === '' || $senha === '') {
            $erro = 'Preencha os campos obrigatorios: nome, email, perfil e senha.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um email valido.';
        } elseif (!in_array($perfil, $perfisPermitidos, true)) {
            $erro = 'Seu usuario nao pode cadastrar esse perfil.';
        } elseif (strlen($senha) < 4) {
            $erro = 'A senha deve ter pelo menos 4 caracteres.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, perfil, senha_hash, ativo, criado_por_id)
                    VALUES (:nome, :email, :perfil, :senha_hash, :ativo, :criado_por_id)
                ");

                $stmt->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':perfil' => $perfil,
                    ':senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                    ':ativo' => $ativo,
                    ':criado_por_id' => usuarioAtualId(),
                ]);

                salvarPermissoesUsuario($pdo, (int) $pdo->lastInsertId(), $permissoes);

                $sucesso = 'Usuario cadastrado com sucesso.';
                $_POST = [];
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $erro = 'Ja existe um usuario cadastrado com esse email.';
                } else {
                    $erro = 'Erro ao cadastrar usuario: ' . $e->getMessage();
                }
            }
        }
    } elseif (in_array($acao, ['ativar', 'desativar', 'excluir', 'salvar_permissoes', 'editar_usuario', 'redefinir_senha'], true)) {
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $usuario = $usuarioId > 0 ? buscarUsuario($pdo, $usuarioId) : null;

        if (!$usuario) {
            $erro = 'Usuario nao encontrado.';
        } elseif (!usuarioPodeAlterarRegistro($usuario)) {
            $erro = 'Voce nao tem permissao para alterar esse usuario.';
        } elseif ($usuarioId === usuarioAtualId()) {
            $erro = 'Voce nao pode alterar o status ou excluir o seu proprio acesso.';
        } elseif ($acao === 'salvar_permissoes' && $usuario['perfil'] !== 'Funcionario') {
            $erro = 'Permissoes finas sao usadas apenas para funcionarios.';
        } elseif ($acao === 'editar_usuario') {
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $perfil = trim($_POST['perfil'] ?? '');
            $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;
            $perfisEdicao = perfisPermitidosParaEdicao($usuario);

            if ($nome === '' || $email === '' || $perfil === '') {
                $erro = 'Preencha nome, email e perfil para editar o usuario.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Informe um email valido.';
            } elseif (!in_array($perfil, $perfisEdicao, true)) {
                $erro = 'Seu usuario nao pode editar para esse perfil.';
            } elseif (in_array($usuario['perfil'], ['Desenvolvedor', 'Administrador'], true)
                && contarDesenvolvedoresAtivos($pdo) <= 1
                && $ativo === 0
            ) {
                $erro = 'Nao e permitido desativar o ultimo desenvolvedor ativo.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios
                        SET nome = :nome,
                            email = :email,
                            perfil = :perfil,
                            ativo = :ativo
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':nome' => $nome,
                        ':email' => $email,
                        ':perfil' => $perfil,
                        ':ativo' => $ativo,
                        ':id' => $usuarioId,
                    ]);
                    $sucesso = 'Usuario atualizado com sucesso.';
                } catch (PDOException $e) {
                    $erro = $e->getCode() === '23000'
                        ? 'Ja existe um usuario cadastrado com esse email.'
                        : 'Nao foi possivel editar o usuario.';
                }
            }
        } elseif ($acao === 'redefinir_senha') {
            $novaSenha = (string) ($_POST['nova_senha'] ?? '');

            if (!in_array($usuario['perfil'], ['Fazendeiro', 'Funcionario'], true)) {
                $erro = 'Redefinicao de senha disponivel apenas para fazendeiros e funcionarios.';
            } elseif (strlen($novaSenha) < 4) {
                $erro = 'A nova senha deve ter pelo menos 4 caracteres.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id");
                    $stmt->execute([
                        ':senha_hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
                        ':id' => $usuarioId,
                    ]);
                    $sucesso = 'Senha redefinida com sucesso.';
                } catch (PDOException $e) {
                    $erro = 'Nao foi possivel redefinir a senha.';
                }
            }
        } elseif (in_array($usuario['perfil'], ['Desenvolvedor', 'Administrador'], true)
            && contarDesenvolvedoresAtivos($pdo) <= 1
            && in_array($acao, ['desativar', 'excluir'], true)
        ) {
            $erro = 'Nao e permitido remover o ultimo desenvolvedor ativo.';
        } else {
            try {
                if ($acao === 'salvar_permissoes') {
                    salvarPermissoesUsuario($pdo, $usuarioId, permissoesPostadas());
                    $sucesso = 'Permissoes atualizadas com sucesso.';
                } elseif ($acao === 'excluir') {
                    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                    $stmt->execute([':id' => $usuarioId]);
                    $sucesso = 'Usuario excluido com sucesso.';
                } else {
                    $novoStatus = $acao === 'ativar' ? 1 : 0;
                    $stmt = $pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id");
                    $stmt->execute([
                        ':ativo' => $novoStatus,
                        ':id' => $usuarioId,
                    ]);
                    $sucesso = $novoStatus === 1 ? 'Usuario ativado com sucesso.' : 'Usuario desativado com sucesso.';
                }
            } catch (PDOException $e) {
                $erro = 'Nao foi possivel concluir a acao no usuario.';
            }
        }
    }
}

if ($erro === '') {
    try {
        $filtroUsuarios = '';
        $params = [];

        if (usuarioEhFazendeiro() && !usuarioEhDesenvolvedor()) {
            $filtroUsuarios = "WHERE u.criado_por_id = :usuario_id OR u.id = :usuario_id";
            $params[':usuario_id'] = usuarioAtualId();
        }

        $stmtResumo = $pdo->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativos,
                SUM(CASE WHEN perfil = 'Fazendeiro' THEN 1 ELSE 0 END) AS fazendeiros,
                SUM(CASE WHEN perfil = 'Funcionario' THEN 1 ELSE 0 END) AS funcionarios
            FROM usuarios u
            {$filtroUsuarios}
        ");
        $stmtResumo->execute($params);
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'ativos' => (int) ($resumoDb['ativos'] ?? 0),
                'fazendeiros' => (int) ($resumoDb['fazendeiros'] ?? 0),
                'funcionarios' => (int) ($resumoDb['funcionarios'] ?? 0),
            ];
        }

        $stmtUsuarios = $pdo->prepare("
            SELECT
                u.id,
                u.nome,
                u.email,
                u.perfil,
                u.ativo,
                u.criado_por_id,
                u.created_at,
                criador.nome AS criado_por_nome
            FROM usuarios u
            LEFT JOIN usuarios criador ON criador.id = u.criado_por_id
            {$filtroUsuarios}
            ORDER BY u.id DESC
        ");
        $stmtUsuarios->execute($params);
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usuarios as &$usuario) {
            $usuario['permissoes'] = permissoesUsuario($pdo, (int) $usuario['id']);
        }
        unset($usuario);
    } catch (PDOException $e) {
        $erro = 'Nao foi possivel carregar os usuarios cadastrados.';
    }
}

layoutInicio('Usuarios');
?>

<div class="page-header">
    <h1>Usuarios</h1>
    <p>Controle quem entra no sistema e quem pode criar novos acessos.</p>
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
        <h3>Total de usuarios</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Usuarios ativos</h3>
        <div class="value"><?= $resumo['ativos'] ?></div>
    </div>
    <div class="card">
        <h3>Fazendeiros</h3>
        <div class="value"><?= $resumo['fazendeiros'] ?></div>
    </div>
    <div class="card">
        <h3>Funcionarios</h3>
        <div class="value"><?= $resumo['funcionarios'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Novo usuario</h2>
        <p>Desenvolvedor cadastra fazendeiros e funcionarios. Fazendeiro cadastra apenas seus funcionarios.</p>

        <form method="POST" action="">
            <input type="hidden" name="acao" value="cadastrar">

            <div class="form-group full-width">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= valorAntigoUsuario('nome') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= valorAntigoUsuario('email') ?>" required>
            </div>

            <div class="form-group">
                <label for="perfil">Perfil</label>
                <select id="perfil" name="perfil" required>
                    <option value="">Selecione</option>
                    <?php foreach ($perfisPermitidos as $perfil): ?>
                        <option value="<?= htmlspecialchars($perfil, ENT_QUOTES, 'UTF-8') ?>" <?= selecionadoUsuario('perfil', $perfil) ?>>
                            <?= htmlspecialchars($perfil, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ativo">Status</label>
                <select id="ativo" name="ativo" required>
                    <option value="1" <?= selecionadoUsuario('ativo', '1') ?: ((!isset($_POST['ativo'])) ? 'selected' : '') ?>>Ativo</option>
                    <option value="0" <?= selecionadoUsuario('ativo', '0') ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <div class="form-group full-width">
                <label>Permissoes do funcionario</label>
                <div class="checkbox-grid">
                    <?php foreach (modulosSistema() as $modulo => $rotulo): ?>
                        <?php $marcado = in_array($modulo, $_POST['permissoes'] ?? array_keys(modulosSistema()), true); ?>
                        <label class="check-option">
                            <input type="checkbox" name="permissoes[]" value="<?= htmlspecialchars($modulo, ENT_QUOTES, 'UTF-8') ?>" <?= $marcado ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="help">Para fazendeiro o sistema libera tudo. Para funcionario, marque somente os modulos permitidos.</p>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar usuario</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Regras de acesso</h2>
        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Desenvolvedor</th>
                        <td>Cria fazendeiros e funcionarios, ativa, desativa ou exclui usuarios.</td>
                    </tr>
                    <tr>
                        <th>Fazendeiro</th>
                        <td>Cria, ativa, desativa ou exclui somente funcionarios criados por ele.</td>
                    </tr>
                    <tr>
                        <th>Funcionario</th>
                        <td>Acessa o sistema, mas nao gerencia usuarios.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel panel-spaced">
    <h2>Usuarios cadastrados</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Criado por</th>
                    <th>Permissoes</th>
                    <th>Status</th>
                    <th>Cadastro</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8">Nenhum usuario cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php $podeAlterar = usuarioPodeAlterarRegistro($usuario) && (int) $usuario['id'] !== usuarioAtualId(); ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['perfil'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['criado_por_nome'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(formatarPermissoesUsuario($usuario['permissoes'] ?? []), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= ((int) $usuario['ativo'] === 1) ? 'badge-sucesso' : 'badge-erro' ?>">
                                    <?= ((int) $usuario['ativo'] === 1) ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($usuario['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($podeAlterar): ?>
                                    <div class="acoes-inline">
                                        <form method="POST" action="" class="form-inline permissoes-inline">
                                            <input type="hidden" name="acao" value="editar_usuario">
                                            <input type="hidden" name="usuario_id" value="<?= (int) $usuario['id'] ?>">
                                            <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?>" required>
                                            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                                            <select name="perfil" required>
                                                <?php foreach (perfisPermitidosParaEdicao($usuario) as $perfilOpcao): ?>
                                                    <option value="<?= htmlspecialchars($perfilOpcao, ENT_QUOTES, 'UTF-8') ?>" <?= $usuario['perfil'] === $perfilOpcao ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($perfilOpcao, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="ativo" required>
                                                <option value="1" <?= ((int) $usuario['ativo'] === 1) ? 'selected' : '' ?>>Ativo</option>
                                                <option value="0" <?= ((int) $usuario['ativo'] === 0) ? 'selected' : '' ?>>Inativo</option>
                                            </select>
                                            <button type="submit" class="btn-tabela">Salvar usuario</button>
                                        </form>
                                        <form method="POST" action="" class="form-inline">
                                            <input type="hidden" name="acao" value="redefinir_senha">
                                            <input type="hidden" name="usuario_id" value="<?= (int) $usuario['id'] ?>">
                                            <input type="password" name="nova_senha" placeholder="Nova senha" required>
                                            <button type="submit" class="btn-tabela">Redefinir senha</button>
                                        </form>
                                        <?php if ($usuario['perfil'] === 'Funcionario'): ?>
                                            <form method="POST" action="" class="form-inline permissoes-inline">
                                                <input type="hidden" name="acao" value="salvar_permissoes">
                                                <input type="hidden" name="usuario_id" value="<?= (int) $usuario['id'] ?>">
                                                <?php foreach (modulosSistema() as $modulo => $rotulo): ?>
                                                    <label class="check-mini">
                                                        <input type="checkbox" name="permissoes[]" value="<?= htmlspecialchars($modulo, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($modulo, $usuario['permissoes'] ?? [], true) ? 'checked' : '' ?>>
                                                        <span><?= htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                                <button type="submit" class="btn-tabela">Salvar permissoes</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="" class="form-inline">
                                            <input type="hidden" name="acao" value="<?= ((int) $usuario['ativo'] === 1) ? 'desativar' : 'ativar' ?>">
                                            <input type="hidden" name="usuario_id" value="<?= (int) $usuario['id'] ?>">
                                            <button type="submit" class="btn-tabela">
                                                <?= ((int) $usuario['ativo'] === 1) ? 'Desativar' : 'Ativar' ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="form-inline" onsubmit="return confirm('Excluir este usuario?');">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="usuario_id" value="<?= (int) $usuario['id'] ?>">
                                            <button type="submit" class="btn-tabela btn-perigo">Excluir</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    -
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
