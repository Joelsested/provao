<?php 
require_once('../../sistema/conexao.php');

function responsavelAtivo($id, array $allowedLevels, string $placeholders)
{
	global $pdo;
	$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND nivel IN ($placeholders) AND ativo = 'Sim' LIMIT 1");
	$params = array_merge([$id], $allowedLevels);
	$stmt->execute($params);
	return (bool) $stmt->fetchColumn();
}

@session_start();
$id_aluno = $_SESSION['id'] ?? null;

if (!$id_aluno) {
	echo 'Usuǭrio nǜo autenticado!';
	exit();
}

$allowedLevels = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro', 'Professor'];
$levelPlaceholders = implode(',', array_fill(0, count($allowedLevels), '?'));

$stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id AND nivel = 'Aluno' LIMIT 1");
$stmt->execute(['id' => $id_aluno]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$alunoPessoaId = $row['id_pessoa'] ?? null;

$responsavelId = filter_input(INPUT_POST, 'responsavel', FILTER_VALIDATE_INT);
$responsavelValido = null;
if ($responsavelId && responsavelAtivo($responsavelId, $allowedLevels, $levelPlaceholders)) {
	$responsavelValido = $responsavelId;
}

$currentResponsavel = null;
if ($alunoPessoaId) {
	$stmt = $pdo->prepare("SELECT usuario FROM alunos WHERE id = :id LIMIT 1");
	$stmt->execute(['id' => $alunoPessoaId]);
	$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
	$currentResponsavel = $aluno['usuario'] ?? null;
	if (!$responsavelValido && $currentResponsavel && responsavelAtivo($currentResponsavel, $allowedLevels, $levelPlaceholders)) {
		$responsavelValido = $currentResponsavel;
	}
}

if ($responsavelValido && $alunoPessoaId) {
	$stmt = $pdo->prepare("UPDATE alunos SET usuario = :responsavel WHERE id = :id");
	$stmt->execute([
		'responsavel' => $responsavelValido,
		'id' => $alunoPessoaId
	]);
}


$usuario = @$_POST['email'];
$curso = (int) $_POST['curso'];
$pacote = $_POST['pacote'];


if($pacote == 'Sim'){
	$tabela = 'pacotes';
}else{
	$tabela = 'cursos';
}

//verficiar email se existe
if($_SESSION['nivel'] == 'Aluno'){
	$query = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
	$query->execute([(int) $id_aluno]);
}else{
	$query = $pdo->prepare("SELECT * FROM usuarios where usuario = :usuario ");
	$query->bindValue(":usuario", "$usuario");
	$query->execute();
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	echo 'Aluno nǜo Cadastrado com este email!';
	exit();
}else{
	$id_aluno = $res[0]['id'];
	$nome_aluno = $res[0]['nome'];	
	$email_aluno = $res[0]['usuario'];
}


$stmtCurso = $pdo->prepare("SELECT * FROM $tabela WHERE id = ?");
$stmtCurso->execute([$curso]);
$res = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
$valor = $res[0]['valor'];
$promocao = $res[0]['promocao'];
$nome_curso = $res[0]['nome'];
$professor = $res[0]['professor'];

if($promocao > 0){
	$valor = $promocao;
}


//verficiar se o aluno jǭ estǭ matriculado no curso
$stmtMat = $pdo->prepare("SELECT * FROM matriculas WHERE aluno = ? AND id_curso = ? AND pacote = ?");
$stmtMat->execute([(int) $id_aluno, $curso, $pacote]);
$res = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	echo 'Aluno jǭ matriculado neste curso!';
	exit();
}else{

	if($valor == '0'){
		$status = 'Matriculado';
	}else{
		$status = 'Aguardando';
	}

	$stmtInsert = $pdo->prepare("INSERT INTO matriculas SET id_curso = :curso, aluno = :aluno, professor = :professor, valor = :valor, data = curDate(), status = :status, pacote = :pacote, subtotal = :subtotal, aulas_concluidas = '1'");
	$stmtInsert->bindValue(":curso", $curso, PDO::PARAM_INT);
	$stmtInsert->bindValue(":aluno", (int) $id_aluno, PDO::PARAM_INT);
	$stmtInsert->bindValue(":professor", (int) $professor, PDO::PARAM_INT);
	$stmtInsert->bindValue(":valor", "$valor");
	$stmtInsert->bindValue(":status", $status);
	$stmtInsert->bindValue(":pacote", $pacote);
	$stmtInsert->bindValue(":subtotal", "$valor");
	$stmtInsert->execute();
	
	
}

echo 'Matriculado com Sucesso';


require_once('email-matricula.php');

 ?>


