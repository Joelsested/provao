<?php
require_once '../../../conexao.php';
@session_start();

if ((string) (@$_SESSION['nivel'] ?? '') !== 'Administrador') {
    echo 'Acesso Negado';
    exit();
}

$nome = strtoupper(trim((string) ($_POST['nome'] ?? '')));
$chave_api = trim((string) ($_POST['chave_api'] ?? ''));
$chave_secreta = trim((string) ($_POST['chave_secreta'] ?? ''));
$webhook_url = trim((string) ($_POST['webhook_url'] ?? ''));
$acao = trim((string) ($_POST['acao'] ?? ''));
$ativar = 'Sim';

if ($nome === '' || $chave_api === '' || $chave_secreta === '') {
    echo 'Nome, chave API e chave secreta sao obrigatorios.';
    exit();
}

if ($nome !== 'EFY') {
    echo 'Apenas o gateway EFY e permitido neste sistema.';
    exit();
}

// EFY only: remove legados e garante somente EFY como ativo.
$pdo->query("DELETE FROM gateways WHERE UPPER(TRIM(nome)) <> 'EFY'");
$pdo->query("UPDATE gateways SET ativo = 'Nao'");

if ($acao === 'inserir') {
    $stmtExiste = $pdo->prepare("SELECT id FROM gateways WHERE UPPER(TRIM(nome)) = 'EFY' LIMIT 1");
    $stmtExiste->execute();
    $idExistente = (int) ($stmtExiste->fetchColumn() ?: 0);

    if ($idExistente > 0) {
        $stmt = $pdo->prepare("
            UPDATE gateways
            SET nome = 'EFY',
                chave_api = :chave_api,
                chave_secreta = :chave_secreta,
                webhook_url = :webhook_url,
                ativo = 'Sim'
            WHERE id = :id
        ");
        $stmt->execute([
            ':chave_api' => $chave_api,
            ':chave_secreta' => $chave_secreta,
            ':webhook_url' => $webhook_url,
            ':id' => $idExistente,
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO gateways (nome, chave_api, chave_secreta, webhook_url, ativo, data_cadastro)
            VALUES ('EFY', :chave_api, :chave_secreta, :webhook_url, 'Sim', NOW())
        ");
        $stmt->execute([
            ':chave_api' => $chave_api,
            ':chave_secreta' => $chave_secreta,
            ':webhook_url' => $webhook_url,
        ]);
    }
} else {
    $id = (int) ($_POST['id-gateway'] ?? 0);
    if ($id <= 0) {
        echo 'Gateway invalido.';
        exit();
    }

    $stmt = $pdo->prepare("
        UPDATE gateways
        SET nome = 'EFY',
            chave_api = :chave_api,
            chave_secreta = :chave_secreta,
            webhook_url = :webhook_url,
            ativo = 'Sim'
        WHERE id = :id
    ");
    $stmt->execute([
        ':chave_api' => $chave_api,
        ':chave_secreta' => $chave_secreta,
        ':webhook_url' => $webhook_url,
        ':id' => $id,
    ]);
}

echo 'Salvo com Sucesso';

