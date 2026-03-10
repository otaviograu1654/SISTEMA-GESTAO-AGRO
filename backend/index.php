<?php
?>
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

        @media (max-width: 700px) {
            .header h1 {
                font-size: 22px;
            }

            .card .value {
                font-size: 24px;
            }
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
                    emptyState.style.display = 'block';
                    atualizarCards([]);
                    return;
                }

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

        carregarAnimais();
    </script>
</body>
</html>
