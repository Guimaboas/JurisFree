<?php
require_once __DIR__ . "/../Config/sessao.php";
$usuarioPainel = exigeSessaoUsuario();
if ($usuarioPainel["funcao"] !== "advogado") {
    http_response_code(403);
    exit("Acesso permitido somente a advogados.");
}

require_once __DIR__ . "/../Config/conecta.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$nomePainel = htmlspecialchars($usuarioPainel["nome"], ENT_QUOTES, "UTF-8");
$iniciaisPainel = htmlspecialchars(iniciaisUsuario($usuarioPainel["nome"]), ENT_QUOTES, "UTF-8");
$escape = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
$mensagens = [
    "concluido" => ["Agendamento concluído e caso registrado.", false],
    "concluido_email" => ["Agendamento concluído, mas não foi possível enviar o aviso por email. Verifique a configuração SMTP.", true],
    "cancelado" => ["Agendamento cancelado.", false],
    "cancelado_email" => ["Agendamento cancelado, mas não foi possível enviar o aviso por email. Verifique a configuração SMTP.", true],
    "csrf" => ["Não foi possível validar o formulário. Atualize a página e tente novamente.", true],
    "dados" => ["Preencha os campos obrigatórios com textos válidos.", true],
    "indisponivel" => ["Este agendamento não está mais disponível para finalização.", true],
    "salvar" => ["Não foi possível atualizar o agendamento. Tente novamente mais tarde.", true]
];
$resultadoMensagem = $_GET["resultado"] ?? "";
$mensagem = is_string($resultadoMensagem) && isset($mensagens[$resultadoMensagem])
    ? $mensagens[$resultadoMensagem]
    : null;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];
$agendamentos = [];
$erroConsulta = false;

try {
    $stmt = $Mysql->prepare(
        "SELECT
            ag.id,
            ag.data_hora,
            ag.status,
            cliente.nome AS cliente,
            area_juridica.nome AS area
         FROM agendamento AS ag
         INNER JOIN solicitacao_atendimento AS sa
             ON sa.id = ag.id_solicitacao
         INNER JOIN cliente
             ON cliente.id = sa.id_cliente
         INNER JOIN area_juridica
             ON area_juridica.id = sa.id_area_juridica
         INNER JOIN advogado
             ON advogado.id = ag.id_advogado
         WHERE advogado.id_usuario = ?
         ORDER BY ag.data_hora DESC, ag.id DESC"
    );
    $stmt->bind_param("i", $usuarioPainel["id"]);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($agendamento = $resultado->fetch_assoc()) {
        $agendamentos[] = $agendamento;
    }
    $stmt->close();
} catch (mysqli_sql_exception $erro) {
    error_log("Falha ao carregar agendamentos para finalização: " . $erro->getMessage());
    http_response_code(500);
    $erroConsulta = true;
}

