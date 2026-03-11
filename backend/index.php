<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - Dashboard</title>
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

        .header {
            background: linear-gradient(135deg, #1f7a3f, #2fa35a);
            color: white;
            padding: 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header p {
            margin: 8px 0 0;
            opacity: 0.95;
        }

        .container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #555;
        }

        .card .value {
            font-size: 30px;
            font-weight: bold;
            color: #1f7a3f;
        }

        .grid-panels {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .panel {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 22px;
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
        }

        th {
            background: #f0f7f2;
            color: #1f7a3f;
        }

        tr:hover {
            background: #fafafa;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e7f6ec;
            color: #1f7a3f;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            padding: 20px;
            text-align: center;
            color: #777;
        }

        .loading {
            padding: 20px;
            text-align: center;
            color: #555;
        }

        form {
            display: grid;
            gap: 12px;
        }

        label {
            font-size: 14px;
            font-weight: bold;
            color: #444;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8d8d8;
            border-radius: 10px;
            font-size: 14px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2fa35a;
            box-shadow: 0 0 0 3px rgba(47,163,90,0.12);
        }

        button {
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1f7a3f;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s ease;
        }

        button:hover {
            background: #186232;
        }

        .mensagem {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            display: none;
        }

        .mensagem.sucesso {
            background: #e7f6ec;
            color: #1f7a3f;
            display: block;
        }

        .mensagem.erro {
            background: #fdeaea;
            color: #b42318;
            display: block;
        }

        .helper-text {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }

        @media (max-width: 900px) {
            .grid-panels {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .header h1 {
                font-size: 22px;
            }

            .card .value {
                font-size: 24px;
            }
        }
        .section-spacing {
    margin-top: 24px;
}

.small-text {
    font-size: 13px;
    color: #666;
}

.badge-blue {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    background: #e8f1ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: bold;
}
    </style>
</head>
<body>
    <div class="header">
        <h1>SGA Pecuária</h1>
        <p>Dashboard simples do rebanho</p>
    </div>

    <div class="container">
        <div class="cards">
            <div class="card">
                <h3>Total de animais</h3>
                <div class="value" id="totalAnimais">0</div>
            </div>

            <div class="card">
                <h3>Machos</h3>
                <div class="value" id="totalMachos">0</div>
            </div>

            <div class="card">
                <h3>Fêmeas</h3>
                <div class="value" id="totalFemeas">0</div>
            </div>

            <div class="card">
                <h3>Status do sistema</h3>
                <div class="value" style="font-size: 20px;">Online</div>
            </div>
        </div>

        <div class="grid-panels">
            <div class="panel">
                <h2>Animais cadastrados</h2>

                <div id="loading" class="loading">Carregando animais...</div>

                <div class="table-wrapper" id="tableWrapper" style="display: none;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Brinco</th>
                                <th>Nome</th>
                                <th>Raça</th>
                                <th>Sexo</th>
                                <th>Nascimento</th>
                                <th>Lote</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaAnimais"></tbody>
                    </table>
                </div>

                <div id="emptyState" class="empty" style="display: none;">
                    Nenhum animal cadastrado.
                </div>
            </div>

            <div class="panel">
                <h2>Cadastrar animal</h2>

                <form id="formAnimal">
                    <div>
                        <label for="brinco">Brinco</label>
                        <input type="text" id="brinco" name="brinco" required>
                    </div>

                    <div>
                        <label for="nome_apelido">Nome / Apelido</label>
                        <input type="text" id="nome_apelido" name="nome_apelido" required>
                    </div>

                    <div>
                        <label for="raca">Raça</label>
                        <input type="text" id="raca" name="raca" required>
                    </div>

                    <div>
                        <label for="sexo">Sexo</label>
                        <select id="sexo" name="sexo" required>
                            <option value="">Selecione</option>
                            <option value="Macho">Macho</option>
                            <option value="Fêmea">Fêmea</option>
                        </select>
                    </div>

                    <div>
                        <label for="data_nascimento">Data de nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento">
                    </div>

                    <div>
                        <label for="lote">Lote</label>
                        <input type="text" id="lote" name="lote">
                    </div>

                    <button type="submit">Cadastrar animal</button>
                </form>

                <div id="mensagem" class="mensagem"></div>
                <div class="helper-text">
                    Preencha os dados principais do animal e clique em cadastrar.
                </div>
            </div>
        </div>
    </div>
<div class="grid-panels section-spacing">
    <div class="panel">
        <h2>Últimas pesagens</h2>

        <div id="loadingPesagens" class="loading">Carregando pesagens...</div>

        <div class="table-wrapper" id="tablePesagensWrapper" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Animal</th>
                        <th>Brinco</th>
                        <th>Data</th>
                        <th>Peso (kg)</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody id="tabelaPesagens"></tbody>
            </table>
        </div>

        <div id="emptyPesagens" class="empty" style="display: none;">
            Nenhuma pesagem cadastrada.
        </div>
    </div>

    <div class="panel">
        <h2>Registrar pesagem</h2>

        <form id="formPesagem">
            <div>
                <label for="animal_id">Animal</label>
                <select id="animal_id" name="animal_id" required>
                    <option value="">Selecione um animal</option>
                </select>
            </div>

            <div>
                <label for="data_pesagem">Data da pesagem</label>
                <input type="date" id="data_pesagem" name="data_pesagem" required>
            </div>

            <div>
                <label for="peso_kg">Peso (kg)</label>
                <input type="number" step="0.01" id="peso_kg" name="peso_kg" required>
            </div>

            <div>
                <label for="observacao">Observação</label>
                <input type="text" id="observacao" name="observacao">
            </div>

            <button type="submit">Cadastrar pesagem</button>
        </form>

        <div id="mensagemPesagem" class="mensagem"></div>
        <div class="helper-text">
            Registre o peso do animal selecionado.
        </div>
    </div>
</div>
    <script>
        async function carregarAnimais() {
            const loading = document.getElementById('loading');
            const tableWrapper = document.getElementById('tableWrapper');
            const emptyState = document.getElementById('emptyState');
            const tabelaAnimais = document.getElementById('tabelaAnimais');
            
            try {
                const resposta = await fetch('animais.php');
                const animais = await resposta.json();

                loading.style.display = 'none';

                if (!Array.isArray(animais) || animais.length === 0) {
                    tableWrapper.style.display = 'none';
                    emptyState.style.display = 'block';
                    tabelaAnimais.innerHTML = '';
                    atualizarCards([]);
                    return;
                }

                emptyState.style.display = 'none';
                tableWrapper.style.display = 'block';
                tabelaAnimais.innerHTML = '';

                animais.forEach(animal => {
                    const tr = document.createElement('tr');

                    tr.innerHTML = `
                        <td>${animal.id ?? ''}</td>
                        <td>${animal.brinco ?? ''}</td>
                        <td>${animal.nome_apelido ?? ''}</td>
                        <td>${animal.raca ?? ''}</td>
                        <td>${animal.sexo ?? ''}</td>
                        <td>${animal.data_nascimento ?? ''}</td>
                        <td>${animal.lote ?? ''}</td>
                        <td><span class="status">Ativo</span></td>
                    `;

                    tabelaAnimais.appendChild(tr);
                });

                atualizarCards(animais);
                popularSelectAnimais(animais);

            } catch (erro) {
                loading.textContent = 'Erro ao carregar animais.';
                console.error('Erro:', erro);
            }
        }

        function atualizarCards(animais) {
            const totalAnimais = animais.length;

            const totalMachos = animais.filter(animal =>
                (animal.sexo || '').toLowerCase() === 'macho'
            ).length;

            const totalFemeas = animais.filter(animal =>
                (animal.sexo || '').toLowerCase() === 'fêmea' ||
                (animal.sexo || '').toLowerCase() === 'femea'
            ).length;

            document.getElementById('totalAnimais').textContent = totalAnimais;
            document.getElementById('totalMachos').textContent = totalMachos;
            document.getElementById('totalFemeas').textContent = totalFemeas;
        }

        function mostrarMensagem(texto, tipo) {
            const mensagem = document.getElementById('mensagem');
            mensagem.textContent = texto;
            mensagem.className = `mensagem ${tipo}`;
        }
        function mostrarMensagemPesagem(texto, tipo) {
        const mensagem = document.getElementById('mensagemPesagem');
    mensagem.textContent = texto;
    mensagem.className = `mensagem ${tipo}`;
}

function popularSelectAnimais(animais) {
    const select = document.getElementById('animal_id');

    if (!select) return;

    select.innerHTML = '<option value="">Selecione um animal</option>';

    animais.forEach(animal => {
        const option = document.createElement('option');
        option.value = animal.id;
        option.textContent = `${animal.nome_apelido} - Brinco ${animal.brinco}`;
        select.appendChild(option);
    });
}

async function carregarPesagens() {
    const loading = document.getElementById('loadingPesagens');
    const tableWrapper = document.getElementById('tablePesagensWrapper');
    const emptyState = document.getElementById('emptyPesagens');
    const tabelaPesagens = document.getElementById('tabelaPesagens');

    try {
        const resposta = await fetch('pesagens.php');
        const pesagens = await resposta.json();

        loading.style.display = 'none';

        if (!Array.isArray(pesagens) || pesagens.length === 0) {
            tableWrapper.style.display = 'none';
            emptyState.style.display = 'block';
            tabelaPesagens.innerHTML = '';
            return;
        }

        emptyState.style.display = 'none';
        tableWrapper.style.display = 'block';
        tabelaPesagens.innerHTML = '';

        pesagens.forEach(pesagem => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${pesagem.id ?? ''}</td>
                <td>${pesagem.nome_apelido ?? ''}</td>
                <td>${pesagem.brinco ?? ''}</td>
                <td>${pesagem.data_pesagem ?? ''}</td>
                <td>${pesagem.peso_kg ?? ''}</td>
                <td>${pesagem.observacao ?? ''}</td>
            `;

            tabelaPesagens.appendChild(tr);
        });

    } catch (erro) {
        loading.textContent = 'Erro ao carregar pesagens.';
        console.error('Erro ao carregar pesagens:', erro);
    }
}
        document.getElementById('formAnimal').addEventListener('submit', async function(event) {
            event.preventDefault();

            const dados = {
                brinco: document.getElementById('brinco').value.trim(),
                nome_apelido: document.getElementById('nome_apelido').value.trim(),
                raca: document.getElementById('raca').value.trim(),
                sexo: document.getElementById('sexo').value,
                data_nascimento: document.getElementById('data_nascimento').value,
                lote: document.getElementById('lote').value.trim()
            };

            try {
                const resposta = await fetch('animais.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const resultado = await resposta.json();

                if (!resposta.ok) {
                    mostrarMensagem(resultado.erro || 'Erro ao cadastrar animal.', 'erro');
                    return;
                }

                mostrarMensagem(resultado.mensagem || 'Animal cadastrado com sucesso.', 'sucesso');
                document.getElementById('formAnimal').reset();
                document.getElementById('loading').style.display = 'block';
                document.getElementById('loading').textContent = 'Carregando animais...';
                await carregarAnimais();

            } catch (erro) {
                mostrarMensagem('Erro de comunicação com o servidor.', 'erro');
                console.error('Erro ao cadastrar:', erro);
            }
        });

        carregarAnimais();
    </script>
</body>
</html>
