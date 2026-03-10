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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

CREATE TABLE IF NOT EXISTS `ciclo_reprodutivo` (
  `id_ciclo` int(11) NOT NULL AUTO_INCREMENT,
  `id_matriz` int(11) DEFAULT NULL,
  `id_touro_ou_semen` int(11) DEFAULT NULL,
  `data_inseminacao` date DEFAULT NULL,
  `confirmacao_prenhez` tinyint(1) DEFAULT 0,
  `previsao_parto` date DEFAULT NULL,
  PRIMARY KEY (`id_ciclo`),
  KEY `id_matriz` (`id_matriz`),
  KEY `id_touro_ou_semen` (`id_touro_ou_semen`),
  CONSTRAINT `1` FOREIGN KEY (`id_matriz`) REFERENCES `animal` (`id_animal`),
  CONSTRAINT `2` FOREIGN KEY (`id_touro_ou_semen`) REFERENCES `animal` (`id_animal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
