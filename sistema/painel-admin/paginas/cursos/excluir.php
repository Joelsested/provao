<?php 
require_once("../../../conexao.php");
$tabela = 'cursos';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT imagem, status FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$foto = $res[0]['imagem'] ?? '';
	$status = $res[0]['status'] ?? '';
	if($status == 'Aprovado'){
		echo 'Cuidado, O Curso nÇœo pode ser excluÇðdo com status de aprovado!';
		exit();
	}
	if($foto != "sem-foto.png" && $foto != ''){
		@unlink('../../img/cursos/'.$foto);
	}

	$stmt = $pdo->prepare("DELETE FROM aulas WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'ExcluÇðdo com Sucesso';

?>
