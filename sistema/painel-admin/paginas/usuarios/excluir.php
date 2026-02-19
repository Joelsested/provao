<?php
require_once("../../../conexao.php");

@session_start();
if (@$_SESSION['nivel'] !== 'Administrador') {
    echo 'Nao autorizado.';
    exit();
}

$raw_id = $_POST['id'] ?? ($_GET['id'] ?? null);
if ($raw_id === null || $raw_id === '' || !is_numeric($raw_id)) {
    echo 'ID invalido.';
    exit();
}
$id = (int) $raw_id;
if ($id <= 0) {
    echo 'ID invalido.';
    exit();
}

function tabelaExiste(PDO $pdo, string $tabela): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tabela");
    $stmt->execute([':tabela' => $tabela]);
    return (bool) $stmt->fetchColumn();
}

try {
    $stmt = $pdo->prepare("SELECT id_pessoa, nivel, foto FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        echo 'Usuario nao encontrado.';
        exit();
    }

    $nivel = $usuario['nivel'] ?? '';
    $idPessoa = (int) ($usuario['id_pessoa'] ?? 0);
    $foto = $usuario['foto'] ?? '';

    $pdo->beginTransaction();

    $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    if ($idPessoa > 0) {
        $tabela = '';
        switch ($nivel) {
            case 'Administrador':
                $tabela = 'administradores';
                break;
            case 'Secretario':
                $tabela = 'secretarios';
                break;
            case 'Tesoureiro':
                $tabela = 'tesoureiros';
                break;
            case 'Tutor':
                $tabela = 'tutores';
                break;
            case 'Parceiro':
                $tabela = 'parceiros';
                break;
            case 'Assessor':
                $tabela = 'assessores';
                break;
            case 'Vendedor':
                $tabela = 'vendedores';
                break;
            case 'Professor':
                $tabela = 'professores';
                break;
            case 'Aluno':
                $tabela = 'alunos';
                break;
        }
        if ($tabela !== '' && tabelaExiste($pdo, $tabela)) {
            $stmtPessoa = $pdo->prepare("DELETE FROM {$tabela} WHERE id = :id");
            $stmtPessoa->execute([':id' => $idPessoa]);
        }
    }

    $pdo->commit();

    if ($foto !== '' && $foto !== 'sem-perfil.jpg') {
        $foto = basename($foto);
        $path = ($nivel === 'Aluno') ? '../../../painel-aluno/img/perfil/' . $foto : '../../img/perfil/' . $foto;
        @unlink($path);
    }

    echo 'Excluido com Sucesso';
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo 'Erro ao excluir usuario.';
}

?>
