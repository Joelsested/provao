<?php 
require_once("../../../conexao.php");
$tabela = 'aulas';
@session_start();
$id_aluno = $_SESSION['id'];

$id = $_POST['id'];
$aula = $_POST['aula'];



$query = $pdo->prepare("SELECT * FROM aulas WHERE id = :id");
$query->execute(['id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$curso = $res[0]['curso'];
$sessao = $res[0]['sessao'];
$seq_aula = $res[0]['sequencia_aula'];

$nome_aula = $res[0]['nome'];	
$num_aula = $res[0]['num_aula'];
$link = $res[0]['link'];
$id_aula = $res[0]['id'];

$query_m = $pdo->prepare("SELECT * FROM sessao WHERE id = :id");
$query_m->execute(['id' => $sessao]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
if(@count($res_m) > 0){
	$nome_sessao = $res_m[0]['nome'] .' - ';
}else{
	$nome_sessao = "";
}


//pegar o id da matricula
$query_m = $pdo->prepare("SELECT * FROM matriculas WHERE id_curso = :curso AND aluno = :aluno");
$query_m->execute(['curso' => $curso, 'aluno' => $id_aluno]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$id_mat = $res_m[0]['id'];
$aulas_conc = $res_m[0]['aulas_concluidas'];
$status_mat = $res_m[0]['status'];

//verificar total de aulas do curso
$query_m = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso");
$query_m->execute(['curso' => $curso]);
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
$total_aulas = @count($res_m);

//mudo o status do curso para finalizado
if($status_mat != 'Finalizado' and $total_aulas == $aulas_conc){
	$query_m = $pdo->prepare("UPDATE matriculas SET status = 'Finalizado' WHERE id = :id");
	$query_m->execute(['id' => $id_mat]);
}

if($aula == 'proximo' and $sessao == 0){	

	$proxima = $num_aula + 1;

	if($proxima > $total_aulas){
		echo 'Curso Finalizado';
		exit();
	}

	if($aulas_conc < $proxima){
		//atualizar aulas concluidas na matricula
		$query_m = $pdo->prepare("UPDATE matriculas SET aulas_concluidas = :aulas WHERE id = :id");
		$query_m->execute(['aulas' => $proxima, 'id' => $id_mat]);
	}
	


	$query = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso AND num_aula = :num_aula");
	$query->execute(['curso' => $curso, 'num_aula' => $proxima]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$nome_aula = $res[0]['nome'];	
	$num_aula = $res[0]['num_aula'];
	$link = $res[0]['link'];
	$id_aula = $res[0]['id'];
}



if($aula == 'proximo' and $sessao != 0){

	
	$proxima = $seq_aula + 1;

	if($proxima > $total_aulas){
		echo 'Curso Finalizado';
		exit();
	}

	if($aulas_conc < $proxima){
	//atualizar aulas concluidas na matricula
	$query_m = $pdo->prepare("UPDATE matriculas SET aulas_concluidas = :aulas WHERE id = :id");
	$query_m->execute(['aulas' => $proxima, 'id' => $id_mat]);
	}

	$query = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso AND sequencia_aula = :seq");
	$query->execute(['curso' => $curso, 'seq' => $proxima]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$nome_aula = $res[0]['nome'];	
	$num_aula = $res[0]['num_aula'];
	$link = $res[0]['link'];
	$id_aula = $res[0]['id'];
	$sessao = $res[0]['sessao'];

	$query_m = $pdo->prepare("SELECT * FROM sessao WHERE id = :id");
	$query_m->execute(['id' => $sessao]);
	$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
	$nome_sessao = $res_m[0]['nome'] .' - ';

}


if($aula == 'anterior' and $num_aula > 1 and $sessao == 0){
	$proxima = $num_aula - 1;	
	$query = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso AND num_aula = :num_aula");
	$query->execute(['curso' => $curso, 'num_aula' => $proxima]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$nome_aula = $res[0]['nome'];	
	$num_aula = $res[0]['num_aula'];
	$link = $res[0]['link'];
	$id_aula = $res[0]['id'];
}



if($aula == 'anterior' and $seq_aula > 1 and $sessao != 0){
	$proxima = $seq_aula - 1;	
	$query = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso AND sequencia_aula = :seq");
	$query->execute(['curso' => $curso, 'seq' => $proxima]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$nome_aula = $res[0]['nome'];	
	$num_aula = $res[0]['num_aula'];
	$link = $res[0]['link'];
	$id_aula = $res[0]['id'];

	$sessao = $res[0]['sessao'];

	$query_m = $pdo->prepare("SELECT * FROM sessao WHERE id = :id");
	$query_m->execute(['id' => $sessao]);
	$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
	$nome_sessao = $res_m[0]['nome'] .' - ';
}


echo $num_aula . '***' . $nome_aula .  '***' . $link.  '***' .$id_aula. '***'.$nome_sessao;

?>


