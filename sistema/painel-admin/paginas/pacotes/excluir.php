<?php
require_once("../../../conexao.php");

@session_start();
if (empty($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Administrador') {
	http_response_code(403);
	echo 'Apenas administradores podem excluir pacotes.';
	exit();
}

$logPath = __DIR__ . '/excluir_debug.log';
$logLine = date('Y-m-d H:i:s') . ' POST=' . json_encode($_POST) . ' GET=' . json_encode($_GET) . PHP_EOL;
@file_put_contents($logPath, $logLine, FILE_APPEND);

$tabela = 'pacotes';
$id = 0;
$hasId = false;
if (isset($_POST['id'])) {
	$id = (int) $_POST['id'];
	$hasId = true;
} elseif (isset($_POST['id_matricula'])) {
	$id = (int) $_POST['id_matricula'];
	$hasId = true;
} elseif (isset($_GET['id'])) {
	$id = (int) $_GET['id'];
	$hasId = true;
}

if (!$hasId || $id <= 0) {
	echo 'ID invalido.';
	exit();
}

$stmt = $pdo->prepare("SELECT imagem FROM $tabela WHERE id = ?");
$stmt->execute([$id]);
$pacote = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pacote) {
	echo 'Pacote nao encontrado.';
	exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE id_pacote = ?");
$stmt->execute([$id]);
$totalMatriculas = (int) $stmt->fetchColumn();
if ($totalMatriculas > 0) {
	echo 'Nao e possivel excluir: existem matriculas/pagamentos vinculados ao pacote.';
	exit();
}

try {
	$pdo->beginTransaction();

	$stmt = $pdo->prepare("DELETE FROM cursos_pacotes WHERE id_pacote = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM avaliacoes WHERE pacote = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);

	$pdo->commit();
} catch (Exception $e) {
	$pdo->rollBack();
	echo 'Erro ao excluir o pacote.';
	exit();
}

$foto = $pacote['imagem'] ?? '';
if ($foto !== "sem-foto.png" && $foto !== '') {
	@unlink('../../img/pacotes/' . $foto);
}

echo 'Excluido com Sucesso';

?>
