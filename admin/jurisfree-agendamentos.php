<?php
require_once __DIR__ . "/../Config/sessao.php";
$usuarioPainel = exigeSessaoUsuario();
$nomePainel = htmlspecialchars($usuarioPainel["nome"], ENT_QUOTES, "UTF-8");
$iniciaisPainel = htmlspecialchars(iniciaisUsuario($usuarioPainel["nome"]), ENT_QUOTES, "UTF-8");
$funcaoPainel = htmlspecialchars(rotuloFuncaoUsuario($usuarioPainel["funcao"]), ENT_QUOTES, "UTF-8");
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
        <a href="jurisfree-admin.html">
          <span aria-hidden="true">♙</span> Administração
        </a>
        <a class="active" href="jurisfree-agendamentos.php" aria-current="page">
          <span aria-hidden="true">▣</span> Agendamentos
        </a>
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

      <section class="dashboard-summary" aria-label="Resumo dos agendamentos demonstrativos">
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true">▣</span>
          <div><strong>7</strong><span>Agendamentos na lista</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon green" aria-hidden="true">◷</span>
          <div><strong>4</strong><span>Atendimentos futuros</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true">✓</span>
          <div><strong>1</strong><span>Atendimento realizado</span></div>
        </article>
      </section>

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
              <tr data-status="confirmado">
                <td><strong>16 nov. 2026</strong><small>09:00</small></td>
                <td>Maria da Silva</td>
                <td>Direito Previdenciário</td>
                <td>Dra. Camila Nunes</td>
                <td><span class="appointment-status confirmed">Confirmado</span></td>
              </tr>
              <tr data-status="agendado">
                <td><strong>17 nov. 2026</strong><small>14:00</small></td>
                <td>João Pereira</td>
                <td>Direito do Consumidor</td>
                <td>Dr. Felipe Martins</td>
                <td><span class="appointment-status scheduled">Agendado</span></td>
              </tr>
              <tr data-status="agendado">
                <td><strong>18 nov. 2026</strong><small>10:00</small></td>
                <td>Ana Oliveira</td>
                <td>Direito Previdenciário</td>
                <td>Dra. Camila Nunes</td>
                <td><span class="appointment-status scheduled">Agendado</span></td>
              </tr>
              <tr data-status="confirmado">
                <td><strong>19 nov. 2026</strong><small>11:30</small></td>
                <td>Pedro Santos</td>
                <td>Direito do Consumidor</td>
                <td>Dra. Luiza Costa</td>
                <td><span class="appointment-status confirmed">Confirmado</span></td>
              </tr>
              <tr data-status="realizado">
                <td><strong>12 nov. 2026</strong><small>15:00</small></td>
                <td>Beatriz Costa</td>
                <td>Direito Previdenciário</td>
                <td>Dr. Felipe Martins</td>
                <td><span class="appointment-status completed">Realizado</span></td>
              </tr>
              <tr data-status="cancelado">
                <td><strong>10 nov. 2026</strong><small>09:30</small></td>
                <td>Carlos Souza</td>
                <td>Direito do Consumidor</td>
                <td>Dra. Luiza Costa</td>
                <td><span class="appointment-status cancelled">Cancelado</span></td>
              </tr>
              <tr data-status="nao_compareceu">
                <td><strong>09 nov. 2026</strong><small>10:30</small></td>
                <td>Marina Rocha</td>
                <td>Direito Previdenciário</td>
                <td>Dra. Camila Nunes</td>
                <td><span class="appointment-status absent">Não compareceu</span></td>
              </tr>
            </tbody>
          </table>
          <p class="appointments-empty" id="appointmentsEmpty" hidden>Nenhum agendamento encontrado.</p>
        </div>
      </section>
      <p class="appointments-note">Os registros exibidos são demonstrativos. A estrutura segue as tabelas de clientes, solicitações, áreas jurídicas, advogados e agendamentos do banco; a integração com o banco será adicionada posteriormente.</p>
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
