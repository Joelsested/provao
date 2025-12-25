<?php
// queries.php

require_once("../../../conexao.php");


function buscarUsuarioAluno($id_aluno)
{
    global $pdo;
    $sql = "SELECT * FROM usuarios WHERE id_pessoa = :id and nivel = 'Aluno'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id_aluno]);
    return $stmt->fetch(mode: PDO::FETCH_ASSOC);
}



function buscarCategoriasCursos($idAluno)
{
    global $pdo;
    $sql = "SELECT 
    m.aluno,
    MAX(CASE WHEN cat.nome = 'Ensino Fundamental' THEN 1 ELSE 0 END) AS tem_fundamental,
    MAX(CASE WHEN cat.nome = 'Ensino Médio' THEN 1 ELSE 0 END) AS tem_medio
        FROM matriculas m
        INNER JOIN cursos c ON c.id = m.id_curso
        INNER JOIN categorias cat ON cat.id = c.categoria
        WHERE m.aluno = :id_aluno AND m.pacote != 'Sim'
        GROUP BY m.aluno;";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_aluno' => $idAluno]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}