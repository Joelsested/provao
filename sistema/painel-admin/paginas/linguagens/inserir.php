<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
$tabela = 'linguagens';

$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$id = $_POST['id'];

$nome_novo = strtolower( preg_replace("[^a-zA-Z0-9-]", "-", 
        strtr(utf8_decode(trim($nome)), utf8_decode("áàãâéêíóôõúüñçÁÀÃÂÉÊÍÓÔÕÚÜÑÇ"),
        "aaaaeeiooouuncAAAAEEIOOOUUNC-")) );
$url = preg_replace('/[ -]+/' , '-' , $nome_novo);


//validar email duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where nome = :nome");
$query->execute([':nome' => $nome]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Categoria já Cadastrada com este nome, escolha Outro!';
	exit();
}


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
$destDir = __DIR__ . '/../../img/linguagens';
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

	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome,  descricao = :descricao, imagem = '$foto', nome_url = '$url'");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, descricao = :descricao, imagem = '$foto', nome_url = '$url' WHERE id = '$id'");
}

$query->bindValue(":nome", "$nome");
$query->bindValue(":descricao", "$descricao");
$query->execute();

echo 'Salvo com Sucesso';

 ?>
