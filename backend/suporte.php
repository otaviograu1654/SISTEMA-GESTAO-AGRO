<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auditoria.php';

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

    $stmtResposta = $pdo->query("SHOW COLUMNS FROM suporte_chamados LIKE 'resposta'");

    if (!$stmtResposta->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE suporte_chamados ADD COLUMN resposta TEXT NULL AFTER mensagem");
    }

    $stmtRespondidoPor = $pdo->query("SHOW COLUMNS FROM suporte_chamados LIKE 'respondido_por_id'");

    if (!$stmtRespondidoPor->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE suporte_chamados ADD COLUMN respondido_por_id INT NULL AFTER resposta");
    }

    $stmtRespondidoEm = $pdo->query("SHOW COLUMNS FROM suporte_chamados LIKE 'respondido_em'");

    if (!$stmtRespondidoEm->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE suporte_chamados ADD COLUMN respondido_em DATETIME NULL AFTER respondido_por_id");
    }
}

function valorAntigo(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

function badgeChamado(string $status): string
{
    if (in_array($status, ['Respondido', 'Fechado'], true)) {
        return 'badge-sucesso';
    }

    if ($status === 'Em atendimento') {
        return 'badge-alerta';
    }

    return 'badge-erro';
}

$erro = '';
$sucesso = '';
$chamados = [];
$usuariosContexto = [];
$desenvolvedorSuporte = usuarioEhDesenvolvedor();
$resumo = [
    'total' => 0,
    'abertos' => 0,
    'em_atendimento' => 0,
    'respondidos' => 0,
    'fechados' => 0,
    'hoje' => 0,
];
$resumoUsuarios = [
    'total' => 0,
    'fazendeiros' => 0,
    'funcionarios' => 0,
    'ativos' => 0,
];

try {
    garantirTabelaSuporte($pdo);
} catch (PDOException $e) {
    $erro = 'Nao foi possivel preparar a estrutura de suporte.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $acao = $_POST['acao'] ?? 'abrir_chamado';

    if ($acao === 'atualizar_chamado') {
        $chamadoId = (int) ($_POST['chamado_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $resposta = trim($_POST['resposta'] ?? '');
        $statusPermitidos = ['Aberto', 'Em atendimento', 'Respondido', 'Fechado'];

        if (!$desenvolvedorSuporte) {
            $erro = 'Apenas desenvolvedor pode atualizar chamados.';
        } elseif ($chamadoId <= 0 || !in_array($status, $statusPermitidos, true)) {
            $erro = 'Informe um chamado e status validos.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE suporte_chamados
                    SET status = :status,
                        resposta = :resposta,
                        respondido_por_id = :respondido_por_id,
                        respondido_em = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':status' => $status,
                    ':resposta' => $resposta !== '' ? $resposta : null,
                    ':respondido_por_id' => usuarioAtualId(),
                    ':id' => $chamadoId,
                ]);
                registrarAuditoria($pdo, 'Atualizacao', 'Suporte', $chamadoId, 'Chamado atualizado para status: ' . $status);
                $sucesso = 'Chamado atualizado com sucesso.';
            } catch (PDOException $e) {
                $erro = 'Nao foi possivel atualizar o chamado.';
            }
        }
    } else {
        $nomeContato = trim($_POST['nome_contato'] ?? '');
        $emailContato = trim($_POST['email_contato'] ?? '');
        $assunto = trim($_POST['assunto'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');

        if ($nomeContato === '' || $emailContato === '' || $assunto === '' || $mensagem === '') {
            $erro = 'Preencha os campos obrigatorios: nome, email, assunto e mensagem.';
        } elseif (!filter_var($emailContato, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um email valido.';
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

                registrarAuditoria($pdo, 'Criacao', 'Suporte', (int) $pdo->lastInsertId(), 'Chamado aberto: ' . $assunto);
                $sucesso = 'Chamado enviado com sucesso.';
                $_POST = [];
            } catch (PDOException $e) {
                error_log('Erro em suporte.php ao registrar chamado: ' . $e->getMessage());
                $erro = 'Nao foi possivel registrar o chamado agora.';
            }
        }
    }
}

if ($erro === '') {
    try {
        $stmtResumo = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Aberto' THEN 1 ELSE 0 END) AS abertos,
                SUM(CASE WHEN status = 'Em atendimento' THEN 1 ELSE 0 END) AS em_atendimento,
                SUM(CASE WHEN status = 'Respondido' THEN 1 ELSE 0 END) AS respondidos,
                SUM(CASE WHEN status = 'Fechado' THEN 1 ELSE 0 END) AS fechados,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS hoje
            FROM suporte_chamados
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'abertos' => (int) ($resumoDb['abertos'] ?? 0),
                'em_atendimento' => (int) ($resumoDb['em_atendimento'] ?? 0),
                'respondidos' => (int) ($resumoDb['respondidos'] ?? 0),
                'fechados' => (int) ($resumoDb['fechados'] ?? 0),
                'hoje' => (int) ($resumoDb['hoje'] ?? 0),
            ];
        }

        $stmtChamados = $pdo->query("
            SELECT
                sc.id,
                sc.nome_contato,
                sc.email_contato,
                sc.assunto,
                sc.mensagem,
                sc.resposta,
                sc.status,
                sc.created_at,
                sc.respondido_em,
                u.nome AS respondido_por_nome
            FROM suporte_chamados sc
            LEFT JOIN usuarios u ON u.id = sc.respondido_por_id
            ORDER BY
                CASE sc.status
                    WHEN 'Aberto' THEN 0
                    WHEN 'Em atendimento' THEN 1
                    WHEN 'Respondido' THEN 2
                    ELSE 3
                END,
                sc.id DESC
        ");
        $chamados = $stmtChamados->fetchAll(PDO::FETCH_ASSOC);

        if ($desenvolvedorSuporte) {
            $stmtUsuariosResumo = $pdo->query("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN perfil = 'Fazendeiro' THEN 1 ELSE 0 END) AS fazendeiros,
                    SUM(CASE WHEN perfil = 'Funcionario' THEN 1 ELSE 0 END) AS funcionarios,
                    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS ativos
                FROM usuarios
            ");
            $resumoUsuariosDb = $stmtUsuariosResumo->fetch(PDO::FETCH_ASSOC);

            if (is_array($resumoUsuariosDb)) {
                $resumoUsuarios = [
                    'total' => (int) ($resumoUsuariosDb['total'] ?? 0),
                    'fazendeiros' => (int) ($resumoUsuariosDb['fazendeiros'] ?? 0),
                    'funcionarios' => (int) ($resumoUsuariosDb['funcionarios'] ?? 0),
                    'ativos' => (int) ($resumoUsuariosDb['ativos'] ?? 0),
                ];
            }

            $stmtUsuarios = $pdo->query("
                SELECT
                    u.nome,
                    u.email,
                    u.perfil,
                    u.ativo,
                    criador.nome AS criado_por_nome,
                    u.created_at
                FROM usuarios u
                LEFT JOIN usuarios criador ON criador.id = u.criado_por_id
                ORDER BY u.id DESC
                LIMIT 12
            ");
            $usuariosContexto = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $erro = 'Nao foi possivel carregar os dados de suporte.';
    }
}

layoutInicio($desenvolvedorSuporte ? 'Painel de suporte' : 'Suporte');
?>

<div class="page-header">
    <h1><?= $desenvolvedorSuporte ? 'Painel interno de suporte' : 'Suporte' ?></h1>
    <p><?= $desenvolvedorSuporte ? 'Acompanhe chamados, status e contexto de clientes sem misturar a operação da fazenda.' : 'Envie sua duvida, dificuldade ou solicitacao de ajuste.' ?></p>
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
        <h3>Total de chamados</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Chamados abertos</h3>
        <div class="value"><?= $resumo['abertos'] ?></div>
    </div>
    <div class="card">
        <h3>Em atendimento</h3>
        <div class="value"><?= $resumo['em_atendimento'] ?></div>
    </div>
    <div class="card">
        <h3>Chamados hoje</h3>
        <div class="value"><?= $resumo['hoje'] ?></div>
    </div>
</div>

<?php if ($desenvolvedorSuporte): ?>
    <div class="cards">
        <div class="card">
            <h3>Usuarios cadastrados</h3>
            <div class="value"><?= $resumoUsuarios['total'] ?></div>
        </div>
        <div class="card">
            <h3>Fazendeiros</h3>
            <div class="value"><?= $resumoUsuarios['fazendeiros'] ?></div>
        </div>
        <div class="card">
            <h3>Funcionarios</h3>
            <div class="value"><?= $resumoUsuarios['funcionarios'] ?></div>
        </div>
        <div class="card">
            <h3>Usuarios ativos</h3>
            <div class="value"><?= $resumoUsuarios['ativos'] ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if (!$desenvolvedorSuporte): ?>
<div class="grid-panels">
    <section class="panel">
        <h2>Abrir chamado</h2>
        <p>Envie sua duvida, dificuldade ou solicitacao de ajuste.</p>

        <form method="POST" action="">
            <input type="hidden" name="acao" value="abrir_chamado">

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
        <h2>Duvidas frequentes</h2>
        <p>Orientacoes rapidas para os fluxos mais comuns do sistema.</p>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Como cadastrar um animal?</th>
                        <td>Use a tela de animais e preencha os dados obrigatorios de identificacao.</td>
                    </tr>
                    <tr>
                        <th>Onde lancar uma venda?</th>
                        <td>Acesse a tela de vendas e registre a operacao como receita.</td>
                    </tr>
                    <tr>
                        <th>Como registrar vacinacao?</th>
                        <td>Abra a tela de vacinacao, escolha o animal e salve a aplicacao.</td>
                    </tr>
                    <tr>
                        <th>O que fazer se faltar uma categoria financeira?</th>
                        <td>Faca um lancamento financeiro com a nova categoria para ela aparecer no plano de contas.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php endif; ?>

<?php if ($desenvolvedorSuporte): ?>
<section class="panel panel-spaced">
    <h2>Chamados para atendimento</h2>
    <p>Atualize o status e registre uma resposta para o historico de suporte.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Contato</th>
                    <th>Assunto</th>
                    <th>Status</th>
                    <th>Mensagem</th>
                    <th>Resposta</th>
                    <th>Atualizar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chamados)): ?>
                    <tr>
                        <td colspan="7">Nenhum chamado cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($chamados as $chamado): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($chamado['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($chamado['nome_contato'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <?= htmlspecialchars($chamado['email_contato'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($chamado['assunto'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= badgeChamado((string) $chamado['status']) ?>">
                                    <?= htmlspecialchars($chamado['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= nl2br(htmlspecialchars($chamado['mensagem'], ENT_QUOTES, 'UTF-8')) ?></td>
                            <td>
                                <?= $chamado['resposta'] ? nl2br(htmlspecialchars($chamado['resposta'], ENT_QUOTES, 'UTF-8')) : '-' ?>
                                <?php if (!empty($chamado['respondido_por_nome'])): ?>
                                    <br><span class="help"><?= htmlspecialchars($chamado['respondido_por_nome'], ENT_QUOTES, 'UTF-8') ?> em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['respondido_em'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="" class="form-inline permissoes-inline">
                                    <input type="hidden" name="acao" value="atualizar_chamado">
                                    <input type="hidden" name="chamado_id" value="<?= (int) $chamado['id'] ?>">
                                    <select name="status" required>
                                        <?php foreach (['Aberto', 'Em atendimento', 'Respondido', 'Fechado'] as $statusOpcao): ?>
                                            <option value="<?= htmlspecialchars($statusOpcao, ENT_QUOTES, 'UTF-8') ?>" <?= $chamado['status'] === $statusOpcao ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($statusOpcao, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="resposta" rows="3" placeholder="Resposta ao chamado"><?= htmlspecialchars((string) ($chamado['resposta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <button type="submit" class="btn-tabela">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel panel-spaced">
    <h2>Contexto de clientes e usuarios</h2>
    <p>Visao rapida dos acessos cadastrados para apoiar o atendimento tecnico.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Criado por</th>
                    <th>Status</th>
                    <th>Cadastro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuariosContexto)): ?>
                    <tr>
                        <td colspan="6">Nenhum usuario cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuariosContexto as $usuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['perfil'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['criado_por_nome'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= (int) $usuario['ativo'] === 1 ? 'badge-sucesso' : 'badge-erro' ?>">
                                    <?= (int) $usuario['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
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
<?php endif; ?>

<?php layoutFim(); ?>
