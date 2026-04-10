<?php
require_once __DIR__ . '/includes/layout.php';
layoutInicio('Compras');
?>

<div class="page-header">
    <h1>Compras</h1>
    <p>Controle as compras de insumos, medicamentos, rações e materiais da fazenda.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Total de compras</h3>
        <div class="value" id="totalCompras">0</div>
    </div>
    <div class="card">
        <h3>Valor comprado</h3>
        <div class="value" id="valorComprado">R$ 0,00</div>
    </div>
    <div class="card">
        <h3>Compras hoje</h3>
        <div class="value" id="comprasHoje">0</div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Registrar compra</h2>
        <p>As compras são lançadas como despesas no módulo financeiro.</p>

        <form id="formCompra">
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione</option>
                    <option value="Compra - Insumos">Compra - Insumos</option>
                    <option value="Compra - Sanidade">Compra - Sanidade</option>
                    <option value="Compra - Alimentação">Compra - Alimentação</option>
                    <option value="Compra - Equipamentos">Compra - Equipamentos</option>
                    <option value="Compra - Outros">Compra - Outros</option>
                </select>
            </div>

            <div class="form-group">
                <label for="data_lancamento">Data da compra</label>
                <input type="date" id="data_lancamento" name="data_lancamento" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label for="fornecedor">Fornecedor</label>
                <input type="text" id="fornecedor" name="fornecedor" placeholder="Ex: Agro Forte" required>
            </div>

            <div class="form-group">
                <label for="produto">Produto</label>
                <input type="text" id="produto" name="produto" placeholder="Ex: Ração 25kg" required>
            </div>

            <div class="form-group">
                <label for="quantidade">Quantidade</label>
                <input type="number" id="quantidade" name="quantidade" min="1" step="1" placeholder="0" required>
            </div>

            <div class="form-group">
                <label for="valor_unitario">Valor unitário (R$)</label>
                <input type="number" id="valor_unitario" name="valor_unitario" min="0.01" step="0.01" placeholder="0,00" required>
            </div>

            <div class="form-group full-width">
                <label for="observacao">Observação</label>
                <input type="text" id="observacao" name="observacao" placeholder="Opcional">
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar compra</button>
            </div>
        </form>

        <div id="mensagem" class="mensagem"></div>
    </section>

    <section class="panel">
        <h2>Compras lançadas</h2>
        <p>Despesas registradas no financeiro e classificadas como compras.</p>

        <div id="loading" class="loading">Carregando compras...</div>

        <div class="table-wrapper" id="tableWrapper" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Categoria</th>
                        <th>Fornecedor</th>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Valor unitário</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tabelaCompras"></tbody>
            </table>
        </div>

        <div id="emptyState" class="empty" style="display: none;">
            Nenhuma compra cadastrada.
        </div>
    </section>
</div>

