<?php
require_once '../../../conexao.php';
@session_start();

if ((string) (@$_SESSION['nivel'] ?? '') !== 'Administrador') {
    echo 'Acesso Negado';
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo 'Gateway invalido';
    exit();
}

$stmt = $pdo->prepare("SELECT id, nome FROM gateways WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$gateway = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$gateway) {
    echo 'Gateway nao encontrado';
    exit();
}

if (strtoupper(trim((string) ($gateway['nome'] ?? ''))) !== 'EFY') {
    echo 'Apenas o gateway EFY pode ser ativado.';
    exit();
}

$pdo->query("DELETE FROM gateways WHERE UPPER(TRIM(nome)) <> 'EFY'");
$pdo->query("UPDATE gateways SET ativo = 'Nao'");
$stmtAtivar = $pdo->prepare("UPDATE gateways SET ativo = 'Sim' WHERE id = :id");
$stmtAtivar->execute([':id' => $id]);

echo 'Ativado com Sucesso';

