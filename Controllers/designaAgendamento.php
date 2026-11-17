<?php

require_once __DIR__ . "/../Config/sessao.php";
exigeSessaoUsuario();

function redirecionaRequisicoes($resultado)
{
    header("Location: ../admin/jurisfree-requisicoes.php?resultado=" . rawurlencode($resultado));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    http_response_code(405);
    exit("Método não permitido.");
}

$csrfEnviado = $_POST["csrf_token"] ?? "";
if (
    !is_string($csrfEnviado)
    || empty($_SESSION["csrf_token"])
    || !hash_equals($_SESSION["csrf_token"], $csrfEnviado)
) {
    redirecionaRequisicoes("csrf");
}

$idSolicitacao = filter_var(
    $_POST["id_solicitacao"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);
$idAdvogado = filter_var(
    $_POST["id_advogado"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);
$dataHora = $_POST["data_hora"] ?? "";
$dataHoraValida = is_string($dataHora)
    && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $dataHora) === 1;
$horario = $dataHoraValida
    ? DateTime::createFromFormat("!Y-m-d\TH:i", $dataHora)
    : false;
$errosData = DateTime::getLastErrors();

if (
    $idSolicitacao === false
    || $idAdvogado === false
    || $horario === false
    || ($errosData !== false && ($errosData["warning_count"] > 0 || $errosData["error_count"] > 0))
    || $horario->format("Y-m-d\TH:i") !== $dataHora
    || $horario->getTimestamp() <= time()
) {
    redirecionaRequisicoes("horario");
}

require_once __DIR__ . "/../Config/conecta.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$transacaoIniciada = false;
try {
    mysqli_begin_transaction($Mysql);
    $transacaoIniciada = true;

    $stmtSolicitacao = $Mysql->prepare(
        "SELECT status
         FROM solicitacao_atendimento
         WHERE id = ?
         FOR UPDATE"
    );
    $stmtSolicitacao->bind_param("i", $idSolicitacao);
    $stmtSolicitacao->execute();
    $solicitacao = $stmtSolicitacao->get_result()->fetch_assoc();
    $stmtSolicitacao->close();

    if (!$solicitacao || !in_array($solicitacao["status"], ["pendente", "em_analise"], true)) {
        throw new DomainException("requisicao");
    }

    $stmtAgendamentoExistente = $Mysql->prepare(
        "SELECT id
         FROM agendamento
         WHERE id_solicitacao = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmtAgendamentoExistente->bind_param("i", $idSolicitacao);
    $stmtAgendamentoExistente->execute();
    $agendamentoExistente = $stmtAgendamentoExistente->get_result()->fetch_assoc();
    $stmtAgendamentoExistente->close();
    if ($agendamentoExistente) {
        throw new DomainException("requisicao");
    }

    $stmtAdvogado = $Mysql->prepare(
        "SELECT advogado.id
         FROM advogado
         INNER JOIN usuario
             ON usuario.id = advogado.id_usuario
         WHERE advogado.id = ?
           AND advogado.ativo = 1
           AND usuario.status = 'ativo'
           AND usuario.funcao = 'advogado'
         FOR UPDATE"
    );
    $stmtAdvogado->bind_param("i", $idAdvogado);
    $stmtAdvogado->execute();
    $advogado = $stmtAdvogado->get_result()->fetch_assoc();
    $stmtAdvogado->close();
    if (!$advogado) {
        throw new DomainException("advogado");
    }

    $dataHoraBanco = $horario->format("Y-m-d H:i:s");
    $stmtConflito = $Mysql->prepare(
        "SELECT id
         FROM agendamento
         WHERE id_advogado = ?
           AND data_hora = ?
           AND status <> 'cancelado'
         LIMIT 1
         FOR UPDATE"
    );
    $stmtConflito->bind_param("is", $idAdvogado, $dataHoraBanco);
    $stmtConflito->execute();
    $conflito = $stmtConflito->get_result()->fetch_assoc();
    $stmtConflito->close();
    if ($conflito) {
        throw new DomainException("conflito");
    }

    $stmtCriarAgendamento = $Mysql->prepare(
        "INSERT INTO agendamento (id_solicitacao, id_advogado, data_hora)
         VALUES (?, ?, ?)"
    );
    $stmtCriarAgendamento->bind_param("iis", $idSolicitacao, $idAdvogado, $dataHoraBanco);
    $stmtCriarAgendamento->execute();
    $stmtCriarAgendamento->close();

    $stmtAtualizarSolicitacao = $Mysql->prepare(
        "UPDATE solicitacao_atendimento
         SET status = 'agendado'
         WHERE id = ?
           AND status IN ('pendente', 'em_analise')"
    );
    $stmtAtualizarSolicitacao->bind_param("i", $idSolicitacao);
    $stmtAtualizarSolicitacao->execute();
    $atualizouSolicitacao = $stmtAtualizarSolicitacao->affected_rows === 1;
    $stmtAtualizarSolicitacao->close();
    if (!$atualizouSolicitacao) {
        throw new DomainException("requisicao");
    }

    mysqli_commit($Mysql);
    $transacaoIniciada = false;
} catch (DomainException $erro) {
    if ($transacaoIniciada) {
        mysqli_rollback($Mysql);
    }
    redirecionaRequisicoes($erro->getMessage());
} catch (mysqli_sql_exception $erro) {
    if ($transacaoIniciada) {
        mysqli_rollback($Mysql);
    }
    error_log("Falha ao designar agendamento para a requisição " . (int) $idSolicitacao . ": " . $erro->getMessage());
    redirecionaRequisicoes("salvar");
}

redirecionaRequisicoes("salvo");

?>
