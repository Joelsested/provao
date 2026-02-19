<?php
require_once('../conexao.php');
require_once('verificar.php');
require_once(__DIR__ . '/../../../helpers.php');
$pag = 'relatorio_financeiro_aluno';

@session_start();

$nivel = $_SESSION['nivel'] ?? '';
$allowedLevels = ['Vendedor', 'Tutor', 'Parceiro', 'Secretario', 'Tesoureiro'];
if (!in_array($nivel, $allowedLevels, true)) {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

$id_usuario = (int) ($_SESSION['id'] ?? 0);
$busca = trim($_GET['busca'] ?? '');

$exprResponsavel = tableHasColumn($pdo, 'alunos', 'responsavel_id')
    ? "COALESCE(NULLIF(responsavel_id, 0), usuario)"
    : "usuario";
$sql = "SELECT id, nome, email, telefone, cpf FROM alunos WHERE {$exprResponsavel} = :usuario";
$params = [':usuario' => $id_usuario];

if ($busca !== '') {
    $sql .= " AND (nome LIKE :busca OR email LIKE :busca OR cpf LIKE :busca)";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_reg = count($res);
?>

<div class="bs-example widget-shadow" style="padding:15px;">
    <form method="GET" action="index.php">
        <input type="hidden" name="pagina" value="relatorio_financeiro_aluno">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Buscar aluno</label>
                    <input type="text" class="form-control" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome, email ou CPF">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group" style="margin-top: 25px;">
                    <button type="submit" class="btn btn-success"><i class="fa fa-filter"></i> Filtrar</button>
                    <a href="index.php?pagina=relatorio_financeiro_aluno" class="btn btn-warning"><i class="fa fa-eraser"></i> Limpar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="bs-example widget-shadow" style="padding:15px;">
    <div style="margin-bottom: 10px;">
        <strong>Total de alunos: <?php echo $total_reg; ?></strong>
    </div>

    <?php if ($total_reg > 0) : ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Relatorio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($res as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nome'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['telefone'] ?? ''); ?></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="index.php?pagina=relatorio_aluno&aluno=<?php echo (int) $row['id']; ?>">
                                <i class="fa fa-money"></i> Financeiro
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="alert alert-info" style="margin: 0;">Nenhum aluno encontrado.</div>
    <?php endif; ?>
</div>
