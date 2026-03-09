<?php
$host = "localhost";
$dbname = "sga_pecuaria";
$user = "root";
$pass = "";

// tenta executar o bloco com try, caso dê errado o catch lá embaixo
// tratamento de erro básico
try {
    // conecta sem banco primeiro
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // cria o banco se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // conecta no banco criado
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // cria a tabela
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animais (
            id INT AUTO_INCREMENT PRIMARY KEY,
            brinco VARCHAR(50) NOT NULL UNIQUE,
            nome_apelido VARCHAR(100) NOT NULL,
            raca VARCHAR(100) NOT NULL,
            sexo VARCHAR(20) NOT NULL,
            data_nascimento DATE,
            lote VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // insere dado fake se estiver vazia
    $count = $pdo->query("SELECT COUNT(*) FROM animais")->fetchColumn();

    if ($count == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO animais (brinco, nome_apelido, raca, sexo, data_nascimento, lote)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            "4052",
            "Campeão",
            "Nelore",
            "Macho",
            "2023-05-15",
            "Lote Engorda A"
        ]);
    }

    $animais = $pdo->query("SELECT * FROM animais ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGA Pecuária</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: #145a32;
        }

        .card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border-bottom: 1px solid #ddd;
            text-align: left;
            padding: 12px;
        }

        th {
            background: #198754;
            color: white;
        }

        tr:hover {
            background: #f2f2f2;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #d1e7dd;
            color: #0f5132;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SGA-Pecuária - Fazenda Progresso</h1>

        <div class="card">
            <h2>Resumo</h2>
            <p><strong>Total de animais:</strong> <?= count($animais) ?></p>
            <span class="badge">MariaDB conectado</span>
        </div>

        <div class="card">
            <h2>Lista de Animais</h2>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Brinco</th>
                        <th>Nome</th>
                        <th>Raça</th>
                        <th>Sexo</th>
                        <th>Nascimento</th>
                        <th>Lote</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($animais as $animal): ?>
                        <tr>
                            <td><?= $animal['id'] ?></td>
                            <td><?= htmlspecialchars($animal['brinco']) ?></td>
                            <td><?= htmlspecialchars($animal['nome_apelido']) ?></td>
                            <td><?= htmlspecialchars($animal['raca']) ?></td>
                            <td><?= htmlspecialchars($animal['sexo']) ?></td>
                            <td><?= htmlspecialchars($animal['data_nascimento']) ?></td>
                            <td><?= htmlspecialchars($animal['lote']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
