<?php

function garantirEstruturaAuditoriaAnimal(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animal_historico_reprodutivo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NOT NULL,
            data_evento DATE NOT NULL,
            tipo_evento VARCHAR(100) NOT NULL,
            observacao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS animal_alteracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animal_id INT NULL,
            brinco_referencia VARCHAR(50),
            nome_referencia VARCHAR(100),
            tipo_alteracao VARCHAR(50) NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            detalhes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animal_id) REFERENCES animais(id)
        )
    ");
}

function registrarAlteracaoAnimal(
    PDO $pdo,
    ?int $animalId,
    ?string $brinco,
    ?string $nome,
    string $tipoAlteracao,
    string $descricao,
    array $detalhes = []
): void {
    $stmt = $pdo->prepare("
        INSERT INTO animal_alteracoes (
            animal_id,
            brinco_referencia,
            nome_referencia,
            tipo_alteracao,
            descricao,
            detalhes
        ) VALUES (
            :animal_id,
            :brinco_referencia,
            :nome_referencia,
            :tipo_alteracao,
            :descricao,
            :detalhes
        )
    ");

    $stmt->execute([
        ':animal_id' => $animalId,
        ':brinco_referencia' => $brinco !== '' ? $brinco : null,
        ':nome_referencia' => $nome !== '' ? $nome : null,
        ':tipo_alteracao' => $tipoAlteracao,
        ':descricao' => $descricao,
        ':detalhes' => !empty($detalhes) ? json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
    ]);
}

function descreverMudancasAnimal(array $antes, array $depois, array $campos): array
{
    $mudancas = [];

    foreach ($campos as $campo => $rotulo) {
        $valorAnterior = (string) ($antes[$campo] ?? '');
        $valorNovo = (string) ($depois[$campo] ?? '');

        if ($valorAnterior === $valorNovo) {
            continue;
        }

        $mudancas[] = sprintf(
            '%s: "%s" -> "%s"',
            $rotulo,
            $valorAnterior !== '' ? $valorAnterior : 'vazio',
            $valorNovo !== '' ? $valorNovo : 'vazio'
        );
    }

    return $mudancas;
}

function formatarDataHoraAlteracao(?string $dataHora): string
{
    if (!$dataHora) {
        return '--';
    }

    $timestamp = strtotime($dataHora);

    if ($timestamp === false) {
        return $dataHora;
    }

    return date('d/m/Y H:i', $timestamp);
}
