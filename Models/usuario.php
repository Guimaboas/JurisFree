<?php

// criaUsuario()              - Cadastra um usuário
// verificaUsuario()          - Verifica se o usuário existe e retorna os dados
// selecionaUsuarioPorId()    - Busca um usuário pelo ID
// selecionaUsuarioPorEmail() - Busca um usuário pelo e-mail
// listaUsuarios()            - Lista todos os usuários
// atualizaUsuario()          - Atualiza os dados do usuário
// atualizaSenha()             - Altera somente a senha
// excluiUsuario()            - Exclui um usuário


function criaUsuario($Mysql, $nome, $email, $senha, $funcao, $id_codigo_convite)
{
    $senha = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuario
            (nome, email, senha, funcao, status, id_codigo_convite)
            VALUES
            (?, ?, ?, ?, 1, ?)";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "ssssi", $nome, $email, $senha, $funcao, $id_codigo_convite);

    return mysqli_stmt_execute($stmt);
}


function verificaUsuario($Mysql, $email, $senha)
{
    $sql = "SELECT *
            FROM usuario
            WHERE email = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) == 0) {
        return 0;
    }

    $usuario = mysqli_fetch_assoc($resultado);

    if (password_verify($senha, $usuario["senha"])) {
        return $usuario;
    }

    return 2;
}


function selecionaUsuarioPorId($Mysql, $id)
{
    $sql = "SELECT *
            FROM usuario
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($resultado);
}


function selecionaUsuarioPorEmail($Mysql, $email)
{
    $sql = "SELECT *
            FROM usuario
            WHERE email = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($resultado);
}


function listaUsuarios($Mysql)
{
    $sql = "SELECT *
            FROM usuario
            ORDER BY nome ASC";

    $resultado = mysqli_query($Mysql, $sql);

    return $resultado;
}


function atualizaUsuario($Mysql, $id, $nome, $email, $funcao, $status)
{
    $sql = "UPDATE usuario
            SET nome = ?,
                email = ?,
                funcao = ?,
                status = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "sssii", $nome, $email, $funcao, $status, $id);

    return mysqli_stmt_execute($stmt);
}


function atualizaSenha($Mysql, $id, $senha)
{
    $senha = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "UPDATE usuario
            SET senha = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "si", $senha, $id);

    return mysqli_stmt_execute($stmt);
}


function excluiUsuario($Mysql, $id)
{
    $sql = "DELETE FROM usuario
            WHERE id = ?";

    $stmt = mysqli_prepare($Mysql, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    return mysqli_stmt_execute($stmt);
}

?>