<?php
require_once __DIR__ . "/../Config/sessao.php";
$usuarioPainel = exigeSessaoUsuario();
require_once __DIR__ . "/../Config/conecta.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$nomePainel = htmlspecialchars($usuarioPainel["nome"], ENT_QUOTES, "UTF-8");
$iniciaisPainel = htmlspecialchars(iniciaisUsuario($usuarioPainel["nome"]), ENT_QUOTES, "UTF-8");
$funcaoPainel = htmlspecialchars(rotuloFuncaoUsuario($usuarioPainel["funcao"]), ENT_QUOTES, "UTF-8");
$escape = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
$periodos = [
    "manha" => "Manhã",
    "tarde" => "Tarde",
    "noite" => "Noite"
];
$statusSolicitacao = [
    "pendente" => "Pendente",
    "em_analise" => "Em análise"
];
$resultadoMensagem = $_GET["resultado"] ?? "";
$mensagens = [
    "salvo" => ["Agendamento criado e código enviado ao cliente por email.", false],
    "salvo_email" => ["Agendamento criado, mas não foi possível enviar o email com o código. Verifique a configuração SMTP.", true],
    "horario" => ["Informe uma data e horário futuros válidos.", true],
    "advogado" => ["Selecione um advogado ativo.", true],
    "requisicao" => ["Esta requisição já foi atendida ou não está mais disponível.", true],
    "conflito" => ["Este advogado já possui um agendamento nesse horário.", true],
    "csrf" => ["Não foi possível validar o formulário. Atualize a página e tente novamente.", true],
    "salvar" => ["Não foi possível criar o agendamento. Tente novamente mais tarde.", true]
];
$mensagem = is_string($resultadoMensagem) && isset($mensagens[$resultadoMensagem])
    ? $mensagens[$resultadoMensagem]
    : null;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

$solicitacoes = [];
$advogados = [];
$erroConsulta = false;
try {
    $resultadoSolicitacoes = $Mysql->query(
        "SELECT
            sa.id,
            sa.status,
            sa.descricao,
            sa.data_desejada,
            sa.periodo_desejado,
            sa.data_solicitacao,
            cliente.nome AS cliente,
            area_juridica.nome AS area
        FROM solicitacao_atendimento AS sa
        INNER JOIN cliente
            ON cliente.id = sa.id_cliente
        INNER JOIN area_juridica
            ON area_juridica.id = sa.id_area_juridica
        WHERE sa.status IN ('pendente', 'em_analise')
          AND NOT EXISTS (
              SELECT 1
              FROM agendamento
              WHERE agendamento.id_solicitacao = sa.id
          )
        ORDER BY
            (sa.data_desejada IS NULL) ASC,
            sa.data_desejada ASC,
            CASE sa.periodo_desejado
                WHEN 'manha' THEN 1
                WHEN 'tarde' THEN 2
                WHEN 'noite' THEN 3
                ELSE 4
            END ASC,
            sa.data_solicitacao ASC,
            sa.id ASC"
    );
    while ($solicitacao = $resultadoSolicitacoes->fetch_assoc()) {
        $solicitacoes[] = $solicitacao;
    }

    $resultadoAdvogados = $Mysql->query(
        "SELECT advogado.id, usuario.nome
         FROM advogado
         INNER JOIN usuario
             ON usuario.id = advogado.id_usuario
         WHERE advogado.ativo = 1
           AND usuario.status = 'ativo'
           AND usuario.funcao = 'advogado'
         ORDER BY usuario.nome ASC"
    );
    while ($advogado = $resultadoAdvogados->fetch_assoc()) {
        $advogados[] = $advogado;
    }
} catch (mysqli_sql_exception $erro) {
    error_log("Falha ao carregar requisições administrativas: " . $erro->getMessage());
    http_response_code(500);
    $erroConsulta = true;
}

