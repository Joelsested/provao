<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
$tabela = 'aulas';

$num_aula = $_POST['num_aula'];
$nome_aula = $_POST['nome_aula'];
$link_aula = $_POST['link_aula'];

$sessao_aula = $_POST['sessao_aula'];
$id_curso = $_POST['id'];
$id_aula = $_POST['id_aula'];


//buscar quantidade de aulas do curso
$query = $pdo->prepare("SELECT * FROM $tabela where curso = :curso");
$query->execute([':curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_aulas = @count($res);
if($total_aulas == 0){
	$seq_aula = 1;
}else{
	$seq_aula = $total_aulas + 1;
}


//validar num aula duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where num_aula = :num_aula and sessao = :sessao and curso = :curso");
$query->execute([
	':num_aula' => $num_aula,
	':sessao' => $sessao_aula,
	':curso' => $id_curso,
]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id_aula){
	echo 'Aula já Cadastrada, escolha outro numero para aula!';
	exit();
}



$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id_aula]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['apostila'];
}else{
	$foto = '';
}


//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$destDir = __DIR__ . '/../../img/arquivos';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
$upload = upload_handle($_FILES['arquivo_2'] ?? [], $destDir, $allowedExt, $allowedMime, 10 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
	echo $upload['error'];
	exit();
}
if (empty($upload['skipped'])) {
	if ($foto != 'sem-arquivo') {
		@unlink($destDir . '/' . $foto);
	}
	$foto = $upload['filename'];
} elseif ($foto == '') {
	$foto = 'sem-arquivo';
}


if($id_aula == ""){

	$query = $pdo->prepare("INSERT INTO $tabela SET num_aula = :num_aula, nome = :nome, link = :link, curso = '$id_curso', apostila = '$foto', sessao = '$sessao_aula', sequencia_aula = '$seq_aula'");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET num_aula = :num_aula, nome = :nome, link = :link, sessao = '$sessao_aula', apostila = '$foto' where id = '$id_aula'");
}

$query->bindValue(":nome", "$nome_aula");
$query->bindValue(":num_aula", "$num_aula");
$query->bindValue(":link", "$link_aula");
$query->execute();

echo 'Salvo com Sucesso';

 ?>
