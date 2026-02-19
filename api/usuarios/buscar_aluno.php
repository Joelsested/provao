<?php
require_once '../conexao.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
  echo json_encode(['success' => false, 'message' => 'ID não informado']);
  exit;
}

$id = intval($_GET['id']);

// Buscar aluno
$stmtAluno = $pdo->prepare("SELECT nome, email, telefone FROM alunos WHERE id = :id");
$stmtAluno->execute(['id' => $id]);
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
  echo json_encode(['success' => false, 'message' => 'Aluno não encontrado']);
  exit;
}

// Buscar id do usuario
$stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :email");
$stmtUsuario->execute(['email' => $aluno['email']]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
  echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
  exit;
}

$idUsuario = $usuario['id'];

// Buscar matriculas + nome do curso
$stmtMatriculas = $pdo->prepare("
  SELECT 
    m.*, 
    c.nome AS nome_curso,
    EXISTS (
      SELECT 1 FROM perguntas_respostas pr WHERE pr.id_curso = m.id_curso
    ) AS has_perguntas
  FROM matriculas m
  JOIN cursos c ON c.id = m.id_curso
  WHERE m.aluno = :aluno_id
");
$stmtMatriculas->execute(['aluno_id' => $idUsuario]);
$matriculas = $stmtMatriculas->fetchAll(PDO::FETCH_ASSOC);

// Resposta
echo json_encode([
  'success' => true,
  'nome' => $aluno['nome'],
  'email' => $aluno['email'],
  'telefone' => $aluno['telefone'],
  'matriculas' => $matriculas
]);
