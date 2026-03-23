<?php
require_once("../../../conexao.php");
$tabela = 'cursos_pacotes';

$id_pacote = isset($_POST['id_pacote']) ? (int) $_POST['id_pacote'] : 0;
$id_curso = isset($_POST['id_curso']) ? (int) $_POST['id_curso'] : 0;

if ($id_pacote <= 0 || $id_curso <= 0) {
	echo 'Dados incompletos para vincular curso ao pacote.';
	exit();
}

$query = $pdo->prepare("SELECT id FROM pacotes WHERE id = :id LIMIT 1");
$query->execute([':id' => $id_pacote]);
if (!$query->fetch(PDO::FETCH_ASSOC)) {
	echo 'Pacote invalido para vincular curso.';
	exit();
}

$query = $pdo->prepare("SELECT id FROM cursos WHERE id = :id LIMIT 1");
$query->execute([':id' => $id_curso]);
if (!$query->fetch(PDO::FETCH_ASSOC)) {
	echo 'Curso invalido para vincular no pacote.';
	exit();
}

$query = $pdo->prepare("SELECT id FROM $tabela WHERE id_curso = :id_curso AND id_pacote = :id_pacote LIMIT 1");
$query->execute([
	':id_curso' => $id_curso,
	':id_pacote' => $id_pacote,
]);

if ($query->fetch(PDO::FETCH_ASSOC)) {
	echo 'Curso ja adicionado ao pacote.';
	exit();
}

$query = $pdo->prepare("INSERT INTO $tabela SET id_pacote = :id_pacote, id_curso = :id_curso");
$query->execute([
	':id_pacote' => $id_pacote,
	':id_curso' => $id_curso,
]);

echo 'Salvo com Sucesso';

?>
