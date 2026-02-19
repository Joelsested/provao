<?php 
require_once("../../../conexao.php");
$tabela = 'avaliacoes';
@session_start();
$id_aluno = $_SESSION['id'];

$id_curso = $_POST['id_curso'];
$comentario = $_POST['avaliacao'];
$nota = $_POST['nota'];

$comentario = str_replace("'", " ", $comentario);
$comentario = str_replace('"', ' ', $comentario);

if($comentario == ""){
	$comentario = 'Excelente curso!';
}

//verificar se o curso já foi avaliado
$query2 = $pdo->prepare("SELECT * FROM avaliacoes WHERE curso = :curso AND aluno = :aluno");
$query2->execute(['curso' => $id_curso, 'aluno' => $id_aluno]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$avaliacoes = @count($res2);
if($avaliacoes > 0){
		exit();
}





$query = $pdo->prepare("INSERT INTO {$tabela} SET nota = :nota, comentario = :comentario, curso = :curso, aluno = :aluno, data = curDate()");

$query->execute([
	'nota' => $nota,
	'comentario' => $comentario,
	'curso' => $id_curso,
	'aluno' => $id_aluno,
]);

echo 'Salvo com Sucesso';


$query2 = $pdo->prepare("SELECT * FROM cursos WHERE id = :id");
$query2->execute(['id' => $id_curso]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$nome_curso = $res2[0]['nome'];

//envio para o administrador
$destinatario = $email_sistema;
$assunto = 'Nova Avaliação no Curso ' .$nome_curso;
$mensagem = "Nota: $nota <br><br> $comentario !!";
$remetente = $email_sistema;
$cabecalhos = 'MIME-Version: 1.0' . "\r\n";
$cabecalhos .= 'Content-type: text/html; charset=utf-8;' . "\r\n";
$cabecalhos .= "From: " .$remetente;

mail($destinatario, $assunto, $mensagem, $cabecalhos);


?>
