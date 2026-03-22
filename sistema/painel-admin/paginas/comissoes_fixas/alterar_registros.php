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

$acao = trim((string)($_POST['acao'] ?? 'editar'));
$idExclusao = (int)($_POST['id_exclusao'] ?? 0);
$registros = $_POST['registros'] ?? [];

if ($acao === 'excluir') {
    if ($idExclusao > 0) {
        $stmtDelete = $pdo->prepare("DELETE FROM comissoes WHERE id = :id");
        $stmtDelete->execute([':id' => $idExclusao]);
    }
    header('Location: ../../index.php?pagina=comissoes_fixas&status=ok');
    exit();
}

if (!is_array($registros) || count($registros) === 0) {
    header('Location: ../../index.php?pagina=comissoes_fixas&status=invalido');
    exit();
}

$stmtUpdate = $pdo->prepare("UPDATE comissoes SET porcentagem = :porcentagem, recebeSempre = :recebeSempre WHERE id = :id");

foreach ($registros as $registro) {
    $id = (int)($registro['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }

    $porcentagemRaw = trim((string)($registro['porcentagem'] ?? '0'));
    $porcentagem = str_replace(',', '.', $porcentagemRaw);
    if (!is_numeric($porcentagem)) {
        $porcentagem = 0;
    }

    $recebeSempre = (int)($registro['recebeSempre'] ?? 0);
    $recebeSempre = $recebeSempre === 1 ? 1 : 0;

    $stmtUpdate->execute([
        ':porcentagem' => $porcentagem,
        ':recebeSempre' => $recebeSempre,
        ':id' => $id
    ]);
}

header('Location: ../../index.php?pagina=comissoes_fixas&status=ok');
exit();
