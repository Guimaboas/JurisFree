<?php

function selecionaClientePorCpf($Mysql, $cpf)
{
    $sql = "SELECT id
            FROM cliente
            WHERE cpf = ?";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "s", $cpf);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $cliente = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $cliente;
}

function criaCliente($Mysql, $nome, $cpf, $email, $telefone)
{
    $sql = "INSERT INTO cliente (nome, cpf, email, telefone)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $nome, $cpf, $email, $telefone);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($Mysql);
    mysqli_stmt_close($stmt);

    return $id;
}

function selecionaAreaJuridicaPorNome($Mysql, $nome)
{
    $sql = "SELECT id
            FROM area_juridica
            WHERE nome = ?";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param($stmt, "s", $nome);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $area = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $area;
}

function criaSolicitacaoAtendimento($Mysql, $idCliente, $idArea, $descricao, $dataDesejada, $periodoDesejado)
{
    $sql = "INSERT INTO solicitacao_atendimento
                (id_cliente, id_area_juridica, descricao, data_desejada, periodo_desejado)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($Mysql, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "iisss",
        $idCliente,
        $idArea,
        $descricao,
        $dataDesejada,
        $periodoDesejado
    );
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($Mysql);
    mysqli_stmt_close($stmt);

    return $id;
}

?>
