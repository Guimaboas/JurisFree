<?php

function consultaClientePorCpf($Mysql, $cpf)
{
    $sql = "SELECT id, email
            FROM cliente
            WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
            LIMIT 1";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "s", $cpf);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $cliente = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $cliente;
}

function contaCodigosConsultaRecentes($Mysql, $idCliente)
{
    $sql = "SELECT COUNT(*) AS quantidade
            FROM codigo_consulta_agendamento
            WHERE id_cliente = ?
              AND criado_em >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $registro = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return (int) $registro["quantidade"];
}

function selecionaUltimoCodigoConsulta($Mysql, $idCliente)
{
    $sql = "SELECT id, criado_em
            FROM codigo_consulta_agendamento
            WHERE id_cliente = ?
            ORDER BY id DESC
            LIMIT 1";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $codigo = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $codigo;
}

function criaCodigoConsulta($Mysql, $idCliente, $codigoHash)
{
    $sql = "INSERT INTO codigo_consulta_agendamento
                (id_cliente, codigo_hash, expira_em)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "is", $idCliente, $codigoHash);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($Mysql);
    mysqli_stmt_close($stmt);

    return $id;
}

function invalidaCodigosConsultaAtivos($Mysql, $idCliente)
{
    $sql = "UPDATE codigo_consulta_agendamento
            SET utilizado_em = NOW()
            WHERE id_cliente = ?
              AND utilizado_em IS NULL";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function invalidaCodigoConsulta($Mysql, $idCodigo)
{
    $sql = "UPDATE codigo_consulta_agendamento
            SET utilizado_em = NOW()
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCodigo);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function selecionaCodigoConsultaAtivo($Mysql, $idCliente)
{
    $sql = "SELECT id, codigo_hash, expira_em, tentativas
            FROM codigo_consulta_agendamento
            WHERE id_cliente = ?
              AND utilizado_em IS NULL
            ORDER BY id DESC
            LIMIT 1";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $codigo = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $codigo;
}

function incrementaTentativasCodigoConsulta($Mysql, $idCodigo)
{
    $sql = "UPDATE codigo_consulta_agendamento
            SET tentativas = tentativas + 1
            WHERE id = ?
              AND tentativas < 5
              AND utilizado_em IS NULL";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCodigo);
    mysqli_stmt_execute($stmt);
    $atualizado = mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);

    return $atualizado;
}

function utilizaCodigoConsulta($Mysql, $idCodigo)
{
    $sql = "UPDATE codigo_consulta_agendamento
            SET utilizado_em = NOW()
            WHERE id = ?
              AND utilizado_em IS NULL";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCodigo);
    mysqli_stmt_execute($stmt);
    $utilizado = mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);

    return $utilizado;
}

function listaAgendamentosCliente($Mysql, $idCliente)
{
    $sql = "SELECT
                sa.id,
                aj.nome AS area,
                sa.status AS status_solicitacao,
                sa.data_desejada,
                sa.periodo_desejado,
                ag.data_hora,
                ag.status AS status_agendamento,
                usuario.nome AS advogado
            FROM solicitacao_atendimento AS sa
            INNER JOIN area_juridica AS aj
                ON aj.id = sa.id_area_juridica
            LEFT JOIN agendamento AS ag
                ON ag.id_solicitacao = sa.id
            LEFT JOIN advogado AS adv
                ON adv.id = ag.id_advogado
            LEFT JOIN usuario
                ON usuario.id = adv.id_usuario
            WHERE sa.id_cliente = ?
            ORDER BY sa.data_solicitacao DESC, ag.data_hora DESC";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idCliente);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $agendamentos = [];
    while ($registro = mysqli_fetch_assoc($resultado)) {
        $agendamentos[] = $registro;
    }

    mysqli_stmt_close($stmt);

    return $agendamentos;
}

?>
