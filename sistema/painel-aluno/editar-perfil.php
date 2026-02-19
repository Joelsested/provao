<?php 
require_once("../conexao.php");
require_once(__DIR__ . "/../../config/upload.php");
require_once(__DIR__ . "/../../helpers.php");
@session_start();

$nome = trim($_POST['nome_usu'] ?? '');
$cpf = trim($_POST['cpf_usu'] ?? '');
$email = trim($_POST['email_usu'] ?? '');
$id = (int) ($_POST['id_usu'] ?? 0);
$foto = $_POST['foto_usu'] ?? '';

$rg = trim($_POST['rg_usu'] ?? '');
$orgao_expedidor = trim($_POST['expedidor_usu'] ?? '');
$expedicao = trim($_POST['expedicao_usu'] ?? '');
$nascimento = trim($_POST['nascimento_usu'] ?? '');
$telefone = trim($_POST['telefone_usu'] ?? '');
$cep = trim($_POST['cep_usu'] ?? '');
$sexo = trim($_POST['sexo_usu'] ?? '');
$endereco = trim($_POST['endereco_usu'] ?? '');
$numero = trim($_POST['numero_usu'] ?? '');
$bairro = trim($_POST['bairro_usu'] ?? '');
$cidade = trim($_POST['cidade_usu'] ?? '');
$estado = trim($_POST['estado_usu'] ?? '');
$mae = trim($_POST['mae_usu'] ?? '');
$pai = trim($_POST['pai_usu'] ?? '');
$naturalidade = trim($_POST['naturalidade_usu'] ?? '');

