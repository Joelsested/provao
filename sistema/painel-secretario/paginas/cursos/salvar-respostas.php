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
	$correta = 'Nǜo';
}

$pergunta = str_replace("'", " ", $pergunta);
$pergunta = str_replace('"', ' ', $pergunta);


$query = $pdo->prepare("SELECT * FROM $tabela where id_curso = :id_curso and id_aluno = :id_aluno and pergunta = :pergunta ORDER BY id asc");
$query->execute([
	':id_curso' => $id_curso,
	':id_aluno' => $id_aluno,
	':pergunta' => $pergunta,
]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);

if ($total_reg > 0) {
	$query = $pdo->prepare("UPDATE $tabela SET pergunta = :pergunta, id_curso = :id_curso, id_aluno = :id_aluno, resposta = :resposta, letra = :letra, numeracao = :numeracao, correta = :correta where pergunta = :pergunta");
} else {
	$query = $pdo->prepare("INSERT INTO $tabela SET pergunta = :pergunta, id_curso = :id_curso, id_aluno = :id_aluno, resposta = :resposta, letra = :letra, numeracao = :numeracao, correta = :correta");
}

$query->execute([
	':pergunta' => $pergunta,
	':id_curso' => $id_curso,
	':id_aluno' => $id_aluno,
	':resposta' => $resposta,
	':letra' => $letras,
	':numeracao' => $numeracao,
	':correta' => $correta,
]);

echo 'Salvo com Sucesso';

?>
