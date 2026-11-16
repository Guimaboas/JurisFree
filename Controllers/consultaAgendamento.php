<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");
header("Pragma: no-cache");
session_start([
    "cookie_httponly" => true,
    "cookie_samesite" => "Strict",
    "cookie_secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
]);

function respondeJson($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    respondeJson(405, ["ok" => false, "mensagem" => "Método não permitido."]);
}

$origem = $_SERVER["HTTP_ORIGIN"] ?? "";
$host = $_SERVER["HTTP_HOST"] ?? "";
if ($origem !== "" && strtolower((string) parse_url($origem, PHP_URL_HOST)) !== strtolower(explode(":", $host)[0])) {
    respondeJson(403, ["ok" => false, "mensagem" => "Solicitação não permitida."]);
}

$tipoConteudo = strtolower(trim(explode(";", $_SERVER["CONTENT_TYPE"] ?? "")[0]));
if ($tipoConteudo !== "application/json") {
    respondeJson(415, ["ok" => false, "mensagem" => "Formato de solicitação inválido."]);
}

$entrada = file_get_contents("php://input");
if ($entrada === false || strlen($entrada) > 4096) {
    respondeJson(400, ["ok" => false, "mensagem" => "Solicitação inválida."]);
}

$dados = json_decode($entrada, true);
if (!is_array($dados)) {
    respondeJson(400, ["ok" => false, "mensagem" => "Solicitação inválida."]);
}

require_once __DIR__ . "/../Config/conecta.php";
require_once __DIR__ . "/../Models/consulta_agendamento.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$acao = $dados["acao"] ?? "";

if ($acao === "encerrar_consulta") {
    unset($_SESSION["consulta_cliente_id"], $_SESSION["consulta_verificada_em"]);
    respondeJson(200, ["ok" => true]);
}

function cpfConsultaValido($cpf)
{
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($digito = 9; $digito <= 10; $digito++) {
        $soma = 0;
        for ($indice = 0; $indice < $digito; $indice++) {
            $soma += (int) $cpf[$indice] * (($digito + 1) - $indice);
        }
        $resultado = ($soma * 10) % 11;
        if ($resultado === 10) {
            $resultado = 0;
        }
        if ($resultado !== (int) $cpf[$digito]) {
            return false;
        }
    }

    return true;
}

function respostaConsultaGenerica()
{
    return [
        "ok" => true,
        "mensagem" => "Se o CPF estiver cadastrado, enviaremos um código ao e-mail associado."
    ];
}

