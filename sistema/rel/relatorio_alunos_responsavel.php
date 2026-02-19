<?php
include('../conexao.php');
require_once(__DIR__ . '/../../helpers.php');
@session_start();

$nivelSessao = $_SESSION['nivel'] ?? '';
$idUsuario = (int) ($_SESSION['id'] ?? 0);

if (!in_array($nivelSessao, ['Administrador', 'Secretario', 'Tesoureiro', 'Tutor', 'Parceiro', 'Professor', 'Vendedor'], true)) {
    echo 'Sem permissao para visualizar este relatorio.';
    exit();
}

$allowedLevels = ['Professor', 'Tutor', 'Parceiro', 'Vendedor', 'Secretario', 'Tesoureiro'];
$isRestrito = in_array($nivelSessao, ['Vendedor', 'Tutor', 'Parceiro', 'Secretario', 'Tesoureiro'], true);

$data_inicial = trim($_GET['data_inicial'] ?? '');
$data_final = trim($_GET['data_final'] ?? '');
$nivel = trim($_GET['nivel'] ?? '');
$status_filtro = trim($_GET['status_filtro'] ?? '');
$responsavel_id = filter_input(INPUT_GET, 'responsavel_id', FILTER_VALIDATE_INT);

if (!in_array($nivel, $allowedLevels, true)) {
    $nivel = '';
}

if (!in_array($status_filtro, ['matriculados', 'pendentes'], true)) {
    $status_filtro = '';
}

if ($responsavel_id) {
    $stmtNivel = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id LIMIT 1");
    $stmtNivel->execute([':id' => $responsavel_id]);
    $nivelDetectado = $stmtNivel->fetchColumn();
    if (in_array($nivelDetectado, $allowedLevels, true)) {
        $nivel = $nivelDetectado;
    }
}

if ($isRestrito) {
    $nivel = $nivelSessao;
    $responsavel_id = $idUsuario;
}

$hasComissoesRecebimentos = false;
$hasPagar = false;
try {
    $stmtTabela = $pdo->query("SHOW TABLES LIKE 'comissoes_recebimentos'");
    $hasComissoesRecebimentos = (bool) ($stmtTabela ? $stmtTabela->fetchColumn() : false);
} catch (Exception $e) {
    $hasComissoesRecebimentos = false;
}
try {
    $stmtTabela = $pdo->query("SHOW TABLES LIKE 'pagar'");
    $hasPagar = (bool) ($stmtTabela ? $stmtTabela->fetchColumn() : false);
} catch (Exception $e) {
    $hasPagar = false;
}

$resumo = [];
$detalhes = [];
$total_registros = 0;
$exprResponsavel = tableHasColumn($pdo, 'alunos', 'responsavel_id')
    ? "COALESCE(NULLIF(a.responsavel_id, 0), a.usuario)"
    : "a.usuario";

