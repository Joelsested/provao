<?php 
require_once("../../../conexao.php");
$tabela = 'alunos';

$raw_id = $_POST['id'] ?? ($_GET['id'] ?? null);
if ($raw_id === null || $raw_id === '' || !is_numeric($raw_id)) {
	echo 'ID invalido.';
	exit();
}
$id = (int) $raw_id;

try {
	$pdo->beginTransaction();

	$stmt = $pdo->prepare("SELECT foto FROM $tabela WHERE id = ? LIMIT 1");
	$stmt->execute([$id]);
	$foto = $stmt->fetchColumn();
	if ($foto === false) {
		$pdo->rollBack();
		echo 'Aluno nao encontrado.';
		exit();
	}
	$foto = $foto ?: '';

	$stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa = ? AND nivel = 'Aluno' LIMIT 1");
	$stmtUsuario->execute([$id]);
	$usuario_id = (int) ($stmtUsuario->fetchColumn() ?: 0);

	if ($usuario_id > 0) {
		$pdo->prepare("DELETE FROM matriculas WHERE aluno = ?")->execute([$usuario_id]);
	}

	$pdo->prepare("DELETE FROM arquivos_alunos WHERE aluno = ?")->execute([$id]);
	$pdo->prepare("DELETE FROM usuarios WHERE id_pessoa = ? AND nivel = 'Aluno'")->execute([$id]);
	$pdo->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$id]);

	$pdo->commit();

	if ($foto != "sem-perfil.jpg" && $foto != '') {
		@unlink('../../../painel-aluno/img/perfil/' . $foto);
	}

	echo 'Excluido com Sucesso';
} catch (Exception $e) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	echo 'Erro ao excluir: ' . $e->getMessage();
}


?>
