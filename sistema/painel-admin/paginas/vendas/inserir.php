<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

$total_recebido = $_POST['total_recebido'];
$forma_pgto = $_POST['forma_pgto'];
$obs = $_POST['obs'];
$id = $_POST['id'];


$query = $pdo->prepare("UPDATE $tabela SET total_recebido = :total_recebido, forma_pgto = :forma_pgto, obs = :obs WHERE id = '$id'");

$query->bindValue(":total_recebido", "$total_recebido");
$query->bindValue(":forma_pgto", "$forma_pgto");
$query->bindValue(":obs", "$obs");
$query->execute();

echo 'Salvo com Sucesso';

 ?>