<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../config/upload.php");
require_once(__DIR__ . "/../../../../helpers.php");
$tabela = 'tutores';

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$tabela} LIKE :coluna");
    $stmt->execute([':coluna' => $coluna]);
    return (bool) $stmt->fetchColumn();
}

function garantirColunaComissao(PDO $pdo, string $tabela, string $coluna, string $ddl): void
{
    if (colunaExiste($pdo, $tabela, $coluna)) {
        return;
    }

    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN {$coluna} {$ddl}");
    } catch (Throwable $e) {
        // Mantem compatibilidade com bancos sem permissao de ALTER.
    }
}

function valorComissao($valor): ?float
{
    if (!isset($valor)) {
        return null;
    }

    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    return (float) str_replace(',', '.', $valor);
}

function comissaoPadraoTutor(PDO $pdo): float
{
    $stmt = $pdo->prepare("SELECT porcentagem FROM comissoes WHERE nivel = 'Tutor' LIMIT 1");
    $stmt->execute();
    $valor = $stmt->fetchColumn();
    return $valor !== false ? (float) $valor : 0.0;
}

function comissaoPadraoTutorOutros(PDO $pdo): float
{
    $stmt = $pdo->query("SELECT comissao_tutor FROM config LIMIT 1");
    $valor = $stmt ? $stmt->fetchColumn() : 0;
    return (float) ($valor ?: 0);
}

$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$cpf = $_POST['cpf'];
$nascimento = $_POST['nascimento'] ?? '';
$id = $_POST['id'];
$wallet_id = $_POST['wallet_id'];

$comissao_legado = valorComissao($_POST['comissao'] ?? null);
$comissao_meus_alunos = valorComissao($_POST['comissao_meus_alunos'] ?? null);
$comissao_outros_alunos = valorComissao($_POST['comissao_outros_alunos'] ?? null);

if ($comissao_meus_alunos === null && $comissao_legado !== null) {
    $comissao_meus_alunos = $comissao_legado;
}

$senha = birthDigits($nascimento);
if (trim($cpf) === '' || trim($nascimento) === '') {
    echo 'CPF e data de nascimento são obrigatórios!';
    exit();
}
if ($senha === '') {
    echo 'Data de nascimento invalida!';
    exit();
}
$senha_crip = md5($senha);

$comissao_padrao_tutor = comissaoPadraoTutor($pdo);
$comissao_padrao_outros = comissaoPadraoTutorOutros($pdo);

garantirColunaComissao($pdo, $tabela, 'comissao_meus_alunos', "DECIMAL(10,2) NULL DEFAULT NULL AFTER comissao");
garantirColunaComissao($pdo, $tabela, 'comissao_outros_alunos', "DECIMAL(10,2) NULL DEFAULT NULL AFTER comissao_meus_alunos");
$tem_coluna_meus = colunaExiste($pdo, $tabela, 'comissao_meus_alunos');
$tem_coluna_outros = colunaExiste($pdo, $tabela, 'comissao_outros_alunos');

// validar email duplicado
$query = $pdo->prepare("SELECT * FROM $tabela WHERE email = :email");
$query->execute([':email' => $email]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'Email ja cadastrado, escolha outro!';
    exit();
}

// validar cpf duplicado
$query = $pdo->prepare("SELECT * FROM $tabela WHERE cpf = :cpf");
$query->execute([':cpf' => $cpf]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'CPF ja cadastrado, escolha outro!';
    exit();
}

$registroAtual = null;
$query = $pdo->prepare("SELECT * FROM $tabela WHERE id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0) {
    $registroAtual = $res[0];
    $foto = $res[0]['foto'];
} else {
    $foto = 'sem-perfil.jpg';
}

if ($registroAtual) {
    if ($comissao_meus_alunos === null) {
        if ($tem_coluna_meus && isset($registroAtual['comissao_meus_alunos']) && $registroAtual['comissao_meus_alunos'] !== null) {
            $comissao_meus_alunos = (float) $registroAtual['comissao_meus_alunos'];
        } elseif (isset($registroAtual['comissao']) && $registroAtual['comissao'] !== null) {
            $comissao_meus_alunos = (float) $registroAtual['comissao'];
        }
    }

    if ($comissao_outros_alunos === null) {
        if ($tem_coluna_outros && isset($registroAtual['comissao_outros_alunos']) && $registroAtual['comissao_outros_alunos'] !== null) {
            $comissao_outros_alunos = (float) $registroAtual['comissao_outros_alunos'];
        }
    }
}

