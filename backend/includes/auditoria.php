<?php

function garantirTabelaAuditoria(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auditoria_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            usuario_nome VARCHAR(150) NULL,
            usuario_perfil VARCHAR(50) NULL,
            acao VARCHAR(80) NOT NULL,
            entidade VARCHAR(80) NOT NULL,
            entidade_id INT NULL,
            descricao VARCHAR(255) NOT NULL,
            ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )
    ");
}

function registrarAuditoria(PDO $pdo, string $acao, string $entidade, ?int $entidadeId, string $descricao): void
{
    try {
        garantirTabelaAuditoria($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO auditoria_sistema (
                usuario_id,
                usuario_nome,
                usuario_perfil,
                acao,
                entidade,
                entidade_id,
                descricao,
                ip
            ) VALUES (
                :usuario_id,
                :usuario_nome,
                :usuario_perfil,
                :acao,
                :entidade,
                :entidade_id,
                :descricao,
                :ip
            )
        ");

        $stmt->execute([
            ':usuario_id' => usuarioAtualId() ?: null,
            ':usuario_nome' => usuarioAtualNome(),
            ':usuario_perfil' => usuarioAtualPerfil(),
            ':acao' => substr($acao, 0, 80),
            ':entidade' => substr($entidade, 0, 80),
            ':entidade_id' => $entidadeId,
            ':descricao' => substr($descricao, 0, 255),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        // Auditoria nao deve impedir a operacao principal do usuario.
    }
}
