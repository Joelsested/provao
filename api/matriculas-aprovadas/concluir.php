<?php 
include_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

//trazer o curso da matricula
$query = $pdo->prepare("SELECT * FROM matriculas WHERE id = ?");
$query->execute([$id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$curso = $res[0]['id_curso'];

$query2 = $pdo->prepare("SELECT * FROM aulas WHERE curso = ?");
$query2->execute([(int) $curso]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$aulas = @count($res2);

$stmt = $pdo->prepare("UPDATE matriculas SET status = 'Finalizado', aulas_concluidas = ? WHERE id = ?");
$stmt->execute([(int) $aulas, $id]);


?>
