<?php 
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$curso = @$postjson['id'];
$email = @$postjson['email'];

//verficiar email se existe
$query = $pdo->prepare("SELECT * FROM usuarios where usuario = :usuario ");
$query->bindValue(":usuario", "$email");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	$result = json_encode(array('mensagem'=>'Email nǜo Cadastrado!', 'sucesso'=>false));
	echo $result;
	exit();
}else{
	$id_aluno = $res[0]['id'];
	$nome_aluno = $res[0]['nome'];	
	$email_aluno = $res[0]['usuario'];
}


$stmtCurso = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
$stmtCurso->execute([(int) $curso]);
$res = $stmtCurso->fetchAll(PDO::FETCH_ASSOC);
$valor = $res[0]['valor'];
$promocao = $res[0]['promocao'];
$nome_curso = $res[0]['nome'];
$professor = $res[0]['professor'];

if($promocao > 0){
	$valor = $promocao;
}

//verficiar se o aluno jǭ estǭ matriculado no curso
$stmtMat = $pdo->prepare("SELECT * FROM matriculas WHERE aluno = ? AND id_curso = ?");
$stmtMat->execute([(int) $id_aluno, (int) $curso]);
$res = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$result = json_encode(array('mensagem'=>'Aluno jǭ Matriculado no Curso!', 'sucesso'=>false));
	echo $result;
	exit();
}else{

	if($valor == '0'){
		$status = 'Matriculado';
	}else{
		$status = 'Aguardando';
	}

	$stmtInsert = $pdo->prepare("INSERT INTO matriculas SET id_curso = :curso, aluno = :aluno, professor = :professor, valor = :valor, data = curDate(), status = :status, pacote = 'Nǜo', subtotal = :subtotal, aulas_concluidas = '1'");
	$stmtInsert->bindValue(":curso", (int) $curso, PDO::PARAM_INT);
	$stmtInsert->bindValue(":aluno", (int) $id_aluno, PDO::PARAM_INT);
	$stmtInsert->bindValue(":professor", (int) $professor, PDO::PARAM_INT);
	$stmtInsert->bindValue(":valor", "$valor");
	$stmtInsert->bindValue(":status", $status);
	$stmtInsert->bindValue(":subtotal", "$valor");
	$stmtInsert->execute();
	
	
}


require_once('../../ajax/cursos/email-matricula.php');

$result = json_encode(array('mensagem'=>'Matriculado com sucesso!', 'sucesso'=>true));
echo $result;


?>
