<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function garantirTabelaParceiros(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS parceiros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            documento VARCHAR(50),
            telefone VARCHAR(50),
            email VARCHAR(150),
            observacao VARCHAR(255),
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function valorAntigo(string $chave): string
{
    return htmlspecialchars($_POST[$chave] ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionado(string $chave, string $valor, string $padrao = ''): string
{
    $atual = $_POST[$chave] ?? $padrao;

    return $atual === $valor ? 'selected' : '';
}

function tipoParceiroValido(string $tipo): bool
{
    return in_array($tipo, ['Comprador', 'Fornecedor', 'Cliente', 'Prestador'], true);
}

function formatarDataParceiro(?string $data): string
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
$parceiros = [];
$resumo = [
    'total' => 0,
    'ativos' => 0,
    'compradores' => 0,
    'fornecedores' => 0,
    'clientes' => 0,
    'prestadores' => 0,
];

try {
    garantirTabelaParceiros($pdo);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a estrutura de parceiros.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $acao = $_POST['acao'] ?? 'cadastrar';

    if ($acao === 'alterar_status') {
        $parceiroId = (int) ($_POST['parceiro_id'] ?? 0);
        $novoStatus = ($_POST['novo_status'] ?? '1') === '0' ? 0 : 1;

        if ($parceiroId <= 0) {
            $erro = 'Parceiro nao encontrado.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE parceiros SET ativo = :ativo WHERE id = :id");
                $stmt->execute([
                    ':ativo' => $novoStatus,
                    ':id' => $parceiroId,
                ]);

                $sucesso = $novoStatus === 1 ? 'Parceiro ativado com sucesso.' : 'Parceiro desativado com sucesso.';
            } catch (PDOException $e) {
                $erro = 'Nao foi possivel alterar o status do parceiro.';
            }
        }
    } else {
    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');
    $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;

    if ($nome === '' || $tipo === '') {
        $erro = 'Preencha os campos obrigatórios: nome e tipo.';
    } elseif (!tipoParceiroValido($tipo)) {
        $erro = 'Selecione um tipo de parceiro válido.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um email válido ou deixe o campo em branco.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO parceiros (
                    nome,
                    tipo,
                    documento,
                    telefone,
                    email,
                    observacao,
                    ativo
                ) VALUES (
                    :nome,
                    :tipo,
                    :documento,
                    :telefone,
                    :email,
                    :observacao,
                    :ativo
                )
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':tipo' => $tipo,
                ':documento' => $documento !== '' ? $documento : null,
                ':telefone' => $telefone !== '' ? $telefone : null,
                ':email' => $email !== '' ? $email : null,
                ':observacao' => $observacao !== '' ? $observacao : null,
                ':ativo' => $ativo,
            ]);

            $sucesso = 'Parceiro cadastrado com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            error_log('Erro em parceiros.php ao cadastrar: ' . $e->getMessage());
            $erro = 'Nao foi possivel cadastrar o parceiro agora.';
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
                SUM(CASE WHEN tipo = 'Comprador' THEN 1 ELSE 0 END) AS compradores,
                SUM(CASE WHEN tipo = 'Fornecedor' THEN 1 ELSE 0 END) AS fornecedores,
                SUM(CASE WHEN tipo = 'Cliente' THEN 1 ELSE 0 END) AS clientes,
                SUM(CASE WHEN tipo = 'Prestador' THEN 1 ELSE 0 END) AS prestadores
            FROM parceiros
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total' => (int) ($resumoDb['total'] ?? 0),
                'ativos' => (int) ($resumoDb['ativos'] ?? 0),
                'compradores' => (int) ($resumoDb['compradores'] ?? 0),
                'fornecedores' => (int) ($resumoDb['fornecedores'] ?? 0),
                'clientes' => (int) ($resumoDb['clientes'] ?? 0),
                'prestadores' => (int) ($resumoDb['prestadores'] ?? 0),
            ];
        }

        $stmtParceiros = $pdo->query("
            SELECT id, nome, tipo, documento, telefone, email, observacao, ativo, created_at
            FROM parceiros
            ORDER BY id DESC
        ");
        $parceiros = $stmtParceiros->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar os parceiros cadastrados.';
    }
}

