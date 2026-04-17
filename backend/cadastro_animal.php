<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

$erro = '';
$loteCookie = $_COOKIE['sga_lote_padrao'] ?? '';
$racaCookie = $_COOKIE['sga_raca_padrao'] ?? '';

function valorAntigo(string $chave, string $padrao = ''): string
{
    return htmlspecialchars($_POST[$chave] ?? $padrao, ENT_QUOTES, 'UTF-8');
}

function selecionado(string $chave, string $valor): string
{
    return (($_POST[$chave] ?? '') === $valor) ? 'selected' : '';
}

try {
    $stmtFemeas = $pdo->query("
        SELECT id, nome_apelido, brinco
        FROM animais
        WHERE sexo = 'Fêmea'
        ORDER BY nome_apelido ASC, id ASC
    ");
    $femeas = $stmtFemeas->fetchAll(PDO::FETCH_ASSOC);

    $stmtMachos = $pdo->query("
        SELECT id, nome_apelido, brinco
        FROM animais
        WHERE sexo = 'Macho'
        ORDER BY nome_apelido ASC, id ASC
    ");
    $machos = $stmtMachos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro ao carregar listas de mãe e pai: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brinco = trim($_POST['brinco'] ?? '');
    $nome_apelido = trim($_POST['nome_apelido'] ?? '');
    $raca = trim($_POST['raca'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $data_nascimento = trim($_POST['data_nascimento'] ?? '');
    $lote = trim($_POST['lote'] ?? '');
    $mae_id = trim($_POST['mae_id'] ?? '');
    $pai_id = trim($_POST['pai_id'] ?? '');
    $data_ultimo_cio = trim($_POST['data_ultimo_cio'] ?? '');
    $prenha = trim($_POST['prenha'] ?? '0');

    if ($brinco === '' || $nome_apelido === '' || $raca === '' || $sexo === '') {
        $erro = 'Preencha os campos obrigatórios: brinco, nome/apelido, raça e sexo.';
    }

    if ($erro === '' && $mae_id !== '' && $pai_id !== '' && $mae_id === $pai_id) {
        $erro = 'Mãe e pai não podem ser o mesmo animal.';
    }

    if ($erro === '') {
        if ($sexo !== 'Fêmea') {
            $data_ultimo_cio = '';
            $prenha = '0';
        }

        try {
            $sql = "
                INSERT INTO animais (
                    brinco,
                    nome_apelido,
                    raca,
                    sexo,
                    data_nascimento,
                    lote,
                    mae_id,
                    pai_id,
                    data_ultimo_cio,
                    prenha
                ) VALUES (
                    :brinco,
                    :nome_apelido,
                    :raca,
                    :sexo,
                    :data_nascimento,
                    :lote,
                    :mae_id,
                    :pai_id,
                    :data_ultimo_cio,
                    :prenha
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':brinco' => $brinco,
                ':nome_apelido' => $nome_apelido,
                ':raca' => $raca,
                ':sexo' => $sexo,
                ':data_nascimento' => $data_nascimento !== '' ? $data_nascimento : null,
                ':lote' => $lote !== '' ? $lote : null,
                ':mae_id' => $mae_id !== '' ? (int) $mae_id : null,
                ':pai_id' => $pai_id !== '' ? (int) $pai_id : null,
                ':data_ultimo_cio' => $data_ultimo_cio !== '' ? $data_ultimo_cio : null,
                ':prenha' => ($prenha === '1') ? 1 : 0,
            ]);

            $novoId = $pdo->lastInsertId();
            header('Location: animal.php?id=' . $novoId);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Já existe um animal cadastrado com esse brinco.';
            } else {
                $erro = 'Erro ao cadastrar animal: ' . $e->getMessage();
            }
        }
    }
}

layoutInicio('Cadastrar animal');
?>

<style>
    .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .card h2 {
        margin-top: 0;
        margin-bottom: 16px;
        font-size: 20px;
        color: #1f7a3f;
    }

    .card p {
        margin-top: 0;
        color: #666;
        font-size: 14px;
    }

    .full {
        grid-column: 1 / -1;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field.full {
        grid-column: 1 / -1;
    }

    .erro {
        margin-bottom: 16px;
        padding: 12px;
        border-radius: 10px;
        background: #fdeaea;
        color: #b42318;
        font-size: 14px;
        font-weight: bold;
    }

    .actions {
        width: 100%;
        margin-top: 20px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    form.animal-form {
        display: block;
        width: 100%;
    }

    .secondary {
        background: #eef2f7;
        color: #333;
    }

    .secondary:hover {
        background: #dde5ee;
    }

    .help {
        font-size: 13px;
        color: #666;
        margin-top: 6px;
    }

    @media (max-width: 900px) {
        .grid,
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <h1>Cadastrar Animal</h1>
    <p>Preencha a ficha do animal com o máximo de informações possíveis</p>
</div>

<div class="top-actions">
    <a href="dashboard.php" class="btn-link">← Voltar ao dashboard</a>
</div>

<?php if ($erro !== ''): ?>
    <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" action="" class="animal-form">
    <div class="grid">
        <div class="card">
            <h2>Identificação</h2>
            <p>Dados principais do cadastro do animal.</p>

            <div class="form-grid">
                <div class="field">
                    <label for="brinco">Brinco *</label>
                    <input type="text" id="brinco" name="brinco" value="<?= valorAntigo('brinco') ?>" required>
                </div>

                <div class="field">
                    <label for="nome_apelido">Nome / Apelido *</label>
                    <input type="text" id="nome_apelido" name="nome_apelido" value="<?= valorAntigo('nome_apelido') ?>" required>
                </div>

                <div class="field">
                    <label for="raca">Raça *</label>
                    <input type="text" id="raca" name="raca" value="<?= valorAntigo('raca', $racaCookie) ?>" required>
                </div>

                <div class="field">
                    <label for="sexo">Sexo *</label>
                    <select id="sexo" name="sexo" required>
                        <option value="">Selecione</option>
                        <option value="Macho" <?= selecionado('sexo', 'Macho') ?>>Macho</option>
                        <option value="Fêmea" <?= selecionado('sexo', 'Fêmea') ?>>Fêmea</option>
                    </select>
                </div>

                <div class="field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= valorAntigo('data_nascimento') ?>">
                </div>

                <div class="field">
                    <label for="lote">Lote</label>
                    <input type="text" id="lote" name="lote" value="<?= valorAntigo('lote', $loteCookie) ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Genealogia</h2>
            <p>Se souber, informe mãe e pai do animal.</p>

            <div class="form-grid">
                <div class="field full">
                    <label for="mae_id">Mãe</label>
                    <select id="mae_id" name="mae_id">
                        <option value="">Não informar</option>
                        <?php foreach ($femeas as $femea): ?>
                            <option value="<?= $femea['id'] ?>" <?= (valorAntigo('mae_id') == $femea['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($femea['nome_apelido'] . ' - Brinco ' . $femea['brinco'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field full">
                    <label for="pai_id">Pai</label>
                    <select id="pai_id" name="pai_id">
                        <option value="">Não informar</option>
                        <?php foreach ($machos as $macho): ?>
                            <option value="<?= $macho['id'] ?>" <?= (valorAntigo('pai_id') == $macho['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($macho['nome_apelido'] . ' - Brinco ' . $macho['brinco'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card full">
            <h2>Reprodução</h2>
            <p>Campos úteis principalmente para fêmeas.</p>

            <div class="form-grid">
                <div class="field">
                    <label for="data_ultimo_cio">Data do último cio</label>
                    <input type="date" id="data_ultimo_cio" name="data_ultimo_cio" value="<?= valorAntigo('data_ultimo_cio') ?>">
                    <span class="help">Se não souber, pode deixar em branco.</span>
                </div>

                <div class="field">
                    <label for="prenha">Prenha?</label>
                    <select id="prenha" name="prenha">
                        <option value="0" <?= selecionado('prenha', '0') ?>>Não</option>
                        <option value="1" <?= selecionado('prenha', '1') ?>>Sim</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        <button type="submit">Salvar animal</button>
        <a href="dashboard.php" class="btn-link secondary">Cancelar</a>
    </div>
</form>

<?php layoutFim(); ?>
