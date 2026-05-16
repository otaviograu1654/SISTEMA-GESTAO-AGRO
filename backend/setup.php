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
            parceiro_id INT NULL,
            comprador_nome VARCHAR(150) NOT NULL,
            data_venda DATE NOT NULL,
            valor DECIMAL(10,2) NULL,
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id),
            FOREIGN KEY (parceiro_id) REFERENCES parceiros(id)
        )
    ");

    if (!colunaExiste($pdo, 'animal_vendas', 'parceiro_id', $banco)) {
        $pdo->exec("ALTER TABLE animal_vendas ADD COLUMN parceiro_id INT NULL AFTER animal_id");
    }

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
            criado_por_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaExiste($pdo, 'usuarios', 'ativo', $banco)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }

    if (!colunaExiste($pdo, 'usuarios', 'criado_por_id', $banco)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN criado_por_id INT NULL AFTER ativo");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuario_permissoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            modulo VARCHAR(50) NOT NULL,
            permitido TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY usuario_modulo (usuario_id, modulo),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )
    ");

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET nome = CASE WHEN nome = 'Administrador' THEN 'Desenvolvedor' ELSE nome END,
            perfil = 'Desenvolvedor'
        WHERE email = 'admin@sga.local'
          AND perfil = 'Administrador'
    ");
    $stmt->execute();
}

function garantirEstruturaParceiros(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS parceiros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            documento VARCHAR(50),
            telefone VARCHAR(50),
            email VARCHAR(150),
            observacao VARCHAR(255),
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaExiste($pdo, 'parceiros', 'ativo', $banco)) {
        $pdo->exec("ALTER TABLE parceiros ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }
}

function garantirEstruturaFinanceiro(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS financeiro (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(20) NOT NULL,
            parceiro_id INT NULL,
            categoria VARCHAR(100),
            descricao VARCHAR(255),
            valor DECIMAL(10,2) NOT NULL,
            data_lancamento DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parceiro_id) REFERENCES parceiros(id)
        )
    ");

    if (!colunaExiste($pdo, 'financeiro', 'parceiro_id', $banco)) {
        $pdo->exec("ALTER TABLE financeiro ADD COLUMN parceiro_id INT NULL AFTER tipo");
    }
}

function garantirEstruturaRacas(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS racas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL UNIQUE,
            descricao VARCHAR(255),
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaExiste($pdo, 'racas', 'ativo', $banco)) {
        $pdo->exec("ALTER TABLE racas ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }
}

function garantirEstruturaEstoque(PDO $pdo, string $banco): void
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

    if (!colunaExiste($pdo, 'estoque_produtos', 'ativo', $banco)) {
        $pdo->exec("ALTER TABLE estoque_produtos ADD COLUMN ativo TINYINT(1) DEFAULT 1");
    }

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

function garantirEstruturaProducaoLeite(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS producao_leite (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NULL,
            data_producao DATE NOT NULL,
            turno VARCHAR(20) NOT NULL,
            litros DECIMAL(10,2) NOT NULL,
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");
}

function garantirEstruturaLotes(PDO $pdo, string $banco): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL UNIQUE,
            descricao VARCHAR(255),
            ativo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    if (!colunaExiste($pdo, 'lotes', 'ativo', $banco)) {
        $pdo->exec("ALTER TABLE lotes ADD COLUMN ativo TINYINT(1) DEFAULT 1");
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

    garantirEstruturaParceiros($pdoDb, $dbname);
    garantirEstruturaFinanceiro($pdoDb, $dbname);
    garantirEstruturaRacas($pdoDb, $dbname);
    garantirEstruturaEstoque($pdoDb, $dbname);
    garantirEstruturaLotes($pdoDb, $dbname);
    garantirEstruturaAnimais($pdoDb, $dbname);
    garantirEstruturaProducaoLeite($pdoDb, $dbname);
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
