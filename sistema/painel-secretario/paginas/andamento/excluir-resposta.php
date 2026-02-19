<?php 
require_once("../../../conexao.php");
$tabela = 'respostas';

$id = $_POST['id'];

$stmt = $pdo->prepare("DELETE FROM $tabela where id = :id");
$stmt->execute([':id' => $id]);

echo 'Excluído com Sucesso';

?>
