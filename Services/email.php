<?php

require_once __DIR__ . "/../PHPMailer/src/Exception.php";
require_once __DIR__ . "/../PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;

function configuracaoEmailJurisFree()
{
    $caminhoConfiguracao = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . "jurisfree-email.php";
    if (!is_file($caminhoConfiguracao) || !is_readable($caminhoConfiguracao)) {
        throw new RuntimeException("Arquivo privado de configuração de email não encontrado ou sem permissão de leitura.");
    }

    $config = require $caminhoConfiguracao;
    if (!is_array($config)) {
        throw new RuntimeException("O arquivo privado de configuração de email tem formato inválido.");
    }

    $usuario = $config["smtp_user"] ?? "";
    $senha = $config["smtp_password"] ?? "";
    $remetente = $config["mail_from"] ?? "";
    $host = $config["smtp_host"] ?? "";
    $porta = $config["smtp_port"] ?? "";

    if (
        !is_string($usuario)
        || !is_string($senha)
        || !is_string($remetente)
        || !is_string($host)
        || $usuario === ""
        || $senha === ""
        || $remetente === ""
        || $host === ""
        || strpos($usuario, "####") !== false
        || strpos($senha, "####") !== false
        || strpos($remetente, "####") !== false
        || !filter_var($usuario, FILTER_VALIDATE_EMAIL)
        || !filter_var($remetente, FILTER_VALIDATE_EMAIL)
        || filter_var($porta, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 65535]]) === false
    ) {
        throw new RuntimeException("Preencha smtp_user, smtp_password e mail_from no arquivo privado de configuração de email.");
    }

    return [
        "usuario" => $usuario,
        "senha" => $senha,
        "remetente" => $remetente,
        "host" => $host,
        "porta" => (int) $porta
    ];
}

function emailJurisFreeConfigurado()
{
    try {
        configuracaoEmailJurisFree();
        return true;
    } catch (RuntimeException $erro) {
        return false;
    }
}

function enviarEmailJurisFree($destinatario, $nome, $assunto, $mensagem)
{
    $config = configuracaoEmailJurisFree();
    $email = new PHPMailer(true);
    $email->isSMTP();
    $email->Host = $config["host"];
    $email->SMTPAuth = true;
    $email->Username = $config["usuario"];
    $email->Password = $config["senha"];
    $email->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $email->Port = $config["porta"];
    $email->CharSet = "UTF-8";
    $email->setFrom($config["remetente"], "JurisFree");
    $email->addAddress($destinatario, $nome);
    $email->Subject = $assunto;
    $email->Body = $mensagem;

    if (!$email->send()) {
        throw new RuntimeException("O servidor SMTP não confirmou o envio do email.");
    }

    return true;
}

?>
