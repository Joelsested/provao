<?php 
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
	$stmt->execute([$id]);
	$res2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$cartoes = $res2[0]['cartao'] - 1;

	$stmt = $pdo->prepare("UPDATE alunos SET cartao = ? WHERE id = ?");
	$stmt->execute([(int) $cartoes, $id]);
}


?>
