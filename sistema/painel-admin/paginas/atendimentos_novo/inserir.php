<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../config/upload.php");
require_once(__DIR__ . '/../../../../helpers.php');
@session_start();

$id_user = (int) ($_SESSION['id'] ?? 0);
$tabela = 'alunos';
$hasResponsavelCol = ensureAlunosResponsavelColumn($pdo);

$id = post_value(['id'], '');
$currentAluno = [];
if ($id !== '') {
    $stmtAtualAluno = $pdo->prepare("SELECT * FROM $tabela WHERE id = :id LIMIT 1");
    $stmtAtualAluno->execute([':id' => $id]);
    $currentAluno = $stmtAtualAluno->fetch(PDO::FETCH_ASSOC) ?: [];
}

$nome = post_value(['nome'], $currentAluno['nome'] ?? '');
$cpf = post_value(['cpf'], $currentAluno['cpf'] ?? '');
$cpfDigits = digitsOnly($cpf);
$email = post_value(['email'], $currentAluno['email'] ?? '');
$rg = post_value(['rg', 'rg_usu'], $currentAluno['rg'] ?? '');
$orgao_expedidor = post_value(['orgao_expedidor', 'expedidor_usu'], $currentAluno['orgao_expedidor'] ?? '');
$expedicao = post_value(['expedicao', 'expedicao_usu'], $currentAluno['expedicao'] ?? '');
$telefone = post_value(['telefone'], $currentAluno['telefone'] ?? '');
$cep = post_value(['cep', 'cep_usu'], $currentAluno['cep'] ?? '');
$endereco = post_value(['endereco', 'endereco_usu'], $currentAluno['endereco'] ?? '');
$numero = post_value(['numero', 'numero_usu'], $currentAluno['numero'] ?? '');
$bairro = post_value(['bairro', 'bairro_usu'], $currentAluno['bairro'] ?? '');
$cidade = post_value(['cidade', 'cidade_usu'], $currentAluno['cidade'] ?? '');
$estado = post_value(['estado', 'estado_usu'], $currentAluno['estado'] ?? '');
$sexo = post_value(['sexo', 'sexo_usu'], $currentAluno['sexo'] ?? '');
$nascimento = post_value(['nascimento', 'nascimento_usu'], $currentAluno['nascimento'] ?? '');
$mae = post_value(['mae', 'mae_usu'], $currentAluno['mae'] ?? '');
$pai = post_value(['pai', 'pai_usu'], $currentAluno['pai'] ?? '');
$naturalidade = post_value(['naturalidade', 'naturalidade_usu'], $currentAluno['naturalidade'] ?? '');
$responsavelId = filter_input(INPUT_POST, 'responsavel_id', FILTER_VALIDATE_INT);
$allowedLevels = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro', 'Parceiro'];
$allowedAtendenteLevels = ['Tutor', 'Secretario'];
$userNivel = $_SESSION['nivel'] ?? '';

$currentAtendenteId = (int) ($currentAluno['usuario'] ?? 0);
$currentResponsavelId = (int) ($currentAluno['responsavel_id'] ?? 0);
if ($currentResponsavelId <= 0) {
    $currentResponsavelId = $currentAtendenteId;
}

if (trim($cpf) === '' || trim($nascimento) === '') {
    echo 'CPF e data de nascimento são obrigatórios!';
    exit();
}
if ($cpfDigits === '') {
    echo 'CPF invalido!';
    exit();
}

if (!$responsavelId && $id === '' && in_array($userNivel, $allowedLevels, true)) {
    $responsavelId = $id_user;
}
if (!$responsavelId && $currentResponsavelId > 0) {
    $responsavelId = $currentResponsavelId;
}
if (!$responsavelId) {
    echo 'Selecione o responsável.';
    exit();
}

$placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));
$stmtResp = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
$stmtResp->execute(array_merge([(int) $responsavelId], $allowedLevels));
$responsavel = $stmtResp->fetch(PDO::FETCH_ASSOC);
if (!$responsavel) {
    echo 'Responsavel invalido.';
    exit();
}

