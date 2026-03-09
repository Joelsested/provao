<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../config/upload.php");
require_once(__DIR__ . '/../../../../helpers.php');
@session_start();

// $id_user = @$_SESSION['id'];
$id_user = isset($_SESSION['id']) ? $_SESSION['id'] : 573;
$tabela = 'alunos';


if (@$_SESSION['nivel'] == 'Aluno') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

foreach ($_POST as $key => $value) {
    $_POST[$key] = addslashes(trim($value));
}

// echo '<pre>';

// echo json_encode($_POST, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;

$id = post_value(['id'], '');
$currentAluno = [];
if ($id !== '') {
    $stmtAtualAluno = $pdo->prepare("SELECT * FROM $tabela WHERE id = :id LIMIT 1");
    $stmtAtualAluno->execute([':id' => $id]);
    $currentAluno = $stmtAtualAluno->fetch(PDO::FETCH_ASSOC) ?: [];
}

$hasTransferCol = tableHasColumn($pdo, $tabela, 'data_transferencia_atendente');
if (!tableHasColumn($pdo, $tabela, 'responsavel_id')) {
	try {
		$pdo->exec("ALTER TABLE {$tabela} ADD COLUMN responsavel_id int(11) DEFAULT NULL");
	} catch (Exception $e) {
		// sem bloqueio
	}
}
$hasResponsavelCol = tableHasColumn($pdo, $tabela, 'responsavel_id');
if (!$hasTransferCol) {
	try {
		$pdo->exec("ALTER TABLE {$tabela} ADD COLUMN data_transferencia_atendente DATE DEFAULT NULL");
	} catch (Exception $e) {
		// sem bloqueio
	}
	$hasTransferCol = tableHasColumn($pdo, $tabela, 'data_transferencia_atendente');
}

$nome = post_value(['nome'], $currentAluno['nome'] ?? '');
$cpf = post_value(['cpf'], $currentAluno['cpf'] ?? '');
$cpfDigits = digitsOnly($cpf);
$email = post_value(['email'], $currentAluno['email'] ?? '');
$emailNorm = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $email), 'UTF-8') : strtolower(trim((string) $email));
$currentEmailNorm = function_exists('mb_strtolower') ? mb_strtolower(trim((string) ($currentAluno['email'] ?? '')), 'UTF-8') : strtolower(trim((string) ($currentAluno['email'] ?? '')));
$emailUnchanged = ($id !== '' && $emailNorm !== '' && $emailNorm === $currentEmailNorm);
$telefone = post_value(['telefone'], $currentAluno['telefone'] ?? '');
$rg = post_value(['rg', 'rg_usu'], $currentAluno['rg'] ?? '');
$orgao_expedidor = post_value(['orgao_expedidor', 'expedidor_usu'], $currentAluno['orgao_expedidor'] ?? '');
$expedicao = post_value(['expedicao', 'expedicao_usu'], $currentAluno['expedicao'] ?? '');
$nascimento = post_value(['nascimento', 'nascimento_usu'], $currentAluno['nascimento'] ?? '');
$nascimento = normalizeDate($nascimento);
$cep = post_value(['cep', 'cep_usu'], $currentAluno['cep'] ?? '');
$sexo = post_value(['sexo', 'sexo_usu'], $currentAluno['sexo'] ?? '');
$endereco = post_value(['endereco', 'endereco_usu'], $currentAluno['endereco'] ?? '');
$numero = post_value(['numero', 'numero_usu'], $currentAluno['numero'] ?? '');
$bairro = post_value(['bairro', 'bairro_usu'], $currentAluno['bairro'] ?? '');
$cidade = post_value(['cidade', 'cidade_usu'], $currentAluno['cidade'] ?? '');
$estado = post_value(['estado', 'estado_usu'], $currentAluno['estado'] ?? '');
$mae = post_value(['mae', 'mae_usu'], $currentAluno['mae'] ?? '');
$pai = post_value(['pai', 'pai_usu'], $currentAluno['pai'] ?? '');
$naturalidade = post_value(['naturalidade', 'naturalidade_usu'], $currentAluno['naturalidade'] ?? '');

