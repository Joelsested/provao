<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../../../helpers.php");
@session_start();

if (empty($_SESSION['nivel']) || !in_array($_SESSION['nivel'], ['Administrador', 'Secretario'], true)) {
	http_response_code(403);
	echo 'Sem permissao.';
	exit();
}

$dataCorte = trim((string) ($_POST['data_corte'] ?? ''));
$dataCorteNorm = $dataCorte !== '' ? normalizeDate($dataCorte) : '';
if ($dataCorte !== '' && $dataCorteNorm === '') {
	echo 'Data invalida.';
	exit();
}

if (!tableHasColumn($pdo, 'config', 'data_corte_atendente')) {
	try {
		$pdo->exec("ALTER TABLE config ADD COLUMN data_corte_atendente DATE DEFAULT NULL");
	} catch (Exception $e) {
		echo 'Erro ao preparar configuracao.';
		exit();
	}
}

try {
	$stmt = $pdo->prepare("UPDATE config SET data_corte_atendente = :data_corte");
	$stmt->execute([':data_corte' => $dataCorteNorm !== '' ? $dataCorteNorm : null]);
	echo 'Salvo com sucesso.';
} catch (Exception $e) {
	echo 'Erro ao salvar data de corte.';
}
