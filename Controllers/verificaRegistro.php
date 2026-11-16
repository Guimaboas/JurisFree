<?php

require_once __DIR__ . "/../Config/conecta.php";
require_once __DIR__ . "/../Config/sessao.php";
require_once __DIR__ . "/../Models/usuario.php";
require_once __DIR__ . "/../Models/codigo_convite.php";

$nome = $_POST["nome"];
$email = $_POST["email"];
$codigo = $_POST["codigo"];
$senha = $_POST["senha"];
$confirma = $_POST["confirma"];
$termos = isset($_POST["termos"]);

if ($senha != $confirma) {
    header("Location: ../admin/jurisfree-admin.html?erroSenha=1");
    exit;
}

if (!$termos) {
    header("Location: ../admin/jurisfree-admin.html?erroTermos=1");
    exit;
}

$usuario = selecionaUsuarioPorEmail($Mysql, $email);

if ($usuario) {
    header("Location: ../admin/jurisfree-admin.html?erro=1");
    exit;
}

$convite = verificaCodigoConvite($Mysql, $codigo);

if (!$convite) {
    header("Location: ../admin/jurisfree-admin.html?erroCod=1");
    exit;
}

mysqli_begin_transaction($Mysql);

try {

    $criou = criaUsuario(
        $Mysql,
        $nome,
        $email,
        $senha,
        $convite["funcao"],
        $convite["id"]
    );

    if (!$criou) {
        throw new Exception("Erro ao criar usuário.");
    }

    if (!utilizaCodigoConvite($Mysql, $convite["id"])) {
        throw new Exception("Erro ao utilizar código de convite.");
    }

    $usuarioSession = selecionaUsuarioPorEmail($Mysql, $email);
    if (!$usuarioSession || !usuarioEstaAtivo($usuarioSession)) {
        throw new Exception("Não foi possível iniciar a sessão do usuário cadastrado.");
    }

    mysqli_commit($Mysql);

} catch (Exception $e) {

    mysqli_rollback($Mysql);

    header("Location: ../admin/jurisfree-admin.html?erroCadastro=1");
    exit;
}

autenticaSessaoUsuario($usuarioSession);
header("Location: ../admin/jurisfree-dashboard.php");
exit;

?>