<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

$erroPagina = '';
$sucessoPagina = '';
$movimentacoes = [];

function formatarMoeda($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function dataValidaFluxo(string $data): bool
{
    $objetoData = DateTime::createFromFormat('Y-m-d', $data);

    return $objetoData !== false && $objetoData->format('Y-m-d') === $data;
}

function garantirOrigemFinanceiro(PDO $pdo): void
{
    $stmtParceiro = $pdo->query("SHOW COLUMNS FROM financeiro LIKE 'parceiro_id'");

    if (!$stmtParceiro->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN parceiro_id INT NULL AFTER tipo");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM financeiro LIKE 'origem'");

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN origem VARCHAR(50) NULL AFTER parceiro_id");
    }
}

function origemLancamento(array $movimentacao): string
{
    if (!empty($movimentacao['origem'])) {
        return (string) $movimentacao['origem'];
    }

    $categoria = strtolower((string) ($movimentacao['categoria'] ?? ''));
    $descricao = strtolower((string) ($movimentacao['descricao'] ?? ''));

    if (str_contains($descricao, 'pagamento de conta')) {
        return 'Conta a pagar';
    }

    if (str_contains($descricao, 'venda do animal') || str_contains($categoria, 'venda de animais')) {
        return 'Venda de animal';
    }

    if (str_contains($categoria, 'compra') || str_contains($descricao, 'fornecedor:')) {
        return 'Compra';
    }

    if (str_contains($categoria, 'venda') || str_contains($descricao, 'venda')) {
        return 'Venda';
    }

    if (str_contains($categoria, 'vista')) {
        return 'Lancamento a vista';
    }

    return 'Outro';
}

function tipoFluxo(string $tipo): string
{
    return $tipo === 'Receita' ? 'Entrada' : 'Saída';
}

