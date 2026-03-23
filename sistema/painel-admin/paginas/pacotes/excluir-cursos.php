<?php
require_once("../../../conexao.php");
$tabela = 'cursos_pacotes';

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
	echo 'ID invalido.';
	exit();
}

$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ? LIMIT 1");
$stmt->execute([$id]);

echo 'Excluido com Sucesso';

?>
