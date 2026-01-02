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

function fetchBirth(PDO $pdo, string $nivel, $idPessoa): string {
	if (empty($idPessoa)) {
		return '';
	}
	$map = [
		'Aluno' => 'alunos',
		'Vendedor' => 'vendedores',
		'Tutor' => 'tutores',
		'Parceiro' => 'parceiros',
		'Secretario' => 'secretarios',
		'Tesoureiro' => 'tesoureiros',
		'Professor' => 'professores',
	];
	if (!isset($map[$nivel])) {
		return '';
	}
	$tabela = $map[$nivel];
	$stmt = $pdo->prepare("SELECT nascimento FROM {$tabela} WHERE id = :id");
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

if ($user['ativo'] == 'Não') {
	echo "Seu Acesso foi desativado pelo Administrador!";
	exit();
}

if ($user['nivel'] === 'Administrador') {
	$storedHash = $user['senha_crip'] ?? '';
	$plainStored = $user['senha'] ?? '';
	$validPassword = false;
	$needsUpgrade = false;

	if ($storedHash !== '' && preg_match('/^\\$(2y|argon2)/', $storedHash)) {
		$validPassword = password_verify($senha, $storedHash);
		$needsUpgrade = $validPassword && password_needs_rehash($storedHash, PASSWORD_DEFAULT);
	} else {
		if ($storedHash !== '' && hash_equals($storedHash, md5($senha))) {
			$validPassword = true;
		} elseif ($plainStored !== '' && hash_equals($plainStored, $senha)) {
			$validPassword = true;
		}
		$needsUpgrade = $validPassword;
	}

	if (!$validPassword) {
		echo "Senha Incorreta!!";
		exit();
	}

	if ($needsUpgrade) {
		$novoHash = password_hash($senha, PASSWORD_DEFAULT);
		$stmt = $pdo->prepare("UPDATE usuarios SET senha_crip = :hash, senha = '' WHERE id = :id");
		$stmt->execute([':hash' => $novoHash, ':id' => $user['id']]);
		$user['senha_crip'] = $novoHash;
	}
} else {
	$storedBirth = normalizeDate(fetchBirth($pdo, $user['nivel'], $user['id_pessoa']));
	$inputBirth = normalizeDate($senha);

	if ($storedBirth === '' || $inputBirth === '' || $storedBirth !== $inputBirth) {
		echo "Senha Incorreta!!";
		exit();
	}
}

$_SESSION['nivel'] = $user['nivel'];
$_SESSION['cpf'] = $user['cpf'];
$_SESSION['id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];

if ($_SESSION['nivel'] == 'Aluno') {
	echo "Logado com Sucesso-" . $_SESSION['id'];
}
?>


