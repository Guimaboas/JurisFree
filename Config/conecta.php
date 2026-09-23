<?php
$Host = "localhost";
$User = "root";
$Pass = "";
$Base = "jurisfree";

$Mysql = new mysqli($Host, $User, $Pass, $Base);
if ($Mysql->connect_error) {
    die("Erro na conexão: " . $Mysql->connect_error);
}
unset($Host,$User,$Pass,$Base);
?>