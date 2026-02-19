<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas';

$id = $_POST['id'];

//excluir as respostas e pergunta
$stmt = $pdo->prepare("DELETE FROM respostas WHERE pergunta = :pergunta");
$stmt->execute(['pergunta' => $id]);
$stmt = $pdo->prepare("DELETE FROM {$tabela} WHERE id = :id");
$stmt->execute(['id' => $id]);


echo 'Excluído com Sucesso';

?>
