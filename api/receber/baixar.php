<?php 
$tabela = 'receber';
include_once('../conexao.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
	$stmt = $pdo->prepare("UPDATE $tabela SET pago = 'Sim', data_pgto = curDate() WHERE id = ?");
	$stmt->execute([$id]);
}

?>
