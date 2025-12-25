<?php 
require_once("../../../conexao.php");
$tabela = 'alunos';

$id = $_POST['id'];
$acao = $_POST['acao'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("UPDATE $tabela SET ativo = ? WHERE id = ?");
	$stmt->execute([$acao, $id]);
	$stmt = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id_pessoa = ? AND nivel = 'Aluno'");
	$stmt->execute([$acao, $id]);
}

echo 'Alterado com Sucesso';
?>
