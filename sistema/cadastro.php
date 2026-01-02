<?php 
require_once("conexao.php");
require_once(__DIR__ . '/../helpers.php');
@session_start();
$nome = $_POST['nome'];
$email = $_POST['email'];
$cpf = $_POST['cpf'];
$nascimento = $_POST['nascimento'];
$cpfDigits = digitsOnly($cpf);
$senha = birthDigits($nascimento);
if ($senha === '') {
	echo 'Informe uma data de nascimento válida!';
	exit();
}
$senha_crip = md5($senha);
$tipo = @$_POST['tipo'];
$professor_tutor_id = filter_input(INPUT_POST, 'professor_tutor_id', FILTER_VALIDATE_INT);
$allowedLevels = ['Tutor', 'Vendedor', 'Secretario', 'Tesoureiro'];

if (empty($cpf) || empty($nascimento)) {
	echo 'Informe o CPF e a data de nascimento para concluir o cadastro!';
	exit();
}
if ($cpfDigits === '') {
	echo 'Informe um CPF vǭlido para concluir o cadastro!';
	exit();
}

$cpfColumn = cleanCpfColumn('cpf');
$query = $pdo->prepare("SELECT * FROM usuarios where $cpfColumn = :cpf_digits");
$query->bindValue(":cpf_digits", "$cpfDigits");
$query->execute();
$dupeCpf = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($dupeCpf) > 0) {
	echo 'Este CPF já está cadastrado, informe outro CPF ou recupere o acesso!';
	exit();
}

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

$professor_id = nextTableId($pdo, 'professores');
if ($professor_id) {
	$query = $pdo->prepare("INSERT INTO professores SET id = :id, nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, foto = 'sem-perfil.jpg', data = curDate(), ativo = 'Sim'");
	$query->bindValue(":id", $professor_id, PDO::PARAM_INT);
} else {
	$query = $pdo->prepare("INSERT INTO professores SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, foto = 'sem-perfil.jpg', data = curDate(), ativo = 'Sim'");
}
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":nascimento", "$nascimento");
$query->execute();
$ult_id = $professor_id ?: $pdo->lastInsertId();

$usuario_id = nextTableId($pdo, 'usuarios');
if ($usuario_id) {
	$query = $pdo->prepare("INSERT INTO usuarios SET id = :id, nome = :nome, usuario = :email, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Professor', foto = 'sem-perfil.jpg', id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
	$query->bindValue(":id", $usuario_id, PDO::PARAM_INT);
} else {
	$query = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, usuario = :email, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Professor', foto = 'sem-perfil.jpg', id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
}
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":senha", "");
$query->bindValue(":senha_crip", "$senha_crip");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":id_pessoa", "$ult_id");
$query->execute();

}else{
	$professor_tutor_id = $professor_tutor_id ? (int) $professor_tutor_id : 0;
	if (!$professor_tutor_id) {
		echo 'Selecione o responsavel para atendimento!';
		exit();
	}

	$placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));
	$stmtResponsavel = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
	$stmtResponsavel->execute(array_merge([$professor_tutor_id], $allowedLevels));
	$responsavel = $stmtResponsavel->fetch(PDO::FETCH_ASSOC);
	if (!$responsavel) {
		echo 'Responsavel invalido!';
		exit();
	}
	if ($responsavel['nivel'] === 'Vendedor') {
		$stmtVend = $pdo->prepare("SELECT professor, tutor_id FROM vendedores WHERE id = :id");
		$stmtVend->execute([':id' => $responsavel['id_pessoa']]);
		$vend = $stmtVend->fetch(PDO::FETCH_ASSOC);
		if ($vend && (int) $vend['professor'] === 1 && empty($vend['tutor_id'])) {
			echo 'Vendedor sem tutor vinculado!';
			exit();
		}
	}

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

$aluno_id = nextTableId($pdo, 'alunos');
if ($aluno_id) {
	$query = $pdo->prepare("INSERT INTO alunos SET id = :id, nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, foto = 'sem-perfil.jpg', data = curDate(), usuario = :usuario, ativo = 'Sim'");
	$query->bindValue(":id", $aluno_id, PDO::PARAM_INT);
} else {
	$query = $pdo->prepare("INSERT INTO alunos SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, foto = 'sem-perfil.jpg', data = curDate(), usuario = :usuario, ativo = 'Sim'");
}
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":nascimento", "$nascimento");
$query->bindValue(":usuario", "$professor_tutor_id");
$query->execute();
$ult_id = $aluno_id ?: $pdo->lastInsertId();

$usuario_id = nextTableId($pdo, 'usuarios');
if ($usuario_id) {
	$query = $pdo->prepare("INSERT INTO usuarios SET id = :id, nome = :nome, usuario = :email, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Aluno', foto = 'sem-perfil.jpg', id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
	$query->bindValue(":id", $usuario_id, PDO::PARAM_INT);
} else {
	$query = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, usuario = :email, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Aluno', foto = 'sem-perfil.jpg', id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
}
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