$responsavelProfessor = responsavelEhProfessor($pdo, $responsavel);
if ($responsavel['nivel'] === 'Vendedor') {
    $hasTutorId = false;
    $hasSecretarioId = false;
    try {
        $stmtTutorCol = $pdo->query("SHOW COLUMNS FROM vendedores LIKE 'tutor_id'");
        $hasTutorId = (bool) ($stmtTutorCol && $stmtTutorCol->fetch(PDO::FETCH_ASSOC));
        $stmtSecretarioCol = $pdo->query("SHOW COLUMNS FROM vendedores LIKE 'secretario_id'");
        $hasSecretarioId = (bool) ($stmtSecretarioCol && $stmtSecretarioCol->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        $hasTutorId = false;
        $hasSecretarioId = false;
    }

    $vendSql = ($hasTutorId || $hasSecretarioId)
        ? "SELECT professor, tutor_id, secretario_id FROM vendedores WHERE id = :id"
        : "SELECT professor FROM vendedores WHERE id = :id";
    $stmtVend = $pdo->prepare($vendSql);
    $stmtVend->execute([':id' => $responsavel['id_pessoa']]);
    $vend = $stmtVend->fetch(PDO::FETCH_ASSOC);
    if ($vend && (int) $vend['professor'] === 1
        && ($hasTutorId || $hasSecretarioId)
        && empty($vend['tutor_id'])
        && empty($vend['secretario_id'])) {
        echo 'Vendedor sem atendente vinculado.';
        exit();
    }
}

$dataReferencia = normalizeDate((string) ($currentAluno['data'] ?? '')) ?: date('Y-m-d');
$usuarioDestino = $responsavelProfessor
    ? resolveAtendenteId($pdo, $responsavel, $dataReferencia)
    : (int) $responsavelId;

if ($responsavelProfessor) {
    if ((int) $usuarioDestino <= 0) {
        echo 'Responsavel com Professor marcado exige atendente ativo (Tutor ou Secretario).';
        exit();
    }
    $stmtNivelDest = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
    $stmtNivelDest->execute([':id' => (int) $usuarioDestino]);
    $nivelDestino = (string) ($stmtNivelDest->fetchColumn() ?: '');
    if (!in_array($nivelDestino, $allowedAtendenteLevels, true)) {
        echo 'Atendente invalido para responsavel com Professor marcado. Use Tutor ou Secretario ativo.';
        exit();
    }
}

if ($id !== '') {
    $atendenteNivelOk = '';
    if ($currentAtendenteId > 0) {
        $stmtNivelAt = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id LIMIT 1");
        $stmtNivelAt->execute([':id' => $currentAtendenteId]);
        $atendenteNivelOk = (string) ($stmtNivelAt->fetchColumn() ?: '');
    }

    if ($responsavelProfessor && ($currentAtendenteId > 0 && in_array($atendenteNivelOk, $allowedAtendenteLevels, true))) {
        $usuarioDestino = $currentAtendenteId;
    }

    $responsavelMudou = (int) $responsavelId !== (int) $currentResponsavelId;
    $atendenteMudou = (int) $usuarioDestino > 0 && (int) $usuarioDestino !== (int) $currentAtendenteId;
    if ($responsavelMudou || $atendenteMudou) {
        $bloqueioTroca = podeTrocarAtendente($pdo, (int) $id, (int) $usuarioDestino, date('Y-m-d'));
        if (!empty($bloqueioTroca['bloqueado'])) {
            echo (string) ($bloqueioTroca['mensagem'] ?? 'Troca bloqueada por regra de comissao.');
            exit();
        }
    }
}

$senha = birthDigits($nascimento);
if ($senha === '') {
    echo 'Data de nascimento invalida!';
    exit();
}
$senha_crip = md5($senha);

$query = $pdo->prepare("SELECT * FROM $tabela where email = :email");
$query->execute([':email' => $email]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'Email ja cadastrado, escolha outro!';
    exit();
}

$cpfColumn = cleanCpfColumn('cpf');
$query = $pdo->prepare("SELECT * FROM $tabela where $cpfColumn = :cpf_digits");
$query->execute([':cpf_digits' => $cpfDigits]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 and $res[0]['id'] != $id) {
    echo 'CPF ja cadastrado, escolha outro!';
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

$destDir = __DIR__ . '/../../../painel-aluno/img/perfil';
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

if ($id == '') {
    $aluno_id = nextTableId($pdo, $tabela);
    $insertAlunoSql = "INSERT INTO $tabela SET ";
    if ($aluno_id) {
        $insertAlunoSql .= "id = :id, ";
    }
    $insertAlunoSql .= "nome = :nome, cpf = :cpf, email = :email, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, telefone = :telefone, cep = :cep, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, sexo = :sexo, nascimento = :nascimento, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, ativo = 'Sim', usuario = :usuario, data = curDate()";
    if ($hasResponsavelCol) {
        $insertAlunoSql .= ", responsavel_id = :responsavel_id";
    }
    $query = $pdo->prepare($insertAlunoSql);
    $alunoParams = [
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':email' => $email,
        ':rg' => $rg,
        ':orgao_expedidor' => $orgao_expedidor,
        ':expedicao' => $expedicao,
        ':telefone' => $telefone,
        ':cep' => $cep,
        ':endereco' => $endereco,
        ':numero' => $numero,
        ':bairro' => $bairro,
        ':cidade' => $cidade,
        ':estado' => $estado,
        ':sexo' => $sexo,
        ':nascimento' => $nascimento,
        ':mae' => $mae,
        ':pai' => $pai,
        ':naturalidade' => $naturalidade,
        ':foto' => $foto,
        ':usuario' => (int) $usuarioDestino,
    ];
    if ($aluno_id) {
        $alunoParams[':id'] = $aluno_id;
    }
    if ($hasResponsavelCol) {
        $alunoParams[':responsavel_id'] = (int) $responsavelId;
    }
    $query->execute($alunoParams);
    $aluno_id_final = $aluno_id ?: $pdo->lastInsertId();

    $usuario_id = nextTableId($pdo, 'usuarios');
    $usuarioParams = [
        ':nome' => $nome,
        ':email' => $email,
        ':cpf' => $cpf,
        ':senha' => '',
        ':senha_crip' => $senha_crip,
        ':foto' => $foto,
        ':id_pessoa' => $aluno_id_final,
    ];
    if ($usuario_id) {
        $query = $pdo->prepare("INSERT INTO usuarios SET id = :id, nome = :nome, usuario = :email, senha = :senha, cpf = :cpf, senha_crip = :senha_crip, nivel = 'Aluno', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
        $usuarioParams[':id'] = $usuario_id;
    } else {
        $query = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, usuario = :email, senha = :senha, cpf = :cpf, senha_crip = :senha_crip, nivel = 'Aluno', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
    }
    $query->execute($usuarioParams);

    registrarHistoricoAtendente(
        $pdo,
        (int) $aluno_id_final,
        null,
        (int) $usuarioDestino,
        'Cadastro inicial',
        'cadastro',
        (int) ($_SESSION['id'] ?? 0)
    );
} else {
    $updateSql = "UPDATE $tabela SET nome = :nome, cpf = :cpf, email = :email, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, telefone = :telefone, cep = :cep, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, sexo = :sexo, nascimento = :nascimento, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, usuario = :usuario";
    if ($hasResponsavelCol) {
        $updateSql .= ", responsavel_id = :responsavel_id";
    }
    $updateSql .= " WHERE id = :id";
    $query = $pdo->prepare($updateSql);
    $paramsUpdate = [
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':email' => $email,
        ':rg' => $rg,
        ':orgao_expedidor' => $orgao_expedidor,
        ':expedicao' => $expedicao,
        ':telefone' => $telefone,
        ':cep' => $cep,
        ':endereco' => $endereco,
        ':numero' => $numero,
        ':bairro' => $bairro,
        ':cidade' => $cidade,
        ':estado' => $estado,
        ':sexo' => $sexo,
        ':nascimento' => $nascimento,
        ':mae' => $mae,
        ':pai' => $pai,
        ':naturalidade' => $naturalidade,
        ':foto' => $foto,
        ':usuario' => (int) $usuarioDestino,
        ':id' => $id,
    ];
    if ($hasResponsavelCol) {
        $paramsUpdate[':responsavel_id'] = (int) $responsavelId;
    }
    $query->execute($paramsUpdate);

    $query = $pdo->prepare("UPDATE usuarios SET nome = :nome, usuario = :email, cpf = :cpf, senha = :senha, senha_crip = :senha_crip, foto = :foto WHERE id_pessoa = :id_pessoa and nivel = 'Aluno'");
    $query->execute([
        ':nome' => $nome,
        ':cpf' => $cpf,
        ':email' => $email,
        ':senha' => '',
        ':senha_crip' => $senha_crip,
        ':foto' => $foto,
        ':id_pessoa' => $id,
    ]);

    if ($currentAtendenteId > 0 && $currentAtendenteId !== (int) $usuarioDestino) {
        registrarHistoricoAtendente(
            $pdo,
            (int) $id,
            (int) $currentAtendenteId,
            (int) $usuarioDestino,
            'Ajuste por edicao',
            'edicao',
            (int) ($_SESSION['id'] ?? 0)
        );
    }
}

echo 'Salvo com Sucesso';


