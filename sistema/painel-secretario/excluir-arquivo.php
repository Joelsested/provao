<?php 
require_once("../conexao.php");


$tabela = 'arquivos_alunos';


$id = $_POST['id_arq'];


$pdo->query("DELETE FROM $tabela where id = '$id'");

echo 'Excluído com Sucesso';
 ?>