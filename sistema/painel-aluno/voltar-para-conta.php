<?php
require_once('../conexao.php');

function sairComMensagemAluno(string $mensagem): void
{
    $mensagem = addslashes($mensagem);
    echo "<script>alert('{$mensagem}');window.location.href='index.php';</script>";
    exit();
}

$idRetorno = (int) ($_SESSION['switch_back_id'] ?? 0);
$nivelRetorno = (string) ($_SESSION['switch_back_nivel'] ?? '');
$idVendedorSwitch = (int) ($_SESSION['switch_vendedor_usuario_id'] ?? 0);
$idResgatePost = (int) ($_POST['vendedor_usuario_id_resgate'] ?? 0);

if (($idRetorno <= 0 || $nivelRetorno === '') && $idVendedorSwitch > 0) {
    $stmtVendedor = $pdo->prepare("SELECT id, nome, cpf, nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
    $stmtVendedor->execute([':id' => $idVendedorSwitch]);
    $vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC) ?: [];
    if ($vendedor && ($vendedor['nivel'] ?? '') === 'Vendedor' && ($vendedor['ativo'] ?? '') === 'Sim') {
        $idRetorno = (int) $vendedor['id'];
        $nivelRetorno = 'Vendedor';
    }
}

if (($idRetorno <= 0 || $nivelRetorno === '') && $idResgatePost > 0) {
    $stmtVendedorPost = $pdo->prepare("SELECT id, nome, cpf, nivel, ativo FROM usuarios WHERE id = :id AND nivel = 'Vendedor' LIMIT 1");
    $stmtVendedorPost->execute([':id' => $idResgatePost]);
    $vendedorPost = $stmtVendedorPost->fetch(PDO::FETCH_ASSOC) ?: [];
    if ($vendedorPost && ($vendedorPost['ativo'] ?? '') === 'Sim') {
        $idRetorno = (int) $vendedorPost['id'];
        $nivelRetorno = 'Vendedor';
    }
}

if ($idRetorno <= 0 || $nivelRetorno === '') {
    sairComMensagemAluno('Nao existe conta de retorno disponivel nesta sessao.');
}

$stmt = $pdo->prepare("SELECT id, nome, cpf, nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $idRetorno]);
$contaRetorno = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$contaRetorno || ($contaRetorno['ativo'] ?? '') !== 'Sim') {
    unset(
        $_SESSION['switch_back_id'],
        $_SESSION['switch_back_nivel'],
        $_SESSION['switch_back_nome'],
        $_SESSION['switch_back_cpf'],
        $_SESSION['switch_vendedor_usuario_id']
    );
    sairComMensagemAluno('Conta de retorno invalida ou inativa.');
}

$_SESSION['id'] = (int) $contaRetorno['id'];
$_SESSION['nivel'] = (string) $contaRetorno['nivel'];
$_SESSION['nome'] = (string) ($contaRetorno['nome'] ?? '');
$_SESSION['cpf'] = (string) ($contaRetorno['cpf'] ?? '');

unset(
    $_SESSION['switch_back_id'],
    $_SESSION['switch_back_nivel'],
    $_SESSION['switch_back_nome'],
    $_SESSION['switch_back_cpf'],
    $_SESSION['switch_vendedor_usuario_id']
);

$idDestino = (int) $_SESSION['id'];
$nivelDestino = (string) $_SESSION['nivel'];
$destino = ($nivelDestino === 'Aluno') ? 'index.php?pagina=home' : '../painel-admin/index.php';

echo "<script>
try {
    localStorage.setItem('active_user_id', '{$idDestino}');
    localStorage.setItem('active_user_level', '{$nivelDestino}');
    localStorage.setItem('active_user_at', String(Date.now()));
} catch (e) {}
window.location.href = '{$destino}';
</script>";
exit();
