<?php
$lancamentos = [
    [
        'data_lancamento' => '2026-03-26',
        'tipo' => 'Entrada',
        'descricao' => 'Venda de leite - tanque da manhã',
        'categoria' => 'Produção',
        'origem_destino' => 'Cooperativa Vale Verde',
        'forma_pagamento' => 'PIX',
        'valor' => 912.40,
    ],
    [
        'data_lancamento' => '2026-03-25',
        'tipo' => 'Saída',
        'descricao' => 'Compra emergencial de ração',
        'categoria' => 'Insumos',
        'origem_destino' => 'Agro Forte',
        'forma_pagamento' => 'Dinheiro',
        'valor' => 480.00,
    ],
    [
        'data_lancamento' => '2026-03-24',
        'tipo' => 'Entrada',
        'descricao' => 'Venda de queijo artesanal',
        'categoria' => 'Derivados',
        'origem_destino' => 'Mercado do Campo',
        'forma_pagamento' => 'Transferência',
        'valor' => 693.00,
    ],
    [
        'data_lancamento' => '2026-03-23',
        'tipo' => 'Saída',
        'descricao' => 'Frete de entrega',
        'categoria' => 'Logística',
        'origem_destino' => 'Transportadora Ribeiro',
        'forma_pagamento' => 'PIX',
        'valor' => 135.00,
    ],
];

