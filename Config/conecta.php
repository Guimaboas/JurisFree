<?php
$Host = "localhost";
$User = "root";
$Pass = "";
$Base = "jurisfree";

mysqli_report(MYSQLI_REPORT_OFF);
$Mysql = new mysqli($Host, $User, $Pass, $Base);
if ($Mysql->connect_error) {
    error_log("Erro na conexão com o banco JurisFree: " . $Mysql->connect_error);
    http_response_code(500);
    exit("Não foi possível conectar ao sistema. Tente novamente mais tarde.");
}
$Mysql->set_charset("utf8mb4");
unset($Host,$User,$Pass,$Base);
?>