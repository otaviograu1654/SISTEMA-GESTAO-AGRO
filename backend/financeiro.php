<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

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

try {
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $stmt = $pdo->query("
            SELECT
                id,
                tipo,
                categoria,
                descricao,
                valor,
                data_lancamento,
                created_at
            FROM financeiro
            ORDER BY data_lancamento DESC, id DESC
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

        $stmt = $pdo->prepare("
            INSERT INTO financeiro (
                tipo,
                categoria,
                descricao,
                valor,
                data_lancamento
            ) VALUES (
                :tipo,
                :categoria,
                :descricao,
                :valor,
                :data_lancamento
            )
        ");

        $stmt->execute([
            ':tipo' => $tipo,
            ':categoria' => $categoria !== '' ? $categoria : null,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':valor' => $valor,
            ':data_lancamento' => $data_lancamento,
        ]);

        responder([
            'mensagem' => 'Lançamento financeiro cadastrado com sucesso.',
            'id' => $pdo->lastInsertId()
        ], 201);
    }

    responder([
        'erro' => 'Método não permitido.'
    ], 405);

} catch (PDOException $e) {
    responder([
        'erro' => 'Erro no banco de dados.',
        'detalhe' => $e->getMessage()
    ], 500);
} catch (Throwable $e) {
    responder([
        'erro' => 'Erro interno do servidor.',
        'detalhe' => $e->getMessage()
    ], 500);
}
