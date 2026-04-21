<?php
require_once __DIR__ . '/db.php';

function responder($dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function dataValida(string $data): bool
{
    $objetoData = DateTime::createFromFormat('Y-m-d', $data);

    return $objetoData !== false && $objetoData->format('Y-m-d') === $data;
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

function buscarPesagens(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            p.id,
            p.animal_id,
            a.nome_apelido,
            a.brinco,
            p.data_pesagem,
            p.peso_kg,
            p.observacao,
            p.created_at
        FROM pesagens p
        INNER JOIN animais a ON a.id = p.animal_id
        ORDER BY p.data_pesagem DESC, p.id DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarAnimais(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, nome_apelido, brinco
        FROM animais
        ORDER BY nome_apelido ASC, id ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (requisicaoJson()) {
    try {
        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($metodo === 'GET') {
            responder(buscarPesagens($pdo));
        }

        if ($metodo === 'POST') {
            $entrada = json_decode(file_get_contents('php://input'), true);

            if (!is_array($entrada)) {
                $entrada = $_POST;
            }

            $animal_id = (int) ($entrada['animal_id'] ?? 0);
            $data_pesagem = trim($entrada['data_pesagem'] ?? '');
            $peso_kg = trim((string) ($entrada['peso_kg'] ?? ''));
            $observacao = trim($entrada['observacao'] ?? '');

            if ($animal_id <= 0 || $data_pesagem === '' || $peso_kg === '') {
                responder([
                    'erro' => 'Campos obrigatórios: animal_id, data_pesagem e peso_kg.'
                ], 400);
            }

            if (!dataValida($data_pesagem)) {
                responder([
                    'erro' => 'A data_pesagem deve estar no formato YYYY-MM-DD.'
                ], 400);
            }

            if (!is_numeric($peso_kg)) {
                responder([
                    'erro' => 'O peso deve ser numérico.'
                ], 400);
            }

            if ((float) $peso_kg <= 0) {
                responder([
                    'erro' => 'O peso deve ser maior que zero.'
                ], 400);
            }

            $stmtAnimal = $pdo->prepare("SELECT id FROM animais WHERE id = :id");
            $stmtAnimal->execute([':id' => $animal_id]);

            if (!$stmtAnimal->fetch(PDO::FETCH_ASSOC)) {
                responder([
                    'erro' => 'Animal não encontrado.'
                ], 404);
            }

            $stmt = $pdo->prepare("
                INSERT INTO pesagens (animal_id, data_pesagem, peso_kg, observacao)
                VALUES (:animal_id, :data_pesagem, :peso_kg, :observacao)
            ");

            $stmt->execute([
                ':animal_id' => $animal_id,
                ':data_pesagem' => $data_pesagem,
                ':peso_kg' => $peso_kg,
                ':observacao' => $observacao !== '' ? $observacao : null,
            ]);

            responder([
                'mensagem' => 'Pesagem cadastrada com sucesso.',
                'id' => $pdo->lastInsertId()
            ], 201);
        }

        responder([
            'erro' => 'Método não permitido.'
        ], 405);
    } catch (PDOException $e) {
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

try {
    $animais = buscarAnimais($pdo);
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar os animais para o formulário.';
}

layoutInicio('Pesagens');
?>

<div class="page-header">
    <h1>Pesagens</h1>
    <p>Cadastre novas pesagens e acompanhe o histórico de peso do rebanho.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Total de pesagens</h3>
        <div class="value" id="totalPesagens">0</div>
    </div>
    <div class="card">
        <h3>Pesagens hoje</h3>
        <div class="value" id="pesagensHoje">0</div>
    </div>
    <div class="card">
        <h3>Último peso</h3>
        <div class="value" id="ultimoPeso">--</div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Registrar pesagem</h2>
        <p>Informe o animal, a data e o peso medido.</p>

        <?php if ($erroPagina !== ''): ?>
            <div class="mensagem erro mensagem-bloco">
                <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form id="formPesagem">
            <div class="form-group full-width">
                <label for="animal_id">Animal</label>
                <select id="animal_id" name="animal_id" required <?= $erroPagina !== '' ? 'disabled' : '' ?>>
                    <option value="">Selecione</option>
                    <?php foreach ($animais as $animal): ?>
                        <option value="<?= (int) $animal['id'] ?>">
                            <?= htmlspecialchars($animal['nome_apelido'] . ' - Brinco ' . $animal['brinco'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data_pesagem">Data da pesagem</label>
                <input type="date" id="data_pesagem" name="data_pesagem" value="<?= date('Y-m-d') ?>" required <?= $erroPagina !== '' ? 'disabled' : '' ?>>
            </div>

            <div class="form-group">
                <label for="peso_kg">Peso (kg)</label>
                <input type="number" id="peso_kg" name="peso_kg" min="0.01" step="0.01" placeholder="Ex: 420.50" required <?= $erroPagina !== '' ? 'disabled' : '' ?>>
            </div>

            <div class="form-group full-width">
                <label for="observacao">Observação</label>
                <textarea id="observacao" name="observacao" rows="3" placeholder="Opcional" <?= $erroPagina !== '' ? 'disabled' : '' ?>></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit" <?= $erroPagina !== '' ? 'disabled' : '' ?>>Salvar pesagem</button>
            </div>
        </form>

        <div id="mensagem" class="mensagem"></div>
    </section>

    <section class="panel">
        <h2>Histórico de pesagens</h2>
        <p>Últimos registros cadastrados no sistema.</p>

        <div id="loading" class="loading">Carregando pesagens...</div>

        <div class="table-wrapper hidden" id="tableWrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Animal</th>
                        <th>Brinco</th>
                        <th>Peso</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody id="tabelaPesagens"></tbody>
            </table>
        </div>

        <div id="emptyState" class="empty hidden">
            Nenhuma pesagem cadastrada.
        </div>
    </section>
</div>

<script>
    const endpointPesagens = 'pesagens.php?format=json';
    const formPesagem = document.getElementById('formPesagem');
    const mensagem = document.getElementById('mensagem');
    const loading = document.getElementById('loading');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const tabelaPesagens = document.getElementById('tabelaPesagens');

    function mostrarMensagem(texto, tipo) {
        mensagem.textContent = texto;
        mensagem.className = `mensagem ${tipo}`;
    }

    function formatarData(data) {
        if (!data) {
            return '';
        }

        const partes = data.split('-');

        if (partes.length !== 3) {
            return data;
        }

        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatarPeso(peso) {
        const numero = Number(peso);

        if (Number.isNaN(numero)) {
            return '--';
        }

        return `${numero.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })} kg`;
    }

    function atualizarCards(pesagens) {
        const hoje = new Date().toISOString().slice(0, 10);
        const pesagensHoje = pesagens.filter((pesagem) => pesagem.data_pesagem === hoje).length;
        const ultimaPesagem = pesagens.length > 0 ? pesagens[0] : null;

        document.getElementById('totalPesagens').textContent = pesagens.length;
        document.getElementById('pesagensHoje').textContent = pesagensHoje;
        document.getElementById('ultimoPeso').textContent = ultimaPesagem ? formatarPeso(ultimaPesagem.peso_kg) : '--';
    }

    function renderizarPesagens(pesagens) {
        loading.style.display = 'none';

        if (!Array.isArray(pesagens) || pesagens.length === 0) {
            tableWrapper.style.display = 'none';
            emptyState.style.display = 'block';
            tabelaPesagens.innerHTML = '';
            atualizarCards([]);
            return;
        }

        emptyState.style.display = 'none';
        tableWrapper.style.display = 'block';
        tabelaPesagens.innerHTML = '';

        pesagens.forEach((pesagem) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${formatarData(pesagem.data_pesagem)}</td>
                <td>${pesagem.nome_apelido ?? ''}</td>
                <td>${pesagem.brinco ?? ''}</td>
                <td>${formatarPeso(pesagem.peso_kg)}</td>
                <td>${pesagem.observacao ?? ''}</td>
            `;

            tabelaPesagens.appendChild(tr);
        });

        atualizarCards(pesagens);
    }

    async function carregarPesagens() {
        try {
            const resposta = await fetch(endpointPesagens, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!resposta.ok) {
                throw new Error('Erro ao buscar pesagens');
            }

            const pesagens = await resposta.json();
            renderizarPesagens(Array.isArray(pesagens) ? pesagens : []);
        } catch (erro) {
            loading.textContent = 'Erro ao carregar pesagens.';
            console.error(erro);
        }
    }

    if (formPesagem) {
        formPesagem.addEventListener('submit', async function (event) {
            event.preventDefault();

            const dados = {
                animal_id: document.getElementById('animal_id').value,
                data_pesagem: document.getElementById('data_pesagem').value,
                peso_kg: document.getElementById('peso_kg').value,
                observacao: document.getElementById('observacao').value.trim()
            };

            try {
                const resposta = await fetch(endpointPesagens, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await resposta.json();

                if (!resposta.ok) {
                    mostrarMensagem(resultado.erro || 'Erro ao salvar pesagem.', 'erro');
                    return;
                }

                mostrarMensagem(resultado.mensagem || 'Pesagem cadastrada com sucesso.', 'sucesso');
                formPesagem.reset();
                document.getElementById('data_pesagem').value = new Date().toISOString().slice(0, 10);
                loading.style.display = 'block';
                loading.textContent = 'Carregando pesagens...';
                await carregarPesagens();
            } catch (erro) {
                mostrarMensagem('Erro de comunicação com o servidor.', 'erro');
                console.error(erro);
            }
        });
    }

    carregarPesagens();
</script>

<?php layoutFim(); ?>
