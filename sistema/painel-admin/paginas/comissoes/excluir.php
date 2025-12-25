<?php 
require_once("../../../conexao.php");
$tabela = 'pagar';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT arquivo FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$foto = $res[0]['arquivo'] ?? '';
	if($foto != "sem-foto.png" && $foto != ''){
		@unlink('../../img/contas/'.$foto);
	}

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'Exclu?do com Sucesso';

?>
