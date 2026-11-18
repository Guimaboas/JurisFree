<?php

require_once __DIR__ . "/../PHPMailer/src/Exception.php";
require_once __DIR__ . "/../PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;

function configuracaoEmailJurisFree()
{
    $usuario = getenv("JURISFREE_SMTP_USER");
    $senha = getenv("JURISFREE_SMTP_PASSWORD");
    $remetente = getenv("JURISFREE_MAIL_FROM");
    $host = getenv("JURISFREE_SMTP_HOST") ?: "smtp.gmail.com";
    $porta = getenv("JURISFREE_SMTP_PORT") ?: "587";

    if (
        !$usuario
        || !$senha
        || !$remetente
        || !filter_var($usuario, FILTER_VALIDATE_EMAIL)
        || !filter_var($remetente, FILTER_VALIDATE_EMAIL)
        || !ctype_digit((string) $porta)
        || (int) $porta < 1
        || (int) $porta > 65535
    ) {
        throw new RuntimeException("Configure as credenciais SMTP do JurisFree no ambiente do PHP.");
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
