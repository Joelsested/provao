<?php 
require_once("../conexao.php");


$tabela = 'arquivos_cursos';


$id = $_POST['id_arq'];


$stmtDelete = $pdo->prepare("DELETE FROM {$tabela} where id = :id");
$stmtDelete->execute([':id' => $id]);

echo 'Excluído com Sucesso';
 ?>
