# JurisFree
Nosso projeto professor, se você leu isso tem que dar mais um ponto pra gente na humildade

## Consulta de agendamentos

A consulta do cliente exige CPF e código enviado ao e-mail cadastrado; o CPF sozinho não libera os dados. Ao criar o primeiro agendamento de um cliente, o sistema envia um código numérico permanente de 12 dígitos. Esse código é único por cliente, não expira e não muda em novos agendamentos. Não há fluxo para solicitar outro código. Em instalações existentes, execute uma única vez `database/migracao_preferencias_solicitacao.sql` (se necessário) e `database/migracao_codigo_acesso_agendamento.sql`. Em uma instalação nova, use `database/jurisfree.sql`, que já inclui essas estruturas.

O envio utiliza PHPMailer via SMTP. Configure no ambiente do PHP, sem gravar credenciais no código-fonte: `JURISFREE_SMTP_USER`, `JURISFREE_SMTP_PASSWORD` (senha de app do Gmail), `JURISFREE_MAIL_FROM` (o endereço autenticado ou um alias autorizado) e, opcionalmente, `JURISFREE_SMTP_HOST` (padrão `smtp.gmail.com`) e `JURISFREE_SMTP_PORT` (padrão `587`, STARTTLS). Sem essas configurações, os emails não serão enviados e o painel informará a falha. O código permanente é bloqueado por 15 minutos após cinco tentativas inválidas.

## Requisições de demonstração

Para preencher a lista administrativa com três advogados, dez clientes fictícios e dez requisições, importe `database/dados_demonstracao_requisicoes.sql` no banco `jurisfree` depois de criar as tabelas. O script pode ser executado novamente sem duplicar esses dados. Use-o somente em ambiente local/de demonstração.

## Finalização de agendamentos

Advogados podem consultar todos os próprios agendamentos pelo item **Meus agendamentos** no painel. Abaixo da lista completa, os agendamentos passados ainda não concluídos ou cancelados aparecem em **Agendamentos para concluir**. A conclusão cria um caso com a descrição informada (opcional) e marca a solicitação como concluída; quando preenchida, a descrição gera um aviso por email ao cliente. O cancelamento exige uma justificativa, permite uma descrição adicional opcional e registra ambos nas observações do agendamento; o cliente recebe essas informações por email. Essas notificações usam as mesmas variáveis SMTP.
