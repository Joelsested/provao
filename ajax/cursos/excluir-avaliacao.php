<?php 
require_once('../../sistema/conexao.php');
$tabela = 'avaliacoes';

if (empty($_SESSION['id']) || ($_SESSION['nivel'] ?? '') === 'Aluno') {
	http_response_code(401);
	echo 'Nao autorizado.';
	exit();
}

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}

echo 'Excluヴdo com Sucesso';

?>
