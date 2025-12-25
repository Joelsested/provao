<?php 
require_once("conexao.php");

$email = $_POST['recuperar'];


$query = $pdo->prepare("SELECT * FROM usuarios where usuario = :email or cpf = :email");
$query->bindValue(":email", "$email");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	echo 'Não possui cadastro com este email ou cpf digitado!';
	exit();
}else{
	$email = $res[0]['usuario'];
	$nivel = $res[0]['nivel'] ?? '';
}

//ENVIAR O EMAIL COM ORIENTACAO
    $destinatario = $email;
    $assunto = $nome_sistema . ' - Recuperacao de Senha';
    $mensagem = ($nivel === 'Administrador') ? 'Sua senha nao pode ser enviada por e-mail. Se esqueceu, entre em contato com o suporte.' : 'Sua senha e sua data de nascimento no formato DDMMAAAA (somente numeros).';
    $cabecalhos = "From: ".$email_sistema;
   
    mail($destinatario, $assunto, $mensagem, $cabecalhos);

?>