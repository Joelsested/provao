<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . '/../../aluno_context.php');

$id_curso = $_POST['id_curso'];
$id_mat = $_POST['id_mat'];

$acertos = 0;

$mediaAprovacao = isset($media_config) ? (float) $media_config : 60.0;
if (!empty($id_mat)) {
	$paramsMat = [':id' => (int) $id_mat];
	$ctxIds = aluno_context_ids($pdo);
	$whereAluno = aluno_context_bind_in('aluno', $ctxIds, $paramsMat, 'al_ctx');
	$queryMat = $pdo->prepare("SELECT nota FROM matriculas WHERE id = :id AND {$whereAluno} LIMIT 1");
	$queryMat->execute($paramsMat);
	$notaAtual = (float) ($queryMat->fetchColumn() ?: 0);
	if ($notaAtual >= $mediaAprovacao) {
		$notaEscala10 = number_format($notaAtual / 10, 1, ',', '.');
		echo 'Prova ja feita com aprovacao***' . $notaEscala10;
		exit();
	}
}

$query = $pdo->prepare("SELECT * FROM perguntas_quest WHERE curso = :curso ORDER BY id asc");
$query->execute(['curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	for($i=0; $i < $total_reg; $i++){
		$id = $res[$i]['id'];				
		$id_alt = @$_POST[$id];

		if($id_alt == ""){
			echo 'Preencha Todas as Questões!';
			exit();
		}

		$query2 = $pdo->prepare("SELECT * FROM alternativas WHERE id = :id");
		$query2->execute(['id' => $id_alt]);
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$correta = $res2[0]['correta'];

		if($correta == 'Sim'){
			$acertos += 1;
		}

	}

	$nota = ($acertos / $total_reg) * 100;
	$notaF = number_format($nota, 2, ',', '.');
	
	$atualizaNota = $pdo->prepare("UPDATE matriculas SET nota = :nota WHERE id = :id");
	$atualizaNota->execute(['nota' => $nota, 'id' => $id_mat]);

	if($nota >= $media_config){
		echo 'Aprovado***'.$notaF;
		$atualizaStatus = $pdo->prepare("UPDATE matriculas SET status = 'Finalizado', data_conclusao = curDate() WHERE id = :id");
		$atualizaStatus->execute(['id' => $id_mat]);
	}else{
		echo 'Reprovado***'.$notaF;
	}
	
}

 ?>
