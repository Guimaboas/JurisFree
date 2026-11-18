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
require_once __DIR__ . "/../Models/codigo_acesso_agendamento.php";

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

try {
    if ($acao === "verificar_codigo") {
        $cpf = preg_replace('/\D/', '', trim((string) ($dados["cpf"] ?? "")));
        $codigo = trim((string) ($dados["codigo"] ?? ""));
        if (!cpfConsultaValido($cpf) || !preg_match('/^\d{12}$/', $codigo)) {
            respondeJson(400, ["ok" => false, "mensagem" => "CPF ou código inválido ou expirado."]);
        }

        $cliente = consultaClientePorCpf($Mysql, $cpf);
        $idCliente = $cliente ? (int) $cliente["id"] : 0;
        $codigoAcesso = $cliente ? selecionaCodigoAcessoAgendamento($Mysql, $idCliente) : null;
        $bloqueado = $codigoAcesso
            && $codigoAcesso["bloqueado_ate"]
            && strtotime($codigoAcesso["bloqueado_ate"]) > time();

        if (
            !$codigoAcesso
            || $bloqueado
            || !password_verify($codigo, $codigoAcesso["codigo_hash"])
        ) {
            if ($codigoAcesso && !$bloqueado) {
                incrementaTentativasCodigoAcessoAgendamento($Mysql, $idCliente);
            }
            respondeJson(400, ["ok" => false, "mensagem" => "CPF ou código inválido ou expirado."]);
        }

        limpaTentativasCodigoAcessoAgendamento($Mysql, $idCliente);
        session_regenerate_id(true);
        $_SESSION["consulta_cliente_id"] = $idCliente;
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