$responsavelId = filter_input(INPUT_POST, 'responsavel_id', FILTER_VALIDATE_INT);
if (!$responsavelId) {
    $respFallback = post_value(['responsavel_id'], '');
    $responsavelId = $respFallback !== '' ? (int) $respFallback : null;
}
$transferirResponsavelId = filter_input(INPUT_POST, 'transferir_responsavel_id', FILTER_VALIDATE_INT);
$dataTransferenciaAtendente = post_value(['data_transferencia_atendente'], '');
$dataTransferenciaNorm = normalizeDate($dataTransferenciaAtendente);
$isAdmin = ($_SESSION['nivel'] ?? '') === 'Administrador';
$isSecretario = ($_SESSION['nivel'] ?? '') === 'Secretario';
$adminOverrideTroca = $isAdmin && getConfigAdminOverrideTrocaAtendente($pdo);
$allowedLevels = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro'];
$userNivel = $_SESSION['nivel'] ?? '';
$allowedAtendenteLevels = ['Tutor', 'Secretario'];
$currentResponsavelId = null;
$currentResponsavelCadastro = null;
$currentAtendenteNivel = '';
$atendenteMudouEdicao = false;

if ($id !== "") {
    $stmtAtual = $pdo->prepare("SELECT usuario, responsavel_id FROM $tabela WHERE id = :id");
    $stmtAtual->execute([':id' => $id]);
    $currentRow = $stmtAtual->fetch(PDO::FETCH_ASSOC) ?: [];
    $currentResponsavelId = (int) ($currentRow['usuario'] ?? 0);
    $currentResponsavelCadastro = isset($currentRow['responsavel_id']) ? (int) $currentRow['responsavel_id'] : null;

    if ($currentResponsavelId) {
        $stmtNivelAt = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id LIMIT 1");
        $stmtNivelAt->execute([':id' => $currentResponsavelId]);
        $currentAtendenteNivel = (string) ($stmtNivelAt->fetchColumn() ?: '');
    }
}

if (trim($nome) === '') {
    echo 'Informe o nome.';
    exit();
}
if (trim($cpf) === '') {
    echo 'Informe o CPF.';
    exit();
}
if (trim($email) === '') {
    echo 'Informe o email.';
    exit();
}
if (trim($telefone) === '') {
    echo 'Informe o telefone.';
    exit();
}
if (trim($nascimento) === '') {
    echo 'Informe a data de nascimento.';
    exit();
}

$hojeRef = new DateTimeImmutable('today');
try {
    $nascimentoObj = new DateTimeImmutable($nascimento);
} catch (Throwable $e) {
    echo 'Data de nascimento inválida.';
    exit();
}
if ($nascimentoObj > $hojeRef) {
    echo 'Data de nascimento inválida.';
    exit();
}
$idadeAluno = (int) $nascimentoObj->diff($hojeRef)->y;
if ($idadeAluno < 18) {
    echo 'Aluno menor de idade. Só admin pode liberar matrículas para alunos menores.';
    exit();
}

if ($cpfDigits === '') {
    echo 'CPF invalido!';
    exit();
}
if ($id === "" && !$responsavelId && in_array($userNivel, $allowedLevels, true)) {
    $responsavelId = (int) $id_user;
}
if ($id !== "" && !($isAdmin || $isSecretario)) {
    // usuarios comuns nao alteram responsavel/atendente
    $responsavelId = $currentResponsavelCadastro ?: $currentResponsavelId;
}
if (!$responsavelId) {
    echo 'Selecione o responsável.';
    exit();
}

$responsavelSelecionadoId = (int) $responsavelId;
$atendenteId = $currentResponsavelId;
if ($id === "") {
    $atendenteId = null;
}
if (($isAdmin || $isSecretario) && $id !== "" && $transferirResponsavelId) {
    $atendenteId = (int) $transferirResponsavelId;
}

$placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));
$stmtResp = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
$stmtResp->execute(array_merge([$responsavelSelecionadoId], $allowedLevels));
$responsavel = $stmtResp->fetch(PDO::FETCH_ASSOC);
if (!$responsavel) {
    echo 'Responsavel invalido.';
    exit();
}
$nivelResponsavelSelecionado = (string) ($responsavel['nivel'] ?? '');
if (in_array($nivelResponsavelSelecionado, ['Tutor', 'Secretario'], true) && (int) ($responsavel['id'] ?? 0) !== (int) $id_user) {
	echo 'Atendente so pode ser responsavel quando faz a propria matricula.';
	exit();
}
if ($id === "") {
    $dataMatriculaRef = date('Y-m-d');
    $atendenteId = resolveAtendenteId($pdo, $responsavel, $dataMatriculaRef);
} elseif (!($isAdmin || $isSecretario) || !$transferirResponsavelId) {
    $dataMatriculaRef = $currentAluno['data'] ?? date('Y-m-d');
    $atendenteNivelOk = $currentAtendenteNivel !== '' && in_array($currentAtendenteNivel, $allowedAtendenteLevels, true);
    if (!$atendenteNivelOk || $currentResponsavelId <= 0) {
        $atendenteCalculado = resolveAtendenteId($pdo, $responsavel, $dataMatriculaRef);
        if ($atendenteCalculado === $responsavelSelecionadoId && in_array($responsavel['nivel'], ['Vendedor', 'Parceiro'], true)) {
            $atendenteCalculado = resolveAtendenteId($pdo, $responsavel, null);
        }
        $atendenteId = $atendenteCalculado;
    }
}
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

$responsavelProfessor = responsavelEhProfessor($pdo, $responsavel);
if ($responsavelProfessor) {
	if ((int) $atendenteId <= 0) {
		echo 'Responsavel com Professor marcado exige atendente ativo (Tutor ou Secretario).';
		exit();
	}
	$stmtNivelDest = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
	$stmtNivelDest->execute([':id' => (int) $atendenteId]);
	$nivelDestino = (string) ($stmtNivelDest->fetchColumn() ?: '');
	if (!in_array($nivelDestino, $allowedAtendenteLevels, true)) {
		echo 'Atendente invalido para responsavel com Professor marcado. Use Tutor ou Secretario ativo.';
		exit();
	}
}

if ($id !== "") {
	$atendenteAtual = (int) ($currentResponsavelId ?? 0);
	$atendenteNovo = (int) ($atendenteId ?? 0);
	$atendenteMudouEdicao = ($atendenteNovo > 0 && $atendenteNovo !== $atendenteAtual);

	if ($atendenteMudouEdicao) {
		if (!$responsavelProfessor && !$adminOverrideTroca) {
			echo 'Responsavel sem Professor nao permite trocar atendente.';
			exit();
		}

		if ($dataTransferenciaAtendente !== '' && $dataTransferenciaNorm === '') {
			echo 'Data de transferencia invalida.';
			exit();
		}

		$dataTransferenciaNorm = $dataTransferenciaNorm !== '' ? $dataTransferenciaNorm : date('Y-m-d');
		$dataMatricula = normalizeDate((string) ($currentAluno['data'] ?? ''));
		if ($dataMatricula !== '' && $dataTransferenciaNorm < $dataMatricula) {
			echo 'Data de transferencia nao pode ser anterior a data de matricula.';
			exit();
		}

		if (!$adminOverrideTroca) {
			$bloqueioTroca = podeTrocarAtendente($pdo, (int) $id, $atendenteNovo, $dataTransferenciaNorm);
			if (!empty($bloqueioTroca['bloqueado'])) {
				echo (string) ($bloqueioTroca['mensagem'] ?? 'Troca bloqueada por regra de comissao.');
				exit();
			}
		}
	}
}

$senha = birthDigits($nascimento);
if ($senha === '') {
    echo 'Data de nascimento inválida!';
    exit();
}
$senha_crip = md5($senha);

