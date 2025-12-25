<?php 
require_once("../../../conexao.php");
$tabela = 'banner_index';

$id = $_POST['id'];
$acao = $_POST['acao'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("UPDATE $tabela SET ativo = ? WHERE id = ?");
	$stmt->execute([$acao, $id]);
}


echo 'Alterado com Sucesso';
?>
