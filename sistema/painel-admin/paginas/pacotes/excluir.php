<?php 
require_once("../../../conexao.php");
$tabela = 'pacotes';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT imagem FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$foto = $res[0]['imagem'] ?? '';

	if($foto != "sem-foto.png" && $foto != ''){
		@unlink('../../img/pacotes/'.$foto);
	}

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'Exclu?do com Sucesso';

?>
