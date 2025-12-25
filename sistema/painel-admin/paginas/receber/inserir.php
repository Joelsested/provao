<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
$tabela = 'receber';

$valor = $_POST['valor'];
$descricao = $_POST['descricao'];
$vencimento = $_POST['vencimento'];
$id = $_POST['id'];
$valor = str_replace(',', '.', $valor);

$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['arquivo'];
}else{
	$foto = 'sem-foto.png';
}



//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$destDir = __DIR__ . '/../../img/contas';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'zip', 'rar'];
$allowedMime = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/zip',
    'application/x-zip-compressed',
    'application/x-rar-compressed',
    'application/vnd.rar',
];
$upload = upload_handle($_FILES['arquivo'] ?? [], $destDir, $allowedExt, $allowedMime, 10 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
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
	$query = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao,  valor = :valor, data = curDate(), vencimento = '$vencimento', pago = 'Não', arquivo = '$foto'");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET descricao = :descricao,  valor = :valor, vencimento = '$vencimento', arquivo = '$foto' WHERE id = '$id'");
}

$query->bindValue(":descricao", "$descricao");
$query->bindValue(":valor", "$valor");
$query->execute();

echo 'Salvo com Sucesso';

 ?>
