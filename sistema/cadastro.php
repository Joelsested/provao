<?php 
require_once("conexao.php");
require_once(__DIR__ . '/../helpers.php');
@session_start();
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$nascimento = trim($_POST['nascimento'] ?? '');
$cpfDigits = digitsOnly($cpf);
$tipo = $_POST['tipo'] ?? '';
$professor_tutor_id = filter_input(INPUT_POST, 'professor_tutor_id', FILTER_VALIDATE_INT);
$allowedLevels = ['Tutor', 'Vendedor', 'Secretario', 'Tesoureiro'];

if ($nome === '' || $email === '' || $cpf === '' || $nascimento === '') {
	echo 'Informe nome, email, CPF e data de nascimento para concluir o cadastro!';
	exit();
}
if ($cpfDigits === '') {
	echo 'Informe um CPF valido para concluir o cadastro!';
	exit();
}
$senha = birthDigits($nascimento);
if ($senha === '') {
	echo 'Informe uma data de nascimento valida!';
	exit();
}
$senha_crip = md5($senha);

$stmtUsuarioEmail = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :email LIMIT 1");
$stmtUsuarioEmail->execute([':email' => $email]);
if ($stmtUsuarioEmail->fetchColumn()) {
	echo 'Este email ja esta cadastrado, escolha outro ou recupere sua senha!';
	exit();
}
$cpfColumn = cleanCpfColumn('cpf');
$query = $pdo->prepare("SELECT * FROM usuarios where $cpfColumn = :cpf_digits");
$query->bindValue(":cpf_digits", "$cpfDigits");
$query->execute();
$dupeCpf = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($dupeCpf) > 0) {
	echo 'Este CPF ja esta cadastrado, informe outro CPF ou recupere o acesso!';
	exit();
}

if($tipo == 'Professor'){

	//CADASTRO DO PROFESSOR
	$query = $pdo->prepare("SELECT * FROM professores where email = :email");
$query->bindValue(":email", "$email");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	echo 'Este email ja esta cadastrado, escolha outro ou recupere sua senha!';
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
	$responsavelProfessor = responsavelEhProfessor($pdo, $responsavel);
	$usuarioDestino = resolveAtendenteId($pdo, $responsavel, date('Y-m-d'));
	if ($responsavelProfessor) {
		if ($usuarioDestino <= 0) {
			echo 'Responsavel com Professor marcado exige atendente ativo (Tutor ou Secretario).';
			exit();
		}
		$stmtNivelDest = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
		$stmtNivelDest->execute([':id' => (int) $usuarioDestino]);
		$nivelDestino = (string) ($stmtNivelDest->fetchColumn() ?: '');
		if (!in_array($nivelDestino, ['Tutor', 'Secretario'], true)) {
			echo 'Atendente invalido para responsavel com Professor marcado.';
			exit();
		}
	}
	if ($responsavel['nivel'] === 'Vendedor') {
		$stmtVend = $pdo->prepare("SELECT professor, tutor_id, secretario_id FROM vendedores WHERE id = :id");
		$stmtVend->execute([':id' => $responsavel['id_pessoa']]);
		$vend = $stmtVend->fetch(PDO::FETCH_ASSOC);
		if ($vend && (int) $vend['professor'] === 1 && empty($vend['tutor_id']) && empty($vend['secretario_id'])) {
			echo 'Vendedor sem atendente vinculado!';
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
	echo 'Este email ja esta cadastrado, escolha outro ou recupere sua senha!';
	exit();
}

$hasResponsavelCol = ensureAlunosResponsavelColumn($pdo);
$aluno_id = nextTableId($pdo, 'alunos');
$insertAlunoSql = "INSERT INTO alunos SET ";
if ($aluno_id) {
	$insertAlunoSql .= "id = :id, ";
}
$insertAlunoSql .= "nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, foto = 'sem-perfil.jpg', data = curDate(), usuario = :usuario, ativo = 'Sim'";
if ($hasResponsavelCol) {
	$insertAlunoSql .= ", responsavel_id = :responsavel_id";
}
$query = $pdo->prepare($insertAlunoSql);
$paramsAluno = [
	':nome' => $nome,
	':email' => $email,
	':cpf' => $cpf,
	':nascimento' => $nascimento,
	':usuario' => $usuarioDestino,
];
if ($aluno_id) {
	$paramsAluno[':id'] = $aluno_id;
}
if ($hasResponsavelCol) {
	$paramsAluno[':responsavel_id'] = (int) $professor_tutor_id;
}
$query->execute($paramsAluno);
$ult_id = $aluno_id ?: $pdo->lastInsertId();

registrarHistoricoAtendente(
	$pdo,
	(int) $ult_id,
	null,
	(int) $usuarioDestino,
	'Cadastro via site',
	'cadastro_site',
	null
);

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


