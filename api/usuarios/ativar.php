<?php 
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
	$stmt = $pdo->prepare("UPDATE alunos SET ativo = 'Sim' WHERE id = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("UPDATE usuarios SET ativo = 'Sim' WHERE id_pessoa = ? AND nivel = 'Aluno'");
	$stmt->execute([$id]);
}

?>