function formatarMoeda($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

$totalEntradas = 0;
$totalSaidas = 0;

foreach ($lancamentos as $lancamento) {
    if ($lancamento['tipo'] === 'Entrada') {
        $totalEntradas += $lancamento['valor'];
    } else {
        $totalSaidas += $lancamento['valor'];
    }
}

$saldo = $totalEntradas - $totalSaidas;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - Lançamentos à Vista</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        html, body {
            min-height: 100%;
        }

        .sidebar {
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
        }

        .main {
            min-height: calc(100vh - 70px);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .page-header h1 {
            margin: 0 0 6px;
            font-size: 28px;
            color: #1f7a3f;
        }

        .page-header p {
            margin: 0;
            color: #666;
        }

        .acoes-topo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-secundario {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid #1f7a3f;
            color: #1f7a3f;
            background: #fff;
        }

        .btn-secundario:hover {
            background: #e7f6ec;
        }

        .grid-resumo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card-resumo {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        .card-resumo .label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .card-resumo .valor {
            font-size: 28px;
            font-weight: bold;
            color: #1f7a3f;
        }

        .card-resumo .valor.entrada {
            color: #1f7a3f;
        }

        .card-resumo .valor.saida {
            color: #b42318;
        }

        .card-resumo .valor.saldo-negativo {
            color: #b42318;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .badge-tipo {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-entrada {
            background: #e7f6ec;
            color: #1f7a3f;
        }

        .badge-saida {
            background: #fdeaea;
            color: #b42318;
        }

        .valor-entrada {
            color: #1f7a3f;
            font-weight: bold;
        }

        .valor-saida {
            color: #b42318;
            font-weight: bold;
        }

        .texto-suave {
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <button id="btnMenu" class="btn-Menu">☰</button>
        <div class="titulo">
            <h2>SGA Pecuária</h2>
            <p>Fazenda Paraíso</p>
        </div>
    </header>

    <div id="overlay" class="overlay"></div>

    <div class="layout">
        <?php include __DIR__ . '/includes/menu.php'; ?>

        <main class="main">
            <div class="content">
                <div class="page-header">
                    <div>
                        <h1>Lançamentos à Vista</h1>
                        <p>Controle de entradas e saídas com liquidação imediata, sem pendência em contas a pagar ou receber.</p>
                    </div>

                    <div class="acoes-topo">
                        <a href="dashboard.php" class="btn-secundario">Voltar ao dashboard</a>
                    </div>
                </div>

                <div class="grid-resumo">
                    <div class="card-resumo">
                        <div class="label">Total de lançamentos</div>
                        <div class="valor"><?= count($lancamentos) ?></div>
                    </div>

                    <div class="card-resumo">
                        <div class="label">Entradas à vista</div>
                        <div class="valor entrada"><?= formatarMoeda($totalEntradas) ?></div>
                    </div>

                    <div class="card-resumo">
                        <div class="label">Saídas à vista</div>
                        <div class="valor saida"><?= formatarMoeda($totalSaidas) ?></div>
                    </div>

                    <div class="card-resumo">
                        <div class="label">Saldo</div>
                        <div class="valor <?= $saldo < 0 ? 'saldo-negativo' : '' ?>">
                            <?= formatarMoeda($saldo) ?>
                        </div>
                    </div>
                </div>

                <section class="panel">
                    <h2>Novo lançamento</h2>

                    <form action="#" method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="data_lancamento">Data</label>
                                <input type="date" id="data_lancamento" name="data_lancamento">
                            </div>

                            <div class="form-group">
                                <label for="tipo">Tipo</label>
                                <select id="tipo" name="tipo">
                                    <option value="">Selecione</option>
                                    <option value="Entrada">Entrada</option>
                                    <option value="Saída">Saída</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="categoria">Categoria</label>
                                <select id="categoria" name="categoria">
                                    <option value="">Selecione</option>
                                    <option value="Produção">Produção</option>
                                    <option value="Animal">Animal</option>
                                    <option value="Derivados">Derivados</option>
                                    <option value="Insumos">Insumos</option>
                                    <option value="Logística">Logística</option>
                                    <option value="Sanidade">Sanidade</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="forma_pagamento">Forma de pagamento</label>
                                <select id="forma_pagamento" name="forma_pagamento">
                                    <option value="">Selecione</option>
                                    <option value="PIX">PIX</option>
                                    <option value="Dinheiro">Dinheiro</option>
                                    <option value="Cartão">Cartão</option>
                                    <option value="Transferência">Transferência</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="descricao">Descrição</label>
                                <input type="text" id="descricao" name="descricao" placeholder="Ex: Venda de leite, compra de ração, frete, medicamento">
                            </div>

                            <div class="form-group">
                                <label for="origem_destino">Origem / destino</label>
                                <input type="text" id="origem_destino" name="origem_destino" placeholder="Ex: Agro Forte, Cooperativa Vale Verde">
                            </div>

                            <div class="form-group">
                                <label for="valor">Valor (R$)</label>
                                <input type="number" id="valor" name="valor" min="0" step="0.01" placeholder="0,00">
                            </div>

                            <div class="form-group full-width">
                                <label for="observacao">Observação</label>
                                <input type="text" id="observacao" name="observacao" placeholder="Opcional">
                            </div>

                            <div class="form-group full-width">
                                <button type="submit">Salvar lançamento</button>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <h2>Histórico de lançamentos</h2>
                    <p class="texto-suave">Movimentações liquidadas no ato.</p>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Origem / destino</th>
                                    <th>Pagamento</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lancamentos)): ?>
                                    <tr>
                                        <td colspan="7">Nenhum lançamento à vista cadastrado ainda.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lancamentos as $lancamento): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($lancamento['data_lancamento']))) ?></td>
                                            <td>
                                                <span class="badge-tipo <?= $lancamento['tipo'] === 'Entrada' ? 'badge-entrada' : 'badge-saida' ?>">
                                                    <?= htmlspecialchars($lancamento['tipo']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($lancamento['descricao']) ?></td>
                                            <td><?= htmlspecialchars($lancamento['categoria']) ?></td>
                                            <td><?= htmlspecialchars($lancamento['origem_destino']) ?></td>
                                            <td><?= htmlspecialchars($lancamento['forma_pagamento']) ?></td>
                                            <td>
                                                <span class="<?= $lancamento['tipo'] === 'Entrada' ? 'valor-entrada' : 'valor-saida' ?>">
                                                    <?= $lancamento['tipo'] === 'Entrada' ? '+ ' : '- ' ?>
                                                    <?= formatarMoeda($lancamento['valor']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function toggleSubMenu(idSubmenu, elementoLink) {
            const submenu = document.getElementById(idSubmenu);
            const setinha = elementoLink.querySelector('.setinha');

            if (!submenu) return;

            if (submenu.style.display === "none" || submenu.style.display === "") {
                submenu.style.display = "block";
                if (setinha) setinha.classList.add("girar");
            } else {
                submenu.style.display = "none";
                if (setinha) setinha.classList.remove("girar");
            }
        }

        const btnMenu = document.getElementById('btnMenu');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        if (btnMenu && sidebar && overlay) {
            btnMenu.addEventListener('click', function() {
                sidebar.classList.toggle('aberto');
                overlay.classList.toggle('ativo');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('aberto');
                overlay.classList.remove('ativo');
            });
        }
    </script>
</body>
</html>
