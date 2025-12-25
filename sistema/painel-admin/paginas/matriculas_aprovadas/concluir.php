<?php 
require_once("../../../conexao.php");
$tabela = 'matriculas';

$id = $_POST['id'];
$id = (int) $id;

if ($id > 0) {
	//trazer o curso da matricula
	$stmt = $pdo->prepare("SELECT * FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$curso = $res[0]['id_curso'] ?? 0;

	$stmtAulas = $pdo->prepare("SELECT * FROM aulas WHERE curso = ?");
	$stmtAulas->execute([(int) $curso]);
	$res2 = $stmtAulas->fetchAll(PDO::FETCH_ASSOC);
	$aulas = @count($res2);

	$stmtUpdate = $pdo->prepare("UPDATE $tabela SET status = 'Finalizado', aulas_concluidas = ? WHERE id = ?");
	$stmtUpdate->execute([(int) $aulas, $id]);
}

echo 'Conclu?do com Sucesso';

?>
