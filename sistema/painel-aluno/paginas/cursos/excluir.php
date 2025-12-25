<?php
require_once("../../../conexao.php");

@session_start();
$aluno = $_SESSION['id'] ?? 0;

$idRaw = $_POST['id'] ?? ($_POST['id_matricula'] ?? ($_GET['id'] ?? ''));
if ($idRaw === '' || $idRaw === null) {
	$rawBody = file_get_contents('php://input');
	$bodyData = [];
	parse_str($rawBody, $bodyData);
	$idRaw = $bodyData['id'] ?? ($bodyData['id_matricula'] ?? '');
}
$id = (int) $idRaw;

if (!$aluno) {
	echo 'Nao autorizado.';
	exit();
}

if ($id <= 0) {
	echo 'Matricula invalida.';
	exit();
}

$stmtBusca = $pdo->prepare("SELECT id, id_curso, pacote, obs FROM matriculas WHERE id = :id AND aluno = :aluno LIMIT 1");
$stmtBusca->execute([
	':id' => $id,
	':aluno' => $aluno,
]);
$matricula = $stmtBusca->fetch(PDO::FETCH_ASSOC);

if (!$matricula) {
	echo 'Matricula nao encontrada.';
	exit();
}

$startedTransaction = false;

try {
	$pdo->beginTransaction();
	$startedTransaction = true;

	$pdo->prepare("DELETE FROM pagamentos_pix WHERE id_matricula = :id")->execute([':id' => $matricula['id']]);
	$pdo->prepare("DELETE FROM pagamentos_boleto WHERE id_matricula = :id")->execute([':id' => $matricula['id']]);
	$pdo->prepare("DELETE FROM parcelas_geradas_por_boleto WHERE id_matricula = :id")->execute([':id' => $matricula['id']]);
	$pdo->prepare("DELETE FROM boletos_parcelados WHERE id_matricula = :id")->execute([':id' => $matricula['id']]);

	$stmtDelete = $pdo->prepare("DELETE FROM matriculas WHERE id = :id AND aluno = :aluno LIMIT 1");
	$stmtDelete->execute([
		':id' => $matricula['id'],
		':aluno' => $aluno,
	]);

	if ($matricula['pacote'] === 'Sim') {
		$idPacote = (int) $matricula['id_curso'];
		$stmtFilhos = $pdo->prepare("SELECT id FROM matriculas WHERE aluno = :aluno AND pacote <> 'Sim' AND id_pacote = :id_pacote AND obs = 'Pacote'");
		$stmtFilhos->execute([
			':aluno' => $aluno,
			':id_pacote' => $idPacote,
		]);
		$idsFilhos = $stmtFilhos->fetchAll(PDO::FETCH_COLUMN);

		if (!empty($idsFilhos)) {
			$placeholders = implode(',', array_fill(0, count($idsFilhos), '?'));
			$pdo->prepare("DELETE FROM pagamentos_pix WHERE id_matricula IN ($placeholders)")->execute($idsFilhos);
			$pdo->prepare("DELETE FROM pagamentos_boleto WHERE id_matricula IN ($placeholders)")->execute($idsFilhos);
			$pdo->prepare("DELETE FROM parcelas_geradas_por_boleto WHERE id_matricula IN ($placeholders)")->execute($idsFilhos);
			$pdo->prepare("DELETE FROM boletos_parcelados WHERE id_matricula IN ($placeholders)")->execute($idsFilhos);

			$idsFilhosComAluno = $idsFilhos;
			$idsFilhosComAluno[] = $aluno;
			$pdo->prepare("DELETE FROM matriculas WHERE id IN ($placeholders) AND aluno = ?")->execute($idsFilhosComAluno);
		}
	}

	$pdo->commit();
	$startedTransaction = false;

	echo 'Excluido com Sucesso';
} catch (Exception $e) {
	if ($startedTransaction && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	echo 'Erro ao excluir matricula.';
}
?>