$rotulosStatus = [
    "agendado" => ["Agendado", "scheduled"],
    "confirmado" => ["Confirmado", "confirmed"],
    "realizado" => ["Realizado", "completed"],
    "cancelado" => ["Cancelado", "cancelled"],
    "nao_compareceu" => ["Não compareceu", "absent"]
];
$agendamentosParaConcluir = array_values(array_filter(
    $agendamentos,
    static function ($agendamento) {
        $dataHora = strtotime($agendamento["data_hora"]);
        return $dataHora !== false
            && $dataHora <= time()
            && in_array($agendamento["status"], ["agendado", "confirmado"], true);
    }
));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meus agendamentos — JurisFree</title>
  <meta name="description" content="Consulte e finalize seus agendamentos jurídicos.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-dashboard">
  <header class="dashboard-header">
    <div class="dashboard-header-inner">
      <a href="../Views/jurisfree-landing.html" class="logo" aria-label="JurisFree - página inicial">
        <span class="logo-icon">J</span>
        <span>juris<span class="brand">free</span></span>
      </a>
      <div class="dashboard-user">
        <span class="dashboard-user-avatar"><?php echo $iniciaisPainel; ?></span>
        <div>
          <strong><?php echo $nomePainel; ?></strong>
          <small>Advogado</small>
        </div>
      </div>
    </div>
  </header>

  <div class="dashboard-layout">
    <aside class="dashboard-sidebar" aria-label="Navegação administrativa">
      <nav class="dashboard-nav">
        <a href="jurisfree-dashboard.php"><span aria-hidden="true">▦</span> Visão geral</a>
        <a href="jurisfree-agendamentos.php"><span aria-hidden="true">▣</span> Agendamentos</a>
        <a href="jurisfree-requisicoes.php"><span aria-hidden="true">☷</span> Requisições</a>
        <a class="active" href="jurisfree-conclusao.php" aria-current="page">
          <span aria-hidden="true">✓</span> Meus agendamentos
        </a>
      </nav>
      <a class="dashboard-back" href="../Views/jurisfree-landing.html">← Voltar ao site</a>
    </aside>

    <main class="dashboard-main">
      <section class="dashboard-welcome">
        <div>
          <span class="dashboard-eyebrow">Painel do advogado</span>
          <h1>Meus agendamentos</h1>
          <p>Consulte todos os seus atendimentos e finalize os que já passaram do horário.</p>
        </div>
      </section>

      <?php if ($mensagem): ?>
        <p class="requests-alert<?php echo $mensagem[1] ? " error" : ""; ?>" role="status">
          <?php echo $escape($mensagem[0]); ?>
        </p>
      <?php endif; ?>
      <?php if ($erroConsulta): ?>
        <p class="requests-alert error" role="alert">Não foi possível carregar os agendamentos do banco de dados.</p>
      <?php endif; ?>

      <section class="dashboard-card appointments-card" aria-label="Todos os seus agendamentos">
        <div class="appointments-toolbar">
          <div>
            <span class="dashboard-eyebrow">Atendimentos</span>
            <h2>Todos os agendamentos</h2>
          </div>
          <div class="appointments-filters">
            <label class="sr-only" for="myAppointmentsSearch">Buscar agendamento</label>
            <input id="myAppointmentsSearch" type="search" placeholder="Buscar cliente ou área">
            <label class="sr-only" for="myAppointmentsStatus">Filtrar por status</label>
            <select id="myAppointmentsStatus">
              <option value="">Todos os status</option>
              <option value="agendado">Agendado</option>
              <option value="confirmado">Confirmado</option>
              <option value="realizado">Realizado</option>
              <option value="cancelado">Cancelado</option>
              <option value="nao_compareceu">Não compareceu</option>
            </select>
          </div>
        </div>

        <div class="appointments-table-wrap">
          <table class="appointments-table">
            <thead>
              <tr>
                <th scope="col">Data e hora</th>
                <th scope="col">Cliente</th>
                <th scope="col">Área jurídica</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody id="myAppointmentRows">
              <?php foreach ($agendamentos as $agendamento): ?>
                <?php
                  $status = $rotulosStatus[$agendamento["status"]]
                      ?? [$agendamento["status"], "scheduled"];
                  $dataHora = strtotime($agendamento["data_hora"]);
                ?>
                <tr data-status="<?php echo $escape($agendamento["status"]); ?>">
                  <td>
                    <strong><?php echo $dataHora === false ? "Data indisponível" : date("d/m/Y", $dataHora); ?></strong>
                    <?php if ($dataHora !== false): ?>
                      <small><?php echo date("H:i", $dataHora); ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?php echo $escape($agendamento["cliente"]); ?></td>
                  <td><?php echo $escape($agendamento["area"]); ?></td>
                  <td><span class="appointment-status <?php echo $escape($status[1]); ?>"><?php echo $escape($status[0]); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="appointments-empty" id="myAppointmentsEmpty" <?php echo $agendamentos ? "hidden" : ""; ?>>
            <?php echo $erroConsulta ? "Os agendamentos não estão disponíveis no momento." : "Você ainda não possui agendamentos."; ?>
          </p>
          <p class="appointments-empty" id="myAppointmentsFilterEmpty" hidden>Nenhum agendamento encontrado para os filtros escolhidos.</p>
        </div>
      </section>

      <section class="dashboard-card appointments-card" aria-label="Agendamentos para concluir">
        <div class="appointments-toolbar">
          <div>
            <span class="dashboard-eyebrow">Ações pendentes</span>
            <h2>Agendamentos para concluir</h2>
          </div>
        </div>
        <div class="appointments-table-wrap">
          <table class="appointments-table">
            <thead>
              <tr>
                <th scope="col">Data e hora</th>
                <th scope="col">Cliente</th>
                <th scope="col">Área jurídica</th>
                <th scope="col">Status</th>
                <th scope="col">Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agendamentosParaConcluir as $agendamento): ?>
                <?php
                  $status = $rotulosStatus[$agendamento["status"]]
                      ?? [$agendamento["status"], "scheduled"];
                  $dataHora = strtotime($agendamento["data_hora"]);
                ?>
                <tr>
                  <td>
                    <strong><?php echo date("d/m/Y", $dataHora); ?></strong>
                    <small><?php echo date("H:i", $dataHora); ?></small>
                  </td>
                  <td><?php echo $escape($agendamento["cliente"]); ?></td>
                  <td><?php echo $escape($agendamento["area"]); ?></td>
                  <td><span class="appointment-status <?php echo $escape($status[1]); ?>"><?php echo $escape($status[0]); ?></span></td>
                  <td>
                    <div class="appointment-actions">
                      <button class="appointment-action complete" type="button"
                        data-appointment-id="<?php echo (int) $agendamento["id"]; ?>"
                        data-client="<?php echo $escape($agendamento["cliente"]); ?>"
                        data-area="<?php echo $escape($agendamento["area"]); ?>"
                        aria-label="Concluir atendimento de <?php echo $escape($agendamento["cliente"]); ?>"
                        title="Concluir agendamento">✓</button>
                      <button class="appointment-action cancel" type="button"
                        data-appointment-id="<?php echo (int) $agendamento["id"]; ?>"
                        data-client="<?php echo $escape($agendamento["cliente"]); ?>"
                        data-area="<?php echo $escape($agendamento["area"]); ?>"
                        aria-label="Cancelar atendimento de <?php echo $escape($agendamento["cliente"]); ?>"
                        title="Cancelar agendamento">×</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="appointments-empty" <?php echo $agendamentosParaConcluir ? "hidden" : ""; ?>>
            <?php echo $erroConsulta ? "Os agendamentos não estão disponíveis no momento." : "Não há agendamentos pendentes de conclusão."; ?>
          </p>
        </div>
      </section>
    </main>
  </div>

  <dialog class="schedule-modal" id="completeModal" aria-labelledby="completeModalTitle">
    <form class="schedule-modal-content" action="../Controllers/finalizaAgendamento.php" method="post">
      <div class="schedule-modal-heading">
        <div>
          <span class="dashboard-eyebrow">Conclusão do atendimento</span>
          <h2 id="completeModalTitle">Concluir agendamento</h2>
        </div>
        <button class="schedule-modal-close" type="button" data-close-modal aria-label="Fechar">×</button>
      </div>
      <div class="schedule-request-summary">
        <strong data-modal-client></strong>
        <p data-modal-area></p>
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
      <input type="hidden" name="acao" value="concluir">
      <input type="hidden" name="id_agendamento" data-modal-appointment-id>
      <div class="schedule-field">
        <label for="caseSummary">Descrição do caso</label>
        <textarea id="caseSummary" name="descricao" maxlength="5000"></textarea>
      </div>
      <div class="schedule-modal-actions">
        <button class="schedule-cancel" type="button" data-close-modal>Voltar</button>
        <button class="request-action" type="submit">Concluir atendimento</button>
      </div>
    </form>
  </dialog>

  <dialog class="schedule-modal" id="cancelModal" aria-labelledby="cancelModalTitle">
    <form class="schedule-modal-content" action="../Controllers/finalizaAgendamento.php" method="post">
      <div class="schedule-modal-heading">
        <div>
          <span class="dashboard-eyebrow">Cancelamento do atendimento</span>
          <h2 id="cancelModalTitle">Cancelar agendamento</h2>
        </div>
        <button class="schedule-modal-close" type="button" data-close-modal aria-label="Fechar">×</button>
      </div>
      <div class="schedule-request-summary">
        <strong data-modal-client></strong>
        <p data-modal-area></p>
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
      <input type="hidden" name="acao" value="cancelar">
      <input type="hidden" name="id_agendamento" data-modal-appointment-id>
      <div class="schedule-field">
        <label for="cancellationReason">Justificativa do cancelamento</label>
        <textarea id="cancellationReason" name="justificativa" maxlength="2000" required></textarea>
      </div>
      <div class="schedule-field">
        <label for="cancellationComment">Descrição adicional (opcional)</label>
        <textarea id="cancellationComment" name="comentario" maxlength="2000"></textarea>
      </div>
      <div class="schedule-modal-actions">
        <button class="schedule-cancel" type="button" data-close-modal>Voltar</button>
        <button class="request-action schedule-submit-danger" type="submit">Confirmar cancelamento</button>
      </div>
    </form>
  </dialog>

  <script>
    const myAppointmentsSearch = document.getElementById("myAppointmentsSearch");
    const myAppointmentsStatus = document.getElementById("myAppointmentsStatus");
    const myAppointmentRows = Array.from(document.querySelectorAll("#myAppointmentRows tr"));
    const myAppointmentsEmpty = document.getElementById("myAppointmentsEmpty");
    const myAppointmentsFilterEmpty = document.getElementById("myAppointmentsFilterEmpty");

    function filterCompletionAppointments() {
      const searchTerm = myAppointmentsSearch.value.trim().toLocaleLowerCase("pt-BR");
      const selectedStatus = myAppointmentsStatus.value;
      let visibleRows = 0;

      myAppointmentRows.forEach((row) => {
        const matchesSearch = row.textContent.toLocaleLowerCase("pt-BR").includes(searchTerm);
        const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
        const isVisible = matchesSearch && matchesStatus;
        row.hidden = !isVisible;
        if (isVisible) visibleRows += 1;
      });

      myAppointmentsEmpty.hidden = myAppointmentRows.length > 0;
      myAppointmentsFilterEmpty.hidden = visibleRows > 0 || myAppointmentRows.length === 0;
    }

    myAppointmentsSearch.addEventListener("input", filterCompletionAppointments);
    myAppointmentsStatus.addEventListener("change", filterCompletionAppointments);

    document.querySelectorAll(".appointment-action").forEach((button) => {
      button.addEventListener("click", () => {
        const modal = button.classList.contains("complete")
          ? document.getElementById("completeModal")
          : document.getElementById("cancelModal");
        modal.querySelector("[data-modal-appointment-id]").value = button.dataset.appointmentId;
        modal.querySelector("[data-modal-client]").textContent = button.dataset.client;
        modal.querySelector("[data-modal-area]").textContent = button.dataset.area;
        modal.showModal();
      });
    });

    document.querySelectorAll("[data-close-modal]").forEach((button) => {
      button.addEventListener("click", () => button.closest("dialog").close());
    });
  </script>
</body>
</html>