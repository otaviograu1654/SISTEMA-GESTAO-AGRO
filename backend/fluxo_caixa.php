<?php
require_once __DIR__ . '/includes/layout.php';

$movimentacoes = [
    [
        'data_movimento' => '2026-03-23',
        'tipo' => 'Saída',
        'categoria' => 'Folha',
        'descricao' => 'Pagamento semanal de funcionários',
        'origem_destino' => 'Equipe da fazenda',
        'forma_pagamento' => 'Transferência',
        'valor' => 1250.00,
    ],
    [
        'data_movimento' => '2026-03-24',
        'tipo' => 'Entrada',
        'categoria' => 'Derivados',
        'descricao' => 'Venda de queijo artesanal',
        'origem_destino' => 'Mercado do Campo',
        'forma_pagamento' => 'PIX',
        'valor' => 693.00,
    ],
    [
        'data_movimento' => '2026-03-25',
        'tipo' => 'Saída',
        'categoria' => 'Insumos',
        'descricao' => 'Compra emergencial de ração',
        'origem_destino' => 'Agro Forte',
        'forma_pagamento' => 'Dinheiro',
        'valor' => 480.00,
    ],
    [
        'data_movimento' => '2026-03-26',
        'tipo' => 'Entrada',
        'categoria' => 'Produção',
        'descricao' => 'Venda de leite do tanque da manhã',
        'origem_destino' => 'Cooperativa Vale Verde',
        'forma_pagamento' => 'PIX',
        'valor' => 912.40,
    ],
    [
        'data_movimento' => '2026-03-27',
        'tipo' => 'Saída',
        'categoria' => 'Sanidade',
        'descricao' => 'Vacinas e medicamentos',
        'origem_destino' => 'Vet Campo',
        'forma_pagamento' => 'Boleto',
        'valor' => 268.90,
    ],
    [
        'data_movimento' => '2026-03-27',
        'tipo' => 'Entrada',
        'categoria' => 'Animal',
        'descricao' => 'Venda de bezerro desmamado',
        'origem_destino' => 'Frigorífico Vale Verde',
        'forma_pagamento' => 'Transferência',
        'valor' => 1850.00,
    ],
];

