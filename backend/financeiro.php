<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auditoria.php';
require_once 'db.php';
exigirPermissaoModulo('financeiro');
header('Content-Type: application/json; charset=utf-8');

function responder($dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function normalizarTipoLancamento(string $tipo): string
{
    $tipo = trim($tipo);
    $permitidos = ['Receita', 'Despesa'];

    if (in_array($tipo, $permitidos, true)) {
        return $tipo;
    }

    return '';
}

function dataValida(string $data): bool
{
    $objetoData = DateTime::createFromFormat('Y-m-d', $data);

    return $objetoData !== false && $objetoData->format('Y-m-d') === $data;
}

function garantirEstruturaFinanceiro(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM financeiro LIKE 'parceiro_id'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN parceiro_id INT NULL AFTER tipo");
    }

    $stmtOrigem = $pdo->query("SHOW COLUMNS FROM financeiro LIKE 'origem'");
    $origemExiste = $stmtOrigem->fetch(PDO::FETCH_ASSOC);

    if (!$origemExiste) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN origem VARCHAR(50) NULL AFTER parceiro_id");
    }
}

function normalizarOrigemLancamento(string $origem): string
{
    $origem = trim($origem);
    $permitidas = [
        'Fluxo de caixa',
        'Compra',
        'Venda',
        'Conta a pagar',
        'Venda de animal',
        'Lancamento a vista',
        'Outro',
    ];

    if (in_array($origem, $permitidas, true)) {
        return $origem;
    }

    return 'Outro';
}

try {
    garantirEstruturaFinanceiro($pdo);

    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $stmt = $pdo->query("
            SELECT
                f.id,
                f.tipo,
                f.parceiro_id,
                f.origem,
                p.nome AS parceiro_nome,
                f.categoria,
                f.descricao,
                f.valor,
                f.data_lancamento,
                f.created_at
            FROM financeiro f
            LEFT JOIN parceiros p ON p.id = f.parceiro_id
            ORDER BY f.data_lancamento DESC, f.id DESC
        ");

        $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        responder($lancamentos);
    }

    if ($metodo === 'POST') {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            $entrada = $_POST;
        }

        $tipo = normalizarTipoLancamento($entrada['tipo'] ?? '');
        $parceiro_id = (int) ($entrada['parceiro_id'] ?? 0);
        $origem = normalizarOrigemLancamento($entrada['origem'] ?? 'Outro');
        $categoria = trim($entrada['categoria'] ?? '');
        $descricao = trim($entrada['descricao'] ?? '');
        $valor = trim((string) ($entrada['valor'] ?? ''));
        $data_lancamento = trim($entrada['data_lancamento'] ?? '');

        if ($tipo === '' || $valor === '' || $data_lancamento === '') {
            responder([
                'erro' => 'Campos obrigatórios: tipo, valor e data_lancamento.'
            ], 400);
        }

        if (!is_numeric($valor)) {
            responder([
                'erro' => 'O valor deve ser numérico.'
            ], 400);
        }

        if (!dataValida($data_lancamento)) {
            responder([
                'erro' => 'A data_lancamento deve estar no formato YYYY-MM-DD.'
            ], 400);
        }

        if ((float) $valor < 0) {
            responder([
                'erro' => 'O valor não pode ser negativo.'
            ], 400);
        }

        if ($parceiro_id > 0) {
            $stmtParceiro = $pdo->prepare("SELECT id FROM parceiros WHERE id = :id AND ativo = 1 LIMIT 1");
            $stmtParceiro->execute([':id' => $parceiro_id]);

            if (!$stmtParceiro->fetch(PDO::FETCH_ASSOC)) {
                responder([
                    'erro' => 'Parceiro não encontrado ou inativo.'
                ], 404);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO financeiro (
                tipo,
                parceiro_id,
                origem,
                categoria,
                descricao,
                valor,
                data_lancamento
            ) VALUES (
                :tipo,
                :parceiro_id,
                :origem,
                :categoria,
                :descricao,
                :valor,
                :data_lancamento
            )
        ");

        $stmt->execute([
            ':tipo' => $tipo,
            ':parceiro_id' => $parceiro_id > 0 ? $parceiro_id : null,
            ':origem' => $origem,
            ':categoria' => $categoria !== '' ? $categoria : null,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':valor' => $valor,
            ':data_lancamento' => $data_lancamento,
        ]);

        $novoLancamentoId = (int) $pdo->lastInsertId();
        registrarAuditoria($pdo, 'Criacao', 'Financeiro', $novoLancamentoId, 'Lancamento financeiro criado: ' . $tipo . ' - ' . $origem . ' - R$ ' . number_format((float) $valor, 2, ',', '.'));

        responder([
            'mensagem' => 'Lançamento financeiro cadastrado com sucesso.',
            'id' => $novoLancamentoId
        ], 201);
    }

    responder([
        'erro' => 'Método não permitido.'
    ], 405);

} catch (PDOException $e) {
    error_log('Erro em financeiro.php: ' . $e->getMessage());
    responder([
        'erro' => 'Nao foi possivel concluir a operacao financeira agora.'
    ], 500);
} catch (Throwable $e) {
    error_log('Erro inesperado em financeiro.php: ' . $e->getMessage());
    responder([
        'erro' => 'Erro interno do servidor.'
    ], 500);
}
