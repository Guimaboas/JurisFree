-- Executar uma única vez em bancos existentes.
-- Em uma instalação nova, use jurisfree.sql.
USE jurisfree;

CREATE TABLE codigo_consulta_agendamento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    codigo_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    utilizado_em DATETIME NULL,

    CONSTRAINT fk_codigo_consulta_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_codigo_consulta_cliente_data
    ON codigo_consulta_agendamento(id_cliente, criado_em);