layoutInicio('Parceiros');
?>

<div class="page-header">
    <h1>Parceiros</h1>
    <p>Cadastre compradores, fornecedores, clientes e prestadores para usar nas próximas integrações do sistema.</p>
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
        <h3>Total de parceiros</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Ativos</h3>
        <div class="value"><?= $resumo['ativos'] ?></div>
    </div>
    <div class="card">
        <h3>Compradores</h3>
        <div class="value"><?= $resumo['compradores'] ?></div>
    </div>
    <div class="card">
        <h3>Fornecedores</h3>
        <div class="value"><?= $resumo['fornecedores'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Novo parceiro</h2>
        <p>Cadastre a pessoa ou empresa que participa das movimentações da fazenda.</p>

        <form method="POST" action="">
            <input type="hidden" name="acao" value="cadastrar">

            <div class="form-group full-width">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= valorAntigo('nome') ?>" required>
            </div>

            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione</option>
                    <option value="Comprador" <?= selecionado('tipo', 'Comprador') ?>>Comprador</option>
                    <option value="Fornecedor" <?= selecionado('tipo', 'Fornecedor') ?>>Fornecedor</option>
                    <option value="Cliente" <?= selecionado('tipo', 'Cliente') ?>>Cliente</option>
                    <option value="Prestador" <?= selecionado('tipo', 'Prestador') ?>>Prestador</option>
                </select>
            </div>

            <div class="form-group">
                <label for="ativo">Status</label>
                <select id="ativo" name="ativo" required>
                    <option value="1" <?= selecionado('ativo', '1', '1') ?>>Ativo</option>
                    <option value="0" <?= selecionado('ativo', '0', '1') ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group">
                <label for="documento">Documento</label>
                <input type="text" id="documento" name="documento" value="<?= valorAntigo('documento') ?>" placeholder="CPF, CNPJ ou IE">
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="<?= valorAntigo('telefone') ?>">
            </div>

            <div class="form-group full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= valorAntigo('email') ?>">
            </div>

            <div class="form-group full-width">
                <label for="observacao">Observação</label>
                <textarea id="observacao" name="observacao" rows="3"><?= valorAntigo('observacao') ?></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar parceiro</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Resumo por tipo</h2>
        <p>Distribuição dos parceiros cadastrados nesta fase.</p>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Compradores</th>
                        <td><?= $resumo['compradores'] ?></td>
                    </tr>
                    <tr>
                        <th>Fornecedores</th>
                        <td><?= $resumo['fornecedores'] ?></td>
                    </tr>
                    <tr>
                        <th>Clientes</th>
                        <td><?= $resumo['clientes'] ?></td>
                    </tr>
                    <tr>
                        <th>Prestadores</th>
                        <td><?= $resumo['prestadores'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel panel-spaced">
    <h2>Parceiros cadastrados</h2>
    <p>Lista geral para consulta. A integração com compras e vendas será feita nas próximas fases.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Cadastro</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parceiros)): ?>
                    <tr>
                        <td colspan="8">Nenhum parceiro cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($parceiros as $parceiro): ?>
                        <tr>
                            <td><?= htmlspecialchars($parceiro['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($parceiro['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($parceiro['documento'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($parceiro['telefone'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($parceiro['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= ((int) $parceiro['ativo'] === 1) ? 'badge-sucesso' : 'badge-erro' ?>">
                                    <?= ((int) $parceiro['ativo'] === 1) ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(formatarDataParceiro($parceiro['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="POST" action="" class="form-inline">
                                    <input type="hidden" name="acao" value="alterar_status">
                                    <input type="hidden" name="parceiro_id" value="<?= (int) $parceiro['id'] ?>">
                                    <input type="hidden" name="novo_status" value="<?= ((int) $parceiro['ativo'] === 1) ? '0' : '1' ?>">
                                    <button type="submit" class="btn-tabela">
                                        <?= ((int) $parceiro['ativo'] === 1) ? 'Desativar' : 'Ativar' ?>
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
