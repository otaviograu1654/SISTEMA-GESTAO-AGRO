<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sga_pecuaria";

function executarArquivoSql(PDO $pdo, string $caminho): void
{
    if (!file_exists($caminho)) {
        throw new Exception("Arquivo SQL não encontrado: " . $caminho);
    }

    $sql = file_get_contents($caminho);

    if ($sql === false) {
        throw new Exception("Não foi possível ler o arquivo: " . $caminho);
    }

    $linhas = explode("\n", $sql);
    $comando = "";
    $comandos = [];

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        if ($linha === "" || str_starts_with($linha, "--")) {
            continue;
        }

        $comando .= " " . $linha;

        if (str_ends_with($linha, ";")) {
            $comandos[] = trim($comando);
            $comando = "";
        }
    }

    if (trim($comando) !== "") {
        $comandos[] = trim($comando);
    }

    foreach ($comandos as $sqlUnitario) {
        $pdo->exec($sqlUnitario);
    }
}
function colunaExiste(PDO $pdo, string $tabela, string $coluna, string $banco): bool
{
    $sql = "
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = :banco
          AND TABLE_NAME = :tabela
          AND COLUMN_NAME = :coluna
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':banco' => $banco,
        ':tabela' => $tabela,
        ':coluna' => $coluna,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function garantirEstruturaAnimais(PDO $pdo, string $banco): void
{
    if (!colunaExiste($pdo, 'animais', 'mae_id', $banco)) {
        $pdo->exec("ALTER TABLE animais ADD COLUMN mae_id INT NULL");
    }

    if (!colunaExiste($pdo, 'animais', 'pai_id', $banco)) {
        $pdo->exec("ALTER TABLE animais ADD COLUMN pai_id INT NULL");
    }

    if (!colunaExiste($pdo, 'animais', 'data_ultimo_cio', $banco)) {
        $pdo->exec("ALTER TABLE animais ADD COLUMN data_ultimo_cio DATE NULL");
    }

    if (!colunaExiste($pdo, 'animais', 'prenha', $banco)) {
        $pdo->exec("ALTER TABLE animais ADD COLUMN prenha TINYINT(1) DEFAULT 0");
    }

    if (!colunaExiste($pdo, 'animais', 'status', $banco)) {
        $pdo->exec("ALTER TABLE animais ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Ativo'");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animal_alteracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NULL,
            brinco_referencia VARCHAR(50),
            nome_referencia VARCHAR(100),
            tipo_alteracao VARCHAR(50) NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animal_vendas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NOT NULL,
            comprador_nome VARCHAR(150) NOT NULL,
            data_venda DATE NOT NULL,
            valor DECIMAL(10,2) NULL,
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animal_obitos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NOT NULL,
            data_obito DATE NOT NULL,
            causa VARCHAR(150),
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");
}

function garantirEstruturaUsuarios(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            perfil VARCHAR(50) NOT NULL,
            senha_hash VARCHAR(255) NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaExiste($pdo, 'usuarios', 'ativo', $banco)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }
}

function garantirEstruturaSuporte(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS suporte_chamados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_contato VARCHAR(150) NOT NULL,
            email_contato VARCHAR(150) NOT NULL,
            assunto VARCHAR(150) NOT NULL,
            mensagem TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Aberto',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaExiste($pdo, 'suporte_chamados', 'status', $banco)) {
        $pdo->exec("ALTER TABLE suporte_chamados ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Aberto'");
    }
}
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $schemaPath = __DIR__ . '/../database/schema.sql';
    $seedPath = __DIR__ . '/../database/seed.sql';

    executarArquivoSql($pdo, $schemaPath);

    $pdoDb = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdoDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    garantirEstruturaAnimais($pdoDb, $dbname);
    garantirEstruturaUsuarios($pdoDb, $dbname);
    garantirEstruturaSuporte($pdoDb, $dbname);

    if (file_exists($seedPath) && trim(file_get_contents($seedPath)) !== '') {
        executarArquivoSql($pdoDb, $seedPath);
        $mensagemSeed = "seed.sql executado.";
    } else {
        $count = $pdoDb->query("SELECT COUNT(*) FROM animais")->fetchColumn();

        if ((int)$count === 0) {
            $stmt = $pdoDb->prepare("
                INSERT INTO animais (brinco, nome_apelido, raca, sexo, data_nascimento, lote)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $dadosFake = [
                ["4052", "Campeão", "Nelore", "Macho", "2023-05-15", "Lote Engorda A"],
                ["4053", "Estrela", "Angus", "Fêmea", "2023-06-10", "Lote Matriz B"],
                ["4054", "Trovão", "Girolando", "Macho", "2022-11-02", "Lote Recria C"],
            ];

            foreach ($dadosFake as $animal) {
                $stmt->execute($animal);
            }

            $mensagemSeed = "Dados fake inseridos automaticamente.";
        } else {
            $mensagemSeed = "Banco já tinha animais; nenhum dado fake foi inserido.";
        }
    }

    echo "<h1>Setup concluído com sucesso</h1>";
    echo "<p>Banco <strong>{$dbname}</strong> criado/verificado.</p>";
    echo "<p>schema.sql executado.</p>";
    echo "<p>{$mensagemSeed}</p>";
    echo "<p><a href='dashboard.php'>Ir para o sistema</a></p>";

} catch (Throwable $e) {
    http_response_code(500);
    echo "<h1>Erro no setup</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
