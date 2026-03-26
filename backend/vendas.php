<?php
$vendas = [
    [
        'data_venda' => '2026-03-25',
        'cliente' => 'Laticínios Boa Serra',
        'produto' => 'Leite',
        'categoria' => 'Produção',
        'quantidade' => 320,
        'unidade' => 'L',
        'valor_unitario' => 2.85,
    ],
    [
        'data_venda' => '2026-03-24',
        'cliente' => 'Frigorífico Vale Verde',
        'produto' => 'Bezerro',
        'categoria' => 'Animal',
        'quantidade' => 2,
        'unidade' => 'un',
        'valor_unitario' => 1850.00,
    ],
    [
        'data_venda' => '2026-03-23',
        'cliente' => 'Mercado do Campo',
        'produto' => 'Queijo artesanal',
        'categoria' => 'Derivados',
        'quantidade' => 18,
        'unidade' => 'kg',
        'valor_unitario' => 38.50,
    ],
];

function formatarMoeda($valor)
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

$totalVendido = 0;
$totalItens = 0;

foreach ($vendas as $venda) {
    $totalVendido += $venda['quantidade'] * $venda['valor_unitario'];
    $totalItens += $venda['quantidade'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - Vendas</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .badge-categoria {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eaf7ee;
            color: #1f7a3f;
            font-size: 12px;
            font-weight: bold;
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
                        <h1>Vendas</h1>
                        <p>Registro de receitas com venda de animais, leite, derivados e outros produtos da propriedade.</p>
                    </div>

                    <div class="acoes-topo">
                        <a href="dashboard.php" class="btn-secundario">Voltar ao dashboard</a>
                    </div>
                </div>

                <div class="grid-resumo">
                    <div class="card-resumo">
                        <div class="label">Total de vendas</div>
                        <div class="valor"><?= count($vendas) ?></div>
                    </div>

                    <div class="card-resumo">
                        <div class="label">Quantidade movimentada</div>
                        <div class="valor"><?= $totalItens ?></div>
                    </div>

                    <div class="card-resumo">
                        <div class="label">Receita total</div>
                        <div class="valor"><?= formatarMoeda($totalVendido) ?></div>
                    </div>
                </div>

                <section class="panel">
                    <h2>Nova venda</h2>

                    <form action="#" method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="data_venda">Data da venda</label>
                                <input type="date" id="data_venda" name="data_venda">
                            </div>

                            <div class="form-group">
                                <label for="cliente">Cliente / comprador</label>
                                <input type="text" id="cliente" name="cliente" placeholder="Ex: Frigorífico Vale Verde">
                            </div>

                            <div class="form-group">
                                <label for="produto">Produto</label>
                                <input type="text" id="produto" name="produto" placeholder="Ex: Leite, Bezerro, Queijo">
                            </div>

                            <div class="form-group">
                                <label for="categoria">Categoria</label>
                                <select id="categoria" name="categoria">
                                    <option value="">Selecione</option>
                                    <option value="Animal">Animal</option>
                                    <option value="Produção">Produção</option>
                                    <option value="Derivados">Derivados</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="quantidade">Quantidade</label>
                                <input type="number" id="quantidade" name="quantidade" min="0" step="0.01">
                            </div>

                            <div class="form-group">
                                <label for="unidade">Unidade</label>
                                <select id="unidade" name="unidade">
                                    <option value="">Selecione</option>
                                    <option value="un">Unidade</option>
                                    <option value="kg">Kg</option>
                                    <option value="L">Litro</option>
                                    <option value="arroba">Arroba</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="valor_unitario">Valor unitário (R$)</label>
                                <input type="number" id="valor_unitario" name="valor_unitario" min="0" step="0.01">
                            </div>

                            <div class="form-group">
                                <label for="forma_pagamento">Forma de pagamento</label>
                                <select id="forma_pagamento" name="forma_pagamento">
                                    <option value="">Selecione</option>
                                    <option value="pix">PIX</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="prazo">A prazo</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="observacao">Observação</label>
                                <input type="text" id="observacao" name="observacao" placeholder="Opcional">
                            </div>

                            <div class="form-group full-width">
                                <button type="submit">Salvar venda</button>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <h2>Vendas lançadas</h2>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Qtd</th>
                                    <th>Unidade</th>
                                    <th>Valor unitário</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vendas)): ?>
                                    <tr>
                                        <td colspan="8">Nenhuma venda cadastrada ainda.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($vendas as $venda): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($venda['data_venda']))) ?></td>
                                            <td><?= htmlspecialchars($venda['cliente']) ?></td>
                                            <td><?= htmlspecialchars($venda['produto']) ?></td>
                                            <td><span class="badge-categoria"><?= htmlspecialchars($venda['categoria']) ?></span></td>
                                            <td><?= rtrim(rtrim(number_format((float)$venda['quantidade'], 2, ',', '.'), '0'), ',') ?></td>
                                            <td><?= htmlspecialchars($venda['unidade']) ?></td>
                                            <td><?= formatarMoeda($venda['valor_unitario']) ?></td>
                                            <td><?= formatarMoeda($venda['quantidade'] * $venda['valor_unitario']) ?></td>
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
