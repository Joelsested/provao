<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas_quest';


$pergunta = $_POST['pergunta'];
$id_curso = $_POST['id'];


$sessao_aula = 0;

$query = $pdo->prepare("INSERT INTO $tabela SET  pergunta = :pergunta, curso = '$id_curso'");

$query->bindValue(":pergunta", "$pergunta");
$query->execute();

echo 'Salvo com Sucesso';

 ?>