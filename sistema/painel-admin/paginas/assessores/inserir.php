<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../helpers.php");
require_once(__DIR__ . "/../../../config/upload.php");

$tabela = 'assessores';

if (!function_exists('assessoresTemColuna')) {
    function assessoresTemColuna(PDO $pdo, string $tabela, string $coluna): bool
    {
        $tabela = preg_replace('/[^a-zA-Z0-9_]/', '', $tabela);
        $coluna = preg_replace('/[^a-zA-Z0-9_]/', '', $coluna);
        if ($tabela === '' || $coluna === '') {
            return false;
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
            $stmt->execute([':coluna' => $coluna]);
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }
}

$temNascimento = assessoresTemColuna($pdo, $tabela, 'nascimento');
if (!$temNascimento) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN nascimento varchar(12) DEFAULT NULL");
        $temNascimento = true;
    } catch (Exception $e) {
        $temNascimento = assessoresTemColuna($pdo, $tabela, 'nascimento');
    }
}

$nome = trim((string) ($_POST['nome'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$telefone = trim((string) ($_POST['telefone'] ?? ''));
$cpf = trim((string) ($_POST['cpf'] ?? ''));
$nascimento = trim((string) ($_POST['nascimento'] ?? ''));
$id = (int) ($_POST['id'] ?? 0);
$wallet_id = trim((string) ($_POST['wallet_id'] ?? ''));

if ($cpf === '' || $nascimento === '') {
    echo 'CPF e data de nascimento são obrigatórios!';
    exit();
}

$senha = birthDigits($nascimento);
if ($senha === '') {
    echo 'Data de nascimento invalida!';
    exit();
}
$senha_crip = md5($senha);

// validar email duplicado
$stmt = $pdo->prepare("SELECT id FROM {$tabela} WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0 && (int) $res[0]['id'] !== $id) {
    echo 'Email ja cadastrado, escolha outro!';
    exit();
}

// validar cpf duplicado
$stmt = $pdo->prepare("SELECT id FROM {$tabela} WHERE cpf = ? LIMIT 1");
$stmt->execute([$cpf]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0 && (int) $res[0]['id'] !== $id) {
    echo 'CPF ja cadastrado, escolha outro!';
    exit();
}

$foto = 'sem-perfil.jpg';
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT foto FROM {$tabela} WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['foto'])) {
        $foto = $row['foto'];
    }
}

// script para subir foto no servidor
$destDir = __DIR__ . '/../../img/perfil';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['foto'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
    echo $upload['error'];
    exit();
}
if (empty($upload['skipped'])) {
    if ($foto !== 'sem-perfil.jpg') {
        @unlink($destDir . '/' . $foto);
    }
    $foto = $upload['filename'];
}

if ($id === 0) {
    if ($temNascimento) {
        $query = $pdo->prepare("INSERT INTO {$tabela} SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto, ativo = 'Sim', data = curDate()");
        $query->bindValue(':nascimento', $nascimento);
    } else {
        $query = $pdo->prepare("INSERT INTO {$tabela} SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone, foto = :foto, ativo = 'Sim', data = curDate()");
    }
    $query->bindValue(':nome', $nome);
    $query->bindValue(':email', $email);
    $query->bindValue(':telefone', $telefone);
    $query->bindValue(':cpf', $cpf);
    $query->bindValue(':foto', $foto);
    $query->execute();

    $ult_id = (int) $pdo->lastInsertId();

    $query = $pdo->prepare("INSERT INTO usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Assessor', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
    $query->bindValue(':nome', $nome);
    $query->bindValue(':email', $email);
    $query->bindValue(':cpf', $cpf);
    $query->bindValue(':wallet_id', $wallet_id);
    $query->bindValue(':senha_crip', $senha_crip);
    $query->bindValue(':foto', $foto);
    $query->bindValue(':id_pessoa', $ult_id, PDO::PARAM_INT);
    $query->execute();
} else {
    if ($temNascimento) {
        $query = $pdo->prepare("UPDATE {$tabela} SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto WHERE id = :id");
        $query->bindValue(':nascimento', $nascimento);
    } else {
        $query = $pdo->prepare("UPDATE {$tabela} SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone, foto = :foto WHERE id = :id");
    }
    $query->bindValue(':nome', $nome);
    $query->bindValue(':email', $email);
    $query->bindValue(':telefone', $telefone);
    $query->bindValue(':cpf', $cpf);
    $query->bindValue(':foto', $foto);
    $query->bindValue(':id', $id, PDO::PARAM_INT);
    $query->execute();

    $query = $pdo->prepare("UPDATE usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, cpf = :cpf, senha = '', senha_crip = :senha_crip, foto = :foto WHERE id_pessoa = :id_pessoa and nivel = 'Assessor'");
    $query->bindValue(':nome', $nome);
    $query->bindValue(':email', $email);
    $query->bindValue(':cpf', $cpf);
    $query->bindValue(':wallet_id', $wallet_id);
    $query->bindValue(':senha_crip', $senha_crip);
    $query->bindValue(':foto', $foto);
    $query->bindValue(':id_pessoa', $id, PDO::PARAM_INT);
    $query->execute();
}

echo 'Salvo com Sucesso';

?>