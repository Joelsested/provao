<?php
require_once('../conexao.php');
require_once(__DIR__ . '/../../helpers.php');
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/csrf.php';
csrf_start();

function sairComMensagem(string $mensagem): void
{
    $mensagem = addslashes($mensagem);
    echo "<script>alert('{$mensagem}');history.back();</script>";
    exit();
}

function montarLoginAlunoUnico(PDO $pdo, string $emailBase, string $cpfDigits, int $usuarioVendedorId): string
{
    $base = trim($emailBase);
    if ($base === '') {
        $base = $cpfDigits !== '' ? ($cpfDigits . '@aluno.local') : ('aluno.vinculado.' . $usuarioVendedorId . '@aluno.local');
    }

    $login = $base;
    $contador = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $stmt->execute([':usuario' => $login]);
        $existe = (int) ($stmt->fetchColumn() ?: 0);
        if ($existe <= 0) {
            return $login;
        }
        $partes = explode('@', $base, 2);
        if (count($partes) === 2) {
            $login = $partes[0] . '+aluno' . $contador . '@' . $partes[1];
        } else {
            $login = $base . '.aluno' . $contador;
        }
        $contador++;
        if ($contador > 1000) {
            return 'aluno.vinculado.' . $usuarioVendedorId . '.' . time() . '@aluno.local';
        }
    }
}

function sincronizarDadosAlunoDoVendedor(
    PDO $pdo,
    int $usuarioAlunoId,
    int $usuarioAtendente,
    int $usuarioVendedorId,
    array $dadosAluno,
    bool $temResponsavelCol
): void {
    if ($usuarioAlunoId <= 0) {
        return;
    }

    $stmtPessoa = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id AND nivel = 'Aluno' LIMIT 1");
    $stmtPessoa->execute([':id' => $usuarioAlunoId]);
    $idPessoaAluno = (int) ($stmtPessoa->fetchColumn() ?: 0);
    if ($idPessoaAluno <= 0) {
        return;
    }

    $sqlAluno = "UPDATE alunos SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto, usuario = :usuario";
    if ($temResponsavelCol) {
        $sqlAluno .= ", responsavel_id = :responsavel_id";
    }
    $sqlAluno .= " WHERE id = :id";

    $paramsAluno = [
        ':id' => $idPessoaAluno,
        ':nome' => (string) ($dadosAluno['nome'] ?? ''),
        ':email' => (string) ($dadosAluno['email'] ?? ''),
        ':cpf' => (string) ($dadosAluno['cpf'] ?? ''),
        ':nascimento' => (string) ($dadosAluno['nascimento'] ?? ''),
        ':telefone' => (string) ($dadosAluno['telefone'] ?? ''),
        ':foto' => (string) ($dadosAluno['foto'] ?? 'sem-perfil.jpg'),
        ':usuario' => $usuarioAtendente
    ];
    if ($temResponsavelCol) {
        $paramsAluno[':responsavel_id'] = $usuarioVendedorId;
    }
    $stmtUpdAluno = $pdo->prepare($sqlAluno);
    $stmtUpdAluno->execute($paramsAluno);

    $emailAluno = trim((string) ($dadosAluno['email'] ?? ''));
    $sqlUsuario = "UPDATE usuarios SET nome = :nome, cpf = :cpf, foto = :foto";
    $paramsUsuario = [
        ':nome' => (string) ($dadosAluno['nome'] ?? ''),
        ':cpf' => (string) ($dadosAluno['cpf'] ?? ''),
        ':foto' => (string) ($dadosAluno['foto'] ?? 'sem-perfil.jpg'),
        ':id' => $usuarioAlunoId
    ];
    if ($emailAluno !== '') {
        $sqlUsuario .= ", usuario = :usuario";
        $paramsUsuario[':usuario'] = $emailAluno;
    }
    $sqlUsuario .= " WHERE id = :id AND nivel = 'Aluno'";
    $stmtUpdUsuario = $pdo->prepare($sqlUsuario);
    $stmtUpdUsuario->execute($paramsUsuario);
}

