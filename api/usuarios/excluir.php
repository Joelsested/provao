<?php
include_once('../conexao.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Metodo nao permitido.']);
	exit();
}

@session_start();

$apiToken = '';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
	$token = trim($matches[1]);
} elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
	$token = trim($_SERVER['HTTP_X_API_KEY']);
}

$authorized = false;
if ($apiToken !== '' && $token !== '' && hash_equals($apiToken, $token)) {
	$authorized = true;
}
if (!empty($_SESSION['id']) && !empty($_SESSION['nivel'])) {
	$authorized = true;
}

if (!$authorized) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Nao autorizado.']);
	exit();
}

$postjson = json_decode(file_get_contents('php://input'), true);
$id = intval($postjson['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Id invalido.']);
	exit();
}

$stmt = $pdo->prepare("SELECT foto FROM alunos WHERE id = :id");
$stmt->execute([':id' => $id]);
$foto = $stmt->fetchColumn();
if ($foto && $foto !== 'sem-perfil.jpg') {
	$foto = basename($foto);
	@unlink('../../sistema/painel-aluno/img/perfil/' . $foto);
}

$stmt = $pdo->prepare("DELETE FROM alunos WHERE id = :id");
$stmt->execute([':id' => $id]);

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_pessoa = :id AND nivel = 'Aluno'");
$stmt->execute([':id' => $id]);

echo json_encode(['success' => true]);
?>
