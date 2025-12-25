<?php
session_cache_limiter('private');
$cache_limiter = session_cache_limiter();

/* define o prazo do cache em 120 minutos */
session_cache_expire(120);
$cache_expire = session_cache_expire();
/* inicia a sessão */
@session_start();

require_once('../../sistema/conexao.php');
require_once(__DIR__ . '/../../helpers.php');

$usuario = trim($_POST['usuario'] ?? '');
$senha = trim($_POST['senha'] ?? '');

function fetchBirth(PDO $pdo, $idPessoa): string {
	if (empty($idPessoa)) {
		return '';
	}
	$stmt = $pdo->prepare("SELECT nascimento FROM alunos WHERE id = :id");
	$stmt->bindValue(":id", "$idPessoa");
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row['nascimento'] ?? '';
}

if ($usuario === '' || $senha === '') {
	echo 'Usuário ou senha não informados!';
	exit();
}

$cpfDigits = digitsOnly($usuario);

$query = $pdo->prepare("SELECT * FROM usuarios where (cpf = :usuario or usuario = :usuario)");
$query->bindValue(":usuario", "$usuario");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) == 0 && $cpfDigits !== '') {
	$cpfColumn = cleanCpfColumn('cpf');
	$query = $pdo->prepare("SELECT * FROM usuarios WHERE $cpfColumn = :cpf_digits");
	$query->bindValue(":cpf_digits", "$cpfDigits");
	$query->execute();
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
}
if (@count($res) == 0) {
	echo 'Usuário não Cadastrado com este email ou CPF inserido!';
	exit();
}

$user = $res[0];

$storedBirth = normalizeDate(fetchBirth($pdo, $user['id_pessoa']));
$inputBirth = normalizeDate($senha);

if ($storedBirth === '' || $inputBirth === '' || $storedBirth !== $inputBirth) {
	echo "Senha Incorreta!!";
	exit();
}

if ($user['ativo'] == 'Não') {
	echo "Seu Acesso foi desativado pelo Administrador!";
	exit();
}

$_SESSION['nivel'] = $user['nivel'];
$_SESSION['cpf'] = $user['cpf'];
$_SESSION['id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];

if ($_SESSION['nivel'] == 'Aluno') {
	echo "Logado com Sucesso-" . $_SESSION['id'];
}
?>


