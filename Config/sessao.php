<?php

function iniciaSessaoJurisfree()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set("session.use_strict_mode", "1");
    session_start([
        "cookie_httponly" => true,
        "cookie_samesite" => "Lax",
        "cookie_secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
    ]);
}

function usuarioEstaAtivo($usuario)
{
    return isset($usuario["status"])
        && in_array((string) $usuario["status"], ["ativo", "1"], true);
}

function autenticaSessaoUsuario($usuario)
{
    iniciaSessaoJurisfree();
    session_regenerate_id(true);

    $_SESSION["id"] = (int) $usuario["id"];
    $_SESSION["nome"] = trim($usuario["nome"]);
    $_SESSION["email"] = $usuario["email"];
    $_SESSION["funcao"] = $usuario["funcao"];
    $_SESSION["status"] = $usuario["status"];
}

function exigeSessaoUsuario()
{
    iniciaSessaoJurisfree();

    if (
        empty($_SESSION["id"])
        || empty($_SESSION["nome"])
        || empty($_SESSION["email"])
        || empty($_SESSION["funcao"])
        || !usuarioEstaAtivo(["status" => $_SESSION["status"] ?? null])
    ) {
        header("Location: jurisfree-admin.html?erroSessao=1");
        exit;
    }

    return [
        "id" => (int) $_SESSION["id"],
        "nome" => trim($_SESSION["nome"]),
        "email" => $_SESSION["email"],
        "funcao" => $_SESSION["funcao"],
        "status" => $_SESSION["status"]
    ];
}

function iniciaisUsuario($nome)
{
    $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY);
    if (!$partes) {
        return "?";
    }

    $primeirasLetras = [];
    if (count($partes) > 1) {
        $primeirasLetras = [$partes[0], $partes[1]];
    } else {
        preg_match_all('/\X/u', $partes[0], $letras);
        $primeirasLetras = array_slice($letras[0], 0, 1);
    }

    $iniciais = "";
    foreach ($primeirasLetras as $parte) {
        preg_match('/^\X/u', $parte, $letra);
        if (isset($letra[0])) {
            $iniciais .= function_exists("mb_strtoupper")
                ? mb_strtoupper($letra[0], "UTF-8")
                : strtr(strtoupper($letra[0]), [
                    "á" => "Á", "à" => "À", "â" => "Â", "ã" => "Ã",
                    "ä" => "Ä", "é" => "É", "è" => "È", "ê" => "Ê",
                    "ë" => "Ë", "í" => "Í", "ì" => "Ì", "î" => "Î",
                    "ï" => "Ï", "ó" => "Ó", "ò" => "Ò", "ô" => "Ô",
                    "õ" => "Õ", "ö" => "Ö", "ú" => "Ú", "ù" => "Ù",
                    "û" => "Û", "ü" => "Ü", "ç" => "Ç", "ñ" => "Ñ"
                ]);
        }
    }

    return $iniciais !== "" ? $iniciais : "?";
}

function rotuloFuncaoUsuario($funcao)
{
    $rotulos = [
        "coordenador" => "Coordenador",
        "docente" => "Docente",
        "advogado" => "Advogado",
        "estudante" => "Estudante"
    ];

    return $rotulos[$funcao] ?? "Usuário";
}

?>
