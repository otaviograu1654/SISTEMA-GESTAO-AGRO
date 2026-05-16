<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function garantirTabelaProducaoLeite(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS producao_leite (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NULL,
            data_producao DATE NOT NULL,
            turno VARCHAR(20) NOT NULL,
            litros DECIMAL(10,2) NOT NULL,
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");
}

function valorAntigoLeite(string $chave, string $padrao = ''): string
{
    return htmlspecialchars($_POST[$chave] ?? $padrao, ENT_QUOTES, 'UTF-8');
}

function selecionadoLeite(string $chave, string $valor, string $padrao = ''): string
{
    $atual = $_POST[$chave] ?? $padrao;

    return $atual === $valor ? 'selected' : '';
}

function formatarDataLeite(?string $data): string
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

function formatarLitros($litros): string
{
    return number_format((float) $litros, 2, ',', '.') . ' L';
}

$erro = '';
$sucesso = '';
$animais = [];
$registros = [];
$resumo = [
    'total_registros' => 0,
    'litros_total' => 0,
    'litros_hoje' => 0,
    'media_registro' => 0,
];

try {
    garantirTabelaProducaoLeite($pdo);

    $stmtAnimais = $pdo->query("
        SELECT id, nome_apelido, brinco
        FROM animais
        WHERE sexo IN ('Fêmea', 'Femea')
          AND status = 'Ativo'
        ORDER BY nome_apelido ASC, id ASC
    ");
    $animais = $stmtAnimais->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a produção de leite.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $animalId = (int) ($_POST['animal_id'] ?? 0);
    $dataProducao = trim($_POST['data_producao'] ?? '');
    $turno = trim($_POST['turno'] ?? '');
    $litros = trim((string) ($_POST['litros'] ?? ''));
    $observacao = trim($_POST['observacao'] ?? '');

    if ($dataProducao === '' || $turno === '' || $litros === '') {
        $erro = 'Preencha data, turno e litros produzidos.';
    } elseif (!in_array($turno, ['Manhã', 'Tarde', 'Noite'], true)) {
        $erro = 'Selecione um turno válido.';
    } elseif (!is_numeric($litros) || (float) $litros <= 0) {
        $erro = 'Informe uma quantidade de litros maior que zero.';
    } else {
        try {
            if ($animalId > 0) {
                $stmtAnimal = $pdo->prepare("
                    SELECT id
                    FROM animais
                    WHERE id = :id
                      AND sexo IN ('Fêmea', 'Femea')
                      AND status = 'Ativo'
                    LIMIT 1
                ");
                $stmtAnimal->execute([':id' => $animalId]);

                if (!$stmtAnimal->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException('Animal não encontrado, inativo ou não é fêmea.');
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO producao_leite (
                    animal_id,
                    data_producao,
                    turno,
                    litros,
                    observacao
                ) VALUES (
                    :animal_id,
                    :data_producao,
                    :turno,
                    :litros,
                    :observacao
                )
            ");
            $stmt->execute([
                ':animal_id' => $animalId > 0 ? $animalId : null,
                ':data_producao' => $dataProducao,
                ':turno' => $turno,
                ':litros' => $litros,
                ':observacao' => $observacao !== '' ? $observacao : null,
            ]);

            $sucesso = 'Produção de leite registrada com sucesso.';
            $_POST = [];
        } catch (Throwable $e) {
            $erro = 'Erro ao registrar produção: ' . $e->getMessage();
        }
    }
}

if ($erro === '') {
    try {
        $stmtResumo = $pdo->query("
            SELECT
                COUNT(*) AS total_registros,
                SUM(litros) AS litros_total,
                SUM(CASE WHEN data_producao = CURDATE() THEN litros ELSE 0 END) AS litros_hoje,
                AVG(litros) AS media_registro
            FROM producao_leite
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total_registros' => (int) ($resumoDb['total_registros'] ?? 0),
                'litros_total' => (float) ($resumoDb['litros_total'] ?? 0),
                'litros_hoje' => (float) ($resumoDb['litros_hoje'] ?? 0),
                'media_registro' => (float) ($resumoDb['media_registro'] ?? 0),
            ];
        }

        $stmtRegistros = $pdo->query("
            SELECT
                pl.id,
                pl.data_producao,
                pl.turno,
                pl.litros,
                pl.observacao,
                a.nome_apelido,
                a.brinco
            FROM producao_leite pl
            LEFT JOIN animais a ON a.id = pl.animal_id
            ORDER BY pl.data_producao DESC, pl.id DESC
            LIMIT 50
        ");
        $registros = $stmtRegistros->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar a produção de leite.';
    }
}

layoutInicio('Produção de leite');
?>

<div class="page-header">
    <h1>Produção de leite</h1>
    <p>Registre a produção diária por turno, vinculando a uma fêmea quando necessário.</p>
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
        <h3>Registros</h3>
        <div class="value"><?= $resumo['total_registros'] ?></div>
    </div>
    <div class="card">
        <h3>Total produzido</h3>
        <div class="value"><?= formatarLitros($resumo['litros_total']) ?></div>
    </div>
    <div class="card">
        <h3>Produção hoje</h3>
        <div class="value"><?= formatarLitros($resumo['litros_hoje']) ?></div>
    </div>
    <div class="card">
        <h3>Média por registro</h3>
        <div class="value"><?= formatarLitros($resumo['media_registro']) ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Novo registro</h2>

        <form method="POST" action="">
            <div class="form-group full-width">
                <label for="animal_id">Animal</label>
                <select id="animal_id" name="animal_id">
                    <option value="">Produção geral do tanque</option>
                    <?php foreach ($animais as $animal): ?>
                        <option value="<?= (int) $animal['id'] ?>" <?= selecionadoLeite('animal_id', (string) $animal['id']) ?>>
                            <?= htmlspecialchars($animal['nome_apelido'] . ' - Brinco ' . $animal['brinco'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data_producao">Data</label>
                <input type="date" id="data_producao" name="data_producao" value="<?= valorAntigoLeite('data_producao', date('Y-m-d')) ?>" required>
            </div>

            <div class="form-group">
                <label for="turno">Turno</label>
                <select id="turno" name="turno" required>
                    <option value="">Selecione</option>
                    <option value="Manhã" <?= selecionadoLeite('turno', 'Manhã') ?>>Manhã</option>
                    <option value="Tarde" <?= selecionadoLeite('turno', 'Tarde') ?>>Tarde</option>
                    <option value="Noite" <?= selecionadoLeite('turno', 'Noite') ?>>Noite</option>
                </select>
            </div>

            <div class="form-group">
                <label for="litros">Litros</label>
                <input type="number" id="litros" name="litros" min="0.01" step="0.01" value="<?= valorAntigoLeite('litros') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="observacao">Observação</label>
                <input type="text" id="observacao" name="observacao" value="<?= valorAntigoLeite('observacao') ?>">
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar produção</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Escopo da fase</h2>
        <p>Esta fase registra a produção diária. Relatórios mais avançados podem ser criados depois.</p>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Produção geral</th>
                        <td>Permitida</td>
                    </tr>
                    <tr>
                        <th>Produção por animal</th>
                        <td>Opcional para fêmeas ativas</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel panel-spaced">
    <h2>Histórico de produção</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Turno</th>
                    <th>Animal</th>
                    <th>Litros</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="5">Nenhuma produção registrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $registro): ?>
                        <tr>
                            <td><?= formatarDataLeite($registro['data_producao']) ?></td>
                            <td><?= htmlspecialchars($registro['turno'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= $registro['nome_apelido']
                                    ? htmlspecialchars($registro['nome_apelido'] . ' / ' . $registro['brinco'], ENT_QUOTES, 'UTF-8')
                                    : 'Produção geral' ?>
                            </td>
                            <td><?= formatarLitros($registro['litros']) ?></td>
                            <td><?= htmlspecialchars($registro['observacao'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
