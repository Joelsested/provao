<?php
require_once("../../../conexao.php");
$tabela = 'cursos';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !isset($_POST['data'])) {
        throw new Exception("Dados incompletos.");
    }

    $id = $_POST['id'];
    $data = $_POST['data']; // já vem no formato yyyy-mm-dd

    $query = $pdo->prepare("UPDATE $tabela SET data_certificado = :data WHERE id = :id");
    $query->bindValue(":data", $data);
    $query->bindValue(":id", $id, PDO::PARAM_INT);
    $query->execute();

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
