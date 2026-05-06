<?php
require_once('../conexao.php');
require_once __DIR__ . '/../../config/csrf.php';
csrf_start();
csrf_require(true);

function responderErroImpersonacaoVendedor(string $mensagem): void
{
    $msg = addslashes($mensagem);
    echo "<script>alert('{$msg}');window.location.href='index.php?pagina=vendedores';</script>";
    exit();
}

function garantirTabelaAuditoriaImpersonacaoVendedor(PDO $pdo): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS auditoria_impersonacao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_admin_id INT NOT NULL,
            usuario_aluno_id INT NOT NULL,
            aluno_id_pessoa INT NOT NULL DEFAULT 0,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            acao VARCHAR(40) NOT NULL DEFAULT 'ENTRAR_COMO_ALUNO',
            INDEX idx_admin (usuario_admin_id),
            INDEX idx_aluno (usuario_aluno_id),
            INDEX idx_data_hora (data_hora)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
}

function registrarAuditoriaImpersonacaoVendedor(PDO $pdo, int $adminId, int $vendedorUsuarioId): void
{
    if ($adminId <= 0 || $vendedorUsuarioId <= 0) {
        return;
    }

    try {
        garantirTabelaAuditoriaImpersonacaoVendedor($pdo);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_impersonacao
                (usuario_admin_id, usuario_aluno_id, aluno_id_pessoa, ip, user_agent, acao)
            VALUES
                (:admin, :vendedor_usuario, 0, :ip, :ua, 'ENTRAR_COMO_VENDEDOR')
        ");
        $stmt->execute([
            ':admin' => $adminId,
            ':vendedor_usuario' => $vendedorUsuarioId,
            ':ip' => $ip,
            ':ua' => $ua,
        ]);
    } catch (Throwable $e) {
        // Nao bloqueia a troca de perfil se a auditoria falhar.
    }
}

$usuarioSessao = (int) ($_SESSION['id'] ?? 0);
$nivelSessao = (string) ($_SESSION['nivel'] ?? '');

if ($usuarioSessao <= 0 || $nivelSessao === '') {
    responderErroImpersonacaoVendedor('Sessao invalida. Faca login novamente.');
}

if ($nivelSessao !== 'Administrador') {
    responderErroImpersonacaoVendedor('Permissao negada para entrar como vendedor.');
}

$vendedorUsuarioId = (int) ($_POST['vendedor_usuario_id'] ?? 0);
if ($vendedorUsuarioId <= 0) {
    responderErroImpersonacaoVendedor('Vendedor invalido.');
}

$stmtVendedor = $pdo->prepare("
    SELECT id, nome, cpf, nivel, ativo
    FROM usuarios
    WHERE id = :id
      AND nivel = 'Vendedor'
    LIMIT 1
");
$stmtVendedor->execute([':id' => $vendedorUsuarioId]);
$vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$vendedor) {
    responderErroImpersonacaoVendedor('Usuario vendedor nao encontrado.');
}

if ((string) ($vendedor['ativo'] ?? '') !== 'Sim') {
    responderErroImpersonacaoVendedor('Usuario vendedor esta inativo.');
}

$_SESSION['switch_back_id'] = $usuarioSessao;
$_SESSION['switch_back_nivel'] = $nivelSessao;
$_SESSION['switch_back_nome'] = (string) ($_SESSION['nome'] ?? '');
$_SESSION['switch_back_cpf'] = (string) ($_SESSION['cpf'] ?? '');

$_SESSION['id'] = (int) ($vendedor['id'] ?? 0);
$_SESSION['nivel'] = 'Vendedor';
$_SESSION['nome'] = (string) ($vendedor['nome'] ?? '');
$_SESSION['cpf'] = (string) ($vendedor['cpf'] ?? '');

registrarAuditoriaImpersonacaoVendedor($pdo, $usuarioSessao, (int) $_SESSION['id']);

$idDestino = (int) $_SESSION['id'];
$nivelDestino = (string) $_SESSION['nivel'];

echo "<script>
try {
    localStorage.setItem('active_user_id', '{$idDestino}');
    localStorage.setItem('active_user_level', '{$nivelDestino}');
    localStorage.setItem('active_user_at', String(Date.now()));
} catch (e) {}
window.location.href = 'index.php?pagina=home_professor';
</script>";
exit();