if ($nivel !== '') {
    $whereParts = ["resp.nivel = :nivel", "(m.pacote = 'Sim' OR m.id_pacote IS NULL OR m.id_pacote = 0)"];
    $params = [':nivel' => $nivel];

    if ($responsavel_id) {
        $whereParts[] = "resp.id = :responsavel_id";
        $params[':responsavel_id'] = $responsavel_id;
    }
    if ($data_inicial !== '') {
        $whereParts[] = "m.data >= :data_inicial";
        $params[':data_inicial'] = $data_inicial;
    }
    if ($data_final !== '') {
        $whereParts[] = "m.data <= :data_final";
        $params[':data_final'] = $data_final;
    }
    if ($status_filtro === 'matriculados') {
        $whereParts[] = "m.status <> 'Aguardando'";
    } elseif ($status_filtro === 'pendentes') {
        $whereParts[] = "m.status = 'Aguardando'";
    }

    $where = implode(' AND ', $whereParts);

    $comissaoResumoJoin = '';
    $comissaoDetalhesJoin = '';
    $comissaoResumo = '0 AS total_recebido, 0 AS total_pendente';
    $comissaoDetalhes = '0 AS comissao_recebida, 0 AS comissao_pendente';
    if ($hasPagar) {
        $comissaoDataFiltro = '';
        if ($data_inicial !== '') {
            $comissaoDataFiltro .= " AND p.data >= :data_inicial";
        }
        if ($data_final !== '') {
            $comissaoDataFiltro .= " AND p.data <= :data_final";
        }
        $comissaoResumoJoin = "LEFT JOIN (
                SELECT professor,
                    SUM(CASE WHEN pago = 'Sim' THEN valor ELSE 0 END) AS total_recebido,
                    SUM(CASE WHEN pago <> 'Sim' THEN valor ELSE 0 END) AS total_pendente
                FROM pagar p
                WHERE (p.descricao LIKE 'Comissao%' OR p.descricao LIKE 'Comissão%'){$comissaoDataFiltro}
                GROUP BY professor
            ) pc ON pc.professor = resp.id";
        $comissaoDetalhesJoin = "LEFT JOIN (
                SELECT professor, curso,
                    SUM(CASE WHEN pago = 'Sim' THEN valor ELSE 0 END) AS comissao_recebida,
                    SUM(CASE WHEN pago <> 'Sim' THEN valor ELSE 0 END) AS comissao_pendente
                FROM pagar p
                WHERE (p.descricao LIKE 'Comissao%' OR p.descricao LIKE 'Comissão%'){$comissaoDataFiltro}
                GROUP BY professor, curso
            ) pcx ON pcx.professor = resp.id AND pcx.curso = COALESCE(p.nome, c.nome)";
        $comissaoResumo = "COALESCE(pc.total_recebido, 0) AS total_recebido,
            COALESCE(pc.total_pendente, 0) AS total_pendente";
        $comissaoDetalhes = "COALESCE(pcx.comissao_recebida, 0) AS comissao_recebida,
            COALESCE(pcx.comissao_pendente, 0) AS comissao_pendente";
    } elseif ($hasComissoesRecebimentos) {
        $comissaoResumoJoin = "LEFT JOIN (
                SELECT usuario_id,
                    SUM(CASE WHEN status = 'RECEBIDO' THEN valor ELSE 0 END) AS total_recebido,
                    SUM(CASE WHEN status <> 'RECEBIDO' THEN valor ELSE 0 END) AS total_pendente
                FROM comissoes_recebimentos
                GROUP BY usuario_id
            ) crsum ON crsum.usuario_id = resp.id";
        $comissaoDetalhesJoin = "LEFT JOIN (
                SELECT id_matricula, usuario_id,
                    SUM(CASE WHEN status = 'RECEBIDO' THEN valor ELSE 0 END) AS comissao_recebida,
                    SUM(CASE WHEN status <> 'RECEBIDO' THEN valor ELSE 0 END) AS comissao_pendente
                FROM comissoes_recebimentos
                GROUP BY id_matricula, usuario_id
            ) cr ON cr.id_matricula = m.id AND cr.usuario_id = resp.id";
        $comissaoResumo = "COALESCE(crsum.total_recebido, 0) AS total_recebido,
            COALESCE(crsum.total_pendente, 0) AS total_pendente";
        $comissaoDetalhes = "COALESCE(cr.comissao_recebida, 0) AS comissao_recebida,
            COALESCE(cr.comissao_pendente, 0) AS comissao_pendente";
    }

    $sqlResumo = "SELECT resp.id, resp.nome, resp.nivel,
            COUNT(DISTINCT m.aluno) AS total_alunos,
            COUNT(DISTINCT m.id) AS total_matriculas,
            $comissaoResumo
        FROM matriculas m
        JOIN usuarios aluno ON aluno.id = m.aluno
        JOIN alunos a ON a.id = aluno.id_pessoa
        JOIN usuarios resp ON resp.id = {$exprResponsavel}
        $comissaoResumoJoin
        WHERE $where
        GROUP BY resp.id, resp.nome, resp.nivel
        ORDER BY resp.nome";
    $stmtResumo = $pdo->prepare($sqlResumo);
    $stmtResumo->execute($params);
    $resumo = $stmtResumo->fetchAll(PDO::FETCH_ASSOC);

    $sqlDetalhes = "SELECT m.id, m.data, m.status, m.pacote,
            aluno.nome AS nome_aluno,
            resp.nome AS nome_responsavel,
            resp.nivel AS nivel_responsavel,
            c.nome AS nome_curso,
            p.nome AS nome_pacote,
            $comissaoDetalhes
        FROM matriculas m
        JOIN usuarios aluno ON aluno.id = m.aluno
        JOIN alunos a ON a.id = aluno.id_pessoa
        JOIN usuarios resp ON resp.id = {$exprResponsavel}
        LEFT JOIN cursos c ON c.id = m.id_curso
        LEFT JOIN pacotes p ON p.id = m.id_curso
        $comissaoDetalhesJoin
        WHERE $where
        ORDER BY m.id DESC";
    $stmtDetalhes = $pdo->prepare($sqlDetalhes);
    $stmtDetalhes->execute($params);
    $detalhes = $stmtDetalhes->fetchAll(PDO::FETCH_ASSOC);

    $total_registros = count($detalhes);
}

