<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../sistema/conexao.php');
@session_start();

$usuarioId = $_SESSION['id'] ?? null;
$emailParam = trim($_POST['email'] ?? '');
if (!$usuarioId && $emailParam === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id AND nivel = 'Aluno' LIMIT 1");
$stmt->execute(['id' => $usuarioId]);
$alunoPessoa = $stmt->fetch(PDO::FETCH_ASSOC);
if ((!$alunoPessoa || empty($alunoPessoa['id_pessoa'])) && $emailParam !== '') {
    $stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE usuario = :email LIMIT 1");
    $stmt->execute(['email' => $emailParam]);
    $alunoPessoa = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$alunoPessoa || empty($alunoPessoa['id_pessoa'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Aluno não encontrado.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$alunoId = $alunoPessoa['id_pessoa'];
$stmt = $pdo->prepare("SELECT usuario FROM alunos WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $alunoId]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
$responsavelAtual = null;
if ($aluno && !empty($aluno['usuario'])) {
    $stmt = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $aluno['usuario']]);
    $responsavelAtual = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$allowedLevels = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro', 'Professor'];
$placeholders = implode(', ', array_fill(0, count($allowedLevels), '?'));
$stmt = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE nivel IN ($placeholders) AND ativo = 'Sim' AND nome <> 'Professor_padrao' ORDER BY nome");
$stmt->execute($allowedLevels);
$opcoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'current' => $responsavelAtual,
    'options' => $opcoes
], JSON_UNESCAPED_UNICODE);
