<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
$tabela = 'alertas';

$ano_atual = date('Y');

@session_start();
$id_usuario = $_SESSION['id'];

$titulo = $_POST['titulo'];
$descricao = $_POST['descricao'];
$data = $_POST['data'];
$video = $_POST['video'];
$link = $_POST['link'];

$descricao = str_replace("'", " ", $descricao);
$descricao = str_replace('"', ' ', $descricao);

$titulo = str_replace("'", " ", $titulo);
$titulo = str_replace('"', ' ', $titulo);


$id = $_POST['id'];


$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['imagem'];
}else{
	$foto = 'sem-foto.png';
}



//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$destDir = __DIR__ . '/../../img/alertas';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['foto'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
	echo $upload['error'];
	exit();
}
if (empty($upload['skipped'])) {
	if ($foto != 'sem-foto.png') {
		@unlink($destDir . '/' . $foto);
	}
	$foto = $upload['filename'];
}


if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET titulo = :titulo, descricao = :descricao, link = :link, video = :video, data = :data, imagem = :imagem");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET titulo = :titulo, descricao = :descricao, link = :link, video = :video, data = :data, imagem = :imagem WHERE id = :id");
}

$query->execute([
	':titulo' => $titulo,
	':descricao' => $descricao,
	':link' => $link,
	':video' => $video,
	':data' => $data,
	':imagem' => $foto,
	':id' => $id,
]);

echo 'Salvo com Sucesso';

 ?>
