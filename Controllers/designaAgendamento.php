<?php

require_once __DIR__ . "/../Config/sessao.php";
require_once __DIR__ . "/../Models/codigo_acesso_agendamento.php";
require_once __DIR__ . "/../Services/email.php";
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
        "SELECT
            solicitacao_atendimento.status,
            solicitacao_atendimento.id_cliente,
            cliente.nome AS cliente_nome,
            cliente.email AS cliente_email
         FROM solicitacao_atendimento
         INNER JOIN cliente
             ON cliente.id = solicitacao_atendimento.id_cliente
         WHERE solicitacao_atendimento.id = ?
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

    $codigoAcesso = null;
    $codigoExistente = selecionaCodigoAcessoAgendamento(
        $Mysql,
        (int) $solicitacao["id_cliente"]
    );
    if (!$codigoExistente) {
        $codigoGerado = str_pad(
            (string) random_int(0, 999999999999),
            12,
            "0",
            STR_PAD_LEFT
        );
        if (salvaCodigoAcessoAgendamento(
            $Mysql,
            (int) $solicitacao["id_cliente"],
            password_hash($codigoGerado, PASSWORD_DEFAULT)
        )) {
            $codigoAcesso = $codigoGerado;
        }
    }

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

$resultadoEmail = "salvo";
try {
    $mensagemEmail = "Olá, " . $solicitacao["cliente_nome"] . "!\r\n\r\n"
        . "Seu agendamento foi criado. Para consultar a situação atual das suas solicitações, "
        . "acesse a página \"Verificar meu agendamento\" no site JurisFree e informe seu CPF ";
    if ($codigoAcesso !== null) {
        $mensagemEmail .= "e este código de acesso permanente:\r\n\r\n"
            . $codigoAcesso . "\r\n\r\n"
            . "Guarde este código: ele será o mesmo para todos os seus agendamentos.\r\n\r\n";
    } else {
        $mensagemEmail .= "e o código de acesso permanente enviado no seu primeiro agendamento. "
            . "Esse código não foi alterado.\r\n\r\n";
    }
    $mensagemEmail .= "Atenciosamente,\r\nEquipe JurisFree";

    enviarEmailJurisFree(
        $solicitacao["cliente_email"],
        $solicitacao["cliente_nome"],
        "Agendamento criado - JurisFree",
        $mensagemEmail
    );
} catch (Throwable $erro) {
    error_log("Falha ao enviar código de acesso do cliente " . (int) $solicitacao["id_cliente"] . ": " . $erro->getMessage());
    $resultadoEmail = "salvo_email";
}

redirecionaRequisicoes($resultadoEmail);

?>
