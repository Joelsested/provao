<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
@session_start();
$id_usuario = $_SESSION['id'];

$tabela = 'arquivos_cursos';




$id_curso = @$_POST['id_do_arq'];
$arquivo = @$_POST['arquivo_4'];
$descricao = @$_POST['descricao'];



if ($id_curso == '') {
	echo 'Primeiro Insira o Curso';
	exit();
}

//SCRIPT PARA SUBIR FOTO NO BANCO
$destDir = __DIR__ . '/../../img/arquivos';
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
$upload = upload_handle($_FILES['arquivo_4'] ?? [], $destDir, $allowedExt, $allowedMime, 10 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
	echo $upload['error'];
	exit();
}
if (empty($upload['skipped'])) {
	$imagem = $upload['filename'];
} else {
	$imagem = 'sem-arquivo.png';
}


$stmt = $pdo->prepare("INSERT INTO arquivos_cursos SET curso = :curso, arquivo = :arquivo, data = curDate(), descricao = :descricao, usuario = :usuario");
$stmt->execute([
	':curso' => $id_curso,
	':arquivo' => $imagem,
	':descricao' => $descricao,
	':usuario' => $id_usuario,
]);


echo 'Salvo com Sucesso';

 ?>
