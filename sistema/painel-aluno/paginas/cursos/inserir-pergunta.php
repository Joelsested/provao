<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas';
@session_start();
$id_aluno = $_SESSION['id'];
$pergunta = $_POST['pergunta'];
$num_aula = $_POST['num_aula'];
$id_curso = $_POST['id_curso'];

$pergunta = str_replace("'", " ", $pergunta);
$pergunta = str_replace('"', ' ', $pergunta);

$query = $pdo->prepare("INSERT INTO {$tabela} SET aula = :aula, pergunta = :pergunta, curso = :curso, aluno = :aluno, data = curDate(), respondida = 'Não'");

$query->execute([
	'aula' => $num_aula,
	'pergunta' => $pergunta,
	'curso' => $id_curso,
	'aluno' => $id_aluno,
]);

echo 'Salvo com Sucesso';

?>