function buscarOuCriarAlunoDoVendedor(PDO $pdo, int $usuarioVendedorId, array $vendedor, int $idPessoaVendedor): int
{
    $cpfDigits = digitsOnly((string) ($vendedor['cpf'] ?? ''));
    if ($cpfDigits === '') {
        return 0;
    }

    $stmtPessoaVendedor = $pdo->prepare("SELECT nome, email, cpf, nascimento, foto, telefone FROM vendedores WHERE id = :id LIMIT 1");
    $stmtPessoaVendedor->execute([':id' => $idPessoaVendedor]);
    $pessoaVendedor = $stmtPessoaVendedor->fetch(PDO::FETCH_ASSOC) ?: [];

    $nomeAluno = trim((string) ($pessoaVendedor['nome'] ?? $vendedor['nome'] ?? 'Aluno'));
    $emailBase = trim((string) ($pessoaVendedor['email'] ?? $vendedor['usuario'] ?? ''));
    $cpfAluno = trim((string) ($pessoaVendedor['cpf'] ?? $vendedor['cpf'] ?? ''));
    $nascimentoAluno = trim((string) ($pessoaVendedor['nascimento'] ?? ''));
    $telefoneAluno = trim((string) ($pessoaVendedor['telefone'] ?? ''));
    if ($nascimentoAluno === '') {
        $nascimentoAluno = '1990-01-01';
    }
    $senhaAluno = birthDigits($nascimentoAluno);
    if ($senhaAluno === '') {
        $nascimentoAluno = '1990-01-01';
        $senhaAluno = '01011990';
    }
    $senhaCrip = md5($senhaAluno);
    $fotoAluno = trim((string) ($pessoaVendedor['foto'] ?? $vendedor['foto'] ?? '')) ?: 'sem-perfil.jpg';
    $temResponsavelCol = ensureAlunosResponsavelColumn($pdo);

    $cpfColUsuarios = cleanCpfColumn('cpf');
    $stmtAlunoCpf = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Aluno' AND {$cpfColUsuarios} = :cpf ORDER BY (ativo = 'Sim') DESC, id DESC LIMIT 1");
    $stmtAlunoCpf->execute([':cpf' => $cpfDigits]);
    $usuarioAlunoId = (int) ($stmtAlunoCpf->fetchColumn() ?: 0);
    if ($usuarioAlunoId > 0) {
        $stmtResponsavel = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
        $stmtResponsavel->execute([':id' => $usuarioVendedorId]);
        $responsavel = $stmtResponsavel->fetch(PDO::FETCH_ASSOC) ?: [];
        $usuarioAtendente = resolveAtendenteId($pdo, $responsavel, date('Y-m-d'));
        if ($usuarioAtendente <= 0) {
            $usuarioAtendente = $usuarioVendedorId;
        }
        sincronizarDadosAlunoDoVendedor($pdo, $usuarioAlunoId, $usuarioAtendente, $usuarioVendedorId, [
            'nome' => $nomeAluno,
            'email' => $emailBase,
            'cpf' => $cpfAluno,
            'nascimento' => $nascimentoAluno,
            'telefone' => $telefoneAluno,
            'foto' => $fotoAluno
        ], $temResponsavelCol);
        if (function_exists('salvarVinculoVendedorAluno')) {
            salvarVinculoVendedorAluno($pdo, $usuarioVendedorId, $usuarioAlunoId);
        } elseif (function_exists('tentarVinculoVendedorAlunoPorCpf')) {
            tentarVinculoVendedorAlunoPorCpf($pdo, $cpfDigits);
        }
        return $usuarioAlunoId;
    }

    $cpfColAlunos = cleanCpfColumn('cpf');
    $stmtPessoaAluno = $pdo->prepare("SELECT id, nome, email, nascimento, foto, telefone FROM alunos WHERE {$cpfColAlunos} = :cpf ORDER BY id DESC LIMIT 1");
    $stmtPessoaAluno->execute([':cpf' => $cpfDigits]);
    $alunoPessoa = $stmtPessoaAluno->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtResponsavel = $pdo->prepare("SELECT id, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
    $stmtResponsavel->execute([':id' => $usuarioVendedorId]);
    $responsavel = $stmtResponsavel->fetch(PDO::FETCH_ASSOC) ?: [];
    $usuarioAtendente = resolveAtendenteId($pdo, $responsavel, date('Y-m-d'));
    if ($usuarioAtendente <= 0) {
        $usuarioAtendente = $usuarioVendedorId;
    }

    $loginAluno = '';
    if (!empty($alunoPessoa['email'])) {
        $emailExistente = trim((string) $alunoPessoa['email']);
        $stmtLogin = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND nivel = 'Aluno' LIMIT 1");
        $stmtLogin->execute([':usuario' => $emailExistente]);
        $usuarioMesmoLogin = (int) ($stmtLogin->fetchColumn() ?: 0);
        if ($usuarioMesmoLogin <= 0) {
            $loginAluno = $emailExistente;
        }
    }
    if ($loginAluno === '') {
        $loginAluno = montarLoginAlunoUnico($pdo, $emailBase, $cpfDigits, $usuarioVendedorId);
    }
    try {
        $pdo->beginTransaction();

        $alunoPessoaId = (int) ($alunoPessoa['id'] ?? 0);
        if ($alunoPessoaId <= 0) {
            $alunoId = nextTableId($pdo, 'alunos');
            if ($alunoId) {
                $sqlAluno = "INSERT INTO alunos SET id = :id, nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto, data = curDate(), usuario = :usuario, ativo = 'Sim'";
            } else {
                $sqlAluno = "INSERT INTO alunos SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto, data = curDate(), usuario = :usuario, ativo = 'Sim'";
            }
            if ($temResponsavelCol) {
                $sqlAluno .= ", responsavel_id = :responsavel_id";
            }
            $stmtInsertAluno = $pdo->prepare($sqlAluno);
            $paramsAluno = [
                ':nome' => $nomeAluno,
                ':email' => $emailBase !== '' ? $emailBase : $loginAluno,
                ':cpf' => $cpfAluno,
                ':nascimento' => $nascimentoAluno,
                ':telefone' => $telefoneAluno,
                ':foto' => $fotoAluno,
                ':usuario' => $usuarioAtendente
            ];
            if ($alunoId) {
                $paramsAluno[':id'] = $alunoId;
            }
            if ($temResponsavelCol) {
                $paramsAluno[':responsavel_id'] = $usuarioVendedorId;
            }
            $stmtInsertAluno->execute($paramsAluno);
            $alunoPessoaId = $alunoId ?: (int) $pdo->lastInsertId();
        } else {
            $sqlSyncAluno = "UPDATE alunos SET nome = :nome, email = :email, cpf = :cpf, nascimento = :nascimento, telefone = :telefone, foto = :foto, usuario = :usuario";
            if ($temResponsavelCol) {
                $sqlSyncAluno .= ", responsavel_id = :responsavel_id";
            }
            $sqlSyncAluno .= " WHERE id = :id";
            $stmtSyncAluno = $pdo->prepare($sqlSyncAluno);
            $paramsSyncAluno = [
                ':id' => $alunoPessoaId,
                ':nome' => $nomeAluno,
                ':email' => $emailBase !== '' ? $emailBase : ($alunoPessoa['email'] ?? ''),
                ':cpf' => $cpfAluno,
                ':nascimento' => $nascimentoAluno,
                ':telefone' => $telefoneAluno,
                ':foto' => $fotoAluno,
                ':usuario' => $usuarioAtendente
            ];
            if ($temResponsavelCol) {
                $paramsSyncAluno[':responsavel_id'] = $usuarioVendedorId;
            }
            $stmtSyncAluno->execute($paramsSyncAluno);
        }

        $usuarioIdAluno = nextTableId($pdo, 'usuarios');
        if ($usuarioIdAluno) {
            $sqlUsuario = "INSERT INTO usuarios SET id = :id, nome = :nome, usuario = :usuario, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Aluno', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()";
        } else {
            $sqlUsuario = "INSERT INTO usuarios SET nome = :nome, usuario = :usuario, senha = :senha, senha_crip = :senha_crip, cpf = :cpf, nivel = 'Aluno', foto = :foto, id_pessoa = :id_pessoa, ativo = 'Sim', data = curDate()";
        }
        $stmtInsertUsuario = $pdo->prepare($sqlUsuario);
        $paramsUsuario = [
            ':nome' => $nomeAluno,
            ':usuario' => $loginAluno,
            ':senha' => '',
            ':senha_crip' => $senhaCrip,
            ':cpf' => $cpfAluno,
            ':foto' => $fotoAluno,
            ':id_pessoa' => $alunoPessoaId
        ];
        if ($usuarioIdAluno) {
            $paramsUsuario[':id'] = $usuarioIdAluno;
        }
        $stmtInsertUsuario->execute($paramsUsuario);
        $usuarioAlunoId = $usuarioIdAluno ?: (int) $pdo->lastInsertId();

        if ($usuarioAlunoId <= 0) {
            throw new Exception('Falha ao criar usuario aluno.');
        }

        if (function_exists('salvarVinculoVendedorAluno')) {
            salvarVinculoVendedorAluno($pdo, $usuarioVendedorId, $usuarioAlunoId);
        } elseif (function_exists('tentarVinculoVendedorAlunoPorCpf')) {
            tentarVinculoVendedorAlunoPorCpf($pdo, $cpfDigits);
        }

        $pdo->commit();
        return $usuarioAlunoId;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 0;
    }
}

$nivelSessao = (string) ($_SESSION['nivel'] ?? '');
$usuarioSessao = (int) ($_SESSION['id'] ?? 0);

if ($usuarioSessao <= 0 || $nivelSessao === '') {
    sairComMensagem('Sessao invalida. Faca login novamente.');
}

$niveisPermitidos = ['Vendedor', 'Administrador', 'Secretario', 'Tesoureiro'];
if (!in_array($nivelSessao, $niveisPermitidos, true)) {
    sairComMensagem('Permissao negada para trocar de perfil.');
}

$usuarioVendedorId = 0;
if ($nivelSessao === 'Vendedor') {
    $usuarioVendedorId = $usuarioSessao;
} else {
    $usuarioVendedorId = (int) ($_POST['vendedor_usuario_id'] ?? 0);
}

if ($usuarioVendedorId <= 0) {
    sairComMensagem('Vendedor nao informado.');
}

$stmtVendedor = $pdo->prepare("SELECT id, nome, usuario, cpf, id_pessoa, nivel, foto, ativo FROM usuarios WHERE id = :id LIMIT 1");
$stmtVendedor->execute([':id' => $usuarioVendedorId]);
$vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC) ?: [];
if (($vendedor['nivel'] ?? '') !== 'Vendedor' || ($vendedor['ativo'] ?? '') !== 'Sim') {
    sairComMensagem('Conta de vendedor invalida ou inativa.');
}

