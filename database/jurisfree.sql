-- O banco de dados utilizado é oMySQL
-- Esse arquivo cria o banco, tabelas, relacionamentos, índices e alguns dados fictícios para teste.

CREATE DATABASE IF NOT EXISTS jurisfree
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE jurisfree;

-- 1. CÓDIGOS DE CONVITE
-- A função do usuário é definida pelo código utilizado.

CREATE TABLE codigo_convite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    funcao ENUM(
        'coordenador',
        'docente',
        'advogado',
        'estudante'
    ) NOT NULL,
    usado BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_uso DATETIME NULL
) ENGINE=InnoDB;

-- 2. USUÁRIOS

CREATE TABLE usuario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    funcao ENUM(
        'coordenador',
        'docente',
        'advogado',
        'estudante'
    ) NOT NULL,
    status ENUM(
        'pendente',
        'ativo',
        'bloqueado'
    ) NOT NULL DEFAULT 'ativo',
    id_codigo_convite INT UNSIGNED NULL,
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuario_codigo_convite
        FOREIGN KEY (id_codigo_convite)
        REFERENCES codigo_convite(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_usuario_funcao ON usuario(funcao);
CREATE INDEX idx_usuario_status ON usuario(status);

-- 3. CLIENTES / CIDADÃOS

CREATE TABLE cliente (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE,
    endereco VARCHAR(255),
    cidade VARCHAR(100),
    estado CHAR(2),
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_cliente_nome ON cliente(nome);
CREATE INDEX idx_cliente_email ON cliente(email);

-- 4. ÁREAS JURÍDICAS

CREATE TABLE area_juridica (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT
) ENGINE=InnoDB;

-- 5. ADVOGADOS

CREATE TABLE advogado (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL UNIQUE,
    oab VARCHAR(30),
    especialidade VARCHAR(150),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_advogado_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_advogado_oab ON advogado(oab);

-- 6. DISPONIBILIDADE DOS ADVOGADOS

CREATE TABLE disponibilidade_advogado (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_advogado INT UNSIGNED NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT chk_disponibilidade_dia
        CHECK (dia_semana BETWEEN 0 AND 6),

    CONSTRAINT chk_disponibilidade_horario
        CHECK (hora_inicio < hora_fim),

    CONSTRAINT fk_disponibilidade_advogado
        FOREIGN KEY (id_advogado)
        REFERENCES advogado(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_disponibilidade_advogado
    ON disponibilidade_advogado(id_advogado, dia_semana);

-- 7. SOLICITAÇÕES DE ATENDIMENTO

CREATE TABLE solicitacao_atendimento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    id_area_juridica INT UNSIGNED NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM(
        'pendente',
        'em_analise',
        'agendado',
        'em_atendimento',
        'concluido',
        'cancelado'
    ) NOT NULL DEFAULT 'pendente',
    data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_solicitacao_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacao_area
        FOREIGN KEY (id_area_juridica)
        REFERENCES area_juridica(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_solicitacao_status
    ON solicitacao_atendimento(status);

CREATE INDEX idx_solicitacao_cliente
    ON solicitacao_atendimento(id_cliente);

-- 8. AGENDAMENTOS

CREATE TABLE agendamento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_solicitacao INT UNSIGNED NOT NULL,
    id_advogado INT UNSIGNED NOT NULL,
    data_hora DATETIME NOT NULL,
    status ENUM(
        'agendado',
        'confirmado',
        'realizado',
        'cancelado',
        'nao_compareceu'
    ) NOT NULL DEFAULT 'agendado',
    observacao TEXT,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_agendamento_solicitacao
        FOREIGN KEY (id_solicitacao)
        REFERENCES solicitacao_atendimento(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_agendamento_advogado
        FOREIGN KEY (id_advogado)
        REFERENCES advogado(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_agendamento_data
    ON agendamento(data_hora);

CREATE INDEX idx_agendamento_advogado_data
    ON agendamento(id_advogado, data_hora);

-- 9. ATENDIMENTOS / CASOS

CREATE TABLE caso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_solicitacao INT UNSIGNED NOT NULL UNIQUE,
    id_advogado INT UNSIGNED NOT NULL,
    status ENUM(
        'aberto',
        'em_andamento',
        'aguardando_cliente',
        'concluido',
        'arquivado'
    ) NOT NULL DEFAULT 'aberto',
    resumo TEXT,
    data_abertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_encerramento DATETIME NULL,

    CONSTRAINT fk_caso_solicitacao
        FOREIGN KEY (id_solicitacao)
        REFERENCES solicitacao_atendimento(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_caso_advogado
        FOREIGN KEY (id_advogado)
        REFERENCES advogado(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_caso_status ON caso(status);

-- ANDAMENTO DO CASO


CREATE TABLE andamento_caso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_caso INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    descricao TEXT NOT NULL,
    data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_andamento_caso
        FOREIGN KEY (id_caso)
        REFERENCES caso(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_andamento_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_andamento_caso_data
    ON andamento_caso(id_caso, data_registro);

-- DADOS FICTÍCIOS PARA TESTE

INSERT INTO codigo_convite (codigo, funcao)
VALUES
    ('JF-COORD-2026', 'coordenador'),
    ('JF-DOC-2026', 'docente'),
    ('JF-ADV-2026', 'advogado'),
    ('JF-EST-2026', 'estudante');

INSERT INTO area_juridica (nome, descricao)
VALUES
    ('Direito Previdenciário',
     'Questões relacionadas ao INSS e benefícios previdenciários.'),
    ('Direito do Consumidor',
     'Questões relacionadas às relações de consumo e direitos do consumidor.');

