<?php
require_once __DIR__ . '/db.php';

function responder($dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function requisicaoJson(): bool
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return true;
    }

    if (($_GET['format'] ?? '') === 'json') {
        return true;
    }

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

    return stripos($accept, 'application/json') !== false;
}

function buscarAnimais(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            id,
            brinco,
            nome_apelido,
            raca,
            sexo,
            data_nascimento,
            lote,
            prenha,
            created_at
        FROM animais
        ORDER BY id DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatarData(?string $data): string
{
    if (!$data) {
        return '--';
    }

    $partes = explode('-', $data);

    if (count($partes) !== 3) {
        return $data;
    }

    return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
}

if (requisicaoJson()) {
    try {
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($metodo === 'GET') {
            responder(buscarAnimais($pdo));
        }

        if ($metodo === 'POST') {
            $entrada = json_decode(file_get_contents('php://input'), true);

            if (!is_array($entrada)) {
                $entrada = $_POST;
            }

            $brinco = trim($entrada['brinco'] ?? '');
            $nome_apelido = trim($entrada['nome_apelido'] ?? '');
            $raca = trim($entrada['raca'] ?? '');
            $sexo = trim($entrada['sexo'] ?? '');
            $data_nascimento = trim($entrada['data_nascimento'] ?? '');
            $lote = trim($entrada['lote'] ?? '');

            if ($brinco === '' || $nome_apelido === '' || $raca === '' || $sexo === '') {
                responder([
                    'erro' => 'Campos obrigatórios: brinco, nome_apelido, raca e sexo.'
                ], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO animais (brinco, nome_apelido, raca, sexo, data_nascimento, lote)
                VALUES (:brinco, :nome_apelido, :raca, :sexo, :data_nascimento, :lote)
            ");

            $stmt->execute([
                ':brinco' => $brinco,
                ':nome_apelido' => $nome_apelido,
                ':raca' => $raca,
                ':sexo' => $sexo,
                ':data_nascimento' => $data_nascimento !== '' ? $data_nascimento : null,
                ':lote' => $lote !== '' ? $lote : null,
            ]);

            responder([
                'mensagem' => 'Animal cadastrado com sucesso.',
                'id' => $pdo->lastInsertId()
            ], 201);
        }

        responder([
            'erro' => 'Método não permitido.'
        ], 405);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            responder([
                'erro' => 'Brinco já cadastrado.'
            ], 409);
        }

        responder([
            'erro' => 'Erro no banco de dados.',
            'detalhe' => $e->getMessage()
        ], 500);
    } catch (Throwable $e) {
        responder([
            'erro' => 'Erro interno do servidor.',
            'detalhe' => $e->getMessage()
        ], 500);
    }
}

require_once __DIR__ . '/includes/layout.php';

$erroPagina = '';
$animais = [];
$resumo = [
    'total' => 0,
    'machos' => 0,
    'femeas' => 0,
    'prenhas' => 0,
];

try {
    $animais = buscarAnimais($pdo);

    foreach ($animais as $animal) {
        $sexo = mb_strtolower((string) ($animal['sexo'] ?? ''), 'UTF-8');
        $resumo['total']++;

        if ($sexo === 'macho') {
            $resumo['machos']++;
        }

        if ($sexo === 'fêmea' || $sexo === 'femea') {
            $resumo['femeas']++;
        }

        if ((int) ($animal['prenha'] ?? 0) === 1) {
            $resumo['prenhas']++;
        }
    }
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar os animais cadastrados.';
}

layoutInicio('Animais');
?>

<div class="page-header">
    <h1>Animais</h1>
    <p>Visualize o rebanho cadastrado e acesse rapidamente os detalhes de cada animal.</p>
</div>

<div class="top-actions">
    <a class="btn-link" href="cadastro_animal.php">Novo animal</a>
    <a class="btn-link" href="dashboard.php">Voltar ao dashboard</a>
</div>

<?php if ($erroPagina !== ''): ?>
    <div class="mensagem erro" style="display: block; margin-bottom: 16px;">
        <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="cards">
    <div class="card">
        <h3>Total de animais</h3>
        <div class="value"><?= $resumo['total'] ?></div>
    </div>
    <div class="card">
        <h3>Machos</h3>
        <div class="value"><?= $resumo['machos'] ?></div>
    </div>
    <div class="card">
        <h3>Fêmeas</h3>
        <div class="value"><?= $resumo['femeas'] ?></div>
    </div>
    <div class="card">
        <h3>Prenhas</h3>
        <div class="value"><?= $resumo['prenhas'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Rebanho cadastrado</h2>
        <p>Use a busca para encontrar rapidamente por brinco, nome, raça, lote ou sexo.</p>

        <div class="form-group" style="margin-bottom: 16px;">
            <label for="buscaAnimais">Buscar animal</label>
            <input type="text" id="buscaAnimais" placeholder="Ex: 4052, Estrela, Nelore, lote A...">
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Brinco</th>
                        <th>Nome</th>
                        <th>Raça</th>
                        <th>Sexo</th>
                        <th>Lote</th>
                        <th>Nascimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabelaAnimais">
                    <?php if (empty($animais)): ?>
                        <tr>
                            <td colspan="7">Nenhum animal cadastrado ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($animais as $animal): ?>
                            <tr data-animal-texto="<?= htmlspecialchars(mb_strtolower(trim(($animal['brinco'] ?? '') . ' ' . ($animal['nome_apelido'] ?? '') . ' ' . ($animal['raca'] ?? '') . ' ' . ($animal['lote'] ?? '') . ' ' . ($animal['sexo'] ?? '')), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= htmlspecialchars($animal['brinco'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['nome_apelido'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['raca'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['sexo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['lote'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatarData($animal['data_nascimento']) ?></td>
                                <td>
                                    <a href="animal.php?id=<?= (int) $animal['id'] ?>" class="btn-link">Ver detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="semResultado" class="empty" style="display: none;">
            Nenhum animal encontrado para a busca informada.
        </div>
    </section>

    <section class="panel">
        <h2>Resumo rápido</h2>
        <p>Atalhos úteis para continuar o fluxo de cadastro e acompanhamento do rebanho.</p>

        <div class="top-actions" style="margin-bottom: 18px;">
            <a class="btn-link" href="cadastro_animal.php">Cadastrar animal</a>
            <a class="btn-link" href="pesagens.php">Abrir pesagens</a>
            <a class="btn-link" href="vacinacao.php">Abrir vacinação</a>
        </div>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <tr>
                        <th>Total cadastrado</th>
                        <td><?= $resumo['total'] ?> animal(is)</td>
                    </tr>
                    <tr>
                        <th>Distribuição por sexo</th>
                        <td><?= $resumo['machos'] ?> macho(s) e <?= $resumo['femeas'] ?> fêmea(s)</td>
                    </tr>
                    <tr>
                        <th>Fêmeas prenhas</th>
                        <td><?= $resumo['prenhas'] ?> registro(s)</td>
                    </tr>
                    <tr>
                        <th>Último cadastro</th>
                        <td>
                            <?= !empty($animais) ? htmlspecialchars(($animais[0]['nome_apelido'] ?: 'Sem nome') . ' / brinco ' . ($animais[0]['brinco'] ?: '-'), ENT_QUOTES, 'UTF-8') : 'Nenhum animal cadastrado' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    function normalizarTexto(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    const buscaAnimais = document.getElementById('buscaAnimais');
    const linhasAnimais = Array.from(document.querySelectorAll('#tabelaAnimais tr[data-animal-texto]'));
    const semResultado = document.getElementById('semResultado');

    if (buscaAnimais) {
        buscaAnimais.addEventListener('input', function () {
            const termo = normalizarTexto(buscaAnimais.value.trim());
            let visiveis = 0;

            linhasAnimais.forEach(function (linha) {
                const texto = linha.getAttribute('data-animal-texto') || '';
                const mostrar = termo === '' || texto.includes(termo);

                linha.style.display = mostrar ? '' : 'none';

                if (mostrar) {
                    visiveis++;
                }
            });

            if (semResultado) {
                semResultado.style.display = (linhasAnimais.length > 0 && visiveis === 0) ? 'block' : 'none';
            }
        });
    }
</script>

<?php layoutFim(); ?>
