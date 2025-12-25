<?php 
require_once("../conexao.php");
require_once(__DIR__ . "/../../config/upload.php");
require_once(__DIR__ . "/../../helpers.php");

$nome = $_POST['nome_usu'];
$cpf = $_POST['cpf_usu'];
$email = $_POST['email_usu'];
$id = $_POST['id_usu'];
$foto = $_POST['foto_usu'];

$rg = $_POST['rg_usu'];
$orgao_expedidor = $_POST['expedidor_usu'];
$expedicao = $_POST['expedicao_usu'];
$nascimento = $_POST['nascimento_usu'];
$telefone = $_POST['telefone_usu'];
$cep = $_POST['cep_usu'];
$sexo = $_POST['sexo_usu'];
$endereco = $_POST['endereco_usu'];
$numero = $_POST['numero_usu'];
$bairro = $_POST['bairro_usu'];
$cidade = $_POST['cidade_usu'];
$estado = $_POST['estado_usu'];
$mae = $_POST['mae_usu'];
$pai = $_POST['pai_usu'];
$naturalidade = $_POST['naturalidade_usu'];

$senha = birthDigits($nascimento);
if ($senha === '') {
	echo 'Data de nascimento inválida!';
	exit();
}
$senha_crip = md5($senha);



$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_pessoa = $res[0]['id_pessoa'];

//validar email duplicado
$query = $pdo->prepare("SELECT * FROM usuarios where usuario = :usuario");
$query->execute([':usuario' => $email]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Email já Cadastrado, escolha Outro!';
	exit();
}


//validar cpf duplicado
$query = $pdo->prepare("SELECT * FROM usuarios where cpf = :cpf");
$query->execute([':cpf' => $cpf]);
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
$query = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, senha_crip = :senha_crip, foto = :foto where id = :id");

$query->bindValue(":nome", "$nome");
$query->bindValue(":usuario", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":senha", "");
$query->bindValue(":senha_crip", "$senha_crip");
$query->bindValue(":foto", "$foto");
$query->bindValue(":id", $id, PDO::PARAM_INT);
$query->execute();


$query = $pdo->prepare("UPDATE alunos SET nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao,  nascimento = :nascimento, cep = :cep, sexo = :sexo, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, mae = :mae, pai = :pai, naturalidade = :naturalidade where id = :id");

$query->bindValue(":nome", "$nome");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":email", "$email");
$query->bindValue(":telefone", "$telefone");
$query->bindValue(":rg", "$rg");
$query->bindValue(":orgao_expedidor", "$orgao_expedidor");
$query->bindValue(":expedicao", "$expedicao");
$query->bindValue(":nascimento", "$nascimento");
$query->bindValue(":cep", "$cep");
$query->bindValue(":sexo", "$sexo");
$query->bindValue(":endereco", "$endereco");
$query->bindValue(":numero", "$numero");
$query->bindValue(":bairro", "$bairro");
$query->bindValue(":cidade", "$cidade");
$query->bindValue(":estado", "$estado");
$query->bindValue(":mae", "$mae");
$query->bindValue(":pai", "$pai");
$query->bindValue(":naturalidade", "$naturalidade");
$query->bindValue(":id", $id_pessoa, PDO::PARAM_INT);
$query->execute();

echo 'Editado com Sucesso';

 ?>
