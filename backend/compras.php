<?php
$compras = [
    [
        'data_compra' => '2026-03-25',
        'fornecedor' => 'Agro Forte',
        'produto' => 'Ração 25kg',
        'categoria' => 'Insumos',
        'quantidade' => 10,
        'valor_unitario' => 89.90,
    ],
    [
        'data_compra' => '2026-03-24',
        'fornecedor' => 'Vet Campo',
        'produto' => 'Vacina Aftosa',
        'categoria' => 'Sanidade',
        'quantidade' => 20,
        'valor_unitario' => 12.50,
    ],
];

function formatarMoeda($valor)
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}
?>

        <?php $cssPagina = 'compras.css'; ?>
        <?php include __DIR__ . '/includes/header.php'; ?>
        <?php include __DIR__ . '/includes/menu.php'; ?>

        <main class="main">
            <div class="content">
                <div class="page-header">
                    <div>
                        <h1>Compras</h1>
                        <p>Registro de compras de insumos, medicamentos, rações e materiais da fazenda.</p>
                    </div>

                    <div class="acoes-topo">
                        <a href="dashboard.php" class="btn-secundario">Voltar ao dashboard</a>
                    </div>
                </div>

                <div class="grid-resumo">
                    <div class="card-resumo">
                        <div class="label">Total de compras</div>
                        <div class="valor"><?= count($compras) ?></div>
                    </div>

                    <div class="card-resumo">
                        <div class="label">Valor movimentado</div>
                        <div class="valor">
                            <?php
                            $total = 0;
                            foreach ($compras as $compra) {
                                $total += $compra['quantidade'] * $compra['valor_unitario'];
                            }
                            echo formatarMoeda($total);
                            ?>
                        </div>
                    </div>
                </div>

                <section class="panel">
                    <h2>Nova compra</h2>

                    <form action="#" method="post" class="form-grid">
                        <div class="form-group">
                            <label for="data_compra">Data da compra</label>
                            <input type="date" id="data_compra" name="data_compra">
                        </div>

                        <div class="form-group">
                            <label for="fornecedor">Fornecedor</label>
                            <input type="text" id="fornecedor" name="fornecedor" placeholder="Ex: Agro Forte">
                        </div>

                        <div class="form-group">
                            <label for="produto">Produto</label>
                            <input type="text" id="produto" name="produto" placeholder="Ex: Ração 25kg">
                        </div>

                        <div class="form-group">
                            <label for="categoria">Categoria</label>
                            <select id="categoria" name="categoria">
                                <option value="">Selecione</option>
                                <option value="Insumos">Insumos</option>
                                <option value="Sanidade">Sanidade</option>
                                <option value="Alimentação">Alimentação</option>
                                <option value="Equipamentos">Equipamentos</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantidade">Quantidade</label>
                            <input type="number" id="quantidade" name="quantidade" min="1" step="1">
                        </div>

                        <div class="form-group">
                            <label for="valor_unitario">Valor unitário (R$)</label>
                            <input type="number" id="valor_unitario" name="valor_unitario" min="0" step="0.01">
                        </div>

                        <div class="form-group full-width">
                            <label for="observacao">Observação</label>
                            <input type="text" id="observacao" name="observacao" placeholder="Opcional">
                        </div>

                        <div class="form-group full-width">
                            <button type="submit">Salvar compra</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <h2>Compras lançadas</h2>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Fornecedor</th>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Qtd</th>
                                    <th>Valor unitário</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($compras)): ?>
                                    <tr>
                                        <td colspan="7">Nenhuma compra cadastrada ainda.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($compras as $compra): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($compra['data_compra']))) ?></td>
                                            <td><?= htmlspecialchars($compra['fornecedor']) ?></td>
                                            <td><?= htmlspecialchars($compra['produto']) ?></td>
                                            <td><?= htmlspecialchars($compra['categoria']) ?></td>
                                            <td><?= (int) $compra['quantidade'] ?></td>
                                            <td><?= formatarMoeda($compra['valor_unitario']) ?></td>
                                            <td><?= formatarMoeda($compra['quantidade'] * $compra['valor_unitario']) ?></td>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
