<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'Exclu?do com Sucesso';

?>