try {
    garantirOrigemFinanceiro($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = trim($_POST['tipo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $origemDestino = trim($_POST['origem_destino'] ?? '');
        $formaPagamento = trim($_POST['forma_pagamento'] ?? '');
        $observacao = trim($_POST['observacao'] ?? '');
        $valor = trim((string) ($_POST['valor'] ?? ''));
        $dataMovimento = trim($_POST['data_movimento'] ?? '');

        if (!in_array($tipo, ['Receita', 'Despesa'], true)) {
            $erroPagina = 'Selecione se o lançamento é entrada ou saída.';
        } elseif ($categoria === '' || $descricao === '' || $valor === '' || $dataMovimento === '') {
            $erroPagina = 'Preencha data, tipo, categoria, descrição e valor.';
        } elseif (!is_numeric($valor) || (float) $valor <= 0) {
            $erroPagina = 'Informe um valor maior que zero.';
        } elseif (!dataValidaFluxo($dataMovimento)) {
            $erroPagina = 'Informe uma data válida.';
        } else {
            $detalhes = [];

            if ($origemDestino !== '') {
                $detalhes[] = 'Origem/Destino: ' . $origemDestino;
            }

            if ($formaPagamento !== '') {
                $detalhes[] = 'Pagamento: ' . $formaPagamento;
            }

            if ($observacao !== '') {
                $detalhes[] = 'Observação: ' . $observacao;
            }

            $descricaoCompleta = $descricao;

            if (!empty($detalhes)) {
                $descricaoCompleta .= ' | ' . implode(' | ', $detalhes);
            }

            $stmtInserir = $pdo->prepare("
                INSERT INTO financeiro (
                    tipo,
                    origem,
                    categoria,
                    descricao,
                    valor,
                    data_lancamento
                ) VALUES (
                    :tipo,
                    :origem,
                    :categoria,
                    :descricao,
                    :valor,
                    :data_lancamento
                )
            ");

            $stmtInserir->execute([
                ':tipo' => $tipo,
                ':origem' => 'Fluxo de caixa',
                ':categoria' => $categoria,
                ':descricao' => $descricaoCompleta,
                ':valor' => (float) $valor,
                ':data_lancamento' => $dataMovimento,
            ]);

            $sucessoPagina = 'Lançamento registrado no financeiro e incluído no fluxo de caixa.';
        }
    }

    $stmtMovimentacoes = $pdo->query("
        SELECT
            f.id,
            f.tipo,
            f.origem,
            f.categoria,
            f.descricao,
            f.valor,
            f.data_lancamento,
            f.created_at,
            p.nome AS parceiro_nome
        FROM financeiro f
        LEFT JOIN parceiros p ON p.id = f.parceiro_id
        ORDER BY f.data_lancamento ASC, f.id ASC
    ");
    $movimentacoes = $stmtMovimentacoes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar o fluxo de caixa: ' . $e->getMessage();
}

$totalEntradas = 0;
$totalSaidas = 0;
$maiorEntrada = 0;
$maiorSaida = 0;
$historico = [];
$saldoAcumulado = 0;
$origensResumo = [];

foreach ($movimentacoes as $movimentacao) {
    $valor = (float) $movimentacao['valor'];
    $tipoMovimento = tipoFluxo((string) $movimentacao['tipo']);
    $origem = origemLancamento($movimentacao);

    if ($tipoMovimento === 'Entrada') {
        $totalEntradas += $valor;
        $maiorEntrada = max($maiorEntrada, $valor);
        $saldoAcumulado += $valor;
    } else {
        $totalSaidas += $valor;
        $maiorSaida = max($maiorSaida, $valor);
        $saldoAcumulado -= $valor;
    }

    $origensResumo[$origem] = ($origensResumo[$origem] ?? 0) + 1;
    $movimentacao['tipo_fluxo'] = $tipoMovimento;
    $movimentacao['origem_exibicao'] = $origem;
    $movimentacao['saldo_apos'] = $saldoAcumulado;
    $historico[] = $movimentacao;
}

$totalMovimentacoes = count($historico);
$saldoFinal = $totalEntradas - $totalSaidas;
$principalOrigem = 'Sem movimentações';

if (!empty($origensResumo)) {
    arsort($origensResumo);
    $principalOrigem = array_key_first($origensResumo);
}

layoutInicio('Fluxo de caixa');
?>
<style>
    .fluxo-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }

    .fluxo-header p {
        max-width: 760px;
    }

    .fluxo-resumo {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .fluxo-resumo .card-resumo {
        background: white;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }

    .fluxo-resumo .label {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
    }

    .fluxo-resumo .valor {
        font-size: 28px;
        font-weight: bold;
        color: #1f7a3f;
    }

    .fluxo-resumo .valor.saida,
    .fluxo-resumo .valor.saldo-negativo {
        color: #b42318;
    }

    .fluxo-acoes {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .fluxo-acoes .btn-secundario {
        display: inline-block;
        padding: 10px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
        border: 1px solid #1f7a3f;
        color: #1f7a3f;
        background: white;
    }

    .fluxo-acoes .btn-secundario:hover {
        background: #e7f6ec;
    }

    .fluxo-resumo-secundario {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .fluxo-resumo-secundario .resumo-item {
        border: 1px solid #edf2ee;
        border-radius: 12px;
        padding: 14px 16px;
        background: #fafcfb;
    }

    .fluxo-resumo-secundario .resumo-item strong {
        display: block;
        color: #444;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .fluxo-resumo-secundario .resumo-item span {
        font-size: 22px;
        font-weight: bold;
        color: #1f7a3f;
    }

    .fluxo-resumo-secundario .resumo-item span.saida {
        color: #b42318;
    }

    @media (max-width: 980px) {
        .fluxo-header {
            flex-direction: column;
        }
    }
</style>

<div class="page-header split-header fluxo-header">
    <div>
        <h1>Fluxo de Caixa</h1>
        <p>Visão consolidada das entradas e saídas registradas no financeiro, com saldo acumulado por movimentação para facilitar o acompanhamento do caixa.</p>
    </div>

    <div class="acoes-topo fluxo-acoes">
        <a href="dashboard.php" class="btn-secundario">Voltar ao dashboard</a>
        <a href="plano_contas.php" class="btn-secundario">Plano de contas</a>
    </div>
</div>

<?php if ($erroPagina !== ''): ?>
    <div class="mensagem erro mensagem-bloco">
        <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($sucessoPagina !== ''): ?>
    <div class="mensagem sucesso mensagem-bloco">
        <?= htmlspecialchars($sucessoPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="grid-resumo fluxo-resumo">
    <div class="card-resumo">
        <div class="label">Movimentações registradas</div>
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

    <form action="fluxo_caixa.php" method="post">
        <div class="form-group">
            <label for="data_movimento">Data</label>
            <input type="date" id="data_movimento" name="data_movimento" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required>
                <option value="">Selecione</option>
                <option value="Receita">Entrada</option>
                <option value="Despesa">Saída</option>
            </select>
        </div>

        <div class="form-group">
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria" required>
                <option value="">Selecione</option>
                <option value="Venda de animais">Venda de animais</option>
                <option value="Venda de leite">Venda de leite</option>
                <option value="Venda de derivados">Venda de derivados</option>
                <option value="Compra - Insumos">Compra - Insumos</option>
                <option value="Compra - Sanidade">Compra - Sanidade</option>
                <option value="Compra - Alimentação">Compra - Alimentação</option>
                <option value="Conta paga">Conta paga</option>
                <option value="Mão de obra">Mão de obra</option>
                <option value="Serviços">Serviços</option>
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
            <input type="text" id="descricao" name="descricao" placeholder="Ex: Venda de leite do tanque da tarde" required>
        </div>

        <div class="form-group">
            <label for="origem_destino">Origem / destino</label>
            <input type="text" id="origem_destino" name="origem_destino" placeholder="Ex: Cooperativa Vale Verde">
        </div>

        <div class="form-group">
            <label for="valor">Valor (R$)</label>
            <input type="number" id="valor" name="valor" min="0.01" step="0.01" placeholder="0,00" required>
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

    <div class="resumo-grid-secundario fluxo-resumo-secundario">
        <div class="resumo-item">
            <strong>Maior entrada individual</strong>
            <span><?= formatarMoeda($maiorEntrada) ?></span>
        </div>

        <div class="resumo-item">
            <strong>Maior saída individual</strong>
            <span class="saida"><?= formatarMoeda($maiorSaida) ?></span>
        </div>

        <div class="resumo-item">
            <strong>Origem mais frequente</strong>
            <span><?= htmlspecialchars($principalOrigem, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="resumo-item">
            <strong>Resultado geral</strong>
            <span class="<?= $saldoFinal < 0 ? 'saida' : '' ?>">
                <?= $saldoFinal < 0 ? 'Prejuízo' : 'Superávit' ?>
            </span>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Extrato geral do caixa</h2>
    <p class="section-note">Histórico consolidado a partir da tabela financeira, incluindo compras, vendas, contas pagas, vendas de animais e lançamentos manuais.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Origem</th>
                    <th>Categoria</th>
                    <th>Descrição</th>
                    <th>Parceiro</th>
                    <th>Valor</th>
                    <th>Saldo acumulado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historico)): ?>
                    <tr>
                        <td colspan="8">Nenhuma movimentação financeira cadastrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historico as $movimentacao): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($movimentacao['data_lancamento'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="tipo-badge <?= $movimentacao['tipo_fluxo'] === 'Entrada' ? 'tipo-entrada' : 'tipo-saida' ?>">
                                    <?= htmlspecialchars($movimentacao['tipo_fluxo'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($movimentacao['origem_exibicao'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($movimentacao['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($movimentacao['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($movimentacao['parceiro_nome'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="<?= $movimentacao['tipo_fluxo'] === 'Entrada' ? 'valor-entrada' : 'valor-saida' ?>">
                                <?= ($movimentacao['tipo_fluxo'] === 'Entrada' ? '+ ' : '- ') . formatarMoeda($movimentacao['valor']) ?>
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
