<?php
require_once __DIR__ . '/db.php';

$erro = '';
$sucesso = '';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function valorAntigo(string $chave, string $padrao = ''): string
{
    return htmlspecialchars($_POST[$chave] ?? $padrao, ENT_QUOTES, 'UTF-8');
}

function selecionado(string $chave, string $valor): string
{
    return (($_POST[$chave] ?? '') === $valor) ? 'selected' : '';
}

try {
    $stmtAnimais = $pdo->query("
        SELECT id, brinco, nome_apelido
        FROM animais
        ORDER BY nome_apelido ASC, id ASC
    ");
    $animais = $stmtAnimais->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro ao carregar animais: ' . h($e->getMessage()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_id = trim($_POST['animal_id'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data_evento = trim($_POST['data_evento'] ?? '');
    $proxima_data = trim($_POST['proxima_data'] ?? '');
    $status = trim($_POST['status'] ?? 'Agendada');

    if ($animal_id === '' || $descricao === '' || $data_evento === '') {
        $erro = 'Preencha os campos obrigatórios: animal, vacina e data da aplicação.';
    }

    if ($erro === '') {
        try {
            $sql = "
                INSERT INTO manejos_sanitarios (
                    animal_id,
                    tipo,
                    descricao,
                    data_evento,
                    proxima_data,
                    status
                ) VALUES (
                    :animal_id,
                    'Vacinação',
                    :descricao,
                    :data_evento,
                    :proxima_data,
                    :status
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':animal_id' => (int) $animal_id,
                ':descricao' => $descricao,
                ':data_evento' => $data_evento,
                ':proxima_data' => $proxima_data !== '' ? $proxima_data : null,
                ':status' => $status,
            ]);

            $sucesso = 'Vacinação registrada com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar vacinação: ' . $e->getMessage();
        }
    }
}

