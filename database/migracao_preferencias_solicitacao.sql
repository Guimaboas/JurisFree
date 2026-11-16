-- Executar uma única vez em uma base existente antes destas alterações.
-- Em uma instalação nova, use jurisfree.sql e não execute esta migração.
USE jurisfree;

SET @id_atendente_existe = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'solicitacao_atendimento'
      AND column_name = 'id_atendente'
);
SET @sql_id_atendente = IF(
    @id_atendente_existe = 0,
    'ALTER TABLE solicitacao_atendimento ADD COLUMN id_atendente INT NULL AFTER id_cliente',
    'ALTER TABLE solicitacao_atendimento MODIFY COLUMN id_atendente INT NULL'
);
PREPARE stmt_id_atendente FROM @sql_id_atendente;
EXECUTE stmt_id_atendente;
DEALLOCATE PREPARE stmt_id_atendente;

ALTER TABLE solicitacao_atendimento
    ADD COLUMN data_desejada DATE NULL AFTER descricao,
    ADD COLUMN periodo_desejado ENUM('manha', 'tarde', 'noite') NULL AFTER data_desejada;