function formatarMoeda($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

usort($movimentacoes, function ($a, $b) {
    return strcmp($a['data_movimento'], $b['data_movimento']);
});

$totalEntradas = 0;
$totalSaidas = 0;
$maiorEntrada = 0;
$maiorSaida = 0;
$historico = [];
$saldoAcumulado = 0;

foreach ($movimentacoes as $movimentacao) {
    if ($movimentacao['tipo'] === 'Entrada') {
        $totalEntradas += $movimentacao['valor'];
        $maiorEntrada = max($maiorEntrada, $movimentacao['valor']);
        $saldoAcumulado += $movimentacao['valor'];
    } else {
        $totalSaidas += $movimentacao['valor'];
        $maiorSaida = max($maiorSaida, $movimentacao['valor']);
        $saldoAcumulado -= $movimentacao['valor'];
    }

    $movimentacao['saldo_apos'] = $saldoAcumulado;
    $historico[] = $movimentacao;
}

$totalMovimentacoes = count($movimentacoes);
$saldoFinal = $totalEntradas - $totalSaidas;

layoutInicio('Fluxo de caixa');
?>

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
        max-width: 760px;
    }

    .grid-resumo {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .card-resumo {
        background: white;
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

    .card-resumo .valor.saida {
        color: #b42318;
    }

    .card-resumo .valor.saldo-negativo {
        color: #b42318;
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
        background: white;
    }

    .btn-secundario:hover {
        background: #e7f6ec;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    .tipo-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 84px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: bold;
    }

    .tipo-entrada {
        background: #e7f6ec;
        color: #1f7a3f;
    }

    .tipo-saida {
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

    .resumo-grid-secundario {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .resumo-item {
        border: 1px solid #edf2ee;
        border-radius: 12px;
        padding: 14px 16px;
        background: #fafcfb;
    }

    .resumo-item strong {
        display: block;
        color: #444;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .resumo-item span {
        font-size: 22px;
        font-weight: bold;
        color: #1f7a3f;
    }

    .resumo-item span.saida {
        color: #b42318;
    }
</style>

<div class="page-header">
    <div>
        <h1>Fluxo de Caixa</h1>
        <p>Visão consolidada das entradas e saídas financeiras da fazenda, com saldo acumulado por movimentação para facilitar o acompanhamento do caixa.</p>
    </div>

    <div class="acoes-topo">
        <a href="dashboard.php" class="btn-secundario">Voltar ao dashboard</a>
    </div>
</div>

<div class="grid-resumo">
    <div class="card-resumo">
        <div class="label">Movimentações no período</div>
        <div class="valor"><?= $totalMovimentacoes ?></div>
    </div>

    <div class="card-resumo">
        <div class="label">Entradas</div>
        <div class="valor"><?= formatarMoeda($totalEntradas) ?></div>
    </div>

    <div class="card-resumo">
        <div class="label">Saídas</div>
        <div class="valor saida"><?= formatarMoeda($totalSaidas) ?></div>
    </div>

    <div class="card-resumo">
        <div class="label">Saldo final</div>
        <div class="valor <?= $saldoFinal < 0 ? 'saldo-negativo' : '' ?>">
            <?= formatarMoeda($saldoFinal) ?>
        </div>
    </div>
</div>

<section class="panel">
    <h2>Novo lançamento no caixa</h2>

    <form action="#" method="post">
        <div class="form-group">
            <label for="data_movimento">Data</label>
            <input type="date" id="data_movimento" name="data_movimento">
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
                <option value="Sanidade">Sanidade</option>
                <option value="Folha">Folha</option>
                <option value="Logística">Logística</option>
                <option value="Outros">Outros</option>
            </select>
        </div>

        <div class="form-group">
            <label for="forma_pagamento">Forma de pagamento</label>
            <select id="forma_pagamento" name="forma_pagamento">
                <option value="">Selecione</option>
                <option value="PIX">PIX</option>
                <option value="Dinheiro">Dinheiro</option>
                <option value="Boleto">Boleto</option>
                <option value="Transferência">Transferência</option>
                <option value="Cartão">Cartão</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label for="descricao">Descrição</label>
            <input type="text" id="descricao" name="descricao" placeholder="Ex: Venda de leite do tanque da tarde">
        </div>

        <div class="form-group">
            <label for="origem_destino">Origem / destino</label>
            <input type="text" id="origem_destino" name="origem_destino" placeholder="Ex: Cooperativa Vale Verde">
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
    </form>
</section>

<section class="panel">
    <h2>Resumo do caixa</h2>

    <div class="resumo-grid-secundario">
        <div class="resumo-item">
            <strong>Maior entrada individual</strong>
            <span><?= formatarMoeda($maiorEntrada) ?></span>
        </div>

        <div class="resumo-item">
            <strong>Maior saída individual</strong>
            <span class="saida"><?= formatarMoeda($maiorSaida) ?></span>
        </div>

        <div class="resumo-item">
            <strong>Resultado do período</strong>
            <span class="<?= $saldoFinal < 0 ? 'saida' : '' ?>">
                <?= $saldoFinal < 0 ? 'Prejuízo' : 'Superávit' ?>
            </span>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Histórico do fluxo de caixa</h2>
    <p style="margin-top: -6px; color: #666;">Saldo acumulado após cada movimentação registrada no período.</p>

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
                    <th>Saldo acumulado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historico)): ?>
                    <tr>
                        <td colspan="8">Nenhuma movimentação cadastrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historico as $movimentacao): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($movimentacao['data_movimento']))) ?></td>
                            <td>
                                <span class="tipo-badge <?= $movimentacao['tipo'] === 'Entrada' ? 'tipo-entrada' : 'tipo-saida' ?>">
                                    <?= htmlspecialchars($movimentacao['tipo']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($movimentacao['descricao']) ?></td>
                            <td><?= htmlspecialchars($movimentacao['categoria']) ?></td>
                            <td><?= htmlspecialchars($movimentacao['origem_destino']) ?></td>
                            <td><?= htmlspecialchars($movimentacao['forma_pagamento']) ?></td>
                            <td class="<?= $movimentacao['tipo'] === 'Entrada' ? 'valor-entrada' : 'valor-saida' ?>">
                                <?= ($movimentacao['tipo'] === 'Entrada' ? '+ ' : '- ') . formatarMoeda($movimentacao['valor']) ?>
                            </td>
                            <td class="<?= $movimentacao['saldo_apos'] >= 0 ? 'valor-entrada' : 'valor-saida' ?>">
                                <?= formatarMoeda($movimentacao['saldo_apos']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
