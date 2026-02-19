<?php 
include_once('../conexao.php');
require_once(__DIR__ . '/../../helpers.php');

$postjson = json_decode(file_get_contents("php://input"), true);
$usuario = trim($postjson['email'] ?? '');
$senha = trim($postjson['senha'] ?? '');

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
	echo json_encode(['success' => false, 'resultado' => 'Informe cpf/email e data de nascimento!']);
	exit();
}

$cpfDigits = digitsOnly($usuario);

$query_buscar = $pdo->prepare("SELECT * from usuarios where (usuario = :usuario or cpf = :usuario)");
$query_buscar->bindValue(":usuario", "$usuario");
$query_buscar->execute();
$dados_buscar = $query_buscar->fetchAll(PDO::FETCH_ASSOC);

if (@count($dados_buscar) === 0 && $cpfDigits !== '') {
	$cpfColumn = cleanCpfColumn('cpf');
	$query_buscar = $pdo->prepare("SELECT * from usuarios where $cpfColumn = :cpf_digits");
	$query_buscar->bindValue(":cpf_digits", "$cpfDigits");
	$query_buscar->execute();
	$dados_buscar = $query_buscar->fetchAll(PDO::FETCH_ASSOC);
}

if (@count($dados_buscar) === 0) {
	echo json_encode(['success' => false, 'resultado' => 'Dados Incorretos!']);
	exit();
}

$user = $dados_buscar[0];
$nivel = $user['nivel'];

if (($user['ativo'] ?? '') === 'Não') {
	echo json_encode(['success' => false, 'resultado' => 'Seu Acesso foi desativado pelo Administrador!']);
	exit();
}

if ($nivel === 'Administrador') {
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
        echo json_encode(['success' => false, 'resultado' => 'Dados Incorretos!']);
        exit();
    }

    if ($needsUpgrade) {
        $novoHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha_crip = :hash, senha = '' WHERE id = :id");
        $stmt->execute([':hash' => $novoHash, ':id' => $user['id']]);
        $user['senha_crip'] = $novoHash;
    }
} else {
	$storedBirth = normalizeDate(fetchBirth($pdo, $nivel, $user['id_pessoa']));
	$inputBirth = normalizeDate($senha);
	if ($storedBirth === '' || $inputBirth === '' || $storedBirth !== $inputBirth) {
		echo json_encode(['success' => false, 'resultado' => 'Dados Incorretos!']);
		exit();
	}
}

$dados = [
	[
		'id' => intval($user['id']),
		'nome' => $user['nome'],
		'email' => $user['usuario'],
		'nivel' => $nivel,
	],
];

echo json_encode(['success' => true, 'resultado' => $dados]);
?>