$totalPendentes = 0;
$totalAnalise = 0;
foreach ($solicitacoes as $solicitacao) {
    if ($solicitacao["status"] === "pendente") {
        $totalPendentes++;
    } else {
        $totalAnalise++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Requisições — JurisFree</title>
  <meta name="description" content="Organize as requisições de atendimento e transforme-as em agendamentos.">
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
          <small><?php echo $funcaoPainel; ?></small>
        </div>
      </div>
    </div>
  </header>

  <div class="dashboard-layout">
    <aside class="dashboard-sidebar" aria-label="Navegação administrativa">
      <nav class="dashboard-nav">
        <a href="jurisfree-dashboard.php">
          <span aria-hidden="true">▦</span> Visão geral
        </a>
        <a href="jurisfree-agendamentos.php">
          <span aria-hidden="true">▣</span> Agendamentos
        </a>
        <a class="active" href="jurisfree-requisicoes.php" aria-current="page">
          <span aria-hidden="true">☷</span> Requisições
        </a>
        <?php if ($usuarioPainel["funcao"] === "advogado"): ?>
          <a href="jurisfree-conclusao.php">
            <span aria-hidden="true">✓</span> Meus agendamentos
          </a>
        <?php endif; ?>
      </nav>
      <a class="dashboard-back" href="../Views/jurisfree-landing.html">← Voltar ao site</a>
    </aside>

    <main class="dashboard-main">
      <section class="dashboard-welcome">
        <div>
          <span class="dashboard-eyebrow">Painel administrativo</span>
          <h1>Requisições</h1>
          <p>Analise os pedidos e defina um advogado e horário para confirmar o atendimento.</p>
        </div>
      </section>

      <?php if ($mensagem): ?>
        <p class="requests-alert<?php echo $mensagem[1] ? " error" : ""; ?>" role="status">
          <?php echo $escape($mensagem[0]); ?>
        </p>
      <?php endif; ?>
      <?php if ($erroConsulta): ?>
        <p class="requests-alert error" role="alert">Não foi possível carregar as requisições do banco de dados.</p>
      <?php elseif (!$advogados): ?>
        <p class="requests-alert" role="status">Cadastre ao menos um advogado ativo para transformar requisições em agendamentos.</p>
      <?php endif; ?>

      <section class="dashboard-summary" aria-label="Resumo das requisições">
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">☷</span>
          <div><strong><?php echo count($solicitacoes); ?></strong><span>Requisições abertas</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon green" aria-hidden="true">◷</span>
          <div><strong><?php echo $totalPendentes; ?></strong><span>Pendentes</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">✓</span>
          <div><strong><?php echo $totalAnalise; ?></strong><span>Em análise</span></div>
        </article>
      </section>

      <section class="dashboard-card appointments-card" aria-label="Lista de requisições">
        <div class="appointments-toolbar">
          <div>
            <span class="dashboard-eyebrow">Atendimentos</span>
            <h2>Requisições abertas</h2>
          </div>
          <div class="appointments-filters">
            <label class="sr-only" for="requestSearch">Buscar requisição</label>
            <input id="requestSearch" type="search" placeholder="Buscar cliente, área ou descrição">
            <label class="sr-only" for="requestStatus">Filtrar por status</label>
            <select id="requestStatus">
              <option value="">Todos os status</option>
              <option value="pendente">Pendente</option>
              <option value="em_analise">Em análise</option>
            </select>
          </div>
        </div>

        <div class="appointments-table-wrap">
          <table class="appointments-table">
            <thead>
              <tr>
                <th scope="col">Data / período desejado</th>
                <th scope="col">Cliente</th>
                <th scope="col">Área jurídica</th>
                <th scope="col">Descrição</th>
                <th scope="col">Status</th>
                <th scope="col">Ação</th>
              </tr>
            </thead>
            <tbody id="requestRows">
              <?php foreach ($solicitacoes as $solicitacao): ?>
                <?php
                  $dataDesejada = $solicitacao["data_desejada"]
                      ? date("d/m/Y", strtotime($solicitacao["data_desejada"]))
                      : "Sem preferência de data";
                  $periodoDesejado = $periodos[$solicitacao["periodo_desejado"] ?? ""] ?? "Sem preferência de período";
                  $textoStatus = $statusSolicitacao[$solicitacao["status"]] ?? $solicitacao["status"];
                ?>
                <tr data-status="<?php echo $escape($solicitacao["status"]); ?>">
                  <td>
                    <strong><?php echo $escape($dataDesejada); ?></strong>
                    <small><?php echo $escape($periodoDesejado); ?></small>
                  </td>
                  <td><?php echo $escape($solicitacao["cliente"]); ?></td>
                  <td><?php echo $escape($solicitacao["area"]); ?></td>
                  <td class="requests-description"><?php echo $escape($solicitacao["descricao"]); ?></td>
                  <td><span class="request-status"><?php echo $escape($textoStatus); ?></span></td>
                  <td>
                    <button
                      type="button"
                      class="request-action"
                      data-request-id="<?php echo (int) $solicitacao["id"]; ?>"
                      data-client="<?php echo $escape($solicitacao["cliente"]); ?>"
                      data-area="<?php echo $escape($solicitacao["area"]); ?>"
                      data-preference="<?php echo $escape($dataDesejada . " · " . $periodoDesejado); ?>"
                      <?php echo !$advogados || $erroConsulta ? "disabled" : ""; ?>
                    >Designar horário</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="appointments-empty" id="requestsFilterEmpty" hidden>Nenhuma requisição encontrada para os filtros escolhidos.</p>
          <?php if (!$erroConsulta && !$solicitacoes): ?>
            <p class="requests-empty">Não há requisições abertas no momento.</p>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>

  <dialog class="schedule-modal" id="scheduleModal" aria-labelledby="scheduleModalTitle">
    <form class="schedule-modal-content" id="scheduleForm" action="../Controllers/designaAgendamento.php" method="post">
      <div class="schedule-modal-heading">
        <div>
          <span class="dashboard-eyebrow">Nova designação</span>
          <h2 id="scheduleModalTitle">Transformar em agendamento</h2>
        </div>
        <button class="schedule-modal-close" type="button" id="closeScheduleModal" aria-label="Fechar">×</button>
      </div>
      <div class="schedule-request-summary" aria-live="polite">
        <strong id="scheduleClient"></strong>
        <p id="scheduleArea"></p>
        <p id="schedulePreference"></p>
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
      <input type="hidden" name="id_solicitacao" id="scheduleRequestId">
      <div class="schedule-field">
        <label for="scheduleLawyer">Advogado</label>
        <select id="scheduleLawyer" name="id_advogado" required>
          <option value="">Selecione um advogado</option>
          <?php foreach ($advogados as $advogado): ?>
            <option value="<?php echo (int) $advogado["id"]; ?>"><?php echo $escape($advogado["nome"]); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="schedule-field">
        <label for="scheduleDateTime">Data e horário</label>
        <input id="scheduleDateTime" type="datetime-local" name="data_hora" required>
      </div>
      <div class="schedule-modal-actions">
        <button class="schedule-cancel" type="button" id="cancelSchedule">Cancelar</button>
        <button class="request-action" type="submit">Criar agendamento</button>
      </div>
    </form>
  </dialog>

  <script>
    const requestSearch = document.getElementById("requestSearch");
    const requestStatus = document.getElementById("requestStatus");
    const requestRows = Array.from(document.querySelectorAll("#requestRows tr"));
    const requestsFilterEmpty = document.getElementById("requestsFilterEmpty");

    function filterRequests() {
      const searchTerm = requestSearch.value.trim().toLocaleLowerCase("pt-BR");
      const selectedStatus = requestStatus.value;
      let visibleRows = 0;

      requestRows.forEach((row) => {
        const matchesSearch = row.textContent.toLocaleLowerCase("pt-BR").includes(searchTerm);
        const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
        const isVisible = matchesSearch && matchesStatus;
        row.hidden = !isVisible;
        if (isVisible) visibleRows += 1;
      });

      requestsFilterEmpty.hidden = visibleRows > 0 || requestRows.length === 0;
    }

    requestSearch.addEventListener("input", filterRequests);
    requestStatus.addEventListener("change", filterRequests);

    const scheduleModal = document.getElementById("scheduleModal");
    const scheduleRequestId = document.getElementById("scheduleRequestId");
    const scheduleClient = document.getElementById("scheduleClient");
    const scheduleArea = document.getElementById("scheduleArea");
    const schedulePreference = document.getElementById("schedulePreference");
    const scheduleDateTime = document.getElementById("scheduleDateTime");
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    scheduleDateTime.min = now.toISOString().slice(0, 16);

    document.querySelectorAll(".request-action[data-request-id]").forEach((button) => {
      button.addEventListener("click", () => {
        scheduleRequestId.value = button.dataset.requestId;
        scheduleClient.textContent = button.dataset.client;
        scheduleArea.textContent = button.dataset.area;
        schedulePreference.textContent = "Preferência: " + button.dataset.preference;
        scheduleModal.showModal();
      });
    });

    document.getElementById("closeScheduleModal").addEventListener("click", () => scheduleModal.close());
    document.getElementById("cancelSchedule").addEventListener("click", () => scheduleModal.close());
  </script>
</body>
</html>
