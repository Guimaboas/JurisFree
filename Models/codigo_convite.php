<?php

// criaCodigoConvite()       - Cria um código de convite
// selecionaCodigoConvite()  - Busca um código pelo ID
// verificaCodigoConvite()   - Verifica se o código existe e ainda não foi usado
// listaCodigosConvite()     - Lista todos os códigos
// utilizaCodigoConvite()    - Marca o código como usado
// excluiCodigoConvite()     - Exclui um código


function criaCodigoConvite($Mysql, $codigo, $funcao)
{
    $sql = "INSERT INTO codigo_convite
            (codigo, funcao, usado)
            VALUES
            (?, ?, 0)";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $codigo, $funcao);

    return mysqli_stmt_execute($stmt);
}


function selecionaCodigoConvite($Mysql, $id)
{
    $sql = "SELECT *
            FROM codigo_convite
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($resultado);
}


function verificaCodigoConvite($Mysql, $codigo)
{
    $sql = "SELECT *
            FROM codigo_convite
            WHERE codigo = ?
            AND usado = 0";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "s", $codigo);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($resultado);
}


function listaCodigosConvite($Mysql)
{
    $sql = "SELECT *
            FROM codigo_convite
            ORDER BY data_criacao DESC";

    return mysqli_query($Mysql, $sql);
}


function utilizaCodigoConvite($Mysql, $id)
{
    $sql = "UPDATE codigo_convite
            SET usado = 1,
                data_uso = NOW()
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    return mysqli_stmt_execute($stmt);
}


function excluiCodigoConvite($Mysql, $id)
{
    $sql = "DELETE FROM codigo_convite
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    return mysqli_stmt_execute($stmt);
}

?>