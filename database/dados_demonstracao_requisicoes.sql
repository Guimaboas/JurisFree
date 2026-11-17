-- Use somente em uma instalação local/de demonstração após importar jurisfree.sql.
-- Pode ser executado novamente sem duplicar os clientes ou as requisições de teste.
START TRANSACTION;

INSERT INTO usuario (nome, email, senha, funcao, status)
VALUES
    ('Dra. Camila Nunes (Demonstração)', 'demo.advogado1@jurisfree.local', '$2y$12$dU44HbOXt6ZhogBiWocZZeiLEcLTOca2/kxmrpgi9I/o/1Wpc1JGu', 'advogado', 'ativo'),
    ('Dr. Felipe Martins (Demonstração)', 'demo.advogado2@jurisfree.local', '$2y$12$dU44HbOXt6ZhogBiWocZZeiLEcLTOca2/kxmrpgi9I/o/1Wpc1JGu', 'advogado', 'ativo'),
    ('Dra. Luiza Costa (Demonstração)', 'demo.advogado3@jurisfree.local', '$2y$12$dU44HbOXt6ZhogBiWocZZeiLEcLTOca2/kxmrpgi9I/o/1Wpc1JGu', 'advogado', 'ativo')
ON DUPLICATE KEY UPDATE id = id;
-- Utilize a senha Demo@123
INSERT INTO advogado (id_usuario, oab, especialidade, ativo)
SELECT usuario.id, dados.oab, dados.especialidade, TRUE
FROM usuario
INNER JOIN (
    SELECT 'demo.advogado1@jurisfree.local' AS email, 'OAB-DEMO-001' AS oab, 'Direito Previdenciário' AS especialidade
    UNION ALL
    SELECT 'demo.advogado2@jurisfree.local', 'OAB-DEMO-002', 'Direito do Consumidor'
    UNION ALL
    SELECT 'demo.advogado3@jurisfree.local', 'OAB-DEMO-003', 'Direito Civil'
) AS dados
    ON dados.email = usuario.email
WHERE NOT EXISTS (
    SELECT 1
    FROM advogado
    WHERE advogado.id_usuario = usuario.id
);

INSERT INTO cliente (nome, cpf, email, telefone)
VALUES
    ('Maria da Silva (Demonstração)', '98765432100', 'cliente01@example.invalid', '11990000001'),
    ('João Pereira (Demonstração)', '98765432101', 'cliente02@example.invalid', '11990000002'),
    ('Ana Oliveira (Demonstração)', '98765432102', 'cliente03@example.invalid', '11990000003'),
    ('Pedro Santos (Demonstração)', '98765432103', 'cliente04@example.invalid', '11990000004'),
    ('Beatriz Costa (Demonstração)', '98765432104', 'cliente05@example.invalid', '11990000005'),
    ('Carlos Souza (Demonstração)', '98765432105', 'cliente06@example.invalid', '11990000006'),
    ('Marina Rocha (Demonstração)', '98765432106', 'cliente07@example.invalid', '11990000007'),
    ('Rafael Almeida (Demonstração)', '98765432107', 'cliente08@example.invalid', '11990000008'),
    ('Juliana Ferreira (Demonstração)', '98765432108', 'cliente09@example.invalid', '11990000009'),
    ('Lucas Ribeiro (Demonstração)', '98765432109', 'cliente10@example.invalid', '11990000010')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO solicitacao_atendimento
    (id_cliente, id_area_juridica, descricao, data_desejada, periodo_desejado)
SELECT
    cliente.id,
    area_juridica.id,
    dados.descricao,
    DATE_ADD(CURDATE(), INTERVAL dados.dias DAY),
    dados.periodo
FROM (
    SELECT '98765432100' AS cpf, 'Direito Previdenciário' AS area, 'Demonstração: orientação sobre benefício previdenciário e documentação necessária.' AS descricao, 1 AS dias, 'manha' AS periodo
    UNION ALL SELECT '98765432101', 'Direito do Consumidor', 'Demonstração: contestação de cobrança indevida em serviço contratado.', 1, 'tarde'
    UNION ALL SELECT '98765432102', 'Direito Previdenciário', 'Demonstração: dúvida sobre aposentadoria e tempo de contribuição.', 2, 'manha'
    UNION ALL SELECT '98765432103', 'Direito do Consumidor', 'Demonstração: pedido de orientação sobre produto com defeito.', 3, 'tarde'
    UNION ALL SELECT '98765432104', 'Direito Previdenciário', 'Demonstração: consulta sobre revisão de benefício do INSS.', 4, 'manha'
    UNION ALL SELECT '98765432105', 'Direito do Consumidor', 'Demonstração: orientação sobre cancelamento de contrato e multa.', 5, 'tarde'
    UNION ALL SELECT '98765432106', 'Direito Previdenciário', 'Demonstração: solicitação de informações sobre auxílio por incapacidade.', 6, 'manha'
    UNION ALL SELECT '98765432107', 'Direito do Consumidor', 'Demonstração: análise de cobrança não reconhecida na fatura.', 7, 'tarde'
    UNION ALL SELECT '98765432108', 'Direito Previdenciário', 'Demonstração: orientação sobre pensão por morte e documentos.', 8, 'manha'
    UNION ALL SELECT '98765432109', 'Direito do Consumidor', 'Demonstração: dúvida sobre garantia e troca de produto.', 9, 'tarde'
) AS dados
INNER JOIN cliente
    ON cliente.cpf = dados.cpf
INNER JOIN area_juridica
    ON area_juridica.nome = dados.area
WHERE NOT EXISTS (
    SELECT 1
    FROM solicitacao_atendimento
    WHERE solicitacao_atendimento.id_cliente = cliente.id
      AND solicitacao_atendimento.descricao = dados.descricao
);

COMMIT;
