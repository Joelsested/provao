<?php
require_once("../conexao.php");
require_once(__DIR__ . "/../../helpers.php");
require_once(__DIR__ . "/../../config/upload.php");
@session_start();

if (!function_exists('perfilColunaExiste')) {
    function perfilColunaExiste(PDO $pdo, string $tabela, string $coluna): bool
    {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND COLUMN_NAME = :coluna LIMIT 1");
            $stmt->execute([':tabela' => $tabela, ':coluna' => $coluna]);
            return (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }
}

$nome = trim($_POST['nome_usu'] ?? '');
$email = trim($_POST['email_usu'] ?? '');
$cpf = trim($_POST['cpf_usu'] ?? '');
$id = (int) ($_POST['id_usu'] ?? 0);
$foto = trim($_POST['foto_usu'] ?? '');
$telefone = trim((string) ($_POST['telefone_usu'] ?? ''));
$nascimento = trim((string) ($_POST['nascimento_usu'] ?? ''));
$senhaInput = trim((string) ($_POST['senha_usu'] ?? ''));

if ($id <= 0 || $nome === '' || $email === '' || $cpf === '') {
    echo 'Dados invalidos para editar perfil.';
    exit();
}

$tableMap = [
    'Administrador' => 'administradores',
    'Professor' => 'professores',
    'Secretario' => 'secretarios',
    'Tesoureiro' => 'tesoureiros',
    'Tutor' => 'tutores',
    'Parceiro' => 'parceiros',
    'Assessor' => 'assessores',
    'Vendedor' => 'vendedores',
];

$stmt = $pdo->prepare("SELECT id, nivel, id_pessoa, senha_crip FROM usuarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo 'Usuario nao encontrado.';
    exit();
}

$nivel = $user['nivel'] ?? '';
$id_pessoa = (int) ($user['id_pessoa'] ?? 0);
$senha_crip = (string) ($user['senha_crip'] ?? '');

if ($nivel === 'Administrador') {
    if ($senhaInput !== '') {
        $senha_crip = password_hash($senhaInput, PASSWORD_DEFAULT);
    } elseif ($senha_crip === '') {
        echo 'Informe a senha para atualizar o perfil do administrador.';
        exit();
    }
} else {
    $nascimentoBase = $nascimento;

    if (
        $nascimentoBase === ''
        && $id_pessoa > 0
        && isset($tableMap[$nivel])
        && perfilColunaExiste($pdo, $tableMap[$nivel], 'nascimento')
    ) {
        $stmtNasc = $pdo->prepare("SELECT nascimento FROM {$tableMap[$nivel]} WHERE id = :id LIMIT 1");
        $stmtNasc->execute([':id' => $id_pessoa]);
        $nascimentoBase = trim((string) ($stmtNasc->fetchColumn() ?: ''));
    }

    $senhaDerivada = birthDigits($nascimentoBase);
    if ($senhaDerivada === '') {
        echo 'Informe data de nascimento valida no formato DD/MM/AAAA.';
        exit();
    }

    $senha_crip = md5($senhaDerivada);
}

// validar email duplicado
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$idExistente = (int) ($stmt->fetchColumn() ?: 0);
if ($idExistente > 0 && $idExistente !== $id) {
    echo 'Email ja Cadastrado, escolha Outro!';
    exit();
}

// validar cpf duplicado
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = :cpf LIMIT 1");
$stmt->execute([':cpf' => $cpf]);
$idExistente = (int) ($stmt->fetchColumn() ?: 0);
if ($idExistente > 0 && $idExistente !== $id) {
    echo 'CPF ja Cadastrado, escolha Outro!';
    exit();
}

// script para subir foto no servidor
$destDir = __DIR__ . '/img/perfil';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['foto'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
    echo $upload['error'];
    exit();
}
if (empty($upload['skipped'])) {
    if ($foto !== '' && $foto !== 'sem-perfil.jpg') {
        @unlink($destDir . '/' . $foto);
    }
    $foto = $upload['filename'];
}

// atualizar os dados do usuario
$stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, senha_crip = :senha_crip, foto = :foto WHERE id = :id");
$stmt->execute([
    ':nome' => $nome,
    ':cpf' => $cpf,
    ':usuario' => $email,
    ':senha' => '',
    ':senha_crip' => $senha_crip,
    ':foto' => $foto,
    ':id' => $id,
]);

// atualizar tabela da pessoa conforme o nivel do usuario
if ($id_pessoa > 0 && isset($tableMap[$nivel])) {
    $tabela = $tableMap[$nivel];
    $sqlPessoa = "UPDATE {$tabela} SET nome = :nome, cpf = :cpf, email = :email, foto = :foto";
    $paramsPessoa = [
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':email' => $email,
        ':foto' => $foto,
        ':id_pessoa' => $id_pessoa,
    ];

    if (array_key_exists('telefone_usu', $_POST) && perfilColunaExiste($pdo, $tabela, 'telefone')) {
        $sqlPessoa .= ", telefone = :telefone";
        $paramsPessoa[':telefone'] = $telefone;
    }

    if (array_key_exists('nascimento_usu', $_POST) && perfilColunaExiste($pdo, $tabela, 'nascimento')) {
        $sqlPessoa .= ", nascimento = :nascimento";
        $paramsPessoa[':nascimento'] = $nascimento;
    }

    $sqlPessoa .= " WHERE id = :id_pessoa";

    $stmt = $pdo->prepare($sqlPessoa);
    $stmt->execute($paramsPessoa);
}

echo 'Editado com Sucesso';

?>