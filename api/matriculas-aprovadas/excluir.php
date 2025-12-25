<?php 
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
	$stmt = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
	$stmt->execute([$id]);
}


?>
