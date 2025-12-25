<?php 
$tabela = 'pagar';
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
	$stmt = $pdo->prepare("SELECT arquivo FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($res && $res['arquivo'] != "sem-foto.png") {
		@unlink('../../sistema/painel-admin/img/contas/' . $res['arquivo']);
	}

	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
}


?>
