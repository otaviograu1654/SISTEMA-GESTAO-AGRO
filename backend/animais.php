<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/animal_auditoria.php';

garantirTabelaAuditoriaAnimal($pdo);
garantirStatusAnimal($pdo);

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
            status,
            created_at
        FROM animais
        ORDER BY id DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarUltimasAlteracoes(PDO $pdo, int $limite = 8): array
{
    $stmt = $pdo->prepare("
        SELECT
            animal_id,
            brinco_referencia,
            nome_referencia,
            tipo_alteracao,
            descricao,
            created_at
        FROM animal_alteracoes
        ORDER BY created_at DESC, id DESC
        LIMIT :limite
    ");
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

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

function classeStatusAnimal($status): string
{
    if ($status === 'Vendido') {
        return 'badge-alerta';
    }

    if ($status === 'Óbito') {
        return 'badge-erro';
    }

    return 'badge-sucesso';
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
$ultimasAlteracoes = [];
$resumo = [
    'total' => 0,
    'machos' => 0,
    'femeas' => 0,
    'prenhas' => 0,
];

try {
    $animais = buscarAnimais($pdo);
    $ultimasAlteracoes = buscarUltimasAlteracoes($pdo);

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
    <div class="mensagem erro mensagem-bloco">
        <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (($_GET['excluido'] ?? '') === '1'): ?>
    <div class="mensagem sucesso mensagem-bloco">
        Animal excluído com sucesso.
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

<div class="panel-spaced">
    <section class="panel">
        <h2>Rebanho cadastrado</h2>
        <p>Use a busca para encontrar rapidamente por brinco, nome, raça, lote ou sexo.</p>

        <div class="form-group busca-animais">
            <label for="buscaAnimais">Buscar animal</label>
            <input type="text" id="buscaAnimais" placeholder="Ex: 4052, Estrela, Nelore, lote A...">
        </div>

        <div class="form-group busca-animais">
            <label for="filtroStatusAnimais">Filtrar por status</label>
            <select id="filtroStatusAnimais">
                <option value="">Todos</option>
                <option value="ativo">Ativo</option>
                <option value="vendido">Vendido</option>
                <option value="óbito">Óbito</option>
            </select>
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
                        <th>Status</th>
                        <th>Nascimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabelaAnimais">
                    <?php if (empty($animais)): ?>
                        <tr>
                            <td colspan="8">Nenhum animal cadastrado ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($animais as $animal): ?>
                            <?php $statusLinha = $animal['status'] ?: 'Ativo'; ?>
                            <tr
                                data-animal-texto="<?= htmlspecialchars(mb_strtolower(trim(($animal['brinco'] ?? '') . ' ' . ($animal['nome_apelido'] ?? '') . ' ' . ($animal['raca'] ?? '') . ' ' . ($animal['lote'] ?? '') . ' ' . ($animal['sexo'] ?? '') . ' ' . $statusLinha), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                                data-animal-status="<?= htmlspecialchars(mb_strtolower($statusLinha, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <td><?= htmlspecialchars($animal['brinco'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['nome_apelido'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['raca'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['sexo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['lote'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= classeStatusAnimal($statusLinha) ?>">
                                        <?= htmlspecialchars($statusLinha, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
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

        <div id="semResultado" class="empty hidden">
            Nenhum animal encontrado para a busca informada.
        </div>
    </section>

    <section class="panel panel-spaced">
        <h2>Últimas alterações</h2>
        <p>Alterações recentes feitas no cadastro dos animais.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Animal</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimasAlteracoes)): ?>
                        <tr>
                            <td colspan="4">Nenhuma alteração registrada ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ultimasAlteracoes as $alteracao): ?>
                            <tr>
                                <td><?= htmlspecialchars(formatarDataHoraAuditoria($alteracao['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                    $referenciaAnimal = trim((string) (($alteracao['nome_referencia'] ?? '') . ' / brinco ' . ($alteracao['brinco_referencia'] ?? '-')));
                                    ?>
                                    <?php if (!empty($alteracao['animal_id'])): ?>
                                        <a href="animal.php?id=<?= (int) $alteracao['animal_id'] ?>" class="btn-link">
                                            <?= htmlspecialchars($referenciaAnimal, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($referenciaAnimal, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(ucfirst((string) $alteracao['tipo_alteracao']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($alteracao['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
    const filtroStatusAnimais = document.getElementById('filtroStatusAnimais');
    const linhasAnimais = Array.from(document.querySelectorAll('#tabelaAnimais tr[data-animal-texto]'));
    const semResultado = document.getElementById('semResultado');

    function filtrarAnimais() {
        const termo = buscaAnimais ? normalizarTexto(buscaAnimais.value.trim()) : '';
        const status = filtroStatusAnimais ? normalizarTexto(filtroStatusAnimais.value) : '';
        let visiveis = 0;

        linhasAnimais.forEach(function (linha) {
            const texto = linha.getAttribute('data-animal-texto') || '';
            const statusLinha = normalizarTexto(linha.getAttribute('data-animal-status') || '');
            const bateTexto = termo === '' || texto.includes(termo);
            const bateStatus = status === '' || statusLinha === status;
            const mostrar = bateTexto && bateStatus;

            linha.style.display = mostrar ? '' : 'none';

            if (mostrar) {
                visiveis++;
            }
        });

        if (semResultado) {
            semResultado.style.display = (linhasAnimais.length > 0 && visiveis === 0) ? 'block' : 'none';
        }
    }

    if (buscaAnimais) {
        buscaAnimais.addEventListener('input', filtrarAnimais);
    }

    if (filtroStatusAnimais) {
        filtroStatusAnimais.addEventListener('change', filtrarAnimais);
    }
</script>

<?php layoutFim(); ?>