try {
    $aplicadasHoje = (int) $pdo->query("
        SELECT COUNT(*)
        FROM manejos_sanitarios
        WHERE tipo = 'Vacinação'
          AND data_evento = CURDATE()
    ")->fetchColumn();

    $proximasSeteDias = (int) $pdo->query("
        SELECT COUNT(*)
        FROM manejos_sanitarios
        WHERE tipo = 'Vacinação'
          AND proxima_data IS NOT NULL
          AND proxima_data BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ")->fetchColumn();

    $atrasadas = (int) $pdo->query("
        SELECT COUNT(*)
        FROM manejos_sanitarios
        WHERE tipo = 'Vacinação'
          AND proxima_data IS NOT NULL
          AND proxima_data < CURDATE()
          AND (status IS NULL OR status <> 'Aplicada')
    ")->fetchColumn();

    $stmtVacinas = $pdo->query("
        SELECT
            ms.id,
            ms.descricao,
            ms.data_evento,
            ms.proxima_data,
            ms.status,
            a.brinco,
            a.nome_apelido
        FROM manejos_sanitarios ms
        INNER JOIN animais a ON a.id = ms.animal_id
        WHERE ms.tipo = 'Vacinação'
        ORDER BY ms.data_evento DESC, ms.id DESC
        LIMIT 20
    ");
    $vacinacoes = $stmtVacinas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Erro ao carregar dados da vacinação: ' . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - Vacinação</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        /* força o menu fixo nesta página */
        .layout {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #264d2f, #1f3f27);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
            left: 0 !important;
        }

        .main {
            flex: 1;
            min-width: 0;
            width: 100%;
            padding-left: 0;
        }

        .btn-Menu,
        .overlay {
            display: none !important;
        }

        .topbar {
            display: flex;
            align-items: center;
            background: white;
            padding: 12px 24px;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .titulo h2 {
            margin: 0;
            font-size: 22px;
            color: #1f7a3f;
        }

        .titulo p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }

        .content {
            width: 100%;
            padding: 24px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            color: #1f7a3f;
        }

        .page-header p {
            margin: 6px 0 0;
            color: #666;
            font-size: 14px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card-resumo,
        .panel {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .card-resumo h3 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #555;
        }

        .card-resumo .value {
            font-size: 30px;
            font-weight: bold;
            color: #1f7a3f;
        }

        .grid-panels {
            display: grid;
            grid-template-columns: 1.1fr 1.4fr;
            gap: 24px;
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 22px;
            color: #1f7a3f;
        }

        .panel p.helper {
            margin-top: -6px;
            margin-bottom: 18px;
            color: #666;
            font-size: 14px;
        }

        .erro,
        .sucesso {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .erro {
            background: #fdeaea;
            color: #b42318;
        }

        .sucesso {
            background: #e7f6ec;
            color: #1f7a3f;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            font-size: 14px;
            font-weight: bold;
            color: #444;
            margin-bottom: 6px;
        }

        input,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8d8d8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2fa35a;
            box-shadow: 0 0 0 3px rgba(47,163,90,0.12);
        }

        button[type="submit"] {
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1f7a3f;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s ease;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background: #186232;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e7e7e7;
            vertical-align: middle;
        }

        th {
            background: #f0f7f2;
            color: #1f7a3f;
        }

        tr:hover {
            background: #fafafa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-agendada {
            background: #fef0c7;
            color: #b54708;
        }

        .status-aplicada {
            background: #e7f6ec;
            color: #1f7a3f;
        }

        .status-atrasada {
            background: #fdeaea;
            color: #b42318;
        }

        .vazio {
            padding: 18px;
            text-align: center;
            color: #777;
        }

        @media (max-width: 980px) {
            .grid-panels {
                grid-template-columns: 1fr;
            }

            form {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .sidebar {
                position: relative;
                top: 0;
                height: auto;
            }

            .layout {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="titulo">
            <h2>SGA Pecuária</h2>
            <p>Fazenda Paraíso</p>
        </div>
    </header>

    <div class="layout">
        <?php include __DIR__ . '/includes/menu.php'; ?>

        <main class="main">
            <div class="content">
                <div class="page-header">
                    <h1>Vacinação</h1>
                    <p>Registro de aplicações e acompanhamento das próximas datas.</p>
                </div>

                <div class="cards">
                    <div class="card-resumo">
                        <h3>Aplicadas hoje</h3>
                        <div class="value"><?= $aplicadasHoje ?></div>
                    </div>

                    <div class="card-resumo">
                        <h3>Próximos 7 dias</h3>
                        <div class="value"><?= $proximasSeteDias ?></div>
                    </div>

                    <div class="card-resumo">
                        <h3>Atrasadas</h3>
                        <div class="value"><?= $atrasadas ?></div>
                    </div>
                </div>

                <div class="grid-panels">
                    <section class="panel">
                        <h2>Registrar vacinação</h2>
                        <p class="helper">Use esta área para lançar a aplicação e já deixar a próxima data programada.</p>

                        <?php if ($erro !== ''): ?>
                            <div class="erro"><?= h($erro) ?></div>
                        <?php endif; ?>

                        <?php if ($sucesso !== ''): ?>
                            <div class="sucesso"><?= h($sucesso) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group full-width">
                                <label for="animal_id">Animal *</label>
                                <select id="animal_id" name="animal_id" required>
                                    <option value="">Selecione o animal</option>
                                    <?php foreach ($animais as $animal): ?>
                                        <option value="<?= (int) $animal['id'] ?>" <?= (valorAntigo('animal_id') == $animal['id']) ? 'selected' : '' ?>>
                                            <?= h($animal['nome_apelido']) ?> - Brinco <?= h($animal['brinco']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="descricao">Vacina *</label>
                                <input
                                    type="text"
                                    id="descricao"
                                    name="descricao"
                                    placeholder="Ex.: Aftosa, Brucelose, Raiva..."
                                    value="<?= valorAntigo('descricao') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="data_evento">Data da aplicação *</label>
                                <input
                                    type="date"
                                    id="data_evento"
                                    name="data_evento"
                                    value="<?= valorAntigo('data_evento', date('Y-m-d')) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="proxima_data">Próxima vacinação</label>
                                <input
                                    type="date"
                                    id="proxima_data"
                                    name="proxima_data"
                                    value="<?= valorAntigo('proxima_data') ?>"
                                >
                            </div>

                            <div class="form-group full-width">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="Aplicada" <?= selecionado('status', 'Aplicada') ?>>Aplicada</option>
                                    <option value="Agendada" <?= selecionado('status', 'Agendada') ?>>Agendada</option>
                                    <option value="Atrasada" <?= selecionado('status', 'Atrasada') ?>>Atrasada</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <button type="submit">Salvar vacinação</button>
                            </div>
                        </form>
                    </section>

                    <section class="panel">
                        <h2>Últimos registros</h2>
                        <p class="helper">Vacinações mais recentes cadastradas no sistema.</p>

                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Animal</th>
                                        <th>Brinco</th>
                                        <th>Vacina</th>
                                        <th>Aplicação</th>
                                        <th>Próxima</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($vacinacoes) === 0): ?>
                                        <tr>
                                            <td colspan="6" class="vazio">Nenhuma vacinação cadastrada ainda.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vacinacoes as $item): ?>
                                            <?php
                                                $statusClasse = 'status-agendada';

                                                if (($item['status'] ?? '') === 'Aplicada') {
                                                    $statusClasse = 'status-aplicada';
                                                } elseif (($item['status'] ?? '') === 'Atrasada') {
                                                    $statusClasse = 'status-atrasada';
                                                }
                                            ?>
                                            <tr>
                                                <td><?= h($item['nome_apelido']) ?></td>
                                                <td><?= h($item['brinco']) ?></td>
                                                <td><?= h($item['descricao']) ?></td>
                                                <td><?= h((string) $item['data_evento']) ?></td>
                                                <td><?= h((string) ($item['proxima_data'] ?? '-')) ?></td>
                                                <td>
                                                    <span class="status-badge <?= $statusClasse ?>">
                                                        <?= h((string) ($item['status'] ?? 'Sem status')) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
