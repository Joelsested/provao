<?php

require_once("../../../conexao.php");
$tabela = 'perguntas_respostas';

$id_aluno = @$_POST['id_aluno'];
$pergunta = @$_POST['pergunta'];
$resposta = @$_POST['id_alt'];
$id_curso = @$_POST['id_curso'];
$letras = $_POST['letras'];
$numeracao = @$_POST['numeracao'];
$correta = @$_POST['correta'];

if ($correta == '') {
	$correta = 'Não';
}




if($id_aluno == ''){
   echo 'Sessao expirada, atualize a pagina';
   
		exit();
}

// $form = [
// 	'id_aluno' => $id_aluno,
// 	'pergunta' => $pergunta,
// 	'resposta' => $resposta,
// 	'id_curso' => $id_curso,
// 	'letras' => $letras,
// 	'correta' => $correta
// ];

// echo json_encode($form);
// return;

$pergunta = str_replace("'", " ", $pergunta);
$pergunta = str_replace('"', ' ', $pergunta);


$query = $pdo->prepare("SELECT * FROM {$tabela} WHERE id_curso = :curso AND id_aluno = :aluno AND pergunta = :pergunta ORDER BY id asc");
$query->execute(['curso' => $id_curso, 'aluno' => $id_aluno, 'pergunta' => $pergunta]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);

if ($total_reg > 0) {

	$query = $pdo->prepare("UPDATE {$tabela} SET pergunta = :pergunta, id_curso = :curso, id_aluno = :aluno, resposta = :resposta, letra = :letra, numeracao = :numeracao, correta = :correta WHERE pergunta = :pergunta");

} else {
	$query = $pdo->prepare("INSERT INTO {$tabela} SET pergunta = :pergunta, id_curso = :curso, id_aluno = :aluno, resposta = :resposta, letra = :letra, numeracao = :numeracao, correta = :correta");
}








$query->execute([
	'pergunta' => $pergunta,
	'curso' => $id_curso,
	'aluno' => $id_aluno,
	'resposta' => $resposta,
	'letra' => $letras,
	'numeracao' => $numeracao,
	'correta' => $correta,
]);

echo 'Salvo com Sucesso';

?>