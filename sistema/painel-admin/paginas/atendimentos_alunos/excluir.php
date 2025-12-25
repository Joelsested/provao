<?php 
require_once("../../../conexao.php");
$tabela = 'alunos';

$id = $_POST['id'];

$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
$foto = $res[0]['foto'];
if($foto != "sem-perfil.jpg"){
	@unlink('../../../painel-aluno/img/perfil/'.$foto);
}

$stmt = $pdo->prepare("DELETE FROM $tabela where id = :id");
$stmt->execute([':id' => $id]);
$stmt = $pdo->prepare("DELETE FROM usuarios where id_pessoa = :id_pessoa and nivel = 'Aluno'");
$stmt->execute([':id_pessoa' => $id]);

echo 'Excluído com Sucesso';

?>
