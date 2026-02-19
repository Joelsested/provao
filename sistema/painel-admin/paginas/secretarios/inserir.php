<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../config/upload.php");
require_once(__DIR__ . "/../../../../helpers.php");
$tabela = 'secretarios';

function tabelaTemColuna(PDO $pdo, string $tabela, string $coluna): bool
{
    $tabela = preg_replace('/[^a-zA-Z0-9_]/', '', $tabela);
    $coluna = preg_replace('/[^a-zA-Z0-9_]/', '', $coluna);
    if ($tabela === '' || $coluna === '') {
        return false;
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
    $stmt->execute([':coluna' => $coluna]);
    return (bool) $stmt->fetchColumn();
}

$temNascimento = tabelaTemColuna($pdo, $tabela, 'nascimento');
if (!$temNascimento) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN nascimento varchar(12) DEFAULT NULL");
        $temNascimento = true;
    } catch (Exception $e) {
        $temNascimento = false;
    }
}

$temComissaoMeus = tabelaTemColuna($pdo, $tabela, 'comissao_meus_alunos');
if (!$temComissaoMeus) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN comissao_meus_alunos decimal(10,2) NOT NULL DEFAULT 0");
        $temComissaoMeus = true;
    } catch (Exception $e) {
        $temComissaoMeus = false;
    }
}

$temComissaoOutros = tabelaTemColuna($pdo, $tabela, 'comissao_outros_alunos');
if (!$temComissaoOutros) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN comissao_outros_alunos decimal(10,2) NOT NULL DEFAULT 0");
        $temComissaoOutros = true;
    } catch (Exception $e) {
        $temComissaoOutros = false;
    }
}


$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$cpf = $_POST['cpf'];
$nascimento = $_POST['nascimento'] ?? '';
$comissao_meus_alunos = $_POST['comissao_meus_alunos'] ?? '0';
$comissao_outros_alunos = $_POST['comissao_outros_alunos'] ?? '0';
$endereco = $_POST['endereco'];
$cidade = $_POST['cidade'];
$estado = $_POST['estado'];
$sexo = $_POST['sexo'];
$id = $_POST['id'];

$wallet_id = $_POST['wallet_id'];

$comissao_meus_alunos = str_replace(',', '.', $comissao_meus_alunos);
$comissao_outros_alunos = str_replace(',', '.', $comissao_outros_alunos);
if (!is_numeric($comissao_meus_alunos)) {
    $comissao_meus_alunos = 0;
}
if (!is_numeric($comissao_outros_alunos)) {
    $comissao_outros_alunos = 0;
}

$senha = birthDigits($nascimento);
if (trim($cpf) === '' || trim($nascimento) === '') {
    echo 'CPF e data de nascimento são obrigatórios!';
    exit();
}
if ($senha === '') {
    echo 'Data de nascimento inválida!';
    exit();
}
$senha_crip = md5($senha);

//validar email duplicado
$stmt = $pdo->prepare("SELECT id FROM $tabela WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'Email já Cadastrado, escolha Outro!';
    exit();
}


//validar cpf duplicado
$stmt = $pdo->prepare("SELECT id FROM $tabela WHERE cpf = ? LIMIT 1");
$stmt->execute([$cpf]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'CPF já Cadastrado, escolha Outro!';
    exit();
}


$stmt = $pdo->prepare("SELECT foto FROM $tabela WHERE id = ? LIMIT 1");
$stmt->execute([(int) $id]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    $camposExtras = [];
    if ($temComissaoMeus) {
        $camposExtras[] = "comissao_meus_alunos = :comissao_meus_alunos";
    }
    if ($temComissaoOutros) {
        $camposExtras[] = "comissao_outros_alunos = :comissao_outros_alunos";
    }
    $camposExtrasSql = $camposExtras ? (', ' . implode(', ', $camposExtras)) : '';

    if ($temNascimento) {
        $query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, endereco = :endereco,  cidade = :cidade, estado = :estado, sexo = :sexo, foto = :foto{$camposExtrasSql}, ativo = 'Sim', data = curDate()");
    } else {
        $query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone, endereco = :endereco,  cidade = :cidade, estado = :estado, sexo = :sexo, foto = :foto{$camposExtrasSql}, ativo = 'Sim', data = curDate()");
    }
    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":telefone", "$telefone");
    $query->bindValue(":cpf", "$cpf");
    if ($temNascimento) {
        $query->bindValue(":nascimento", "$nascimento");
    }
    if ($temComissaoMeus) {
        $query->bindValue(":comissao_meus_alunos", $comissao_meus_alunos);
    }
    if ($temComissaoOutros) {
        $query->bindValue(":comissao_outros_alunos", $comissao_outros_alunos);
    }
    $query->bindValue(":endereco", "$endereco");
    $query->bindValue(":cidade", "$cidade");
    $query->bindValue(":estado", "$estado");
    $query->bindValue(":sexo", "$sexo");
    $query->bindValue(":foto", "$foto");
    $query->execute();
    $ult_id = $pdo->lastInsertId();

    $query = $pdo->prepare("INSERT INTO usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Secretario',  foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");

    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":wallet_id", "$wallet_id");
    $query->bindValue(":senha_crip", "$senha_crip");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id_pessoa", (int) $ult_id, PDO::PARAM_INT);
    $query->execute();

} else {
    $camposExtras = [];
    if ($temComissaoMeus) {
        $camposExtras[] = "comissao_meus_alunos = :comissao_meus_alunos";
    }
    if ($temComissaoOutros) {
        $camposExtras[] = "comissao_outros_alunos = :comissao_outros_alunos";
    }
    $camposExtrasSql = $camposExtras ? (', ' . implode(', ', $camposExtras)) : '';

    if ($temNascimento) {
        $query = $pdo->prepare("UPDATE $tabela SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, endereco = :endereco,  cidade = :cidade, estado = :estado, sexo = :sexo, foto = :foto{$camposExtrasSql} WHERE id = :id");
    } else {
        $query = $pdo->prepare("UPDATE $tabela SET nome = :nome, email = :email, cpf = :cpf, telefone = :telefone, endereco = :endereco,  cidade = :cidade, estado = :estado, sexo = :sexo, foto = :foto{$camposExtrasSql} WHERE id = :id");
    }
    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":telefone", "$telefone");
    $query->bindValue(":cpf", "$cpf");
    if ($temNascimento) {
        $query->bindValue(":nascimento", "$nascimento");
    }
    if ($temComissaoMeus) {
        $query->bindValue(":comissao_meus_alunos", $comissao_meus_alunos);
    }
    if ($temComissaoOutros) {
        $query->bindValue(":comissao_outros_alunos", $comissao_outros_alunos);
    }
    $query->bindValue(":endereco", "$endereco");
    $query->bindValue(":cidade", "$cidade");
    $query->bindValue(":estado", "$estado");
    $query->bindValue(":sexo", "$sexo");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id", (int) $id, PDO::PARAM_INT);
    $query->execute();

    $query = $pdo->prepare("UPDATE usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, cpf = :cpf, foto = :foto WHERE id_pessoa = :id_pessoa and nivel = 'Secretario'");

    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":wallet_id", "$wallet_id");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id_pessoa", (int) $id, PDO::PARAM_INT);
    $query->execute();
}


echo 'Salvo com Sucesso';

?>