$idPessoaVendedor = (int) ($vendedor['id_pessoa'] ?? 0);
if ($idPessoaVendedor <= 0) {
    $cpfDigits = digitsOnly((string) ($vendedor['cpf'] ?? ''));
    $emailVendedor = trim((string) ($vendedor['usuario'] ?? ''));

    if ($cpfDigits !== '') {
        $stmtVend = $pdo->prepare("SELECT id FROM vendedores WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf LIMIT 1");
        $stmtVend->execute([':cpf' => $cpfDigits]);
        $idPessoaVendedor = (int) ($stmtVend->fetchColumn() ?: 0);
    }

    if ($idPessoaVendedor <= 0 && $emailVendedor !== '') {
        $stmtVend = $pdo->prepare("SELECT id FROM vendedores WHERE email = :email LIMIT 1");
        $stmtVend->execute([':email' => $emailVendedor]);
        $idPessoaVendedor = (int) ($stmtVend->fetchColumn() ?: 0);
    }

    if ($idPessoaVendedor > 0) {
        $stmtAtualizaPessoa = $pdo->prepare("UPDATE usuarios SET id_pessoa = :id_pessoa WHERE id = :id LIMIT 1");
        $stmtAtualizaPessoa->execute([
            ':id_pessoa' => $idPessoaVendedor,
            ':id' => $usuarioVendedorId
        ]);
    }
}

