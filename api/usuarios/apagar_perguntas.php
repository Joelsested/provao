<?php
require_once '../conexao.php';
header('Content-Type: application/json');

if (!isset($_POST['id_curso'])) {
  echo json_encode(['success' => false, 'message' => 'Curso não informado']);
  exit;
}

$idCurso = intval($_POST['id_curso']);

// Deletar perguntas
$stmt = $pdo->prepare("DELETE FROM perguntas_respostas WHERE id_curso = :id");
$success = $stmt->execute(['id' => $idCurso]);

if ($success) {
  // Atualizar nota e status das matrículas
  $update = $pdo->prepare("
    UPDATE matriculas 
    SET nota = NULL, status = 'Matriculado' 
    WHERE id_curso = :id
  ");
  $updateSuccess = $update->execute(['id' => $idCurso]);

  echo json_encode([
    'success' => $updateSuccess,
    'message' => $updateSuccess 
      ? 'Respostas apagadas, notas zeradas e status atualizado para "Matriculado"!' 
      : 'Respostas apagadas, mas falha ao atualizar matrícula.'
  ]);
} else {
  echo json_encode([
    'success' => false,
    'message' => 'Erro ao apagar respostas.'
  ]);
}
