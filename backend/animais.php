<?php
header('Content-Type: application/json; charset=utf-8');
// talvez mudar para require_once __DIR__ em todos os arquivos que usam para evitar conflito de busca
require_once 'db.php';

function responder($dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        $stmt = $pdo->query("
            SELECT id, brinco, nome_apelido, raca, sexo, data_nascimento, lote, created_at
            FROM animais
            ORDER BY id DESC
        ");

        $animais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        responder($animais);
    }

    if ($metodo === 'POST') {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            $entrada = $_POST;
        }

        $brinco = trim($entrada['brinco'] ?? '');
        $nome_apelido = trim($entrada['nome_apelido'] ?? '');
        $raca = trim($entrada['raca'] ?? '');
        $sexo = trim($entrada['sexo'] ?? '');
        $data_nascimento = trim($entrada['data_nascimento'] ?? '');
        $lote = trim($entrada['lote'] ?? '');

        if ($brinco === '' || $nome_apelido === '' || $raca === '' || $sexo === '') {
            responder([
                'erro' => 'Campos obrigatórios: brinco, nome_apelido, raca e sexo.'
            ], 400);
        }

        $sql = "
            INSERT INTO animais (brinco, nome_apelido, raca, sexo, data_nascimento, lote)
            VALUES (:brinco, :nome_apelido, :raca, :sexo, :data_nascimento, :lote)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':brinco' => $brinco,
            ':nome_apelido' => $nome_apelido,
            ':raca' => $raca,
            ':sexo' => $sexo,
            ':data_nascimento' => $data_nascimento !== '' ? $data_nascimento : null,
            ':lote' => $lote !== '' ? $lote : null,
        ]);

        responder([
            'mensagem' => 'Animal cadastrado com sucesso.',
            'id' => $pdo->lastInsertId()
        ], 201);
    }

    responder([
        'erro' => 'Método não permitido.'
    ], 405);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        responder([
            'erro' => 'Brinco já cadastrado.'
        ], 409);
    }

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
