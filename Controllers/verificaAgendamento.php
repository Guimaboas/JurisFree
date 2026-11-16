<?php

require_once __DIR__ . "/../Config/conecta.php";
require_once __DIR__ . "/../Models/solicitacao_atendimento.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function redirecionaFormularioAgendamento($parametro)
{
    header("Location: ../Views/jurisfree-agendamento.html?" . $parametro);
    exit;
}

function contaCaracteres($texto)
{
    $quantidade = preg_match_all('/./us', $texto);
    return $quantidade === false ? -1 : $quantidade;
}

function cpfValido($cpf)
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    http_response_code(405);
    exit("Método não permitido.");
}

$nome = trim($_POST["nome"] ?? "");
$email = trim($_POST["email"] ?? "");
$cpf = preg_replace('/\D/', '', trim($_POST["cpf"] ?? ""));
$telefone = preg_replace('/\D/', '', trim($_POST["whatsapp"] ?? ""));
$areaSelecionada = $_POST["area"] ?? "";
$descricao = trim($_POST["caso"] ?? "");
$dataDesejada = trim($_POST["data"] ?? "");
$periodoDesejado = trim($_POST["horario"] ?? "");

$tamanhoNome = contaCaracteres($nome);
$tamanhoEmail = contaCaracteres($email);
$tamanhoDescricao = contaCaracteres($descricao);
$areas = [
    "previdenciario" => "Direito Previdenciário",
    "consumidor" => "Direito do Consumidor"
];
$periodosPermitidos = ["", "manha", "tarde", "noite"];

$dataValida = true;
if ($dataDesejada !== "") {
    $dataFormatada = DateTime::createFromFormat("!Y-m-d", $dataDesejada);
    $errosData = DateTime::getLastErrors();
    $dataValida = $dataFormatada !== false
        && ($errosData === false || ($errosData["warning_count"] === 0 && $errosData["error_count"] === 0))
        && $dataFormatada->format("Y-m-d") === $dataDesejada
        && $dataDesejada >= date("Y-m-d");
}

$dadosValidos = $tamanhoNome >= 3
    && $tamanhoNome <= 150
    && $tamanhoEmail > 0
    && $tamanhoEmail <= 150
    && filter_var($email, FILTER_VALIDATE_EMAIL)
    && cpfValido($cpf)
    && strlen($telefone) >= 10
    && strlen($telefone) <= 20
    && isset($areas[$areaSelecionada])
    && $tamanhoDescricao >= 20
    && $tamanhoDescricao <= 10000
    && $dataValida
    && in_array($periodoDesejado, $periodosPermitidos, true);

if (!$dadosValidos) {
    redirecionaFormularioAgendamento("erro=validacao");
}

mysqli_set_charset($Mysql, "utf8mb4");
try {
    $area = selecionaAreaJuridicaPorNome($Mysql, $areas[$areaSelecionada]);
} catch (mysqli_sql_exception $erro) {
    error_log("Falha ao consultar área jurídica: " . $erro->getMessage());
    redirecionaFormularioAgendamento("erro=salvar");
}

if (!$area) {
    error_log("Área jurídica do formulário não cadastrada: " . $areas[$areaSelecionada]);
    redirecionaFormularioAgendamento("erro=area");
}

if ($dataDesejada === "") {
    $dataDesejada = null;
}
if ($periodoDesejado === "") {
    $periodoDesejado = null;
}

try {
    mysqli_begin_transaction($Mysql);
    $transacaoIniciada = true;

    $cliente = selecionaClientePorCpf($Mysql, $cpf);
    if ($cliente) {
        $idCliente = (int) $cliente["id"];
    } else {
        $idCliente = criaCliente($Mysql, $nome, $cpf, $email, $telefone);
    }

    criaSolicitacaoAtendimento(
        $Mysql,
        $idCliente,
        (int) $area["id"],
        $descricao,
        $dataDesejada,
        $periodoDesejado
    );

    mysqli_commit($Mysql);
    $transacaoIniciada = false;
} catch (mysqli_sql_exception $erro) {
    if (!empty($transacaoIniciada)) {
        mysqli_rollback($Mysql);
    }
    error_log("Falha ao salvar solicitação de atendimento: " . $erro->getMessage());
    redirecionaFormularioAgendamento("erro=salvar");
}

redirecionaFormularioAgendamento("enviado=1");

?>
