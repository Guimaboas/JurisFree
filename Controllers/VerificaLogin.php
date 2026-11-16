<?php

require_once __DIR__ . "/../Config/conecta.php";
require_once __DIR__ . "/../Config/sessao.php";
require_once __DIR__ . "/../Models/usuario.php";

$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";

$usuario = verificaUsuario($Mysql, $email, $senha);

if ($usuario === 0) {
    header("Location: ../admin/jurisfree-admin.html?erroLogin=1");
    exit;
}

if ($usuario === 2) {
    header("Location: ../admin/jurisfree-admin.html?erroSenhaLogin=1");
    exit;
}

if (!usuarioEstaAtivo($usuario)) {
    header("Location: ../admin/jurisfree-admin.html?erroInativo=1");
    exit;
}

autenticaSessaoUsuario($usuario);

header("Location: ../admin/jurisfree-dashboard.php");
exit;

?>