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
                .btn-secondary {
            background: white;
            color: #1f7a3f;
            border: 1px solid #d8e3db;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 20px;
            color: #1f7a3f;
        }

        .info-list {
            display: grid;
            gap: 12px;
        }

        .info-item {
            border-bottom: 1px solid #ececec;
            padding-bottom: 10px;
        }

        .label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            color: #222;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e7f6ec;
            color: #1f7a3f;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-nao {
            background: #fdeaea;
            color: #b42318;
        }

        .topbar {
    display: flex;
    align-items: center;
    background: white;
    padding: 12px 24px;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    position: sticky;
    top: 0; z-index: 100; 
        }

        .topbar h2 {
            margin: 0;
            font-size: 28px;
            color: #1f7a3f;
        }

        .topbar p {
            margin: 6px 0 0;
            color: #666;
            font-size: 14px;
        }

    </style>
</head>
<body>

    <header class="topbar">
        <button id="btnMenu" class="btn-Menu">☰</button>
        <div class="titulo">
            <h2>SGA Pecuária</h2>
            <p>Fazenda Paraíso</p>
        </div>
    </header>
        <?php include __DIR__ . '/includes/header.php'; ?>
        <?php include __DIR__ . '/includes/menu.php'; ?>
    <div class="container">
        <div class="top-actions">
            <a href="dashboard.php" class="btn btn-secondary">← Voltar</a>
            <a href="editar_animal.php?id=<?= $animal['id'] ?>" class="btn btn-primary">Editar animal</a>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Identificação</h2>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Brinco</span>
                        <span class="value"><?= textoSeguro($animal['brinco']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Nome / Apelido</span>
                        <span class="value"><?= textoSeguro($animal['nome_apelido']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Raça</span>
                        <span class="value"><?= textoSeguro($animal['raca']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Sexo</span>
                        <span class="value"><?= textoSeguro($animal['sexo']) ?></span>
                    </div>
                                        <div class="info-item">
                        <span class="label">Nascimento</span>
                        <span class="value"><?= textoOuPadrao($animal['data_nascimento']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Lote</span>
                        <span class="value"><?= textoOuPadrao($animal['lote']) ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Genealogia</h2>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Mãe</span>
                        <span class="value"><?= $maeTexto ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Pai</span>
                        <span class="value"><?= $paiTexto ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Número de crias</span>
                        <span class="value"><?= $totalCrias ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Reprodução</h2>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Último cio</span>
                        <span class="value"><?= textoOuPadrao($animal['data_ultimo_cio']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Prenha</span>
                        <span class="value">
                            <span class="badge <?= $prenhaTexto === 'Sim' ? '' : 'badge-nao' ?>">
                                <?= $prenhaTexto ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
                                <div class="info-item">
                        <span class="label">Nascimento</span>
                        <span class="value"><?= textoOuPadrao($animal['data_nascimento']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Lote</span>
                        <span class="value"><?= textoOuPadrao($animal['lote']) ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Genealogia</h2>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Mãe</span>
                        <span class="value"><?= $maeTexto ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Pai</span>
                        <span class="value"><?= $paiTexto ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Número de crias</span>
                        <span class="value"><?= $totalCrias ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Reprodução</h2>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Último cio</span>
                        <span class="value"><?= textoOuPadrao($animal['data_ultimo_cio']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="label">Prenha</span>
                        <span class="value">
                            <span class="badge <?= $prenhaTexto === 'Sim' ? '' : 'badge-nao' ?>">
                                <?= $prenhaTexto ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
