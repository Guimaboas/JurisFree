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
  <title>Painel do Administrador — JurisFree</title>
  <meta name="description" content="Painel administrativo do JurisFree para acompanhar atendimentos e próximos agendamentos.">
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
        <a class="active" href="jurisfree-dashboard.php" aria-current="page">
          <span aria-hidden="true">▦</span> Visão geral
        </a>
        <a href="jurisfree-admin.html">
          <span aria-hidden="true">♙</span> Administração
        </a>
        <a href="jurisfree-agendamentos.php">
          <span aria-hidden="true">▣</span> Agendamentos
        </a>
      </nav>
      <a class="dashboard-back" href="../Views/jurisfree-landing.html">← Voltar ao site</a>
    </aside>

    <main class="dashboard-main">
      <section class="dashboard-welcome">
        <div>
          <span class="dashboard-eyebrow">Painel administrativo</span>
          <h1>Olá, <?php echo $nomePainel; ?></h1>
          <p>Acompanhe os atendimentos e organize os próximos encontros jurídicos.</p>
        </div>
        <a href="../Views/jurisfree-agendamento.html" class="btn btn-primary dashboard-new-btn">+ Novo agendamento</a>
      </section>

      <section class="dashboard-summary" aria-label="Resumo dos atendimentos">
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">✓</span>
          <div><strong>12</strong><span>Atendidos este mês</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon green" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">◷</span>
          <div><strong>8</strong><span>Consultas pendentes</span></div>
        </article>
        <article class="summary-card">
          <span class="summary-icon blue" aria-hidden="true" style="font-size: 2em; display: flex; justify-content: center; align-items: center">♙</span>
          <div><strong>5</strong><span>Advogados ativos</span></div>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card progress-card">
          <div class="dashboard-card-heading">
            <div>
              <span class="dashboard-eyebrow">Meta mensal</span>
              <h2>Atendimentos concluídos</h2>
            </div>
            <button class="icon-button" id="toggleProgressEditor" type="button" aria-expanded="false" aria-controls="progressEditor" title="Editar gráfico">✎</button>
          </div>

          <div class="progress-chart-wrap">
            <div class="progress-chart" id="progressChart" role="img" aria-label="75 por cento da meta concluída">
              <div class="progress-chart-center">
                <strong id="progressValue">75%</strong>
                <span>concluído</span>
              </div>
            </div>
            <div class="progress-details">
              <div><span class="legend-dot blue-dot"></span><strong id="completedCount">15</strong><span>concluídos</span></div>
              <div><span class="legend-dot pale-dot"></span><strong id="remainingCount">5</strong><span>restantes</span></div>
            </div>
          </div>

          <form class="progress-editor" id="progressEditor" hidden>
            <label for="progressInput">Percentual concluído</label>
            <div class="progress-input-row">
              <input id="progressInput" type="range" min="0" max="100" value="75">
              <output id="progressOutput" for="progressInput">75%</output>
            </div>
          </form>
        </article>

        <article class="dashboard-card agenda-card">
          <div class="dashboard-card-heading">
            <div>
              <span class="dashboard-eyebrow">Próximos encontros</span>
              <h2>Agenda</h2>
            </div>
            <a href="jurisfree-agendamentos.php" class="card-link">Ver tudo →</a>
          </div>

          <div class="agenda-list">
            <article class="agenda-item">
              <time datetime="2026-11-09T09:30">
                <strong>09</strong><span>Nov</span>
              </time>
              <div class="agenda-time">09:30</div>
              <div class="agenda-person">
                <strong>Maria da Silva</strong>
                <span>Direito Previdenciário</span>
              </div>
              <span class="agenda-status confirmed">Confirmado</span>
            </article>
            <article class="agenda-item">
              <time datetime="2026-11-10T14:00">
                <strong>10</strong><span>Nov</span>
              </time>
              <div class="agenda-time">14:00</div>
              <div class="agenda-person">
                <strong>João Pereira</strong>
                <span>Direito do Consumidor</span>
              </div>
              <span class="agenda-status pending">Pendente</span>
            </article>
            <article class="agenda-item">
              <time datetime="2026-11-12T10:15">
                <strong>12</strong><span>Nov</span>
              </time>
              <div class="agenda-time">10:15</div>
              <div class="agenda-person">
                <strong>Ana Oliveira</strong>
                <span>Direito Previdenciário</span>
              </div>
              <span class="agenda-status confirmed">Confirmado</span>
            </article>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script>
    const progressInput = document.getElementById("progressInput");
    const progressOutput = document.getElementById("progressOutput");
    const progressValue = document.getElementById("progressValue");
    const progressChart = document.getElementById("progressChart");
    const completedCount = document.getElementById("completedCount");
    const remainingCount = document.getElementById("remainingCount");
    const editorToggle = document.getElementById("toggleProgressEditor");
    const progressEditor = document.getElementById("progressEditor");

    function updateProgress(value) {
      const percentage = Math.max(0, Math.min(100, Number(value)));
      progressChart.style.setProperty("--progress", percentage + "%");
      progressValue.textContent = percentage + "%";
      progressOutput.textContent = percentage + "%";
      progressChart.setAttribute("aria-label", percentage + " por cento da meta concluída");
      const total = 20;
      completedCount.textContent = Math.round(total * percentage / 100);
      remainingCount.textContent = total - Math.round(total * percentage / 100);
    }

    progressInput.addEventListener("input", (event) => updateProgress(event.target.value));

    editorToggle.addEventListener("click", () => {
      const isOpen = !progressEditor.hidden;
      progressEditor.hidden = isOpen;
      editorToggle.setAttribute("aria-expanded", String(!isOpen));
    });

    updateProgress(progressInput.value);
  </script>
</body>
</html>