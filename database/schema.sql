CREATE DATABASE IF NOT EXISTS sga_pecuaria
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sga_pecuaria;

CREATE TABLE IF NOT EXISTS animais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brinco VARCHAR(50) NOT NULL UNIQUE,
    nome_apelido VARCHAR(100) NOT NULL,
    raca VARCHAR(100) NOT NULL,
    sexo VARCHAR(20) NOT NULL,
    data_nascimento DATE,
    lote VARCHAR(100),
    mae_id INT NULL,
    pai_id INT NULL,
    data_ultimo_cio DATE NULL,
    prenha TINYINT(1) DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'Ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mae_id) REFERENCES animais(id),
    FOREIGN KEY (pai_id) REFERENCES animais(id)
);

CREATE TABLE IF NOT EXISTS animal_alteracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NULL,
    brinco_referencia VARCHAR(50),
    nome_referencia VARCHAR(100),
    tipo_alteracao VARCHAR(50) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animais(id)
);

CREATE TABLE IF NOT EXISTS animal_vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    comprador_nome VARCHAR(150) NOT NULL,
    data_venda DATE NOT NULL,
    valor DECIMAL(10,2) NULL,
    observacao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animais(id)
);

CREATE TABLE IF NOT EXISTS animal_obitos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    data_obito DATE NOT NULL,
    causa VARCHAR(150),
    observacao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animais(id)
);

CREATE TABLE IF NOT EXISTS pesagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    data_pesagem DATE NOT NULL,
    peso_kg DECIMAL(10,2) NOT NULL,
    observacao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animais(id)
);

CREATE TABLE IF NOT EXISTS manejos_sanitarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    descricao VARCHAR(255),
    data_evento DATE NOT NULL,
    proxima_data DATE,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animais(id)
);

CREATE TABLE IF NOT EXISTS financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    categoria VARCHAR(100),
    descricao VARCHAR(255),
    valor DECIMAL(10,2) NOT NULL,
    data_lancamento DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tabelacontas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150),
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    natureza VARCHAR(100) NOT NULL,
    prioridade ENUM('baixa','media','alta'),
    status ENUM('pendente','pago') DEFAULT 'pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    perfil VARCHAR(50) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS suporte_chamados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_contato VARCHAR(150) NOT NULL,
    email_contato VARCHAR(150) NOT NULL,
    assunto VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Aberto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
