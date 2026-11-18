-- Executar uma única vez em bancos existentes.
-- Em uma instalação nova, use jurisfree.sql.
USE jurisfree;

CREATE TABLE codigo_acesso_agendamento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL UNIQUE,
    codigo_hash VARCHAR(255) NOT NULL,
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_codigo_acesso_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;
