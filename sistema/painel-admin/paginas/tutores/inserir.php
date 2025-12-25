<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
$tabela = 'tutores';

$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$cpf = $_POST['cpf'];
$id = $_POST['id'];
$comissao = $_POST['comissao'] ?? null;
$wallet_id = $_POST['wallet_id'];

$senha = '123456';
$senha_crip = md5($senha);

//validar email duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where email = :email");
$query->execute([':email' => $email]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'Email já Cadastrado, escolha Outro!';
    exit();
}


//validar cpf duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where cpf = :cpf");
$query->execute([':cpf' => $cpf]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'CPF já Cadastrado, escolha Outro!';
    exit();
}


$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0) {
    $foto = $res[0]['foto'];
} else {
    $foto = 'sem-perfil.jpg';
}


//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$destDir = __DIR__ . '/../../img/perfil';
$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['foto'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', true);
if (!$upload['ok']) {
	echo $upload['error'];
	exit();
}
if (empty($upload['skipped'])) {
	if ($foto != 'sem-perfil.jpg') {
		@unlink($destDir . '/' . $foto);
	}
	$foto = $upload['filename'];
}


if ($id == "") {

    $comissao = isset($_POST['comissao']) && $_POST['comissao'] !== '' ? $_POST['comissao'] : null;

    // Se comissão for null, buscar na tabela comissao
    if ($comissao === null) {
        $query = $pdo->prepare("SELECT porcentagem FROM comissoes WHERE nivel = 'Tutor' LIMIT 1");
        $query->execute();
        $resultado = $query->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $comissao = $resultado['porcentagem'];
        } else {
            $comissao = 0; // Definir um valor padrão caso nada seja encontrado
        }
    }

    $query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone, comissao = :comissao, foto = :foto, ativo = 'Sim', data = curDate()");
    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":telefone", "$telefone");
    $query->bindValue(":comissao", $comissao);
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":foto", "$foto");
    $query->execute();
    $ult_id = $pdo->lastInsertId();

    $query = $pdo->prepare("INSERT INTO usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Tutor', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");

    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":wallet_id", "$wallet_id");
    $query->bindValue(":senha_crip", "$senha_crip");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id_pessoa", "$ult_id");
    $query->execute();
} else {

    // Se comissão for null, buscar na tabela comissao
    if ($comissao === null) {
        $query = $pdo->prepare("SELECT porcentagem FROM comissoes WHERE nivel = 'Tutor' LIMIT 1");
        $query->execute();
        $resultado = $query->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $comissao = $resultado['porcentagem'];
        } else {
            $comissao = 0; // Definir um valor padrão caso nada seja encontrado
        }
    }
    $query = $pdo->prepare("UPDATE $tabela SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone, comissao = :comissao, foto = :foto WHERE id = :id");
    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":telefone", "$telefone");
    $query->bindValue(":comissao", $comissao);
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id", "$id");
    $query->execute();
    $ult_id = $pdo->lastInsertId();

    $query = $pdo->prepare("UPDATE usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, cpf = :cpf, foto = :foto WHERE id_pessoa = :id_pessoa and nivel = 'Tutor'");

    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":wallet_id", "$wallet_id");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id_pessoa", "$id");
    $query->execute();
}


echo 'Salvo com Sucesso';
