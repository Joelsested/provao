<?php 
require_once("conexao.php");
require_once(__DIR__ . '/../helpers.php');
$nome = $_POST['nome'];
$email = $_POST['email'];
$cpf = $_POST['cpf'];
$nascimento = $_POST['nascimento'];
$senha = birthDigits($nascimento);
if ($senha === '') {
	echo 'Informe uma data de nascimento válida!';
	exit();
}
$senha_crip = md5($senha);
$tipo = @$_POST['tipo'];

if (empty($cpf) || empty($nascimento)) {
	echo 'Informe o CPF e a data de nascimento para concluir o cadastro!';
	exit();
}

$query = $pdo->prepare("SELECT * FROM usuarios where cpf = :cpf");
$query->bindValue(":cpf", "$cpf");
$query->execute();
$dupeCpf = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($dupeCpf) > 0) {
	echo 'Este CPF já está cadastrado, informe outro CPF ou recupere o acesso!';
	exit();
}

$query = $pdo->query("SELECT * FROM usuarios where nome = 'Professor_padrao'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$prof_padrao = $res[0]['id'];

if($tipo == 'Professor'){

	//CADASTRO DO PROFESSOR
	$query = $pdo->prepare("SELECT * FROM professores where email = :email");
$query->bindValue(":email", "$email");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	echo 'Este email já está cadastrado, escolha outro ou recupere sua senha!';
	exit();
}

$query = $pdo->prepare("INSERT INTO professores SET nome = :nome, email = :email, foto = 'sem-perfil.jpg', data = curDate(), ativo = 'Sim'");
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->execute();
$ult_id = $pdo->lastInsertId();

$query = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, usuario = :email, senha = :senha, senha_crip = :senha_crip, nivel = 'Professor', foto = 'sem-perfil.jpg', id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":senha", "");
$query->bindValue(":senha_crip", "$senha_crip");
$query->bindValue(":id_pessoa", "$ult_id");
$query->execute();

}else{

	//capturar o email do aluno para o email marketing
$query = $pdo->prepare("SELECT * from emails where email = :email");
$query->execute([':email' => $email]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res) == 0){
		$query = $pdo->prepare("INSERT INTO emails SET email = :email, nome = :nome, enviar = 'sim'");

		$query->bindValue(":email", "$email");
		$query->bindValue(":nome", "$nome");		
		$query->execute();
	}	


$query = $pdo->prepare("SELECT * FROM alunos where email = :email");
$query->bindValue(":email", "$email");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	echo 'Este email já está cadastrado, escolha outro ou recupere sua senha!';
	exit();
}

$query = $pdo->prepare("INSERT INTO alunos SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, foto = 'sem-perfil.jpg', data = curDate(), usuario = :usuario, ativo = 'Sim'");
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":nascimento", "$nascimento");
$query->bindValue(":usuario", "$prof_padrao");
$query->execute();
$ult_id = $pdo->lastInsertId();

$query = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, usuario = :email, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Aluno', foto = 'sem-perfil.jpg', id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":senha", "");
$query->bindValue(":senha_crip", "$senha_crip");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":id_pessoa", "$ult_id");
$query->execute();

}

echo 'Cadastrado com Sucesso';

 ?>