try {
    if ($acao === "solicitar_codigo") {
        $cpf = preg_replace('/\D/', '', trim((string) ($dados["cpf"] ?? "")));
        if (!cpfConsultaValido($cpf)) {
            respondeJson(400, ["ok" => false, "mensagem" => "Informe um CPF válido."]);
        }

        $remetente = getenv("JURISFREE_MAIL_FROM");
        if (!$remetente || !filter_var($remetente, FILTER_VALIDATE_EMAIL) || !function_exists("mail")) {
            error_log("Consulta de agendamento indisponível: configure JURISFREE_MAIL_FROM e o envio de e-mail do PHP.");
            respondeJson(503, [
                "ok" => false,
                "mensagem" => "A consulta por e-mail está temporariamente indisponível. Tente novamente mais tarde."
            ]);
        }

        $cliente = consultaClientePorCpf($Mysql, $cpf);
        if (!$cliente || !filter_var($cliente["email"], FILTER_VALIDATE_EMAIL)) {
            unset($_SESSION["consulta_cliente_id"], $_SESSION["consulta_verificada_em"]);
            respondeJson(202, respostaConsultaGenerica());
        }

        $idCliente = (int) $cliente["id"];
        $ultimoCodigo = selecionaUltimoCodigoConsulta($Mysql, $idCliente);
        $agora = time();
        if (
            contaCodigosConsultaRecentes($Mysql, $idCliente) >= 3
            || ($ultimoCodigo && $agora - strtotime($ultimoCodigo["criado_em"]) < 60)
        ) {
            unset($_SESSION["consulta_cliente_id"], $_SESSION["consulta_verificada_em"]);
            respondeJson(202, respostaConsultaGenerica());
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
        invalidaCodigosConsultaAtivos($Mysql, $idCliente);
        $idCodigo = criaCodigoConsulta($Mysql, $idCliente, password_hash($codigo, PASSWORD_DEFAULT));
        $assunto = "Código para consultar seus agendamentos - JurisFree";
        $mensagem = "Seu código de verificação é: " . $codigo . "\r\n\r\n"
            . "Ele expira em 10 minutos e pode ser usado uma única vez. "
            . "Se você não solicitou esta consulta, ignore este e-mail.";
        $cabecalhos = "From: JurisFree <" . $remetente . ">\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "X-Mailer: PHP/" . phpversion();

        if (!mail($cliente["email"], $assunto, $mensagem, $cabecalhos)) {
            invalidaCodigoConsulta($Mysql, $idCodigo);
            error_log("Falha ao enviar código de consulta para o cliente ID " . $idCliente);
            unset($_SESSION["consulta_cliente_id"], $_SESSION["consulta_verificada_em"]);
            respondeJson(202, respostaConsultaGenerica());
        }

        unset($_SESSION["consulta_cliente_id"], $_SESSION["consulta_verificada_em"]);
        respondeJson(202, respostaConsultaGenerica());
    }

    if ($acao === "verificar_codigo") {
        $cpf = preg_replace('/\D/', '', trim((string) ($dados["cpf"] ?? "")));
        $codigo = trim((string) ($dados["codigo"] ?? ""));
        if (!cpfConsultaValido($cpf) || !preg_match('/^\d{6}$/', $codigo)) {
            respondeJson(400, ["ok" => false, "mensagem" => "CPF ou código inválido ou expirado."]);
        }

        $cliente = consultaClientePorCpf($Mysql, $cpf);
        $codigoAtivo = $cliente ? selecionaCodigoConsultaAtivo($Mysql, (int) $cliente["id"]) : null;
        if (
            !$codigoAtivo
            || strtotime($codigoAtivo["expira_em"]) <= time()
            || (int) $codigoAtivo["tentativas"] >= 5
            || !incrementaTentativasCodigoConsulta($Mysql, (int) $codigoAtivo["id"])
            || !password_verify($codigo, $codigoAtivo["codigo_hash"])
        ) {
            respondeJson(400, ["ok" => false, "mensagem" => "CPF ou código inválido ou expirado."]);
        }

        if (!utilizaCodigoConsulta($Mysql, (int) $codigoAtivo["id"])) {
            respondeJson(400, ["ok" => false, "mensagem" => "CPF ou código inválido ou expirado."]);
        }

        session_regenerate_id(true);
        $_SESSION["consulta_cliente_id"] = (int) $cliente["id"];
        $_SESSION["consulta_verificada_em"] = time();
    } elseif ($acao !== "consultar_agendamentos") {
        respondeJson(400, ["ok" => false, "mensagem" => "Ação inválida."]);
    }

    if (
        empty($_SESSION["consulta_cliente_id"])
        || empty($_SESSION["consulta_verificada_em"])
        || time() - (int) $_SESSION["consulta_verificada_em"] > 600
    ) {
        unset($_SESSION["consulta_cliente_id"], $_SESSION["consulta_verificada_em"]);
        respondeJson(401, ["ok" => false, "mensagem" => "Solicite um novo código para continuar."]);
    }

    $agendamentos = listaAgendamentosCliente($Mysql, (int) $_SESSION["consulta_cliente_id"]);
    $_SESSION["consulta_verificada_em"] = time();
    respondeJson(200, ["ok" => true, "agendamentos" => $agendamentos]);
} catch (mysqli_sql_exception $erro) {
    error_log("Erro ao consultar agendamentos do cliente: " . $erro->getMessage());
    respondeJson(500, [
        "ok" => false,
        "mensagem" => "Não foi possível consultar os agendamentos agora. Tente novamente mais tarde."
    ]);
} catch (Throwable $erro) {
    error_log("Erro inesperado na consulta de agendamentos: " . $erro->getMessage());
    respondeJson(500, [
        "ok" => false,
        "mensagem" => "Não foi possível consultar os agendamentos agora. Tente novamente mais tarde."
    ]);
}

?>
