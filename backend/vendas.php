<?php
require_once __DIR__ . '/includes/layout.php';
layoutInicio('Vendas');
?>

<div class="page-header">
    <h1>Vendas</h1>
    <p>Controle de vendas de produtos, animais ou produção.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Total de vendas</h3>
        <div class="value" id="totalVendas">0</div>
    </div>
    <div class="card">
        <h3>Valor vendido</h3>
        <div class="value" id="valorVendido">R$ 0,00</div>
    </div>
    <div class="card">
        <h3>Vendas hoje</h3>
        <div class="value" id="vendasHoje">0</div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Lançar venda</h2>
        <p>Registre a venda como uma receita do módulo financeiro.</p>

        <form id="formVenda">
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione</option>
                    <option value="Venda de animais">Venda de animais</option>
                    <option value="Venda de leite">Venda de leite</option>
                    <option value="Venda de derivados">Venda de derivados</option>
                    <option value="Venda de insumos">Venda de insumos</option>
                    <option value="Outras vendas">Outras vendas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="data_lancamento">Data da venda</label>
                <input type="date" id="data_lancamento" name="data_lancamento" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="descricao">Descrição</label>
                <input type="text" id="descricao" name="descricao" placeholder="Ex: Venda de 2 novilhas para cliente local" required>
            </div>

            <div class="form-group">
                <label for="valor">Valor (R$)</label>
                <input type="number" id="valor" name="valor" min="0.01" step="0.01" placeholder="0,00" required>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar venda</button>
            </div>
        </form>

        <div id="mensagem" class="mensagem"></div>
    </section>

    <section class="panel">
        <h2>Vendas lançadas</h2>
        <p>Receitas registradas no sistema e classificadas como vendas.</p>

        <div id="loading" class="loading">Carregando vendas...</div>

        <div class="table-wrapper" id="tableWrapper" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody id="tabelaVendas"></tbody>
            </table>
        </div>

        <div id="emptyState" class="empty" style="display: none;">
            Nenhuma venda cadastrada.
        </div>
    </section>
</div>

<script>
    const endpointFinanceiro = 'financeiro.php';
    const formVenda = document.getElementById('formVenda');
    const mensagem = document.getElementById('mensagem');
    const loading = document.getElementById('loading');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const tabelaVendas = document.getElementById('tabelaVendas');

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

    function ehVenda(lancamento) {
        if (normalizarTexto(lancamento.tipo) !== 'receita') {
            return false;
        }

        const categoria = normalizarTexto(lancamento.categoria);
        const descricao = normalizarTexto(lancamento.descricao);

        return categoria.includes('venda') || descricao.includes('venda');
    }

    function atualizarCards(vendas) {
        const hoje = new Date().toISOString().slice(0, 10);
        const totalVendas = vendas.length;
        const valorVendido = vendas.reduce((acumulado, venda) => acumulado + Number(venda.valor || 0), 0);
        const vendasHoje = vendas.filter((venda) => venda.data_lancamento === hoje).length;

        document.getElementById('totalVendas').textContent = totalVendas;
        document.getElementById('valorVendido').textContent = formatarMoeda(valorVendido);
        document.getElementById('vendasHoje').textContent = vendasHoje;
    }

    function renderizarVendas(vendas) {
        loading.style.display = 'none';

        if (!Array.isArray(vendas) || vendas.length === 0) {
            tableWrapper.style.display = 'none';
            emptyState.style.display = 'block';
            tabelaVendas.innerHTML = '';
            atualizarCards([]);
            return;
        }

        emptyState.style.display = 'none';
        tableWrapper.style.display = 'block';
        tabelaVendas.innerHTML = '';

        vendas.forEach((venda) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${formatarData(venda.data_lancamento)}</td>
                <td>${venda.categoria ?? ''}</td>
                <td>${venda.descricao ?? ''}</td>
                <td>${formatarMoeda(venda.valor)}</td>
            `;

            tabelaVendas.appendChild(tr);
        });

        atualizarCards(vendas);
    }

    async function carregarVendas() {
        try {
            const resposta = await fetch(endpointFinanceiro, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!resposta.ok) {
                throw new Error('Erro ao buscar vendas');
            }

            const registros = await resposta.json();
            const vendas = (Array.isArray(registros) ? registros : []).filter(ehVenda);
            renderizarVendas(vendas);
        } catch (erro) {
            loading.textContent = 'Erro ao carregar vendas.';
            console.error(erro);
        }
    }

    if (formVenda) {
        formVenda.addEventListener('submit', async function (event) {
            event.preventDefault();

            const dados = {
                tipo: 'Receita',
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
                    mostrarMensagem(resultado.erro || 'Erro ao salvar venda.', 'erro');
                    return;
                }

                mostrarMensagem(resultado.mensagem || 'Venda cadastrada com sucesso.', 'sucesso');
                formVenda.reset();
                document.getElementById('data_lancamento').value = new Date().toISOString().slice(0, 10);
                loading.style.display = 'block';
                loading.textContent = 'Carregando vendas...';
                await carregarVendas();
            } catch (erro) {
                mostrarMensagem('Erro de comunicação com o servidor.', 'erro');
                console.error(erro);
            }
        });
    }

    carregarVendas();
</script>

<?php layoutFim(); ?>