<script>
    const endpointFinanceiro = 'financeiro.php';
    const formCompra = document.getElementById('formCompra');
    const mensagem = document.getElementById('mensagem');
    const loading = document.getElementById('loading');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const tabelaCompras = document.getElementById('tabelaCompras');

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

    function ehCompra(lancamento) {
        if (normalizarTexto(lancamento.tipo) !== 'despesa') {
            return false;
        }

        const categoria = normalizarTexto(lancamento.categoria);
        const descricao = normalizarTexto(lancamento.descricao);

        return categoria.includes('compra') || descricao.includes('fornecedor:');
    }

    function montarDescricaoCompra(dadosCompra) {
        const partes = [
            `Fornecedor: ${dadosCompra.fornecedor}`,
            `Produto: ${dadosCompra.produto}`,
            `Quantidade: ${dadosCompra.quantidade}`
        ];

        if (dadosCompra.observacao) {
            partes.push(`Observação: ${dadosCompra.observacao}`);
        }

        return partes.join(' | ');
    }

    function extrairDetalhesCompra(descricao) {
        const detalhes = {
            fornecedor: '',
            produto: '',
            quantidade: 0,
            observacao: ''
        };

        (descricao || '').split('|').forEach((parte) => {
            const [chave, ...resto] = parte.split(':');
            const valor = resto.join(':').trim();

            switch (normalizarTexto(chave)) {
                case 'fornecedor':
                    detalhes.fornecedor = valor;
                    break;
                case 'produto':
                    detalhes.produto = valor;
                    break;
                case 'quantidade':
                    detalhes.quantidade = Number(valor) || 0;
                    break;
                case 'observacao':
                    detalhes.observacao = valor;
                    break;
            }
        });

        return detalhes;
    }

    function atualizarCards(compras) {
        const hoje = new Date().toISOString().slice(0, 10);
        const totalCompras = compras.length;
        const valorComprado = compras.reduce((acumulado, compra) => acumulado + Number(compra.valor || 0), 0);
        const comprasHoje = compras.filter((compra) => compra.data_lancamento === hoje).length;

        document.getElementById('totalCompras').textContent = totalCompras;
        document.getElementById('valorComprado').textContent = formatarMoeda(valorComprado);
        document.getElementById('comprasHoje').textContent = comprasHoje;
    }

    function criarColuna(texto) {
        const td = document.createElement('td');
        td.textContent = texto;
        return td;
    }

    function renderizarCompras(compras) {
        loading.style.display = 'none';

        if (!Array.isArray(compras) || compras.length === 0) {
            tableWrapper.style.display = 'none';
            emptyState.style.display = 'block';
            tabelaCompras.innerHTML = '';
            atualizarCards([]);
            return;
        }

        emptyState.style.display = 'none';
        tableWrapper.style.display = 'block';
        tabelaCompras.innerHTML = '';

        compras.forEach((compra) => {
            const detalhes = extrairDetalhesCompra(compra.descricao);
            const quantidade = Number(detalhes.quantidade || 0);
            const total = Number(compra.valor || 0);
            const valorUnitario = quantidade > 0 ? total / quantidade : 0;
            const tr = document.createElement('tr');

            tr.appendChild(criarColuna(formatarData(compra.data_lancamento)));
            tr.appendChild(criarColuna(compra.categoria || ''));
            tr.appendChild(criarColuna(detalhes.fornecedor || '-'));
            tr.appendChild(criarColuna(detalhes.produto || (compra.descricao || '-')));
            tr.appendChild(criarColuna(quantidade > 0 ? String(quantidade) : '-'));
            tr.appendChild(criarColuna(quantidade > 0 ? formatarMoeda(valorUnitario) : '-'));
            tr.appendChild(criarColuna(formatarMoeda(total)));

            tabelaCompras.appendChild(tr);
        });

        atualizarCards(compras);
    }

    async function carregarCompras() {
        try {
            const resposta = await fetch(endpointFinanceiro, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!resposta.ok) {
                throw new Error('Erro ao buscar compras');
            }

            const registros = await resposta.json();
            const compras = (Array.isArray(registros) ? registros : []).filter(ehCompra);
            renderizarCompras(compras);
        } catch (erro) {
            loading.textContent = 'Erro ao carregar compras.';
            console.error(erro);
        }
    }

    if (formCompra) {
        formCompra.addEventListener('submit', async function (event) {
            event.preventDefault();

            const fornecedor = document.getElementById('fornecedor').value.trim();
            const produto = document.getElementById('produto').value.trim();
            const quantidade = Number(document.getElementById('quantidade').value);
            const valorUnitario = Number(document.getElementById('valor_unitario').value);
            const observacao = document.getElementById('observacao').value.trim();
            const valorTotal = quantidade * valorUnitario;

            if (!Number.isFinite(quantidade) || !Number.isFinite(valorUnitario) || quantidade <= 0 || valorUnitario <= 0) {
                mostrarMensagem('Informe quantidade e valor unitário válidos.', 'erro');
                return;
            }

            const dados = {
                tipo: 'Despesa',
                categoria: document.getElementById('categoria').value,
                descricao: montarDescricaoCompra({
                    fornecedor,
                    produto,
                    quantidade,
                    observacao
                }),
                valor: valorTotal.toFixed(2),
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
                    mostrarMensagem(resultado.erro || 'Erro ao salvar compra.', 'erro');
                    return;
                }

                mostrarMensagem(resultado.mensagem || 'Compra cadastrada com sucesso.', 'sucesso');
                formCompra.reset();
                document.getElementById('data_lancamento').value = new Date().toISOString().slice(0, 10);
                loading.style.display = 'block';
                loading.textContent = 'Carregando compras...';
                await carregarCompras();
            } catch (erro) {
                mostrarMensagem('Erro de comunicação com o servidor.', 'erro');
                console.error(erro);
            }
        });
    }

    carregarCompras();
</script>

<?php layoutFim(); ?>
