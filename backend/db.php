<?php
// Dados de conexão com o banco: servidor, nome do banco, usuário e senha.
$host = "localhost";
$dbname = "sga_pecuaria";
$user = "root";
$pass = "";

try {
    // Cria a conexão PDO com o MariaDB/MySQL e define UTF-8 para caracteres especiais.
    // Migrar para o mysqli?
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);

    // Configura o PDO para mostrar erros como exceções, facilitando depuração.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Erro na conexão com o banco: " . $e->getMessage());
    http_response_code(500);
    die("Nao foi possivel conectar ao banco de dados agora.");
}
