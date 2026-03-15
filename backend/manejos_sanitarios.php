<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

function responder($dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function normalizarStatus(string $status): string
{
    $status = trim($status);
    $permitidos = ['Agendado', 'Realizado', 'Pendente', 'Em tratamento'];

    if (in_array($status, $permitidos, true)) {
        return $status;
    }

    return 'Pendente';
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
                ms.id,
                ms.animal_id,
                a.nome_apelido,
                a.brinco,
                ms.tipo,
                ms.descricao,
                ms.data_evento,
                ms.proxima_data,
                ms.status,
                ms.created_at
            FROM manejos_sanitarios ms
            INNER JOIN animais a ON a.id = ms.animal_id
            ORDER BY ms.data_evento DESC, ms.id DESC
        ");

        $manejos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        responder($manejos);
    }

    if ($metodo === 'POST') {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            $entrada = $_POST;
        }

        $animal_id = (int) ($entrada['animal_id'] ?? 0);
        $tipo = trim($entrada['tipo'] ?? '');
        $descricao = trim($entrada['descricao'] ?? '');
        $data_evento = trim($entrada['data_evento'] ?? '');
        $proxima_data = trim($entrada['proxima_data'] ?? '');
        $status = normalizarStatus($entrada['status'] ?? '');

        if ($animal_id <= 0 || $tipo === '' || $data_evento === '') {
            responder([
                'erro' => 'Campos obrigatórios: animal_id, tipo e data_evento.'
            ], 400);
        }

        if (!dataValida($data_evento)) {
            responder([
                'erro' => 'A data_evento deve estar no formato YYYY-MM-DD.'
            ], 400);
        }

        $stmtAnimal = $pdo->prepare("SELECT id FROM animais WHERE id = :id");
        $stmtAnimal->execute([':id' => $animal_id]);

        if (!$stmtAnimal->fetch(PDO::FETCH_ASSOC)) {
            responder([
                'erro' => 'Animal não encontrado.'
            ], 404);
        }

        if ($proxima_data !== '' && $proxima_data < $data_evento) {
            responder([
                'erro' => 'A próxima data não pode ser anterior à data do evento.'
            ], 400);
        }

        if ($proxima_data !== '' && !dataValida($proxima_data)) {
            responder([
                'erro' => 'A proxima_data deve estar no formato YYYY-MM-DD.'
            ], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO manejos_sanitarios (
                animal_id,
                tipo,
                descricao,
                data_evento,
                proxima_data,
                status
            ) VALUES (
                :animal_id,
                :tipo,
                :descricao,
                :data_evento,
                :proxima_data,
                :status
            )
        ");

        $stmt->execute([
            ':animal_id' => $animal_id,
            ':tipo' => $tipo,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':data_evento' => $data_evento,
            ':proxima_data' => $proxima_data !== '' ? $proxima_data : null,
            ':status' => $status,
        ]);

        responder([
            'mensagem' => 'Manejo sanitário cadastrado com sucesso.',
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
