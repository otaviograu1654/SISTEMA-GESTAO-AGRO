<?php
header('Content-Type: application/json; charset=utf-8');

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
            SELECT 
                p.id,
                p.animal_id,
                a.nome_apelido,
                a.brinco,
                p.data_pesagem,
                p.peso_kg,
                p.observacao,
                p.created_at
            FROM pesagens p
            INNER JOIN animais a ON a.id = p.animal_id
            ORDER BY p.data_pesagem DESC, p.id DESC
        ");

        $pesagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        responder($pesagens);
    }

    if ($metodo === 'POST') {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            $entrada = $_POST;
        }

        $animal_id = (int)($entrada['animal_id'] ?? 0);
        $data_pesagem = trim($entrada['data_pesagem'] ?? '');
        $peso_kg = trim($entrada['peso_kg'] ?? '');
        $observacao = trim($entrada['observacao'] ?? '');

        if ($animal_id <= 0 || $data_pesagem === '' || $peso_kg === '') {
            responder([
                'erro' => 'Campos obrigatórios: animal_id, data_pesagem e peso_kg.'
            ], 400);
        }

        if (!is_numeric($peso_kg)) {
            responder([
                'erro' => 'O peso deve ser numérico.'
            ], 400);
        }

        $stmtAnimal = $pdo->prepare("SELECT id FROM animais WHERE id = :id");
        $stmtAnimal->execute([':id' => $animal_id]);

        if (!$stmtAnimal->fetch(PDO::FETCH_ASSOC)) {
            responder([
                'erro' => 'Animal não encontrado.'
            ], 404);
        }

        $sql = "
            INSERT INTO pesagens (animal_id, data_pesagem, peso_kg, observacao)
            VALUES (:animal_id, :data_pesagem, :peso_kg, :observacao)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':animal_id' => $animal_id,
            ':data_pesagem' => $data_pesagem,
            ':peso_kg' => $peso_kg,
            ':observacao' => $observacao !== '' ? $observacao : null,
        ]);

        responder([
            'mensagem' => 'Pesagem cadastrada com sucesso.',
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
