<?php 
require_once("../../../conexao.php");


$tabela = 'arquivos_cursos';


$id = $_POST['id_arq'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'Exclu?do com Sucesso';
 ?>
