<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auditoria.php';

function garantirTabelaEstoque(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estoque_produtos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL UNIQUE,
            nome VARCHAR(150) NOT NULL,
            categoria VARCHAR(80) NOT NULL,
            preco_custo DECIMAL(10,2) NOT NULL DEFAULT 0,
            quantidade_atual DECIMAL(10,2) NOT NULL DEFAULT 0,
            unidade VARCHAR(30) NOT NULL,
            lote_produto VARCHAR(80),
            validade DATE,
            data_entrada DATE NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produto_id INT NOT NULL,
            tipo VARCHAR(20) NOT NULL,
            quantidade DECIMAL(10,2) NOT NULL,
            data_movimento DATE NOT NULL,
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (produto_id) REFERENCES estoque_produtos(id)
        )
    ");
}

function valorAntigoEstoque(string $chave, string $padrao = ''): string
{
    return htmlspecialchars($_POST[$chave] ?? $padrao, ENT_QUOTES, 'UTF-8');
}

function selecionadoEstoque(string $chave, string $valor, string $padrao = ''): string
{
    $atual = $_POST[$chave] ?? $padrao;

    return $atual === $valor ? 'selected' : '';
}

function formatarMoedaEstoque(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarNumeroEstoque($valor): string
{
    return number_format((float) $valor, 2, ',', '.');
}

function formatarDataEstoque(?string $data): string
{
    if (!$data) {
        return '--';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return $data;
    }

    return date('d/m/Y', $timestamp);
}

$erro = '';
$sucesso = '';
$produtos = [];
$movimentacoes = [];
$resumo = [
    'total_itens' => 0,
    'vacinas' => 0,
    'vencendo' => 0,
    'valor_estoque' => 0,
    'baixo_estoque' => 0,
];

try {
    garantirTabelaEstoque($pdo);
} catch (PDOException $e) {
    $erro = 'Não foi possível preparar a estrutura de estoque.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '' && ($_POST['acao'] ?? '') === 'produto') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $precoCusto = trim((string) ($_POST['preco_custo'] ?? '0'));
    $quantidadeAtual = trim((string) ($_POST['quantidade_atual'] ?? '0'));
    $unidade = trim($_POST['unidade'] ?? '');
    $loteProduto = trim($_POST['lote_produto'] ?? '');
    $validade = trim($_POST['validade'] ?? '');
    $dataEntrada = trim($_POST['data_entrada'] ?? '');
    $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;

    if ($codigo === '' || $nome === '' || $categoria === '' || $unidade === '' || $dataEntrada === '') {
        $erro = 'Preencha os campos obrigatórios: código, produto, categoria, unidade e data de entrada.';
    } elseif (!is_numeric($precoCusto) || (float) $precoCusto < 0) {
        $erro = 'Informe um preço de custo válido.';
    } elseif (!is_numeric($quantidadeAtual) || (float) $quantidadeAtual < 0) {
        $erro = 'Informe uma quantidade válida.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO estoque_produtos (
                    codigo,
                    nome,
                    categoria,
                    preco_custo,
                    quantidade_atual,
                    unidade,
                    lote_produto,
                    validade,
                    data_entrada,
                    ativo
                ) VALUES (
                    :codigo,
                    :nome,
                    :categoria,
                    :preco_custo,
                    :quantidade_atual,
                    :unidade,
                    :lote_produto,
                    :validade,
                    :data_entrada,
                    :ativo
                )
            ");

            $stmt->execute([
                ':codigo' => $codigo,
                ':nome' => $nome,
                ':categoria' => $categoria,
                ':preco_custo' => $precoCusto,
                ':quantidade_atual' => $quantidadeAtual,
                ':unidade' => $unidade,
                ':lote_produto' => $loteProduto !== '' ? $loteProduto : null,
                ':validade' => $validade !== '' ? $validade : null,
                ':data_entrada' => $dataEntrada,
                ':ativo' => $ativo,
            ]);

            registrarAuditoria($pdo, 'Criacao', 'Estoque', (int) $pdo->lastInsertId(), 'Produto cadastrado: ' . $nome . ' (' . $codigo . ')');
            $sucesso = 'Produto cadastrado no estoque com sucesso.';
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Já existe um produto cadastrado com esse código.';
            } else {
                error_log('Erro em estoque.php ao cadastrar produto: ' . $e->getMessage());
                $erro = 'Nao foi possivel cadastrar o produto agora.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '' && ($_POST['acao'] ?? '') === 'movimentacao') {
    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $tipoMovimento = trim($_POST['tipo_movimento'] ?? '');
    $quantidadeMovimento = trim((string) ($_POST['quantidade_movimento'] ?? ''));
    $dataMovimento = trim($_POST['data_movimento'] ?? '');
    $observacaoMovimento = trim($_POST['observacao_movimento'] ?? '');

    if ($produtoId <= 0 || !in_array($tipoMovimento, ['Entrada', 'Saída'], true) || $quantidadeMovimento === '' || $dataMovimento === '') {
        $erro = 'Preencha produto, tipo, quantidade e data da movimentação.';
    } elseif (!is_numeric($quantidadeMovimento) || (float) $quantidadeMovimento <= 0) {
        $erro = 'Informe uma quantidade de movimentação maior que zero.';
    } else {
        try {
            $quantidadeBanco = (float) $quantidadeMovimento;

            $pdo->beginTransaction();

            $stmtProduto = $pdo->prepare("
                SELECT id, quantidade_atual
                FROM estoque_produtos
                WHERE id = :id
                  AND ativo = 1
                LIMIT 1
                FOR UPDATE
            ");
            $stmtProduto->execute([':id' => $produtoId]);
            $produtoAtual = $stmtProduto->fetch(PDO::FETCH_ASSOC);

            if (!$produtoAtual) {
                throw new RuntimeException('Produto não encontrado ou inativo.');
            }

            $quantidadeAtual = (float) $produtoAtual['quantidade_atual'];
            $novaQuantidade = $tipoMovimento === 'Entrada'
                ? $quantidadeAtual + $quantidadeBanco
                : $quantidadeAtual - $quantidadeBanco;

            if ($novaQuantidade < 0) {
                throw new RuntimeException('Saída maior que a quantidade disponível em estoque.');
            }

            $stmtMovimento = $pdo->prepare("
                INSERT INTO estoque_movimentacoes (
                    produto_id,
                    tipo,
                    quantidade,
                    data_movimento,
                    observacao
                ) VALUES (
                    :produto_id,
                    :tipo,
                    :quantidade,
                    :data_movimento,
                    :observacao
                )
            ");
            $stmtMovimento->execute([
                ':produto_id' => $produtoId,
                ':tipo' => $tipoMovimento,
                ':quantidade' => $quantidadeBanco,
                ':data_movimento' => $dataMovimento,
                ':observacao' => $observacaoMovimento !== '' ? $observacaoMovimento : null,
            ]);
            $movimentoId = (int) $pdo->lastInsertId();

            $stmtAtualizar = $pdo->prepare("
                UPDATE estoque_produtos
                SET quantidade_atual = :quantidade_atual
                WHERE id = :id
            ");
            $stmtAtualizar->execute([
                ':quantidade_atual' => $novaQuantidade,
                ':id' => $produtoId,
            ]);

            registrarAuditoria($pdo, 'Movimentacao', 'Estoque', $produtoId, $tipoMovimento . ' de ' . $quantidadeBanco . ' no produto ID ' . $produtoId);
            $pdo->commit();
            $sucesso = 'Movimentação registrada com sucesso.';
            $_POST = [];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Erro em estoque.php ao movimentar: ' . $e->getMessage());
            $erro = 'Nao foi possivel movimentar o estoque agora.';
        }
    }
}

if ($erro === '') {
    try {
        $stmtResumo = $pdo->query("
            SELECT
                COUNT(*) AS total_itens,
                SUM(CASE WHEN categoria = 'Vacina' THEN 1 ELSE 0 END) AS vacinas,
                SUM(
                    CASE
                        WHEN validade IS NOT NULL
                         AND validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        THEN 1 ELSE 0
                    END
                ) AS vencendo,
                SUM(preco_custo * quantidade_atual) AS valor_estoque,
                SUM(CASE WHEN quantidade_atual <= 0 THEN 1 ELSE 0 END) AS baixo_estoque
            FROM estoque_produtos
            WHERE ativo = 1
        ");
        $resumoDb = $stmtResumo->fetch(PDO::FETCH_ASSOC);

        if (is_array($resumoDb)) {
            $resumo = [
                'total_itens' => (int) ($resumoDb['total_itens'] ?? 0),
                'vacinas' => (int) ($resumoDb['vacinas'] ?? 0),
                'vencendo' => (int) ($resumoDb['vencendo'] ?? 0),
                'valor_estoque' => (float) ($resumoDb['valor_estoque'] ?? 0),
                'baixo_estoque' => (int) ($resumoDb['baixo_estoque'] ?? 0),
            ];
        }

        $stmtProdutos = $pdo->query("
            SELECT
                id,
                codigo,
                nome,
                categoria,
                preco_custo,
                quantidade_atual,
                unidade,
                lote_produto,
                validade,
                data_entrada,
                ativo
            FROM estoque_produtos
            ORDER BY nome ASC, id DESC
        ");
        $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

        $stmtMovimentacoes = $pdo->query("
            SELECT
                m.id,
                m.tipo,
                m.quantidade,
                m.data_movimento,
                m.observacao,
                p.codigo,
                p.nome,
                p.unidade
            FROM estoque_movimentacoes m
            INNER JOIN estoque_produtos p ON p.id = m.produto_id
            ORDER BY m.data_movimento DESC, m.id DESC
            LIMIT 20
        ");
        $movimentacoes = $stmtMovimentacoes->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = 'Não foi possível carregar os produtos do estoque.';
    }
}

layoutInicio('Estoque');
?>

<div class="page-header">
    <h1>Estoque</h1>
    <p>Controle real de produtos, vacinas e insumos cadastrados no banco de dados.</p>
</div>

<?php if ($erro !== ''): ?>
    <div class="mensagem erro mensagem-bloco">
        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($sucesso !== ''): ?>
    <div class="mensagem sucesso mensagem-bloco">
        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="cards">
    <div class="card">
        <h3>Total de itens</h3>
        <div class="value"><?= $resumo['total_itens'] ?></div>
    </div>
    <div class="card">
        <h3>Vacinas no estoque</h3>
        <div class="value"><?= $resumo['vacinas'] ?></div>
    </div>
    <div class="card">
        <h3>Itens vencendo</h3>
        <div class="value"><?= $resumo['vencendo'] ?></div>
    </div>
    <div class="card">
        <h3>Valor em estoque</h3>
        <div class="value value-money"><?= formatarMoedaEstoque($resumo['valor_estoque']) ?></div>
    </div>
    <div class="card">
        <h3>Baixo estoque</h3>
        <div class="value"><?= $resumo['baixo_estoque'] ?></div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Novo produto</h2>
        <p>Cadastre a posição inicial do item.</p>

        <form method="POST" action="">
            <input type="hidden" name="acao" value="produto">

            <div class="form-group">
                <label for="codigo">Código</label>
                <input type="text" id="codigo" name="codigo" value="<?= valorAntigoEstoque('codigo') ?>" required>
            </div>

            <div class="form-group">
                <label for="nome">Produto</label>
                <input type="text" id="nome" name="nome" value="<?= valorAntigoEstoque('nome') ?>" required>
            </div>

            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione</option>
                    <option value="Vacina" <?= selecionadoEstoque('categoria', 'Vacina') ?>>Vacina</option>
                    <option value="Medicamento" <?= selecionadoEstoque('categoria', 'Medicamento') ?>>Medicamento</option>
                    <option value="Ração" <?= selecionadoEstoque('categoria', 'Ração') ?>>Ração</option>
                    <option value="Suplemento" <?= selecionadoEstoque('categoria', 'Suplemento') ?>>Suplemento</option>
                    <option value="Equipamento" <?= selecionadoEstoque('categoria', 'Equipamento') ?>>Equipamento</option>
                    <option value="Outro" <?= selecionadoEstoque('categoria', 'Outro') ?>>Outro</option>
                </select>
            </div>

            <div class="form-group">
                <label for="unidade">Unidade</label>
                <select id="unidade" name="unidade" required>
                    <option value="">Selecione</option>
                    <option value="un" <?= selecionadoEstoque('unidade', 'un') ?>>un</option>
                    <option value="kg" <?= selecionadoEstoque('unidade', 'kg') ?>>kg</option>
                    <option value="g" <?= selecionadoEstoque('unidade', 'g') ?>>g</option>
                    <option value="L" <?= selecionadoEstoque('unidade', 'L') ?>>L</option>
                    <option value="mL" <?= selecionadoEstoque('unidade', 'mL') ?>>mL</option>
                    <option value="saco" <?= selecionadoEstoque('unidade', 'saco') ?>>saco</option>
                </select>
            </div>

            <div class="form-group">
                <label for="preco_custo">Preço de custo</label>
                <input type="number" id="preco_custo" name="preco_custo" min="0" step="0.01" value="<?= valorAntigoEstoque('preco_custo', '0.00') ?>" required>
            </div>

            <div class="form-group">
                <label for="quantidade_atual">Quantidade atual</label>
                <input type="number" id="quantidade_atual" name="quantidade_atual" min="0" step="0.01" value="<?= valorAntigoEstoque('quantidade_atual', '0') ?>" required>
            </div>

            <div class="form-group">
                <label for="lote_produto">Lote do produto</label>
                <input type="text" id="lote_produto" name="lote_produto" value="<?= valorAntigoEstoque('lote_produto') ?>">
            </div>

            <div class="form-group">
                <label for="validade">Validade</label>
                <input type="date" id="validade" name="validade" value="<?= valorAntigoEstoque('validade') ?>">
            </div>

            <div class="form-group">
                <label for="data_entrada">Data da entrada</label>
                <input type="date" id="data_entrada" name="data_entrada" value="<?= valorAntigoEstoque('data_entrada', date('Y-m-d')) ?>" required>
            </div>

            <div class="form-group">
                <label for="ativo">Status</label>
                <select id="ativo" name="ativo" required>
                    <option value="1" <?= selecionadoEstoque('ativo', '1', '1') ?>>Ativo</option>
                    <option value="0" <?= selecionadoEstoque('ativo', '0', '1') ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar produto</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Movimentar estoque</h2>
        <p>Registre entradas e saídas para atualizar a quantidade atual do produto.</p>

        <form method="POST" action="">
            <input type="hidden" name="acao" value="movimentacao">

            <div class="form-group full-width">
                <label for="produto_id">Produto</label>
                <select id="produto_id" name="produto_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($produtos as $produto): ?>
                        <?php if ((int) $produto['ativo'] === 1): ?>
                            <option value="<?= (int) $produto['id'] ?>">
                                <?= htmlspecialchars($produto['nome'] . ' / ' . $produto['codigo'] . ' - saldo ' . formatarNumeroEstoque($produto['quantidade_atual']) . ' ' . $produto['unidade'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tipo_movimento">Tipo</label>
                <select id="tipo_movimento" name="tipo_movimento" required>
                    <option value="">Selecione</option>
                    <option value="Entrada">Entrada</option>
                    <option value="Saída">Saída</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quantidade_movimento">Quantidade</label>
                <input type="number" id="quantidade_movimento" name="quantidade_movimento" min="0.01" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="data_movimento">Data</label>
                <input type="date" id="data_movimento" name="data_movimento" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="observacao_movimento">Observação</label>
                <input type="text" id="observacao_movimento" name="observacao_movimento">
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar movimentação</button>
            </div>
        </form>
    </section>
</div>

<?php if ($resumo['vencendo'] > 0 || $resumo['baixo_estoque'] > 0): ?>
    <section class="panel panel-spaced">
        <h2>Alertas do estoque</h2>

        <div class="table-wrapper">
            <table>
                <tbody>
                    <?php if ($resumo['vencendo'] > 0): ?>
                        <tr>
                            <th>Itens vencendo</th>
                            <td><?= $resumo['vencendo'] ?> item(ns) com validade nos próximos 30 dias.</td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($resumo['baixo_estoque'] > 0): ?>
                        <tr>
                            <th>Baixo estoque</th>
                            <td><?= $resumo['baixo_estoque'] ?> item(ns) com quantidade zerada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="panel panel-spaced">
    <h2>Histórico de movimentações</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentacoes)): ?>
                    <tr>
                        <td colspan="5">Nenhuma movimentação registrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimentacoes as $movimentacao): ?>
                        <tr>
                            <td><?= formatarDataEstoque($movimentacao['data_movimento']) ?></td>
                            <td>
                                <span class="badge <?= $movimentacao['tipo'] === 'Entrada' ? 'badge-sucesso' : 'badge-alerta' ?>">
                                    <?= htmlspecialchars($movimentacao['tipo'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($movimentacao['nome'] . ' / ' . $movimentacao['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatarNumeroEstoque($movimentacao['quantidade']) . ' ' . htmlspecialchars($movimentacao['unidade'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($movimentacao['observacao'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel panel-spaced">
    <h2>Produtos cadastrados</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Preço de custo</th>
                    <th>Quantidade</th>
                    <th>Unidade</th>
                    <th>Lote</th>
                    <th>Validade</th>
                    <th>Data da entrada</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produtos)): ?>
                    <tr>
                        <td colspan="10">Nenhum produto cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?= htmlspecialchars($produto['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($produto['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatarMoedaEstoque((float) $produto['preco_custo']) ?></td>
                            <td><?= formatarNumeroEstoque($produto['quantidade_atual']) ?></td>
                            <td><?= htmlspecialchars($produto['unidade'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($produto['lote_produto'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatarDataEstoque($produto['validade']) ?></td>
                            <td><?= formatarDataEstoque($produto['data_entrada']) ?></td>
                            <td>
                                <span class="badge <?= ((int) $produto['ativo'] === 1) ? 'badge-sucesso' : 'badge-erro' ?>">
                                    <?= ((int) $produto['ativo'] === 1) ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layoutFim(); ?>
