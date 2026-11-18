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
                usuario.nome AS advogado,
                caso.resumo AS resumo_caso,
                ag.observacao
            FROM solicitacao_atendimento AS sa
            INNER JOIN area_juridica AS aj
                ON aj.id = sa.id_area_juridica
            LEFT JOIN agendamento AS ag
                ON ag.id_solicitacao = sa.id
            LEFT JOIN advogado AS adv
                ON adv.id = ag.id_advogado
            LEFT JOIN usuario
                ON usuario.id = adv.id_usuario
            LEFT JOIN caso
                ON caso.id_solicitacao = sa.id
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
