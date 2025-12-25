<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas_quest';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	//excluir aulas relacionadas a sessÇœo
	$stmt = $pdo->prepare("DELETE FROM alternativas WHERE pergunta = ?");
	$stmt->execute([$id]);

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'ExcluÇðdo com Sucesso';

?>
