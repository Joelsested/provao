<?php
require_once("../../../conexao.php");
require_once("../../verificar.php");
@session_start();

if (!in_array($_SESSION['nivel'] ?? '', ['Administrador', 'Secretario', 'Tesoureiro'], true)) {
    echo "<script>alert('Nao autorizado.');window.location='../../index.php?pagina=vendedores';</script>";
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$valorAtual = (int) ($_POST['valor'] ?? 0);
$novoValor = $valorAtual === 1 ? 0 : 1;

if ($id <= 0) {
    echo "<script>alert('Vendedor invalido.');window.location='../../index.php?pagina=vendedores';</script>";
    exit();
}

try {
    $stmtCol = $pdo->query("SHOW COLUMNS FROM vendedores LIKE 'pode_login_como_aluno'");
    $hasCol = (bool) ($stmtCol && $stmtCol->fetch(PDO::FETCH_ASSOC));
    if (!$hasCol) {
        $pdo->exec("ALTER TABLE vendedores ADD COLUMN pode_login_como_aluno TINYINT(1) NOT NULL DEFAULT 0");
    }

    $stmt = $pdo->prepare("UPDATE vendedores SET pode_login_como_aluno = :valor WHERE id = :id");
    $stmt->execute([
        ':valor' => $novoValor,
        ':id' => $id,
    ]);

    $msg = $novoValor === 1 ? 'Login como aluno habilitado.' : 'Login como aluno desabilitado.';
    echo "<script>alert('{$msg}');window.location='../../index.php?pagina=vendedores';</script>";
    exit();
} catch (Exception $e) {
    echo "<script>alert('Nao foi possivel atualizar a permissao.');window.location='../../index.php?pagina=vendedores';</script>";
    exit();
}

