<?php

function garantirTabelaAuditoriaAnimal(PDO $pdo): void
{
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
}

function garantirStatusAnimal(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM animais LIKE 'status'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        $pdo->exec("ALTER TABLE animais ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Ativo'");
    }
}

function garantirBaixasAnimal(PDO $pdo): void
{
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

function registrarAlteracaoAnimal(PDO $pdo, $animal_id, $brinco, $nome_apelido, $tipo, $descricao): void
{
    $stmt = $pdo->prepare("
        INSERT INTO animal_alteracoes (
            animal_id,
            brinco_referencia,
            nome_referencia,
            tipo_alteracao,
            descricao
        ) VALUES (
            :animal_id,
            :brinco_referencia,
            :nome_referencia,
            :tipo_alteracao,
            :descricao
        )
    ");

    $stmt->execute([
        ':animal_id' => $animal_id,
        ':brinco_referencia' => $brinco !== '' ? $brinco : null,
        ':nome_referencia' => $nome_apelido !== '' ? $nome_apelido : null,
        ':tipo_alteracao' => $tipo,
        ':descricao' => $descricao,
    ]);
}

function formatarDataHoraAuditoria($data): string
{
    if (!$data) {
        return '--';
    }

    $timestamp = strtotime((string) $data);

    if ($timestamp === false) {
        return (string) $data;
    }

    return date('d/m/Y H:i', $timestamp);
}
