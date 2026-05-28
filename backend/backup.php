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

function garantirTabelaBackups(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backup_registros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            nome_arquivo VARCHAR(255) NOT NULL,
            caminho_arquivo VARCHAR(255) NOT NULL,
            tamanho_bytes BIGINT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");
}

function diretorioBackups(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
}

function nomeTabelaSeguro(string $tabela): string
{
    return '`' . str_replace('`', '``', $tabela) . '`';
}

function valorSql(PDO $pdo, mixed $valor): string
{
    if ($valor === null) {
        return 'NULL';
    }

    return $pdo->quote((string) $valor);
}

function tabelasParaBackup(PDO $pdo): array
{
    $tabelasPrincipais = [
        'animais',
        'animal_alteracoes',
        'parceiros',
        'racas',
        'lotes',
        'animal_vendas',
        'animal_obitos',
        'pesagens',
        'producao_leite',
        'manejos_sanitarios',
        'financeiro',
        'estoque_produtos',
        'estoque_movimentacoes',
        'tabelacontas',
        'usuarios',
        'usuario_permissoes',
        'suporte_chamados',
        'backup_registros',
    ];

    $existentes = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
    $nomesExistentes = array_map(static fn(array $linha): string => (string) $linha[0], $existentes);

    return array_values(array_intersect($tabelasPrincipais, $nomesExistentes));
}

function gerarSqlBackup(PDO $pdo, array $tabelas): string
{
    $linhas = [
        '-- Backup SGA Pecuaria',
        '-- Gerado em: ' . date('Y-m-d H:i:s'),
        'SET FOREIGN_KEY_CHECKS=0;',
        '',
    ];

    foreach ($tabelas as $tabela) {
        $nomeTabela = nomeTabelaSeguro($tabela);
        $stmtCreate = $pdo->query('SHOW CREATE TABLE ' . $nomeTabela);
        $create = $stmtCreate->fetch(PDO::FETCH_ASSOC);
        $sqlCreate = (string) ($create['Create Table'] ?? array_values($create)[1] ?? '');

        $linhas[] = '-- Estrutura da tabela ' . $tabela;
        $linhas[] = 'DROP TABLE IF EXISTS ' . $nomeTabela . ';';
        $linhas[] = $sqlCreate . ';';
        $linhas[] = '';

        $stmtDados = $pdo->query('SELECT * FROM ' . $nomeTabela);
        $primeiraLinha = $stmtDados->fetch(PDO::FETCH_ASSOC);

        if ($primeiraLinha === false) {
            $linhas[] = '-- Sem registros em ' . $tabela;
            $linhas[] = '';
            continue;
        }

        $colunas = array_map(static fn(string $coluna): string => nomeTabelaSeguro($coluna), array_keys($primeiraLinha));
        $prefixoInsert = 'INSERT INTO ' . $nomeTabela . ' (' . implode(', ', $colunas) . ') VALUES ';

        $linhaAtual = $primeiraLinha;
        do {
            $valores = array_map(static fn($valor): string => valorSql($pdo, $valor), array_values($linhaAtual));
            $linhas[] = $prefixoInsert . '(' . implode(', ', $valores) . ');';
        } while ($linhaAtual = $stmtDados->fetch(PDO::FETCH_ASSOC));

        $linhas[] = '';
    }

    $linhas[] = 'SET FOREIGN_KEY_CHECKS=1;';
    $linhas[] = '';

    return implode(PHP_EOL, $linhas);
}

