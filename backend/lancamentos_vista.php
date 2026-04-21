<!--PAGINA E PARA MOVIMENTAÇÃO IMEDIATA-->
<?php
//PAGINA E PARA MOVIMENTAÇÃO IMEDIATA
require_once __DIR__ . '/includes/layout.php';
layoutInicio('Lançamentos à vista');
?>

<div class="page-header">
    <h1>Lançamentos à vista</h1>
    <p>Movimentações pagas ou recebidas na hora.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Total de lançamentos</h3>
        <div class="value" id="totalLancamentos">0</div>
    </div>
    <div class="card">
        <h3>Entradas</h3>
        <div class="value" id="totalEntradas">R$ 0,00</div>
    </div>
    <div class="card">
        <h3>Saídas</h3>
        <div class="value" id="totalSaidas">R$ 0,00</div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Registrar lançamento</h2>
        <p>Use esta tela para receitas e despesas liquidadas no mesmo momento.</p>

        <form id="formLancamentoVista">
            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione</option>
                    <option value="Receita">Receita</option>
                    <option value="Despesa">Despesa</option>
                </select>
            </div>

            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione</option>
                    <option value="Lançamento à vista - Venda">Lançamento à vista - Venda</option>
                    <option value="Lançamento à vista - Compra">Lançamento à vista - Compra</option>
                    <option value="Lançamento à vista - Serviço">Lançamento à vista - Serviço</option>
                    <option value="Lançamento à vista - Insumo">Lançamento à vista - Insumo</option>
                    <option value="Lançamento à vista - Outro">Lançamento à vista - Outro</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="descricao">Descrição</label>
                <input type="text" id="descricao" name="descricao" placeholder="Ex: Pagamento à vista de ração" required>
            </div>

            <div class="form-group">
                <label for="valor">Valor (R$)</label>
                <input type="number" id="valor" name="valor" min="0.01" step="0.01" placeholder="0,00" required>
            </div>

            <div class="form-group">
                <label for="data_lancamento">Data</label>
                <input type="date" id="data_lancamento" name="data_lancamento" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar lançamento</button>
            </div>
        </form>

        <div id="mensagem" class="mensagem"></div>
    </section>

    <section class="panel">
        <h2>Caixa imediato</h2>
        <p>Lançamentos registrados no financeiro com categoria de pagamento à vista.</p>

        <div id="loading" class="loading">Carregando lançamentos...</div>

        <div class="table-wrapper hidden" id="tableWrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody id="tabelaLancamentos"></tbody>
            </table>
        </div>

        <div id="emptyState" class="empty hidden">
            Nenhum lançamento à vista cadastrado.
        </div>
    </section>
</div>

<script>
    const endpointFinanceiro = 'financeiro.php';
    const formLancamentoVista = document.getElementById('formLancamentoVista');
    const mensagem = document.getElementById('mensagem');
    const loading = document.getElementById('loading');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const tabelaLancamentos = document.getElementById('tabelaLancamentos');

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

    function formatarMoeda(valor) {
        const numero = Number(valor);

        if (Number.isNaN(numero)) {
            return 'R$ 0,00';
        }

        return numero.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function normalizarTexto(texto) {
        return (texto || '')
            .toString()
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function ehLancamentoVista(lancamento) {
        const categoria = normalizarTexto(lancamento.categoria);

        return categoria.includes('lancamento a vista');
    }

    function atualizarCards(lancamentos) {
        const totalLancamentos = lancamentos.length;
        const totalEntradas = lancamentos
            .filter((lancamento) => normalizarTexto(lancamento.tipo) === 'receita')
            .reduce((acumulado, lancamento) => acumulado + Number(lancamento.valor || 0), 0);
        const totalSaidas = lancamentos
            .filter((lancamento) => normalizarTexto(lancamento.tipo) === 'despesa')
            .reduce((acumulado, lancamento) => acumulado + Number(lancamento.valor || 0), 0);

        document.getElementById('totalLancamentos').textContent = totalLancamentos;
        document.getElementById('totalEntradas').textContent = formatarMoeda(totalEntradas);
        document.getElementById('totalSaidas').textContent = formatarMoeda(totalSaidas);
    }

    function renderizarLancamentos(lancamentos) {
        loading.style.display = 'none';

        if (!Array.isArray(lancamentos) || lancamentos.length === 0) {
            tableWrapper.style.display = 'none';
            emptyState.style.display = 'block';
            tabelaLancamentos.innerHTML = '';
            atualizarCards([]);
            return;
        }

        emptyState.style.display = 'none';
        tableWrapper.style.display = 'block';
        tabelaLancamentos.innerHTML = '';

        lancamentos.forEach((lancamento) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${formatarData(lancamento.data_lancamento)}</td>
                <td>${lancamento.tipo ?? ''}</td>
                <td>${lancamento.categoria ?? ''}</td>
                <td>${lancamento.descricao ?? ''}</td>
                <td>${formatarMoeda(lancamento.valor)}</td>
            `;

            tabelaLancamentos.appendChild(tr);
        });

        atualizarCards(lancamentos);
    }

    async function carregarLancamentos() {
        try {
            const resposta = await fetch(endpointFinanceiro, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!resposta.ok) {
                throw new Error('Erro ao buscar lançamentos');
            }

            const registros = await resposta.json();
            const lancamentos = (Array.isArray(registros) ? registros : []).filter(ehLancamentoVista);
            renderizarLancamentos(lancamentos);
        } catch (erro) {
            loading.textContent = 'Erro ao carregar lançamentos.';
            console.error(erro);
        }
    }

    if (formLancamentoVista) {
        formLancamentoVista.addEventListener('submit', async function (event) {
            event.preventDefault();

            const dados = {
                tipo: document.getElementById('tipo').value,
                categoria: document.getElementById('categoria').value,
                descricao: document.getElementById('descricao').value.trim(),
                valor: document.getElementById('valor').value,
                data_lancamento: document.getElementById('data_lancamento').value
            };

            try {
                const resposta = await fetch(endpointFinanceiro, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await resposta.json();

                if (!resposta.ok) {
                    mostrarMensagem(resultado.erro || 'Erro ao salvar lançamento.', 'erro');
                    return;
                }

                mostrarMensagem(resultado.mensagem || 'Lançamento à vista cadastrado com sucesso.', 'sucesso');
                formLancamentoVista.reset();
                document.getElementById('data_lancamento').value = new Date().toISOString().slice(0, 10);
                loading.style.display = 'block';
                loading.textContent = 'Carregando lançamentos...';
                await carregarLancamentos();
            } catch (erro) {
                mostrarMensagem('Erro de comunicação com o servidor.', 'erro');
                console.error(erro);
            }
        });
    }

    carregarLancamentos();
</script>

<?php layoutFim(); ?>
