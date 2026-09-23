<?php

require_once("../Config/conecta.php");
require_once("../Models/usuario.php");

$email = $_POST["email"];
$senha = $_POST["senha"];

$usuario = verificaUsuario($Mysql, $email, $senha);

if ($usuario == 0) {
    header("Location: ../admin/jurisfree-admin.html?erroLogin=1");
    exit;
}

if ($usuario == 2) {
    header("Location: ../admin/jurisfree-admin.html?erroSenhaLogin=1");
    exit;
}

if ($usuario["status"] == 0) {
    header("Location: ../admin/jurisfree-admin.html?erroInativo=1");
    exit;
}

session_start();

$_SESSION["id"] = $usuario["id"];
$_SESSION["nome"] = $usuario["nome"];
$_SESSION["email"] = $usuario["email"];
$_SESSION["funcao"] = $usuario["funcao"];
$_SESSION["status"] = $usuario["status"];

header("Location: ../index.php");
exit;

?>