function registrarBackup(PDO $pdo, string $nomeArquivo, string $caminhoArquivo, int $tamanho): void
{
    $stmt = $pdo->prepare("
        INSERT INTO backup_registros (usuario_id, nome_arquivo, caminho_arquivo, tamanho_bytes)
        VALUES (:usuario_id, :nome_arquivo, :caminho_arquivo, :tamanho_bytes)
    ");
    $stmt->execute([
        ':usuario_id' => usuarioAtualId() ?: null,
        ':nome_arquivo' => $nomeArquivo,
        ':caminho_arquivo' => $caminhoArquivo,
        ':tamanho_bytes' => $tamanho,
    ]);
}

function formatarBytes(?int $bytes): string
{
    if ($bytes === null || $bytes <= 0) {
        return '-';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    return number_format($bytes / 1024, 2, ',', '.') . ' KB';
}

garantirTabelaBackups($pdo);

$mensagem = '';
$erro = '';

if (isset($_GET['baixar'])) {
    $id = (int) $_GET['baixar'];
    $stmt = $pdo->prepare('SELECT * FROM backup_registros WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$backup || !is_file((string) $backup['caminho_arquivo'])) {
        http_response_code(404);
        echo 'Backup nao encontrado.';
        exit;
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename((string) $backup['nome_arquivo']) . '"');
    header('Content-Length: ' . filesize((string) $backup['caminho_arquivo']));
    readfile((string) $backup['caminho_arquivo']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $diretorio = diretorioBackups();

        if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true)) {
            throw new RuntimeException('Nao foi possivel criar a pasta de backups.');
        }

        $tabelas = tabelasParaBackup($pdo);

        if ($tabelas === []) {
            throw new RuntimeException('Nenhuma tabela foi encontrada para backup.');
        }

        $nomeArquivo = 'backup_sga_pecuaria_' . date('Ymd_His') . '.sql';
        $caminhoArquivo = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo;
        $sql = gerarSqlBackup($pdo, $tabelas);

        if (file_put_contents($caminhoArquivo, $sql) === false) {
            throw new RuntimeException('Nao foi possivel gravar o arquivo de backup.');
        }

        registrarBackup($pdo, $nomeArquivo, $caminhoArquivo, filesize($caminhoArquivo) ?: 0);
        $idBackup = (int) $pdo->lastInsertId();
        registrarAuditoria($pdo, 'Backup', 'Backup', $idBackup, 'Backup gerado: ' . $nomeArquivo);
        $mensagem = 'Backup gerado com sucesso. Arquivo: ' . $nomeArquivo;
        $_GET['backup_gerado'] = (string) $idBackup;
    } catch (Throwable $e) {
        $erro = 'Nao foi possivel gerar o backup agora. Verifique o banco e tente novamente.';
    }
}

$stmtBackups = $pdo->query("
    SELECT b.*, u.nome AS usuario_nome
    FROM backup_registros b
    LEFT JOIN usuarios u ON u.id = b.usuario_id
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT 20
");
$backups = $stmtBackups->fetchAll(PDO::FETCH_ASSOC);

layoutInicio('Backup do banco');
?>

<div class="page-header split-header">
    <div>
        <h1>Backup do banco</h1>
        <p>Gere uma copia dos dados principais antes de demonstrar, atualizar ou usar com dados reais.</p>
    </div>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="alert success">
        <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        <?php if (!empty($_GET['backup_gerado'])): ?>
            <a href="backup.php?baixar=<?= (int) $_GET['backup_gerado'] ?>">Baixar agora</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="alert error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="panel panel-spaced">
    <h2>Gerar novo backup</h2>
    <p>O arquivo SQL inclui cadastros, animais, estoque, financeiro, usuarios, chamados e historicos principais.</p>

    <form method="post" class="form-inline">
        <button type="submit" class="btn-link">
            <i class="fa-solid fa-download"></i>
            Gerar backup
        </button>
    </form>
</section>

<section class="panel">
    <h2>Backups recentes</h2>

    <?php if ($backups === []): ?>
        <p>Nenhum backup foi gerado ainda.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Gerado por</th>
                        <th>Data</th>
                        <th>Tamanho</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?= htmlspecialchars($backup['nome_arquivo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($backup['usuario_nome'] ?? 'Usuario removido', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime((string) $backup['created_at'])) ?></td>
                            <td><?= formatarBytes((int) ($backup['tamanho_bytes'] ?? 0)) ?></td>
                            <td>
                                <a class="btn-tabela" href="backup.php?baixar=<?= (int) $backup['id'] ?>">Baixar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php layoutFim(); ?>
