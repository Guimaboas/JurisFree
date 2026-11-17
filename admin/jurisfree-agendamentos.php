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
$rotulosStatus = [
    "agendado" => ["Agendado", "scheduled"],
    "confirmado" => ["Confirmado", "confirmed"],
    "realizado" => ["Realizado", "completed"],
    "cancelado" => ["Cancelado", "cancelled"],
    "nao_compareceu" => ["Não compareceu", "absent"]
];
$agendamentos = [];
$resumo = ["total" => 0, "futuros" => 0, "realizados" => 0];
$erroConsulta = false;

try {
    $resultadoResumo = $Mysql->query(
        "SELECT
            COUNT(*) AS total,
            COALESCE(SUM(data_hora >= NOW() AND status IN ('agendado', 'confirmado')), 0) AS futuros,
            COALESCE(SUM(status = 'realizado'), 0) AS realizados
         FROM agendamento"
    );
    $resumo = $resultadoResumo->fetch_assoc();

    $resultadoAgendamentos = $Mysql->query(
        "SELECT
            ag.data_hora,
            ag.status,
            cliente.nome AS cliente,
            area_juridica.nome AS area,
            usuario.nome AS advogado
         FROM agendamento AS ag
         INNER JOIN solicitacao_atendimento AS sa
             ON sa.id = ag.id_solicitacao
         INNER JOIN cliente
             ON cliente.id = sa.id_cliente
         INNER JOIN area_juridica
             ON area_juridica.id = sa.id_area_juridica
         INNER JOIN advogado
             ON advogado.id = ag.id_advogado
         INNER JOIN usuario
             ON usuario.id = advogado.id_usuario
         ORDER BY ag.data_hora ASC, ag.id ASC"
    );
    while ($agendamento = $resultadoAgendamentos->fetch_assoc()) {
        $agendamentos[] = $agendamento;
    }
} catch (mysqli_sql_exception $erro) {
    error_log("Falha ao carregar agendamentos administrativos: " . $erro->getMessage());
    http_response_code(500);
    $erroConsulta = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agendamentos — JurisFree</title>
  <meta name="description" content="Acompanhe os agendamentos de atendimentos jurídicos do JurisFree.">
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
        <a class="active" href="jurisfree-agendamentos.php" aria-current="page">
          <span aria-hidden="true">▣</span> Agendamentos
        </a>
        <a href="jurisfree-requisicoes.php">
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
          <h1>Agendamentos</h1>
          <p>Consulte os atendimentos, clientes, áreas jurídicas e advogados responsáveis.</p>
        </div>
        <a href="../Views/jurisfree-agendamento.html" class="btn btn-primary dashboard-new-btn">+ Novo agendamento</a>
      </section>

      <?php if ($erroConsulta): ?>
        <p class="requests-alert error" role="alert">Não foi possível carregar os agendamentos do banco de dados.</p>
      <?php else: ?>
      <section class="dashboard-summary" aria-label="Resumo dos agendamentos demonstrativos">
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">▣</span>
          <div><strong><?php echo (int) $resumo["total"]; ?></strong><span>Agendamentos na lista</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon green" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">◷</span>
          <div><strong><?php echo (int) $resumo["futuros"]; ?></strong><span>Atendimentos futuros</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">✓</span>
          <div><strong><?php echo (int) $resumo["realizados"]; ?></strong><span>Atendimento realizado</span></div>
        </article>
      </section>
      <?php endif; ?>

      <section class="dashboard-card appointments-card" aria-label="Lista de agendamentos">
        <div class="appointments-toolbar">
          <div>
            <span class="dashboard-eyebrow">Atendimentos</span>
            <h2>Todos os agendamentos</h2>
          </div>
          <div class="appointments-filters">
            <label class="sr-only" for="appointmentSearch">Buscar agendamento</label>
            <input id="appointmentSearch" type="search" placeholder="Buscar cliente, área ou advogado">
            <label class="sr-only" for="appointmentStatus">Filtrar por status</label>
            <select id="appointmentStatus">
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
                <th scope="col">Advogado</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody id="appointmentRows">
              <?php foreach ($agendamentos as $agendamento): ?>
                <?php
                  $status = $rotulosStatus[$agendamento["status"]]
                      ?? [$agendamento["status"], "scheduled"];
                  $dataHora = strtotime($agendamento["data_hora"]);
                ?>
                <tr data-status="<?php echo $escape($agendamento["status"]); ?>">
                  <td>
                    <strong><?php echo date("d/m/Y", $dataHora); ?></strong>
                    <small><?php echo date("H:i", $dataHora); ?></small>
                  </td>
                  <td><?php echo $escape($agendamento["cliente"]); ?></td>
                  <td><?php echo $escape($agendamento["area"]); ?></td>
                  <td><?php echo $escape($agendamento["advogado"]); ?></td>
                  <td><span class="appointment-status <?php echo $escape($status[1]); ?>"><?php echo $escape($status[0]); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="appointments-empty" id="appointmentsEmpty" <?php echo $agendamentos ? "hidden" : ""; ?>><?php echo $erroConsulta ? "Os agendamentos não estão disponíveis no momento." : "Nenhum agendamento cadastrado."; ?></p>
        </div>
      </section>
    </main>
  </div>

  <script>
    const appointmentSearch = document.getElementById("appointmentSearch");
    const appointmentStatus = document.getElementById("appointmentStatus");
    const appointmentRows = Array.from(document.querySelectorAll("#appointmentRows tr"));
    const appointmentsEmpty = document.getElementById("appointmentsEmpty");

    function filterAppointments() {
      const searchTerm = appointmentSearch.value.trim().toLocaleLowerCase("pt-BR");
      const selectedStatus = appointmentStatus.value;
      let visibleRows = 0;

      appointmentRows.forEach((row) => {
        const matchesSearch = row.textContent.toLocaleLowerCase("pt-BR").includes(searchTerm);
        const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
        const isVisible = matchesSearch && matchesStatus;
        row.hidden = !isVisible;
        if (isVisible) visibleRows += 1;
      });

      appointmentsEmpty.hidden = visibleRows > 0;
    }

    appointmentSearch.addEventListener("input", filterAppointments);
    appointmentStatus.addEventListener("change", filterAppointments);
  </script>
</body>
</html>
