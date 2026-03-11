<?php
require_once 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Animal inválido.');
}

try {
    $sql = "
        SELECT
            a.id,
            a.brinco,
            a.nome_apelido,
            a.raca,
            a.sexo,
            a.data_nascimento,
            a.lote,
            a.data_ultimo_cio,
            a.prenha,
            a.mae_id,
            a.pai_id,
            mae.nome_apelido AS nome_mae,
            mae.brinco AS brinco_mae,
            pai.nome_apelido AS nome_pai,
            pai.brinco AS brinco_pai
        FROM animais a
        LEFT JOIN animais mae ON a.mae_id = mae.id
        LEFT JOIN animais pai ON a.pai_id = pai.id
        WHERE a.id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$animal) {
        die('Animal não encontrado.');
    }

    $stmtCrias = $pdo->prepare("
        SELECT COUNT(*)
        FROM animais
        WHERE mae_id = :id
    ");
    $stmtCrias->execute([':id' => $id]);
    $totalCrias = (int) $stmtCrias->fetchColumn();

} catch (PDOException $e) {
    die('Erro ao buscar animal: ' . $e->getMessage());
}

function textoSeguro($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function textoOuPadrao($valor, string $padrao = 'Não informado'): string
{
    if ($valor === null || $valor === '') {
        return $padrao;
    }

    return textoSeguro($valor);
}

$prenhaTexto = ((int) ($animal['prenha'] ?? 0) === 1) ? 'Sim' : 'Não';

$maeTexto = $animal['nome_mae']
    ? textoSeguro($animal['nome_mae']) . ' (Brinco ' . textoSeguro($animal['brinco_mae']) . ')'
    : 'Não informado';

$paiTexto = $animal['nome_pai']
    ? textoSeguro($animal['nome_pai']) . ' (Brinco ' . textoSeguro($animal['brinco_pai']) . ')'
    : 'Não informado';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Animal</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        .header {
            background: linear-gradient(135deg, #1f7a3f, #2fa35a);
            color: white;
            padding: 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header p {
            margin: 8px 0 0;
            opacity: 0.95;
        }

        .container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-primary {
            background: #1f7a3f;
            color: white;
        }
