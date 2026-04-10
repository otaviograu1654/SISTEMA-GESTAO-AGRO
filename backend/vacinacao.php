<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function buscarAnimais(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, nome_apelido, brinco
        FROM animais
        ORDER BY nome_apelido ASC, id ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$erroPagina = '';
$animais = [];

try {
    $animais = buscarAnimais($pdo);
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar os animais para o formulário.';
}

layoutInicio('Vacinação');
?>

<div class="page-header">
    <h1>Vacinação</h1>
    <p>Registro de aplicações e acompanhamento das próximas vacinações.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Aplicadas hoje</h3>
        <div class="value" id="aplicadasHoje">0</div>
    </div>
    <div class="card">
        <h3>Próximas vacinações</h3>
        <div class="value" id="proximasVacinacoes">0</div>
    </div>
    <div class="card">
        <h3>Atrasadas</h3>
        <div class="value" id="vacinacoesAtrasadas">0</div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Registrar vacinação</h2>
        <p>Informe o animal, a vacina aplicada e a próxima data prevista.</p>

        <?php if ($erroPagina !== ''): ?>
            <div class="mensagem erro" style="display: block; margin-bottom: 16px;">
                <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form id="formVacinacao">
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
                <label for="descricao">Vacina / observação</label>
                <input type="text" id="descricao" name="descricao" placeholder="Ex: Aftosa - 2ª dose" required <?= $erroPagina !== '' ? 'disabled' : '' ?>>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required <?= $erroPagina !== '' ? 'disabled' : '' ?>>
                    <option value="Realizado">Realizado</option>
                    <option value="Agendado">Agendado</option>
                    <option value="Pendente">Pendente</option>
                </select>
            </div>

            <div class="form-group">
                <label for="data_evento">Data da aplicação</label>
                <input type="date" id="data_evento" name="data_evento" value="<?= date('Y-m-d') ?>" required <?= $erroPagina !== '' ? 'disabled' : '' ?>>
            </div>

            <div class="form-group">
                <label for="proxima_data">Próxima data</label>
                <input type="date" id="proxima_data" name="proxima_data" <?= $erroPagina !== '' ? 'disabled' : '' ?>>
            </div>

            <div class="form-group full-width">
                <button type="submit" <?= $erroPagina !== '' ? 'disabled' : '' ?>>Salvar vacinação</button>
            </div>
        </form>

        <div id="mensagem" class="mensagem"></div>
    </section>

    <section class="panel">
        <h2>Últimos registros</h2>
        <p>Vacinações cadastradas no sistema.</p>

        <div id="loading" class="loading">Carregando vacinações...</div>

        <div class="table-wrapper" id="tableWrapper" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Animal</th>
                        <th>Brinco</th>
                        <th>Vacina</th>
                        <th>Próxima data</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tabelaVacinacoes"></tbody>
            </table>
        </div>

        <div id="emptyState" class="empty" style="display: none;">
            Nenhuma vacinação cadastrada.
        </div>
    </section>
</div>

<script>
    const endpointManejos = 'manejos_sanitarios.php';
    const formVacinacao = document.getElementById('formVacinacao');
    const mensagem = document.getElementById('mensagem');
    const loading = document.getElementById('loading');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const tabelaVacinacoes = document.getElementById('tabelaVacinacoes');

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

    function normalizarTexto(texto) {
        return (texto || '')
            .toString()
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function ehVacinacao(registro) {
        return normalizarTexto(registro.tipo) === 'vacinacao';
    }

    function atualizarCards(vacinacoes) {
        const hoje = new Date().toISOString().slice(0, 10);

        const aplicadasHoje = vacinacoes.filter((vacinacao) =>
            vacinacao.data_evento === hoje && normalizarTexto(vacinacao.status) === 'realizado'
        ).length;

        const proximasVacinacoes = vacinacoes.filter((vacinacao) =>
            vacinacao.proxima_data && vacinacao.proxima_data >= hoje
        ).length;

        const vacinacoesAtrasadas = vacinacoes.filter((vacinacao) =>
            vacinacao.proxima_data &&
            vacinacao.proxima_data < hoje &&
            normalizarTexto(vacinacao.status) !== 'realizado'
        ).length;

        document.getElementById('aplicadasHoje').textContent = aplicadasHoje;
        document.getElementById('proximasVacinacoes').textContent = proximasVacinacoes;
        document.getElementById('vacinacoesAtrasadas').textContent = vacinacoesAtrasadas;
    }

    function classeStatus(status) {
        const statusNormalizado = normalizarTexto(status);

        if (statusNormalizado === 'realizado') {
            return 'badge badge-sucesso';
        }

        if (statusNormalizado === 'agendado') {
            return 'badge badge-alerta';
        }

        return 'badge badge-erro';
    }

    function renderizarVacinacoes(vacinacoes) {
        loading.style.display = 'none';

        if (!Array.isArray(vacinacoes) || vacinacoes.length === 0) {
            tableWrapper.style.display = 'none';
            emptyState.style.display = 'block';
            tabelaVacinacoes.innerHTML = '';
            atualizarCards([]);
            return;
        }

        emptyState.style.display = 'none';
        tableWrapper.style.display = 'block';
        tabelaVacinacoes.innerHTML = '';

        vacinacoes.forEach((vacinacao) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${formatarData(vacinacao.data_evento)}</td>
                <td>${vacinacao.nome_apelido ?? ''}</td>
                <td>${vacinacao.brinco ?? ''}</td>
                <td>${vacinacao.descricao ?? ''}</td>
                <td>${formatarData(vacinacao.proxima_data)}</td>
                <td><span class="${classeStatus(vacinacao.status)}">${vacinacao.status ?? ''}</span></td>
            `;

            tabelaVacinacoes.appendChild(tr);
        });

        atualizarCards(vacinacoes);
    }

    async function carregarVacinacoes() {
        try {
            const resposta = await fetch(endpointManejos, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!resposta.ok) {
                throw new Error('Erro ao buscar vacinações');
            }

            const registros = await resposta.json();
            const vacinacoes = (Array.isArray(registros) ? registros : []).filter(ehVacinacao);
            renderizarVacinacoes(vacinacoes);
        } catch (erro) {
            loading.textContent = 'Erro ao carregar vacinações.';
            console.error(erro);
        }
    }

    if (formVacinacao) {
        formVacinacao.addEventListener('submit', async function (event) {
            event.preventDefault();

            const dados = {
                animal_id: document.getElementById('animal_id').value,
                tipo: 'Vacinação',
                descricao: document.getElementById('descricao').value.trim(),
                data_evento: document.getElementById('data_evento').value,
                proxima_data: document.getElementById('proxima_data').value,
                status: document.getElementById('status').value
            };

            try {
                const resposta = await fetch(endpointManejos, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await resposta.json();

                if (!resposta.ok) {
                    mostrarMensagem(resultado.erro || 'Erro ao salvar vacinação.', 'erro');
                    return;
                }

                mostrarMensagem(resultado.mensagem || 'Vacinação cadastrada com sucesso.', 'sucesso');
                formVacinacao.reset();
                document.getElementById('status').value = 'Realizado';
                document.getElementById('data_evento').value = new Date().toISOString().slice(0, 10);
                loading.style.display = 'block';
                loading.textContent = 'Carregando vacinações...';
                await carregarVacinacoes();
            } catch (erro) {
                mostrarMensagem('Erro de comunicação com o servidor.', 'erro');
                console.error(erro);
            }
        });
    }

    carregarVacinacoes();
</script>

<?php layoutFim(); ?>