if (!$emailUnchanged) {
	// validar email duplicado na tabela de alunos, ignorando o proprio registro em edicao
	$stmtEmailAluno = $pdo->prepare("SELECT id FROM $tabela WHERE email = :email AND id <> :id LIMIT 1");
	$stmtEmailAluno->execute([
		':email' => $email,
		':id' => (int) $id,
	]);
	if ($stmtEmailAluno->fetch(PDO::FETCH_ASSOC)) {
		echo 'Email ja Cadastrado, escolha Outro!';
		exit();
	}

	// validar email duplicado na tabela usuarios para outra pessoa/nível
	$stmtUsuarioEmail = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :email AND (nivel <> 'Aluno' OR id_pessoa <> :id_pessoa) LIMIT 1");
	$stmtUsuarioEmail->execute([
		':email' => $email,
		':id_pessoa' => (int) $id,
	]);
	if ($stmtUsuarioEmail->fetch(PDO::FETCH_ASSOC)) {
		echo 'Email ja Cadastrado, escolha Outro!';
		exit();
	}
}

//validar cpf duplicado
$cpfColumn = cleanCpfColumn('cpf');
$query = $pdo->prepare("SELECT * FROM $tabela where $cpfColumn = :cpf_digits");
$query->execute([':cpf_digits' => $cpfDigits]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'CPF ja Cadastrado, escolha Outro!';
	exit();
}

if ($cpfDigits !== '') {
	$stmtUsuarioCpf = $pdo->prepare("SELECT id_pessoa, nivel FROM usuarios WHERE $cpfColumn = :cpf_digits LIMIT 1");
	$stmtUsuarioCpf->execute([':cpf_digits' => $cpfDigits]);
	$usuarioCpf = $stmtUsuarioCpf->fetch(PDO::FETCH_ASSOC);
	if ($usuarioCpf && !($usuarioCpf['nivel'] === 'Aluno' && (int) $usuarioCpf['id_pessoa'] === (int) $id)) {
		echo 'CPF ja Cadastrado, escolha Outro!';
		exit();
	}
}


$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['foto'];
}else{
	$foto = 'sem-perfil.jpg';
}


//SCRIPT PARA SUBIR FOTO NO SERVIDOR
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

// Garantir id valido quando a tabela nao estiver com auto_increment
$aluno_id = null;
if ($id == "") {
	$idAuto = true;
	$stmtCol = $pdo->query("SHOW COLUMNS FROM $tabela LIKE 'id'");
	$colInfo = $stmtCol ? $stmtCol->fetch(PDO::FETCH_ASSOC) : null;
	if (!$colInfo || stripos($colInfo['Extra'] ?? '', 'auto_increment') === false) {
		$idAuto = false;
	}
	if (!$idAuto) {
		$nextId = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM $tabela")->fetchColumn();
		$aluno_id = (int) $nextId;
	}
}

