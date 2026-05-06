<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../sistema/conexao.php');
@session_start();

function buscarUsuarioAtivo(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE id = :id AND ativo = 'Sim' LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

$usuarioId = (int) ($_SESSION['id'] ?? 0);
$emailParam = trim((string) ($_POST['email'] ?? ''));

if ($usuarioId <= 0 && $emailParam === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario nao autenticado.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id AND nivel = 'Aluno' LIMIT 1");
$stmt->execute([':id' => $usuarioId]);
$alunoPessoa = $stmt->fetch(PDO::FETCH_ASSOC);

if ((!$alunoPessoa || empty($alunoPessoa['id_pessoa'])) && $emailParam !== '') {
    $stmt = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE usuario = :email AND nivel = 'Aluno' LIMIT 1");
    $stmt->execute([':email' => $emailParam]);
    $alunoPessoa = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$alunoPessoa || empty($alunoPessoa['id_pessoa'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Aluno nao encontrado.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$alunoId = (int) $alunoPessoa['id_pessoa'];
$stmt = $pdo->prepare("SELECT usuario, responsavel_id FROM alunos WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $alunoId]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$currentResponsavelId = (int) ($aluno['responsavel_id'] ?? 0);
$currentAtendenteId = (int) ($aluno['usuario'] ?? 0);

$vendedorContextoId = (int) ($_SESSION['switch_vendedor_usuario_id'] ?? 0);
$vendedorContexto = buscarUsuarioAtivo($pdo, $vendedorContextoId);
if ($vendedorContexto && ($vendedorContexto['nivel'] ?? '') !== 'Vendedor') {
    $vendedorContexto = null;
}

if ($vendedorContexto) {
    $currentResponsavelId = (int) $vendedorContexto['id'];
}

if ($currentResponsavelId <= 0) {
    // Fallback legado restrito: apenas perfis que podem ser dono comercial.
    $atendenteAtual = buscarUsuarioAtivo($pdo, $currentAtendenteId);
    if ($atendenteAtual && in_array((string) ($atendenteAtual['nivel'] ?? ''), ['Vendedor', 'Parceiro', 'Administrador', 'Tesoureiro'], true)) {
        $currentResponsavelId = (int) $atendenteAtual['id'];
    }
}

$current = buscarUsuarioAtivo($pdo, $currentResponsavelId);

$options = [];
if ($current) {
    $options[] = $current;
}
if ($vendedorContexto && (!$current || (int) $current['id'] !== (int) $vendedorContexto['id'])) {
    $options[] = $vendedorContexto;
}

$ids = [];
$options = array_values(array_filter($options, static function ($item) use (&$ids) {
    $id = (int) ($item['id'] ?? 0);
    if ($id <= 0 || isset($ids[$id])) {
        return false;
    }
    $ids[$id] = true;
    return true;
}));

echo json_encode([
    'success' => true,
    'current' => $current,
    'options' => $options,
], JSON_UNESCAPED_UNICODE);
