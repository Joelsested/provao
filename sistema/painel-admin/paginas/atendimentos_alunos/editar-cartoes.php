<?php 
require_once("../../../conexao.php");
$tabela = 'alunos';

$id = $_POST['id'];
$cartoes = $_POST['cartoes'];

$stmt = $pdo->prepare("UPDATE $tabela SET cartao = :cartao where id = :id");
$stmt->execute([
	':cartao' => $cartoes,
	':id' => $id,
]);

echo 'Alterado com Sucesso';

?>
