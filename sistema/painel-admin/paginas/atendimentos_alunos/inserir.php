<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../config/upload.php");
require_once(__DIR__ . '/../../../../helpers.php');
@session_start();

$id_user = @$_SESSION['id'];
$tabela = 'alunos';

$nome = $_POST['nome'];
$cpf = trim($_POST['cpf']);
$cpfDigits = digitsOnly($cpf);
$email = $_POST['email'];
$rg = $_POST['rg'];
$orgao_expedidor = $_POST['orgao_expedidor'];
$expedicao = $_POST['expedicao'];
$telefone = $_POST['telefone'];
$cep = $_POST['cep'];
$endereco = $_POST['endereco'];
$cidade = $_POST['cidade'];
$estado = $_POST['estado'];
$sexo = $_POST['sexo'];
$nascimento = $_POST['nascimento'];
$mae = $_POST['mae'];
$pai = $_POST['pai'];
 $naturalidade = $_POST['naturalidade'];
 $id = $_POST['id'];
$responsavelId = filter_input(INPUT_POST, 'responsavel_id', FILTER_VALIDATE_INT);
$allowedLevels = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro'];
$userNivel = $_SESSION['nivel'] ?? '';
$currentResponsavelId = null;
if ($id !== "") {
    $stmtAtual = $pdo->prepare("SELECT usuario FROM $tabela WHERE id = :id");
    $stmtAtual->execute([':id' => $id]);
    $currentResponsavelId = (int) ($stmtAtual->fetchColumn() ?: 0);
}
if ($cpf === '' || trim($nascimento) === '') {
    echo 'CPF e data de nascimento são obrigatórios!';
    exit();
}

if ($cpfDigits === '') {
    echo 'CPF invalido!';
    exit();
}
if ($id === "" && !$responsavelId && in_array($userNivel, $allowedLevels, true)) {
    $responsavelId = (int) $id_user;
}
if (!$responsavelId && $currentResponsavelId) {
    $responsavelId = $currentResponsavelId;
}
if (!$responsavelId) {
    echo 'Selecione o responsavel.';
    exit();
}

$placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));
$stmtResp = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
$stmtResp->execute(array_merge([$responsavelId], $allowedLevels));
$responsavel = $stmtResp->fetch(PDO::FETCH_ASSOC);
if (!$responsavel) {
    echo 'Responsavel invalido.';
    exit();
}
if ($responsavel['nivel'] === 'Vendedor') {
    $stmtVend = $pdo->prepare("SELECT professor, tutor_id FROM vendedores WHERE id = :id");
    $stmtVend->execute([':id' => $responsavel['id_pessoa']]);
    $vend = $stmtVend->fetch(PDO::FETCH_ASSOC);
    if ($vend && (int) $vend['professor'] === 1 && empty($vend['tutor_id'])) {
        echo 'Vendedor sem tutor vinculado.';
        exit();
    }
}

$senha = birthDigits($nascimento);
if ($senha === '') {
    echo 'Data de nascimento inválida!';
    exit();
}
$senha_crip = md5($senha);

//validar email duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where email = :email");
$query->execute([':email' => $email]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Email já Cadastrado, escolha Outro!';
	exit();
}


//validar cpf duplicado
$cpfColumn = cleanCpfColumn('cpf');
$query = $pdo->prepare("SELECT * FROM $tabela where $cpfColumn = :cpf_digits");
$query->execute([':cpf_digits' => $cpfDigits]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'CPF já Cadastrado, escolha Outro!';
	exit();
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


if($id == ""){
	$aluno_id = nextTableId($pdo, $tabela);
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
		':cidade' => $cidade,
		':estado' => $estado,
		':sexo' => $sexo,
		':nascimento' => $nascimento,
		':mae' => $mae,
		':pai' => $pai,
		':naturalidade' => $naturalidade,
		':foto' => $foto,
		':usuario' => $responsavelId,
	];
	if ($aluno_id) {
		$query = $pdo->prepare("INSERT INTO $tabela SET id = :id, nome = :nome, cpf = :cpf, email = :email, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, telefone = :telefone, cep = :cep, endereco = :endereco, cidade = :cidade, estado = :estado, sexo = :sexo, nascimento = :nascimento, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, ativo = 'Sim', usuario = :usuario, data = curDate()");
		$alunoParams[':id'] = $aluno_id;
	} else {
		$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, cpf = :cpf, email = :email, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, telefone = :telefone, cep = :cep, endereco = :endereco, cidade = :cidade, estado = :estado, sexo = :sexo, nascimento = :nascimento, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, ativo = 'Sim', usuario = :usuario, data = curDate()");
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

}else{
	 $query = $pdo->prepare("UPDATE $tabela SET nome = :nome, cpf = :cpf, email = :email, rg = :rg, orgao_expedidor = :orgao_expedidor, expedicao = :expedicao, telefone = :telefone, cep = :cep, endereco = :endereco, cidade = :cidade, estado = :estado, sexo = :sexo, nascimento = :nascimento, mae = :mae, pai = :pai, naturalidade = :naturalidade, foto = :foto, usuario = :usuario WHERE id = :id");

	 $query->execute([
	 	':nome' => $nome,
	 	':cpf' => $cpf,
	 	':email' => $email,
	 	':rg' => $rg,
	 	':orgao_expedidor' => $orgao_expedidor,
	 	':expedicao' => $expedicao,
	 	':telefone' => $telefone,
	 	':cep' => $cep,
	 	':endereco' => $endereco,
	 	':cidade' => $cidade,
	 	':estado' => $estado,
	 	':sexo' => $sexo,
	 	':nascimento' => $nascimento,
	 	':mae' => $mae,
	 	':pai' => $pai,
	 	':naturalidade' => $naturalidade,
	 	':foto' => $foto,
	 	':usuario' => $responsavelId,
	 	':id' => $id,
	 ]);
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
}




echo 'Salvo com Sucesso';

 ?>
