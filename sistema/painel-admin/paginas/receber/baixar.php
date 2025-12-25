<?php 
require_once("../../../conexao.php");
$tabela = 'receber';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("UPDATE $tabela SET pago = 'Sim', data_pgto = curDate() WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'Baixado com Sucesso';

?>
