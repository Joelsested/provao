<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	//excluir as respostas e pergunta
	$stmt = $pdo->prepare("DELETE FROM respostas WHERE pergunta = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}


echo 'Exclu?do com Sucesso';

?>
