<?php 
require_once("../../../conexao.php");
$tabela = 'aulas';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT apostila FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$foto = $res[0]['apostila'] ?? '';

	if($foto != "sem-arquivo.png" && $foto != ''){
		@unlink('../../img/arquivos/'.$foto);
	}

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}


echo 'Exclu?do com Sucesso';

?>
