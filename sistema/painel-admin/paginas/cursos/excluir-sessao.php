<?php 
require_once("../../../conexao.php");
$tabela = 'sessao';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	//excluir aulas relacionadas a sessÇœo
	$stmt = $pdo->prepare("DELETE FROM aulas WHERE sessao = ?");
	$stmt->execute([$id]);

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'ExcluÇðdo com Sucesso';

?>
