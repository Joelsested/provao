<?php
require_once(__DIR__ . '/../config/env.php');
require_once(__DIR__ . '/../config/csrf.php');

session_cache_limiter('private');
session_cache_expire(120);

$cookieParams = session_get_cookie_params();
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 0) == 443);
$cookieDomain = function_exists('csrf_cookie_domain') ? csrf_cookie_domain() : '';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $cookieDomain !== '' ? $cookieDomain : ($cookieParams['domain'] ?? ''),
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
    echo "<script>window.alert('Informe o CPF/usuario e a data de nascimento!')</script>";
    echo "<script>window.location='index.php'</script>";
    exit();
}

function fetchBirth(PDO $pdo, string $nivel, $idPessoa): string
{
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
    $stmt->bindValue(':id', $idPessoa);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['nascimento'] ?? '';
}

$cpfDigits = digitsOnly($usuario);

$query = $pdo->prepare("SELECT * FROM usuarios WHERE (cpf = :usuario OR usuario = :usuario) ORDER BY (nivel = 'Administrador') DESC, id DESC");
$query->bindValue(':usuario', $usuario);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (@count($res) === 0 && $cpfDigits !== '') {
    $cpfColumn = cleanCpfColumn('cpf');
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE {$cpfColumn} = :cpf_digits ORDER BY (nivel = 'Administrador') DESC, id DESC");
    $query->bindValue(':cpf_digits', $cpfDigits);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
}

if (@count($res) === 0) {
    echo "<script>window.alert('Dados Incorretos!')</script>";
    echo "<script>window.location='index.php'</script>";
    exit();
}

$user = null;
$upgradeHash = false;
$inputBirth = normalizeDate($senha);
$hasAtivo = false;
$hasInativo = false;

foreach ($res as $candidate) {
    $ativo = (string) ($candidate['ativo'] ?? '');
    if ($ativo === 'Não' || $ativo === 'NÃ£o') {
        $hasInativo = true;
        continue;
    }
    $hasAtivo = true;

    $nivelCand = (string) ($candidate['nivel'] ?? '');
    $ok = false;
    $candidateUpgrade = false;

    if ($nivelCand === 'Administrador') {
        $storedHash = (string) ($candidate['senha_crip'] ?? '');
        $plainStored = (string) ($candidate['senha'] ?? '');

        if ($storedHash !== '' && preg_match('/^\$(2y|argon2)/', $storedHash)) {
            $ok = password_verify($senha, $storedHash);
            $candidateUpgrade = $ok && password_needs_rehash($storedHash, PASSWORD_DEFAULT);
        } else {
            if ($storedHash !== '' && hash_equals($storedHash, md5($senha))) {
                $ok = true;
            } elseif ($plainStored !== '' && hash_equals($plainStored, $senha)) {
                $ok = true;
            }
            $candidateUpgrade = $ok;
        }
    } else {
        $storedBirth = normalizeDate(fetchBirth($pdo, $nivelCand, $candidate['id_pessoa'] ?? null));
        $ok = ($storedBirth !== '' && $inputBirth !== '' && $storedBirth === $inputBirth);
    }

    if ($ok) {
        $user = $candidate;
        $upgradeHash = $candidateUpgrade;
        break;
    }
}

if (!$user) {
    if (!$hasAtivo && $hasInativo) {
        echo "<script>window.alert('Seu Acesso foi desativado pelo Administrador!')</script>";
        echo "<script>window.location='index.php'</script>";
        exit();
    }
    echo "<script>window.alert('Dados Incorretos!')</script>";
    echo "<script>window.location='index.php'</script>";
    exit();
}

if (($user['nivel'] ?? '') === 'Administrador' && $upgradeHash) {
    try {
        $novoHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha_crip = :hash, senha = '' WHERE id = :id");
        $stmt->execute([':hash' => $novoHash, ':id' => $user['id']]);
        $user['senha_crip'] = $novoHash;
    } catch (Exception $e) {
        // Falha no upgrade do hash nao deve impedir o login.
    }
}

session_regenerate_id(true);

$_SESSION['nivel'] = $user['nivel'];
$_SESSION['cpf'] = $user['cpf'];
$_SESSION['id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];

$id = $user['id'];
$nivel = $user['nivel'];
echo "<script>
localStorage.setItem('id_usu', '$id');
localStorage.setItem('active_user_id', '$id');
localStorage.setItem('active_user_level', '$nivel');
localStorage.setItem('active_user_at', String(Date.now()));
</script>";

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