$usuarioVendedorSwitchId = (int) ($_SESSION['switch_vendedor_usuario_id'] ?? 0);
$idPessoaVendedorSwitch = 0;
if ($usuarioVendedorSwitchId > 0) {
    $stmtVendSwitch = $pdo->prepare("SELECT id, id_pessoa, nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
    $stmtVendSwitch->execute([':id' => $usuarioVendedorSwitchId]);
    $vendSwitch = $stmtVendSwitch->fetch(PDO::FETCH_ASSOC) ?: [];
    if (($vendSwitch['nivel'] ?? '') === 'Vendedor' && ($vendSwitch['ativo'] ?? '') === 'Sim') {
        $idPessoaVendedorSwitch = (int) ($vendSwitch['id_pessoa'] ?? 0);
    } else {
        $usuarioVendedorSwitchId = 0;
    }
}

if ($nome === '') {
    echo 'Informe o nome.';
    exit();
}
if ($cpf === '') {
    echo 'Informe o CPF.';
    exit();
}
if ($email === '') {
    echo 'Informe o email.';
    exit();
}
if ($telefone === '') {
    echo 'Informe o telefone.';
    exit();
}
if ($nascimento === '') {
    echo 'Informe a data de nascimento.';
    exit();
}
$cpfDigits = digitsOnly($cpf);
if ($cpfDigits === '') {
    echo 'CPF invalido!';
    exit();
}

$senha = birthDigits($nascimento);
if ($senha === '') {
    echo 'Data de nascimento invalida!';
    exit();
}
$senha_crip = md5($senha);

$stmtUser = $pdo->prepare("SELECT id, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];
if (!$user) {
    echo 'Usuario nao encontrado.';
    exit();
}
$id_pessoa = (int) ($user['id_pessoa'] ?? 0);

$stmtEmail = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id <> :id");
$stmtEmail->execute([':usuario' => $email, ':id' => $id]);
$idsEmail = array_map('intval', $stmtEmail->fetchAll(PDO::FETCH_COLUMN) ?: []);
foreach ($idsEmail as $idDup) {
    if ($usuarioVendedorSwitchId > 0 && $idDup === $usuarioVendedorSwitchId) {
        continue;
    }
    echo 'Email ja Cadastrado, escolha Outro!';
    exit();
}

$cpfColumn = cleanCpfColumn('cpf');
$stmtCpf = $pdo->prepare("SELECT id FROM usuarios WHERE {$cpfColumn} = :cpf_digits AND id <> :id");
$stmtCpf->execute([':cpf_digits' => $cpfDigits, ':id' => $id]);
$idsCpf = array_map('intval', $stmtCpf->fetchAll(PDO::FETCH_COLUMN) ?: []);
foreach ($idsCpf as $idDup) {
    if ($usuarioVendedorSwitchId > 0 && $idDup === $usuarioVendedorSwitchId) {
        continue;
    }
    echo 'CPF ja Cadastrado, escolha Outro!';
    exit();
}

$destDir = __DIR__ . '/img/perfil';
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

try {
    $pdo->beginTransaction();

    $query = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, senha_crip = :senha_crip, foto = :foto WHERE id = :id");
    $query->execute([
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':usuario' => $email,
        ':senha' => '',
        ':senha_crip' => $senha_crip,
        ':foto' => $foto,
        ':id' => $id,
    ]);

    if ($id_pessoa <= 0) {
        throw new Exception('Cadastro de aluno invalido.');
    }

    $stmtAluno = $pdo->prepare("SELECT id FROM alunos WHERE id = :id LIMIT 1");
    $stmtAluno->execute([':id' => $id_pessoa]);
    $alunoExiste = (bool) $stmtAluno->fetchColumn();

    if ($alunoExiste) {
        $query = $pdo->prepare("UPDATE alunos SET nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, nascimento = :nascimento, cep = :cep, sexo = :sexo, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, mae = :mae, pai = :pai, naturalidade = :naturalidade WHERE id = :id");
        $paramsAluno = [
            ':id' => $id_pessoa,
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':email' => $email,
            ':telefone' => $telefone,
            ':rg' => $rg,
            ':orgao_expedidor' => $orgao_expedidor,
            ':expedicao' => $expedicao,
            ':nascimento' => $nascimento,
            ':cep' => $cep,
            ':sexo' => $sexo,
            ':endereco' => $endereco,
            ':numero' => $numero,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':mae' => $mae,
            ':pai' => $pai,
            ':naturalidade' => $naturalidade,
        ];
        $query->execute($paramsAluno);
    } else {
        $query = $pdo->prepare("INSERT INTO alunos SET id = :id, nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, nascimento = :nascimento, cep = :cep, sexo = :sexo, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, usuario = :usuario, ativo = 'Sim', data = curDate()");
        $paramsAluno = [
            ':id' => $id_pessoa,
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':email' => $email,
            ':telefone' => $telefone,
            ':rg' => $rg,
            ':orgao_expedidor' => $orgao_expedidor,
            ':expedicao' => $expedicao,
            ':nascimento' => $nascimento,
            ':cep' => $cep,
            ':sexo' => $sexo,
            ':endereco' => $endereco,
            ':numero' => $numero,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':mae' => $mae,
            ':pai' => $pai,
            ':naturalidade' => $naturalidade,
            ':foto' => $foto,
            ':usuario' => $id,
        ];
        $query->execute($paramsAluno);
    }

    if ($usuarioVendedorSwitchId > 0 && $idPessoaVendedorSwitch > 0) {
        $stmtSyncVend = $pdo->prepare("UPDATE vendedores SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto WHERE id = :id");
        $stmtSyncVend->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':cpf' => $cpf,
            ':nascimento' => $nascimento,
            ':telefone' => $telefone,
            ':foto' => $foto,
            ':id' => $idPessoaVendedorSwitch,
        ]);

        // Mantem login do vendedor intacto para nao quebrar autenticacao.
        $stmtSyncUsuarioVend = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, foto = :foto WHERE id = :id AND nivel = 'Vendedor'");
        $stmtSyncUsuarioVend->execute([
            ':nome' => $nome,
            ':cpf' => $cpf,
            ':foto' => $foto,
            ':id' => $usuarioVendedorSwitchId,
        ]);
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo 'Nao foi possivel salvar o cadastro. Tente novamente.';
    exit();
}

echo 'Editado com Sucesso';

?>
