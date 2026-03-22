<?php
require_once("../../../conexao.php");
@session_start();

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Secretario') {
    header('Location: ../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?pagina=comissoes_fixas&status=invalido');
    exit();
}

$tabela = 'comissoes';
$nivel = trim((string)($_POST['nivel'] ?? ''));
$porcentagemRaw = trim((string)($_POST['porcentagem'] ?? ''));
$porcentagem = str_replace(',', '.', $porcentagemRaw);
$recebeSempre = (int)($_POST['recebeSempre'] ?? 0);
$recebeSempre = $recebeSempre === 1 ? 1 : 0;

if ($nivel === '' || $porcentagem === '' || !is_numeric($porcentagem)) {
    header('Location: ../../index.php?pagina=comissoes_fixas&status=invalido');
    exit();
}

$stmtExiste = $pdo->prepare("SELECT id FROM {$tabela} WHERE LOWER(TRIM(nivel)) = LOWER(TRIM(:nivel)) LIMIT 1");
$stmtExiste->execute([':nivel' => $nivel]);
if ($stmtExiste->fetchColumn()) {
    header('Location: ../../index.php?pagina=comissoes_fixas&status=duplicado');
    exit();
}

$stmt = $pdo->prepare("INSERT INTO {$tabela} (nivel, porcentagem, recebeSempre, created_at) VALUES (:nivel, :porcentagem, :recebeSempre, NOW())");
$stmt->execute([
    ':nivel' => $nivel,
    ':porcentagem' => $porcentagem,
    ':recebeSempre' => $recebeSempre
]);

header('Location: ../../index.php?pagina=comissoes_fixas&status=ok');
exit();
