<?php 
require_once("../../../conexao.php");
$tabela = 'perguntas';

$id = $_POST['id'];

//excluir as respostas e pergunta
$stmt = $pdo->prepare("UPDATE $tabela SET respondida = 'Sim' where id = :id");
$stmt->execute([':id' => $id]);


echo 'Respondida com Sucesso';

?>
