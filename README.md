# JurisFree
Nosso projeto professor, se você leu isso tem que dar mais um ponto pra gente na humildade

## Consulta de agendamentos

A consulta do cliente exige um código temporário enviado ao e-mail cadastrado; o CPF sozinho não libera os dados. Em instalações existentes que ainda não possuem as preferências de data/período, execute uma única vez `database/migracao_preferencias_solicitacao.sql`; depois execute `database/migracao_consulta_agendamento.sql` para criar a tabela de códigos. Em uma instalação nova, use `database/jurisfree.sql`, que já inclui essas estruturas.

O envio utiliza `mail()` do PHP. Configure `JURISFREE_MAIL_FROM` com um endereço remetente válido no ambiente do PHP e configure o transporte SMTP/sendmail no `php.ini` do XAMPP. Sem essas configurações, o sistema não enviará códigos e a tela informará indisponibilidade. Os códigos expiram em 10 minutos, permitem até cinco tentativas e têm limite de três solicitações por hora por cliente.

## Requisições de demonstração

Para preencher a lista administrativa com três advogados, dez clientes fictícios e dez requisições, importe `database/dados_demonstracao_requisicoes.sql` no banco `jurisfree` depois de criar as tabelas. O script pode ser executado novamente sem duplicar esses dados. Use-o somente em ambiente local/de demonstração.

## Finalização de agendamentos

Advogados podem consultar todos os próprios agendamentos pelo item **Meus agendamentos** no painel. Abaixo da lista completa, os agendamentos passados ainda não concluídos ou cancelados aparecem em **Agendamentos para concluir**. A conclusão cria um caso com a descrição informada e marca a solicitação como concluída. O cancelamento exige uma justificativa, permite um comentário opcional e registra ambos nas observações do agendamento. Não é necessária migração adicional do banco.