if ($comissao_meus_alunos === null) {
    $comissao_meus_alunos = $comissao_padrao_tutor;
}
if ($comissao_outros_alunos === null) {
    $comissao_outros_alunos = $comissao_padrao_outros;
}
$comissao = $comissao_meus_alunos;

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
    if ($foto != 'sem-perfil.jpg') {
        @unlink($destDir . '/' . $foto);
    }
    $foto = $upload['filename'];
}

if ($id == "") {
    $tutor_id = nextTableId($pdo, $tabela);

    $sqlInsert = "INSERT INTO $tabela SET ";
    if ($tutor_id) {
        $sqlInsert .= "id = :id, ";
    }
    $sqlInsert .= "nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, comissao = :comissao, foto = :foto, ativo = 'Sim', data = curDate()";
    if ($tem_coluna_meus) {
        $sqlInsert .= ", comissao_meus_alunos = :comissao_meus_alunos";
    }
    if ($tem_coluna_outros) {
        $sqlInsert .= ", comissao_outros_alunos = :comissao_outros_alunos";
    }

    $query = $pdo->prepare($sqlInsert);
    if ($tutor_id) {
        $query->bindValue(":id", $tutor_id, PDO::PARAM_INT);
    }
    $query->bindValue(":nome", $nome);
    $query->bindValue(":email", $email);
    $query->bindValue(":telefone", $telefone);
    $query->bindValue(":comissao", $comissao);
    $query->bindValue(":cpf", $cpf);
    $query->bindValue(":nascimento", $nascimento);
    $query->bindValue(":foto", $foto);
    if ($tem_coluna_meus) {
        $query->bindValue(":comissao_meus_alunos", $comissao_meus_alunos);
    }
    if ($tem_coluna_outros) {
        $query->bindValue(":comissao_outros_alunos", $comissao_outros_alunos);
    }
    $query->execute();

    $ult_id = $tutor_id ?: $pdo->lastInsertId();

    $usuario_id = nextTableId($pdo, 'usuarios');
    if ($usuario_id) {
        $query = $pdo->prepare("INSERT INTO usuarios SET id = :id, wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Tutor', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
        $query->bindValue(":id", $usuario_id, PDO::PARAM_INT);
    } else {
        $query = $pdo->prepare("INSERT INTO usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, senha = '', cpf = :cpf, senha_crip = :senha_crip, nivel = 'Tutor', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
    }

    $query->bindValue(":nome", $nome);
    $query->bindValue(":email", $email);
    $query->bindValue(":cpf", $cpf);
    $query->bindValue(":wallet_id", $wallet_id);
    $query->bindValue(":senha_crip", $senha_crip);
    $query->bindValue(":foto", $foto);
    $query->bindValue(":id_pessoa", $ult_id);
    $query->execute();
} else {
    $sqlUpdate = "UPDATE $tabela SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, comissao = :comissao, foto = :foto";
    if ($tem_coluna_meus) {
        $sqlUpdate .= ", comissao_meus_alunos = :comissao_meus_alunos";
    }
    if ($tem_coluna_outros) {
        $sqlUpdate .= ", comissao_outros_alunos = :comissao_outros_alunos";
    }
    $sqlUpdate .= " WHERE id = :id";

    $query = $pdo->prepare($sqlUpdate);
    $query->bindValue(":nome", $nome);
    $query->bindValue(":email", $email);
    $query->bindValue(":telefone", $telefone);
    $query->bindValue(":comissao", $comissao);
    $query->bindValue(":cpf", $cpf);
    $query->bindValue(":nascimento", $nascimento);
    $query->bindValue(":foto", $foto);
    if ($tem_coluna_meus) {
        $query->bindValue(":comissao_meus_alunos", $comissao_meus_alunos);
    }
    if ($tem_coluna_outros) {
        $query->bindValue(":comissao_outros_alunos", $comissao_outros_alunos);
    }
    $query->bindValue(":id", $id);
    $query->execute();

    $query = $pdo->prepare("UPDATE usuarios SET wallet_id = :wallet_id, nome = :nome, usuario = :email, cpf = :cpf, foto = :foto WHERE id_pessoa = :id_pessoa AND nivel = 'Tutor'");
    $query->bindValue(":nome", $nome);
    $query->bindValue(":email", $email);
    $query->bindValue(":cpf", $cpf);
    $query->bindValue(":wallet_id", $wallet_id);
    $query->bindValue(":foto", $foto);
    $query->bindValue(":id_pessoa", $id);
    $query->execute();
}

echo 'Salvo com Sucesso';
?>