if($id == ""){
	$usuarioDestino = resolveAtendenteId($pdo, $responsavel, date('Y-m-d'));

	$insertSql = "INSERT INTO $tabela SET id = :id, nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, nascimento = :nascimento, cep = :cep, sexo = :sexo, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, ativo = 'Sim', usuario = :usuario, data = curDate()";
	if ($hasResponsavelCol) {
		$insertSql .= ", responsavel_id = :responsavel_id";
	}
	$query = $pdo->prepare($insertSql);


$paramsInsert = [
	':id' => $aluno_id,
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
	':usuario' => $usuarioDestino,
];
if ($hasResponsavelCol) {
	$paramsInsert[':responsavel_id'] = $responsavelSelecionadoId;
}
$query->execute($paramsInsert);
$ult_id = $aluno_id ?: $pdo->lastInsertId();

$usuario_id = nextTableId($pdo, 'usuarios');
$usuarioParams = [
	':nome' => $nome,
	':email' => $email,
	':cpf' => $cpf,
	':senha' => '',
	':senha_crip' => $senha_crip,
	':foto' => $foto,
	':id_pessoa' => $ult_id,
];
if ($usuario_id !== null) {
	$query = $pdo->prepare("INSERT INTO usuarios SET id = :id, nome = :nome, usuario = :email, senha = :senha, cpf = :cpf, senha_crip = :senha_crip, nivel = 'Aluno', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
	$usuarioParams[':id'] = $usuario_id;
} else {
	$query = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, usuario = :email, senha = :senha, cpf = :cpf, senha_crip = :senha_crip, nivel = 'Aluno', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()");
}

$query->execute($usuarioParams);
$usuarioAlunoId = isset($usuarioParams[':id']) ? (int) $usuarioParams[':id'] : (int) $pdo->lastInsertId();
if ($usuarioAlunoId > 0) {
	tentarVinculoVendedorAlunoPorCpf($pdo, $cpfDigits);
}

registrarHistoricoAtendente(
	$pdo,
	(int) $ult_id,
	null,
	(int) $usuarioDestino,
	'Cadastro inicial',
	'cadastro',
	(int) ($_SESSION['id'] ?? 0)
);

}else{
	 $querySql = "UPDATE $tabela SET nome = :nome, cpf = :cpf, email = :email, telefone = :telefone, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, nascimento = :nascimento, cep = :cep, sexo = :sexo, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, usuario = :usuario";
	 if ($hasTransferCol) {
	 	$querySql .= ", data_transferencia_atendente = :data_transferencia_atendente";
	 }
	 if ($hasResponsavelCol) {
	 	$querySql .= ", responsavel_id = :responsavel_id";
	 }
	 $querySql .= " WHERE id = :id";
	 $query = $pdo->prepare($querySql);

$paramsUpdate = [
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
	':usuario' => (int) $atendenteId,
	':id' => $id,
];
if ($hasTransferCol) {
	$paramsUpdate[':data_transferencia_atendente'] = (($isAdmin || $isSecretario) && $atendenteMudouEdicao)
		? $dataTransferenciaNorm
		: ($currentAluno['data_transferencia_atendente'] ?? null);
}
if ($hasResponsavelCol) {
	$paramsUpdate[':responsavel_id'] = $responsavelSelecionadoId;
}
$query->execute($paramsUpdate);
$ult_id = $pdo->lastInsertId();

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
if ($cpfDigits !== '') {
	tentarVinculoVendedorAlunoPorCpf($pdo, $cpfDigits);
}
}

if (($isAdmin || $isSecretario) && $id !== "" && $atendenteMudouEdicao) {
	try {
		$pdo->exec("CREATE TABLE IF NOT EXISTS transferencias_atendentes (
			id int(11) NOT NULL AUTO_INCREMENT,
			aluno_id int(11) NOT NULL,
			usuario_anterior int(11) NOT NULL,
			usuario_novo int(11) NOT NULL,
			motivo varchar(255) DEFAULT NULL,
			admin_id int(11) NOT NULL,
			data datetime NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$stmtLog = $pdo->prepare("INSERT INTO transferencias_atendentes SET aluno_id = :aluno_id, usuario_anterior = :anterior, usuario_novo = :novo, motivo = :motivo, admin_id = :admin_id, data = NOW()");
		$stmtLog->execute([
			':aluno_id' => (int) $id,
			':anterior' => (int) $currentResponsavelId,
			':novo' => (int) $atendenteId,
			':motivo' => 'Transferencia via edicao',
			':admin_id' => (int) ($_SESSION['id'] ?? 0),
		]);
	} catch (Exception $e) {
		// Falha no log nao impede a transferencia
	}

	registrarHistoricoAtendente(
		$pdo,
		(int) $id,
		(int) $currentResponsavelId,
		(int) $atendenteId,
		'Transferencia via edicao',
		'edicao',
		(int) ($_SESSION['id'] ?? 0),
		$dataTransferenciaNorm !== '' ? $dataTransferenciaNorm : null
	);
}




echo 'Salvo com Sucesso';