if ($idPessoaVendedor <= 0) {
    sairComMensagem('Vendedor invalido para troca de perfil.');
}
try {
    $stmtCol = $pdo->query("SHOW COLUMNS FROM vendedores LIKE 'pode_login_como_aluno'");
    $hasCol = (bool) ($stmtCol && $stmtCol->fetch(PDO::FETCH_ASSOC));
    if (!$hasCol) {
        $pdo->exec("ALTER TABLE vendedores ADD COLUMN pode_login_como_aluno TINYINT(1) NOT NULL DEFAULT 0");
    }
    $stmtPerm = $pdo->prepare("SELECT pode_login_como_aluno FROM vendedores WHERE id = :id LIMIT 1");
    $stmtPerm->execute([':id' => $idPessoaVendedor]);
    $podeLoginComoAluno = (int) ($stmtPerm->fetchColumn() ?: 0) === 1;
    if (!$podeLoginComoAluno) {
        sairComMensagem('Login como aluno nao esta liberado para este vendedor.');
    }
} catch (Exception $e) {
    sairComMensagem('Nao foi possivel validar permissao de login como aluno.');
}

$usuarioAlunoId = buscarOuCriarAlunoDoVendedor($pdo, $usuarioVendedorId, $vendedor, $idPessoaVendedor);