$totalResumoAlunos = 0;
$totalResumoMatriculas = 0;
$totalResumoRecebido = 0.0;
$totalResumoPendente = 0.0;
foreach ($resumo as $row) {
    $totalResumoAlunos += (int) ($row['total_alunos'] ?? 0);
    $totalResumoMatriculas += (int) ($row['total_matriculas'] ?? 0);
    $totalResumoRecebido += (float) ($row['total_recebido'] ?? 0);
    $totalResumoPendente += (float) ($row['total_pendente'] ?? 0);
}

$dataInicialF = $data_inicial !== '' ? implode('/', array_reverse(explode('-', $data_inicial))) : '';
$dataFinalF = $data_final !== '' ? implode('/', array_reverse(explode('-', $data_final))) : '';
$periodoText = 'Periodo: ';
if ($dataInicialF !== '' && $dataFinalF !== '') {
    $periodoText .= $dataInicialF . ' ate ' . $dataFinalF;
} elseif ($dataInicialF !== '') {
    $periodoText .= $dataInicialF . ' em diante';
} elseif ($dataFinalF !== '') {
    $periodoText .= 'ate ' . $dataFinalF;
} else {
    $periodoText .= 'todo o periodo';
}

$statusText = 'Status: ';
if ($status_filtro === 'matriculados') {
    $statusText .= 'Matriculados/Aprovados';
} elseif ($status_filtro === 'pendentes') {
    $statusText .= 'Inscritos/Pendentes';
} else {
    $statusText .= 'Todos';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Relatorio de Alunos por Responsavel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { margin: 18px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
        h2 { font-size: 18px; margin-bottom: 4px; }
        .sub { margin: 0; font-size: 12px; color: #555; }
        .box { border: 1px solid #ddd; padding: 10px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f4f4f4; }
        .text-end { text-align: right; }
        .muted { color: #777; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Relatorio de Alunos por Responsavel</h2>
        <p class="sub"><?php echo htmlspecialchars($periodoText); ?></p>
        <p class="sub"><?php echo htmlspecialchars($statusText); ?></p>
        <?php if ($nivel !== '') : ?>
            <p class="sub">Nivel: <?php echo htmlspecialchars($nivel); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($nivel === '') : ?>
        <div class="box">
            <strong>Selecione o nivel do responsavel para gerar o relatorio.</strong>
        </div>
    <?php else : ?>
        <div class="box">
            <div style="margin-bottom: 8px;"><strong>Resumo por Responsavel</strong></div>
            <?php if (count($resumo) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Responsavel</th>
                            <th>Nivel</th>
                            <th>Qtde Alunos</th>
                            <th>Qtde Matriculas</th>
                            <th>Comissao Recebida</th>
                            <th>Comissao a Receber</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumo as $row) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nome'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['nivel'] ?? ''); ?></td>
                                <td><?php echo (int) ($row['total_alunos'] ?? 0); ?></td>
                                <td><?php echo (int) ($row['total_matriculas'] ?? 0); ?></td>
                                <td>R$ <?php echo number_format((float) ($row['total_recebido'] ?? 0), 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format((float) ($row['total_pendente'] ?? 0), 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Totais</th>
                            <th><?php echo $totalResumoAlunos; ?></th>
                            <th><?php echo $totalResumoMatriculas; ?></th>
                            <th>R$ <?php echo number_format($totalResumoRecebido, 2, ',', '.'); ?></th>
                            <th>R$ <?php echo number_format($totalResumoPendente, 2, ',', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else : ?>
                <div class="muted">Nenhum registro encontrado para o filtro informado.</div>
            <?php endif; ?>
        </div>

        <div class="box">
            <div style="margin-bottom: 8px;"><strong>Total de registros encontrados: <?php echo $total_registros; ?></strong></div>
            <?php if (count($detalhes) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Responsavel</th>
                            <th>Nivel</th>
                            <th>Aluno</th>
                            <th>Data Matricula</th>
                            <th>Curso/Pacote</th>
                            <th>Tipo</th>
                            <th>Comissao Recebida</th>
                            <th>Comissao a Receber</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalhes as $row) : ?>
                            <?php
                            $pacote = trim($row['pacote'] ?? '');
                            $isPacote = ($pacote === 'Sim');
                            $nomeItem = $isPacote ? ($row['nome_pacote'] ?? '') : ($row['nome_curso'] ?? '');
                            $dataMatricula = $row['data'] ? implode('/', array_reverse(explode('-', $row['data']))) : '';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nome_responsavel'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['nivel_responsavel'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['nome_aluno'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dataMatricula); ?></td>
                                <td><?php echo htmlspecialchars($nomeItem); ?></td>
                                <td><?php echo $isPacote ? 'Pacote' : 'Curso'; ?></td>
                                <td>R$ <?php echo number_format((float) ($row['comissao_recebida'] ?? 0), 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format((float) ($row['comissao_pendente'] ?? 0), 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="muted">Nenhum registro encontrado para o filtro informado.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
