<?php

require_once("../../../conexao.php");

$tabela = 'comissoes';

$nivel = $_POST['nivel'];
$porcentagem = $_POST['porcentagem'];
$recebeSempre = $_POST['recebeSempre'];

// Valida cadastro duplicado
$consulta = $pdo->prepare("SELECT COUNT(*) FROM $tabela WHERE nivel = :nivel");
$consulta->bindValue(':nivel', $nivel);
$consulta->execute();
$total_registros = $consulta->fetchColumn();

if ($total_registros > 0) {
    header('Location: ../../index.php?pagina=asaas_comissoes');
    exit();
}

// Inserir novo registro
$query = $pdo->prepare("INSERT INTO $tabela (nivel, porcentagem, recebeSempre, created_at) VALUES (:nivel, :porcentagem, :recebeSempre, CURDATE())");
$query->bindValue(':nivel', $nivel);
$query->bindValue(':porcentagem', $porcentagem);
$query->bindValue(':recebeSempre', $recebeSempre);
$query->execute();

header('Location: ../../index.php?pagina=asaas_comissoes');
exit();
