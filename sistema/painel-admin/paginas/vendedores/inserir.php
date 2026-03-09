<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../config/upload.php");
require_once(__DIR__ . "/../../../../helpers.php");
@session_start();
$tabela = 'vendedores';

if (($_SESSION['nivel'] ?? '') === 'Aluno') {
    echo 'Aluno nao pode cadastrar vendedor.';
    exit();
}

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

$temSecretarioId = tabelaTemColuna($pdo, $tabela, 'secretario_id');
if (!$temSecretarioId) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN secretario_id int(11) DEFAULT NULL");
    } catch (Exception $e) {
        // sem bloqueio
    }
    // Revalida para ambientes sem permissao de ALTER TABLE
    $temSecretarioId = tabelaTemColuna($pdo, $tabela, 'secretario_id');
}

$temPodeLoginAluno = tabelaTemColuna($pdo, $tabela, 'pode_login_como_aluno');
if (!$temPodeLoginAluno) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN pode_login_como_aluno TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Exception $e) {
        // sem bloqueio
    }
    // Revalida para ambientes sem permissao de ALTER TABLE
    $temPodeLoginAluno = tabelaTemColuna($pdo, $tabela, 'pode_login_como_aluno');
}

$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$cpf = $_POST['cpf'];
$nascimento = $_POST['nascimento'] ?? '';
$id = $_POST['id'];
$comissao = $_POST['comissao'] ?? null;
// $professor = $_POST['professor'] ?? 0;
$wallet_id = $_POST['wallet_id'];
$atendente_raw = trim($_POST['atendente'] ?? '');
$tutor_id = null;
$secretario_id = null;
if ($atendente_raw !== '') {
    if (strpos($atendente_raw, 'tutor:') === 0) {
        $tutor_id = (int) substr($atendente_raw, 6);
    } elseif (strpos($atendente_raw, 'secretario:') === 0) {
        $secretario_id = (int) substr($atendente_raw, 11);
    } else {
        echo 'Atendente inválido.';
        exit();
    }
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

if (isset($_POST['professor'])) {
    $professor = 1;
} else {
    $professor = 0;
}
$pode_login_como_aluno = isset($_POST['pode_login_como_aluno']) ? 1 : 0;
if ($professor && empty($tutor_id) && empty($secretario_id)) {
    echo 'Selecione o atendente.';
    exit();
}
if ($professor && $tutor_id) {
    $stmtTutor = $pdo->prepare("SELECT id FROM tutores WHERE id = :id AND ativo = 'Sim' LIMIT 1");
    $stmtTutor->execute([':id' => $tutor_id]);
    if (!$stmtTutor->fetchColumn()) {
        echo 'Tutor atendente inválido.';
        exit();
    }
}
if ($professor && $secretario_id) {
    $stmtSecretario = $pdo->prepare("SELECT id FROM secretarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
    $stmtSecretario->execute([':id' => $secretario_id]);
    if (!$stmtSecretario->fetchColumn()) {
        echo 'Secretário atendente inválido.';
        exit();
    }
}
if (!$professor) {
    $tutor_id = null;
    $secretario_id = null;
}

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
        $query = $pdo->prepare("SELECT porcentagem FROM comissoes WHERE nivel IN ('Vendedor','Vendedores') LIMIT 1");
        $query->execute();
        $resultado = $query->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $comissao = $resultado['porcentagem'];
        } else {
            $comissao = 0; // Definir um valor padrão caso nada seja encontrado
        }
    }

    $secretarioFieldSql = $temSecretarioId ? ", secretario_id = :secretario_id" : "";
    $podeLoginAlunoSql = $temPodeLoginAluno ? ", pode_login_como_aluno = :pode_login_como_aluno" : "";
    $vendedor_id = nextTableId($pdo, $tabela);
    if ($vendedor_id) {
        $query = $pdo->prepare("INSERT INTO $tabela SET id = :id, nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, comissao = :comissao, professor = :professor, tutor_id = :tutor_id{$secretarioFieldSql}{$podeLoginAlunoSql}, foto = :foto, ativo = 'Sim', data = curDate()");
        $query->bindValue(":id", $vendedor_id, PDO::PARAM_INT);
    } else {
        $query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, comissao = :comissao, professor = :professor, tutor_id = :tutor_id{$secretarioFieldSql}{$podeLoginAlunoSql}, foto = :foto, ativo = 'Sim', data = curDate()");
    }
    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":telefone", "$telefone");
    $query->bindValue(":comissao", $comissao);
    $query->bindValue(":professor", $professor);
    $query->bindValue(":tutor_id", $tutor_id, $tutor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    if ($temSecretarioId) {
        $query->bindValue(":secretario_id", $secretario_id, $secretario_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }
    if ($temPodeLoginAluno) {
        $query->bindValue(":pode_login_como_aluno", $pode_login_como_aluno, PDO::PARAM_INT);
    }
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":nascimento", "$nascimento");
    $query->bindValue(":foto", "$foto");
    $query->execute();
    $ult_id = $vendedor_id ?: $pdo->lastInsertId();

    $usuario_id = nextTableId($pdo, 'usuarios');
    if ($usuario_id) {
        $query = $pdo->prepare("INSERT INTO usuarios SET id = :id, wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Vendedor', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
        $query->bindValue(":id", $usuario_id, PDO::PARAM_INT);
    } else {
        $query = $pdo->prepare("INSERT INTO usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Vendedor', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
    }

    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":wallet_id", "$wallet_id");
    $query->bindValue(":senha_crip", "$senha_crip");
    $query->bindValue(":foto", "$foto");
$query->bindValue(":id_pessoa", "$ult_id");
$query->execute();
$usuarioVendedorId = $usuario_id ?: (int) $pdo->lastInsertId();
if ($usuarioVendedorId > 0) {
    if (function_exists('tentarVinculoVendedorAlunoPorCpf')) {
        tentarVinculoVendedorAlunoPorCpf($pdo, $cpf);
    }
}
} else {

    // Se comissão for null, buscar na tabela comissao
    if ($comissao === null) {
        $query = $pdo->prepare("SELECT porcentagem FROM comissoes WHERE nivel IN ('Vendedor','Vendedores') LIMIT 1");
        $query->execute();
        $resultado = $query->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $comissao = $resultado['porcentagem'];
        } else {
            $comissao = 0; // Definir um valor padrão caso nada seja encontrado
        }
    }
    $secretarioFieldSql = $temSecretarioId ? ", secretario_id = :secretario_id" : "";
    $podeLoginAlunoSql = $temPodeLoginAluno ? ", pode_login_como_aluno = :pode_login_como_aluno" : "";
    $query = $pdo->prepare("UPDATE $tabela SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, comissao = :comissao, professor = :professor, tutor_id = :tutor_id{$secretarioFieldSql}{$podeLoginAlunoSql}, foto = :foto WHERE id = :id");
    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":telefone", "$telefone");
    $query->bindValue(":comissao", $comissao);
    $query->bindValue(":professor", $professor);
    $query->bindValue(":tutor_id", $tutor_id, $tutor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    if ($temSecretarioId) {
        $query->bindValue(":secretario_id", $secretario_id, $secretario_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }
    if ($temPodeLoginAluno) {
        $query->bindValue(":pode_login_como_aluno", $pode_login_como_aluno, PDO::PARAM_INT);
    }
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":nascimento", "$nascimento");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id", "$id");
    $query->execute();
    $ult_id = $pdo->lastInsertId();

    $query = $pdo->prepare("UPDATE usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, cpf = :cpf, foto = :foto WHERE id_pessoa = :id_pessoa and nivel = 'Vendedor'");

    $query->bindValue(":nome", "$nome");
    $query->bindValue(":email", "$email");
    $query->bindValue(":cpf", "$cpf");
    $query->bindValue(":wallet_id", "$wallet_id");
    $query->bindValue(":foto", "$foto");
    $query->bindValue(":id_pessoa", "$id");
    $query->execute();
    if (function_exists('tentarVinculoVendedorAlunoPorCpf')) {
        tentarVinculoVendedorAlunoPorCpf($pdo, $cpf);
    }
}


echo 'Salvo com Sucesso';


