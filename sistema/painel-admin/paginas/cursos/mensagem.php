<?php 
require_once("../../../conexao.php");
$tabela = 'cursos';

$id = $_POST['id'];
$mensagem = $_POST['mensagem'];

$query = $pdo->prepare("UPDATE $tabela SET mensagem = :mensagem where id = '$id'");
$query->bindValue(":mensagem", "$mensagem");
$query->execute();

echo 'Alterado com Sucesso';
?>