<?php
require_once('../conexao.php');
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/csrf.php';
csrf_start();
csrf_require(true);

function responderErroImpersonacao(string $mensagem): void
{
    $msg = addslashes($mensagem);
    echo "<script>alert('{$msg}');window.location.href='index.php?pagina=alunos';</script>";
    exit();
}

function garantirTabelaAuditoriaImpersonacao(PDO $pdo): void
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

function registrarAuditoriaImpersonacao(PDO $pdo, int $adminId, int $alunoUsuarioId, int $alunoIdPessoa): void
{
    if ($adminId <= 0 || $alunoUsuarioId <= 0) {
        return;
    }

    try {
        garantirTabelaAuditoriaImpersonacao($pdo);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_impersonacao
                (usuario_admin_id, usuario_aluno_id, aluno_id_pessoa, ip, user_agent, acao)
            VALUES
                (:admin, :aluno_usuario, :aluno_pessoa, :ip, :ua, 'ENTRAR_COMO_ALUNO')
        ");
        $stmt->execute([
            ':admin' => $adminId,
            ':aluno_usuario' => $alunoUsuarioId,
            ':aluno_pessoa' => $alunoIdPessoa,
            ':ip' => $ip,
            ':ua' => $ua,
        ]);
    } catch (Throwable $e) {
        // Nao bloqueia a troca de perfil se a auditoria falhar.
    }
}

$usuarioSessao = (int) ($_SESSION['id'] ?? 0);
$nivelSessao = (string) ($_SESSION['nivel'] ?? '');
$niveisPermitidosImpersonacao = ['Administrador', 'Secretario', 'Tutor', 'Vendedor'];

if ($usuarioSessao <= 0 || $nivelSessao === '') {
    responderErroImpersonacao('Sessao invalida. Faca login novamente.');
}

if (!in_array($nivelSessao, $niveisPermitidosImpersonacao, true)) {
    responderErroImpersonacao('Permissao negada para entrar como aluno.');
}

$alunoId = (int) ($_POST['aluno_id'] ?? 0);
if ($alunoId <= 0) {
    responderErroImpersonacao('Aluno invalido.');
}

$stmtAlunoUsuario = $pdo->prepare("
    SELECT id, nome, cpf, nivel, ativo, id_pessoa
    FROM usuarios
    WHERE id_pessoa = :id_pessoa
      AND nivel = 'Aluno'
    ORDER BY (ativo = 'Sim') DESC, id DESC
    LIMIT 1
");
$stmtAlunoUsuario->execute([':id_pessoa' => $alunoId]);
$alunoUsuario = $stmtAlunoUsuario->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$alunoUsuario) {
    responderErroImpersonacao('Nao existe usuario de acesso para este aluno.');
}

if ((string) ($alunoUsuario['ativo'] ?? '') !== 'Sim') {
    responderErroImpersonacao('Usuario do aluno esta inativo.');
}

$_SESSION['switch_back_id'] = $usuarioSessao;
$_SESSION['switch_back_nivel'] = $nivelSessao;
$_SESSION['switch_back_nome'] = (string) ($_SESSION['nome'] ?? '');
$_SESSION['switch_back_cpf'] = (string) ($_SESSION['cpf'] ?? '');
unset($_SESSION['switch_vendedor_usuario_id']);

$_SESSION['id'] = (int) ($alunoUsuario['id'] ?? 0);
$_SESSION['nivel'] = 'Aluno';
$_SESSION['nome'] = (string) ($alunoUsuario['nome'] ?? '');
$_SESSION['cpf'] = (string) ($alunoUsuario['cpf'] ?? '');

registrarAuditoriaImpersonacao(
    $pdo,
    $usuarioSessao,
    (int) ($_SESSION['id'] ?? 0),
    (int) ($alunoUsuario['id_pessoa'] ?? 0)
);

$idDestino = (int) ($_SESSION['id'] ?? 0);
$nivelDestino = (string) ($_SESSION['nivel'] ?? '');

echo "<script>
try {
    localStorage.setItem('active_user_id', '{$idDestino}');
    localStorage.setItem('active_user_level', '{$nivelDestino}');
    localStorage.setItem('active_user_at', String(Date.now()));
} catch (e) {}
window.location.href = '../painel-aluno/index.php?pagina=home';
</script>";
exit();
