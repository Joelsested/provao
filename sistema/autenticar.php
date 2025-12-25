<?php
session_cache_limiter('private');
$cache_limiter = session_cache_limiter();

/* define o prazo do cache em 120 minutos */
session_cache_expire(120);
$cache_expire = session_cache_expire();
/* inicia a sessão */
$cookieParams = session_get_cookie_params();
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) == 443;
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
@session_start();

require_once('conexao.php');
require_once(__DIR__ . '/../helpers.php');

$usuario = trim($_POST['usuario'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if ($usuario === '' || $senha === '') {
	echo "<script>window.alert('Informe o CPF/usuário e a data de nascimento!')</script>";
	echo "<script>window.location='index.php'</script>";
	exit();
}

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

$cpfDigits = digitsOnly($usuario);

$query = $pdo->prepare("SELECT * FROM usuarios where (cpf = :usuario or usuario = :usuario)");
$query->bindValue(":usuario", "$usuario");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) === 0 && $cpfDigits !== '') {
	$cpfColumn = cleanCpfColumn('cpf');
	$query = $pdo->prepare("SELECT * FROM usuarios WHERE $cpfColumn = :cpf_digits");
	$query->bindValue(":cpf_digits", "$cpfDigits");
	$query->execute();
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
}
if (@count($res) === 0) {
	echo "<script>window.alert('Dados Incorretos!')</script>";
	echo "<script>window.location='index.php'</script>";
	exit();
}

$user = $res[0];

if ($user['ativo'] === 'Não') {
	echo "<script>window.alert('Seu Acesso foi desativado pelo Administrador!')</script>";
	echo "<script>window.location='index.php'</script>";
	exit();
}

if ($user['nivel'] === 'Administrador') {
    $storedHash = $user['senha_crip'] ?? '';
    $plainStored = $user['senha'] ?? '';
    $validPassword = false;
    $needsUpgrade = false;

    if ($storedHash !== '' && preg_match('/^\$(2y|argon2)/', $storedHash)) {
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
        echo "<script>window.alert('Dados Incorretos!')</script>";
        echo "<script>window.location='index.php'</script>";
        exit();
    }

    if ($needsUpgrade) {
        $novoHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha_crip = :hash, senha = '' WHERE id = :id");
        $stmt->execute([':hash' => $novoHash, ':id' => $user['id']]);
        $user['senha_crip'] = $novoHash;
    }
} else {
	$storedBirth = normalizeDate(fetchBirth($pdo, $user['id_pessoa']));
	$inputBirth = normalizeDate($senha);
	if ($storedBirth === '' || $inputBirth === '' || $storedBirth !== $inputBirth) {
		echo "<script>window.alert('Dados Incorretos!')</script>";
		echo "<script>window.location='index.php'</script>";
		exit();
	}
}

session_regenerate_id(true);

//recuperar o nível do usuário
$_SESSION['nivel'] = $user['nivel'];
$_SESSION['cpf'] = $user['cpf'];
$_SESSION['id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];

$id = $user['id'];
echo "<script>localStorage.setItem('id_usu', '$id')</script>";

if ($_SESSION['nivel'] == 'Administrador') {
	echo "<script>window.location='painel-admin'</script>";
}

if ($_SESSION['nivel'] == 'Tesoureiro') {
	echo "<script>window.location='painel-admin'</script>";
}


if ($_SESSION['nivel'] == 'Secretario') {
	echo "<script>window.location='painel-admin'</script>";
}

if ($_SESSION['nivel'] == 'Professor') {
	echo "<script>window.location='painel-admin'</script>";
}

if ($_SESSION['nivel'] == 'Aluno') {
	echo "<script>window.location='painel-aluno'</script>";
}

if ($_SESSION['nivel'] == 'Tutor') {
	echo "<script>window.location='painel-admin'</script>";
}

if ($_SESSION['nivel'] == 'Parceiro') {
	echo "<script>window.location='painel-admin'</script>";
}

if ($_SESSION['nivel'] == 'Assessor') {
	echo "<script>window.location='painel-admin'</script>";
}

if ($_SESSION['nivel'] == 'Vendedor') {
	echo "<script>window.location='painel-admin'</script>";
}
 ?>
