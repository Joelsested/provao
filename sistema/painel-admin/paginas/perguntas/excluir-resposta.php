<?php 
require_once("../../../conexao.php");
$tabela = 'respostas';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT * FROM respostas WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$pergunta = $res[0]['pergunta'] ?? 0;

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);

	echo 'Exclu?do com Sucesso';

	$stmt = $pdo->prepare("UPDATE perguntas SET respondida = 'Sim' WHERE id = ?");
	$stmt->execute([(int) $pergunta]);
}

?>
