<?php 
require_once("../conexao.php");
require_once(__DIR__ . "/../../config/upload.php");

$nome = $_POST['nome_usu'];
$email = $_POST['email_usu'];
$senha = $_POST['senha_usu'];
$senha_crip = md5($senha);
$cpf = $_POST['cpf_usu'];
$id = $_POST['id_usu'];
$foto = $_POST['foto_usu'];


$telefone = $_POST['telefone_usu'];
$endereco = $_POST['endereco_usu'];
$estado = $_POST['estado_usu'];
$cidade = $_POST['cidade_usu'];
$sexo = $_POST['sexo_usu'];


$query = $pdo->query("SELECT * FROM usuarios where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_pessoa = $res[0]['id_pessoa'];

//validar email duplicado
$query = $pdo->query("SELECT * FROM usuarios where usuario = '$email'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Email já Cadastrado, escolha Outro!';
	exit();
}


//validar cpf duplicado
$query = $pdo->query("SELECT * FROM usuarios where cpf = '$cpf'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'CPF já Cadastrado, escolha Outro!';
	exit();
}





//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$destDir = __DIR__ . '/img/perfil';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['foto'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
	echo $upload['error'];
	exit();
}
if (empty($upload['skipped'])) {
	if ($foto != 'sem-perfil.jpg') {
		@unlink($destDir . '/' . $foto);
	}
	$foto = $upload['filename'];
}


//atualizar os dados do usuário
$query = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, senha_crip = '$senha_crip', foto = '$foto' where id = '$id'");
$query->bindValue(":nome", "$nome");
$query->bindValue(":usuario", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":senha", "");
$query->execute();


$query = $pdo->prepare("UPDATE alunos SET nome = :nome, cpf = :cpf, email = :usuario, foto = '$foto', telefone = :telefone, endereco = :endereco, cidade = :cidade, estado = :estado, sexo = :sexo where id = '$id_pessoa'");

$query->bindValue(":nome", "$nome");
$query->bindValue(":usuario", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":telefone", "$telefone");
$query->bindValue(":endereco", "$endereco");
$query->bindValue(":cidade", "$cidade");
$query->bindValue(":estado", "$estado");
$query->bindValue(":sexo", "$sexo");
$query->execute();

echo 'Editado com Sucesso';

 ?>
