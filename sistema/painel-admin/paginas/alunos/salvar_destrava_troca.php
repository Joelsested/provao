<?php
require_once("../../../conexao.php");
require_once(__DIR__ . '/../../../../helpers.php');
@session_start();

if (empty($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Administrador') {
    http_response_code(403);
    echo 'Sem permissao.';
    exit();
}

$destravaRaw = trim((string) ($_POST['destrava'] ?? '0'));
$destrava = in_array(strtolower($destravaRaw), ['1', 'sim', 'true', 'on'], true) ? 1 : 0;

if (!tableHasColumn($pdo, 'config', 'destrava_troca_atendente_admin')) {
    try {
        $pdo->exec("ALTER TABLE config ADD COLUMN destrava_troca_atendente_admin TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Exception $e) {
        echo 'Erro ao preparar chave de destrava.';
        exit();
    }
}

try {
    $stmt = $pdo->prepare("UPDATE config SET destrava_troca_atendente_admin = :destrava");
    $stmt->execute([':destrava' => $destrava]);

    if ($destrava === 1) {
        echo 'Chave de destrava ativada com sucesso.';
    } else {
        echo 'Chave de destrava desativada com sucesso.';
    }
} catch (Exception $e) {
    echo 'Erro ao salvar chave de destrava.';
}
