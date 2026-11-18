<?php

function salvaCodigoAcessoAgendamento($Mysql, $idCliente, $codigoHash)
{
    $sql = "INSERT IGNORE INTO codigo_acesso_agendamento
                (id_cliente, codigo_hash)
            VALUES (?, ?)";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "is", $idCliente, $codigoHash);
    mysqli_stmt_execute($stmt);
    $criado = mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);

    return $criado;
}

function selecionaCodigoAcessoAgendamento($Mysql, $idCliente)
{
    $sql = "SELECT codigo_hash, tentativas, bloqueado_ate
            FROM codigo_acesso_agendamento
            WHERE id_cliente = ?
            LIMIT 1";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $codigo = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $codigo;
}

function incrementaTentativasCodigoAcessoAgendamento($Mysql, $idCliente)
{
    $sql = "UPDATE codigo_acesso_agendamento
            SET bloqueado_ate = CASE
                    WHEN tentativas >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE bloqueado_ate
                END,
                tentativas = tentativas + 1
            WHERE id_cliente = ?";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function limpaTentativasCodigoAcessoAgendamento($Mysql, $idCliente)
{
    $sql = "UPDATE codigo_acesso_agendamento
            SET tentativas = 0,
                bloqueado_ate = NULL
            WHERE id_cliente = ?";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

?>
