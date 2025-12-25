<?php 
require_once("../../../conexao.php");
$tabela = 'grupos';

$nome = $_POST['nome'];
$id = $_POST['id'];


//validar email duplicado
$query = $pdo->prepare("SELECT * FROM $tabela where nome = :nome");
$query->execute([':nome' => $nome]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Grupo já Cadastrado com este nome, escolha Outro!';
	exit();
}


if($id == ""){

	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome WHERE id = '$id'");
}

$query->bindValue(":nome", "$nome");
$query->execute();

echo 'Salvo com Sucesso';

 ?>
