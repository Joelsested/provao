<?php 
require_once("sistema/conexao.php");

$remetente = $email_sistema;
$assunto = 'Contato - ' .$nome_sistema;
$novidades = @$_POST['novidades'];

$email_aluno = $_POST['email'];
$nome_aluno = $_POST['nome'];

//capturar o email do aluno para o email marketing
if($novidades == 'Sim'){
	$query = $pdo->prepare("SELECT * from emails where email = :email");
	$query->execute([':email' => $email_aluno]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res) == 0){
		$query = $pdo->prepare("INSERT INTO emails SET email = :email, nome = :nome, enviar = 'sim'");

		$query->bindValue(":email", "$email_aluno");
		$query->bindValue(":nome", "$nome_aluno");		
		$query->execute();
	}	
}

if($_POST['nome'] == ""){
	echo 'Preencha o Campo Nome!';
	exit();
}


if($_POST['email'] == ""){
	echo 'Preencha o Campo Email!';
	exit();
}

if($_POST['mensagem'] == ""){
	echo 'Preencha o Campo Mensagem!';
	exit();
}


if(@$_POST['nome_curso'] != ""){
	$assunto = 'Pergunta do Curso - ' .@$_POST['nome_curso'];
}


$mensagem = utf8_decode('Nome: '.$_POST['nome']. "\r\n"."\r\n" . 'Telefone: '.$_POST['telefone']. "\r\n"."\r\n" . 'Mensagem: ' . "\r\n"."\r\n" .$_POST['mensagem']);
$dest = $_POST['email'];
$cabecalhos = "From: " .$dest;

mail($remetente, $assunto, $mensagem, $cabecalhos);

echo 'Enviado com Sucesso!';

 ?>
