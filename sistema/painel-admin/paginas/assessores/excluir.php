<?php 
require_once("../../../conexao.php");
$tabela = 'assessores';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT foto FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$foto = $res[0]['foto'] ?? '';
	if($foto != "sem-perfil.jpg" && $foto != ''){
		@unlink('../../img/perfil/'.$foto);
	}

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_pessoa = ? AND nivel = 'Assessor'");
	$stmt->execute([$id]);
}

echo 'Exclu?do com Sucesso';

?>
