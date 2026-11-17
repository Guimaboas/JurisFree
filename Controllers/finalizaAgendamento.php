<?php
require_once __DIR__ . "/../Config/sessao.php";
$usuario = exigeSessaoUsuario();

function redirecionaFinalizacao($resultado)
{
    header("Location: ../admin/jurisfree-conclusao.php?resultado=" . rawurlencode($resultado));
    exit;
}

if ($usuario["funcao"] !== "advogado") {
    http_response_code(403);
    exit("Acesso permitido somente a advogados.");
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
    redirecionaFinalizacao("csrf");
}

$idAgendamento = filter_var(
    $_POST["id_agendamento"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);
$acao = $_POST["acao"] ?? "";
$descricao = $_POST["descricao"] ?? "";
$justificativa = $_POST["justificativa"] ?? "";
$comentario = $_POST["comentario"] ?? "";

if (
    $idAgendamento === false
    || !is_string($acao)
    || !in_array($acao, ["concluir", "cancelar"], true)
    || !is_string($descricao)
    || !is_string($justificativa)
    || !is_string($comentario)
) {
    redirecionaFinalizacao("dados");
}

$descricao = trim($descricao);
$justificativa = trim($justificativa);
$comentario = trim($comentario);
if (
    ($acao === "concluir" && ($descricao === "" || strlen($descricao) > 5000))
    || ($acao === "cancelar" && ($justificativa === "" || strlen($justificativa) > 2000 || strlen($comentario) > 2000))
) {
    redirecionaFinalizacao("dados");
}

require_once __DIR__ . "/../Config/conecta.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$transacaoIniciada = false;
try {
    mysqli_begin_transaction($Mysql);
    $transacaoIniciada = true;

    $stmtAgendamento = $Mysql->prepare(
        "SELECT
            ag.id,
            ag.id_solicitacao,
            ag.id_advogado,
            ag.data_hora,
            ag.status
         FROM agendamento AS ag
         INNER JOIN advogado
             ON advogado.id = ag.id_advogado
         WHERE ag.id = ?
           AND advogado.id_usuario = ?
         FOR UPDATE"
    );
    $stmtAgendamento->bind_param("ii", $idAgendamento, $usuario["id"]);
    $stmtAgendamento->execute();
    $agendamento = $stmtAgendamento->get_result()->fetch_assoc();
    $stmtAgendamento->close();

    $dataHoraAgendamento = $agendamento ? strtotime($agendamento["data_hora"]) : false;
    if (
        !$agendamento
        || !in_array($agendamento["status"], ["agendado", "confirmado"], true)
        || $dataHoraAgendamento === false
        || $dataHoraAgendamento > time()
    ) {
        throw new DomainException("indisponivel");
    }

    if ($acao === "concluir") {
        $stmtCaso = $Mysql->prepare(
            "INSERT INTO caso
                (id_solicitacao, id_advogado, status, resumo, data_encerramento)
             VALUES (?, ?, 'concluido', ?, NOW())"
        );
        $stmtCaso->bind_param("iis", $agendamento["id_solicitacao"], $agendamento["id_advogado"], $descricao);
        $stmtCaso->execute();
        $stmtCaso->close();

        $statusAgendamento = "realizado";
        $statusSolicitacao = "concluido";
    } else {
        $observacao = "Justificativa do cancelamento: " . $justificativa;
        if ($comentario !== "") {
            $observacao .= "\nComentário: " . $comentario;
        }

        $stmtObservacao = $Mysql->prepare(
            "UPDATE agendamento
             SET observacao = ?
             WHERE id = ?"
        );
        $stmtObservacao->bind_param("si", $observacao, $idAgendamento);
        $stmtObservacao->execute();
        $stmtObservacao->close();

        $statusAgendamento = "cancelado";
        $statusSolicitacao = "cancelado";
    }

    $stmtAtualizaAgendamento = $Mysql->prepare(
        "UPDATE agendamento
         SET status = ?
         WHERE id = ?
           AND status IN ('agendado', 'confirmado')"
    );
    $stmtAtualizaAgendamento->bind_param("si", $statusAgendamento, $idAgendamento);
    $stmtAtualizaAgendamento->execute();
    $agendamentoAtualizado = $stmtAtualizaAgendamento->affected_rows === 1;
    $stmtAtualizaAgendamento->close();
    if (!$agendamentoAtualizado) {
        throw new DomainException("indisponivel");
    }

    $stmtAtualizaSolicitacao = $Mysql->prepare(
        "UPDATE solicitacao_atendimento
         SET status = ?
         WHERE id = ?
           AND status = 'agendado'"
    );
    $stmtAtualizaSolicitacao->bind_param("si", $statusSolicitacao, $agendamento["id_solicitacao"]);
    $stmtAtualizaSolicitacao->execute();
    $solicitacaoAtualizada = $stmtAtualizaSolicitacao->affected_rows === 1;
    $stmtAtualizaSolicitacao->close();
    if (!$solicitacaoAtualizada) {
        throw new DomainException("indisponivel");
    }

    mysqli_commit($Mysql);
    $transacaoIniciada = false;
} catch (DomainException $erro) {
    if ($transacaoIniciada) {
        mysqli_rollback($Mysql);
    }
    redirecionaFinalizacao($erro->getMessage());
} catch (mysqli_sql_exception $erro) {
    if ($transacaoIniciada) {
        mysqli_rollback($Mysql);
    }
    error_log("Falha ao finalizar agendamento " . (int) $idAgendamento . ": " . $erro->getMessage());
    redirecionaFinalizacao("salvar");
}

redirecionaFinalizacao($acao === "concluir" ? "concluido" : "cancelado");
?>
