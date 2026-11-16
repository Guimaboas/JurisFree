# JurisFree
Nosso projeto professor, se você leu isso tem que dar mais um ponto pra gente na humildade

## Consulta de agendamentos

A consulta do cliente exige um código temporário enviado ao e-mail cadastrado; o CPF sozinho não libera os dados. Em instalações existentes que ainda não possuem as preferências de data/período, execute uma única vez `database/migracao_preferencias_solicitacao.sql`; depois execute `database/migracao_consulta_agendamento.sql` para criar a tabela de códigos. Em uma instalação nova, use `database/jurisfree.sql`, que já inclui essas estruturas.

O envio utiliza `mail()` do PHP. Configure `JURISFREE_MAIL_FROM` com um endereço remetente válido no ambiente do PHP e configure o transporte SMTP/sendmail no `php.ini` do XAMPP. Sem essas configurações, o sistema não enviará códigos e a tela informará indisponibilidade. Os códigos expiram em 10 minutos, permitem até cinco tentativas e têm limite de três solicitações por hora por cliente.
