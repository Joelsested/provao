<?php
require_once('../conexao.php');
require_once __DIR__ . '/../../config/csrf.php';
csrf_start();
csrf_require(true);

function sairComMensagemAdmin(string $mensagem): void
{
    $mensagem = addslashes($mensagem);
    echo "<script>alert('{$mensagem}');window.location.href='index.php';</script>";
    exit();
}

$idRetorno = (int) ($_SESSION['switch_back_id'] ?? 0);
$nivelRetorno = (string) ($_SESSION['switch_back_nivel'] ?? '');

if ($idRetorno <= 0 || $nivelRetorno === '') {
    sairComMensagemAdmin('Nao existe conta de retorno disponivel nesta sessao.');
}

$stmt = $pdo->prepare("SELECT id, nome, cpf, nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $idRetorno]);
$contaRetorno = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$contaRetorno || (string) ($contaRetorno['ativo'] ?? '') !== 'Sim') {
    unset(
        $_SESSION['switch_back_id'],
        $_SESSION['switch_back_nivel'],
        $_SESSION['switch_back_nome'],
        $_SESSION['switch_back_cpf']
    );
    sairComMensagemAdmin('Conta de retorno invalida ou inativa.');
}

$_SESSION['id'] = (int) $contaRetorno['id'];
$_SESSION['nivel'] = (string) $contaRetorno['nivel'];
$_SESSION['nome'] = (string) ($contaRetorno['nome'] ?? '');
$_SESSION['cpf'] = (string) ($contaRetorno['cpf'] ?? '');

unset(
    $_SESSION['switch_back_id'],
    $_SESSION['switch_back_nivel'],
    $_SESSION['switch_back_nome'],
    $_SESSION['switch_back_cpf']
);

$idDestino = (int) $_SESSION['id'];
$nivelDestino = (string) $_SESSION['nivel'];

echo "<script>
try {
    localStorage.setItem('active_user_id', '{$idDestino}');
    localStorage.setItem('active_user_level', '{$nivelDestino}');
    localStorage.setItem('active_user_at', String(Date.now()));
} catch (e) {}
window.location.href = 'index.php';
</script>";
exit();