if ($usuarioAlunoId <= 0) {
    sairComMensagem('Nao foi possivel localizar ou criar o cadastro de aluno deste vendedor.');
}

$stmtAluno = $pdo->prepare("SELECT id, nome, cpf, nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
$stmtAluno->execute([':id' => $usuarioAlunoId]);
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];
if (($aluno['nivel'] ?? '') !== 'Aluno' || ($aluno['ativo'] ?? '') !== 'Sim') {
    sairComMensagem('Conta de aluno invalida ou inativa.');
}
if (function_exists('salvarVinculoVendedorAluno')) {
    salvarVinculoVendedorAluno($pdo, $usuarioVendedorId, $usuarioAlunoId);
}

$_SESSION['switch_back_id'] = $usuarioSessao;
$_SESSION['switch_back_nivel'] = $nivelSessao;
$_SESSION['switch_back_nome'] = (string) ($_SESSION['nome'] ?? '');
$_SESSION['switch_back_cpf'] = (string) ($_SESSION['cpf'] ?? '');
$_SESSION['switch_vendedor_usuario_id'] = $usuarioVendedorId;

$_SESSION['id'] = (int) $aluno['id'];
$_SESSION['nivel'] = 'Aluno';
$_SESSION['nome'] = (string) ($aluno['nome'] ?? '');
$_SESSION['cpf'] = (string) ($aluno['cpf'] ?? '');

$idDestino = (int) $_SESSION['id'];
$nivelDestino = (string) $_SESSION['nivel'];
echo "<script>
try {
    localStorage.setItem('active_user_id', '{$idDestino}');
    localStorage.setItem('active_user_level', '{$nivelDestino}');
    localStorage.setItem('active_user_at', String(Date.now()));
} catch (e) {}
window.location.href = '../painel-aluno/index.php?pagina=home';
</script>";
exit();
