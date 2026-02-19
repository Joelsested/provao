<?php
require_once("../../../conexao.php");

@session_start();
if (empty($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Administrador') {
	http_response_code(403);
	echo 'Apenas administradores podem excluir cursos.';
	exit();
}

$logPath = __DIR__ . '/excluir_debug.log';
$logLine = date('Y-m-d H:i:s') . ' POST=' . json_encode($_POST) . ' GET=' . json_encode($_GET) . PHP_EOL;
@file_put_contents($logPath, $logLine, FILE_APPEND);

$tabela = 'cursos';
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

if (!$hasId || $id < 0) {
	echo 'ID invalido.';
	exit();
}

$stmt = $pdo->prepare("SELECT imagem, status FROM $tabela WHERE id = ?");
$stmt->execute([$id]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$curso) {
	echo 'Curso nao encontrado.';
	exit();
}

$status = $curso['status'] ?? '';
if ($status === 'Aprovado') {
	echo 'Cuidado, o curso nao pode ser excluido com status de aprovado.';
	exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE id_curso = ?");
$stmt->execute([$id]);
$totalMatriculas = (int) $stmt->fetchColumn();
if ($totalMatriculas > 0) {
	echo 'Nao e possivel excluir: existem matriculas/pagamentos vinculados ao curso.';
	exit();
}

try {
	$pdo->beginTransaction();

	$stmt = $pdo->prepare("DELETE FROM cursos_pacotes WHERE id_curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM perguntas_respostas WHERE id_curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM alternativas WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM perguntas_quest WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM respostas WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM perguntas WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM avaliacoes WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM arquivos_cursos WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM sessao WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM aulas WHERE curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM comissoes_pagar WHERE id_curso = ?");
	$stmt->execute([$id]);
	$stmt = $pdo->prepare("DELETE FROM $tabela WHERE id = ?");
	$stmt->execute([$id]);

	$pdo->commit();
} catch (Exception $e) {
	$pdo->rollBack();
	echo 'Erro ao excluir o curso.';
	exit();
}

$foto = $curso['imagem'] ?? '';
if ($foto !== "sem-foto.png" && $foto !== '') {
	@unlink('../../img/cursos/' . $foto);
}

echo 'Excluido com Sucesso';

?>
