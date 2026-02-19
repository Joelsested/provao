<?php
include('../conexao.php');
@session_start();

if (!in_array(@$_SESSION['nivel'], ['Administrador', 'Secretario', 'Tesoureiro', 'Tutor', 'Parceiro', 'Professor', 'Vendedor'], true)) {
    echo 'Sem permissao para visualizar este relatorio.';
    exit();
}

$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';
$status_filtro = $_GET['status_filtro'] ?? '';
$nivel_responsavel = trim($_GET['nivel_responsavel'] ?? '');
$niveis_responsavel = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro'];
$responsavel_id = filter_input(INPUT_GET, 'responsavel_id', FILTER_VALIDATE_INT);
$responsavel_nome = '';

if (!in_array($nivel_responsavel, $niveis_responsavel, true)) {
    $nivel_responsavel = '';
}

if ($responsavel_id) {
    $stmtNivel = $pdo->prepare("SELECT nome, nivel FROM usuarios WHERE id = :id LIMIT 1");
    $stmtNivel->execute([':id' => $responsavel_id]);
    $respRow = $stmtNivel->fetch(PDO::FETCH_ASSOC);
    if ($respRow) {
        $responsavel_nome = $respRow['nome'] ?? '';
        $nivelDetectado = $respRow['nivel'] ?? '';
        if (in_array($nivelDetectado, $niveis_responsavel, true)) {
            $nivel_responsavel = $nivelDetectado;
        }
    }
}

$sql = "SELECT 
            m.id AS id_matricula,
            m.data AS data_matricula,
            m.status,
            m.valor,
            m.subtotal,
            m.total_recebido,
            m.forma_pgto,
            m.alertado,
            m.pacote,
            m.id_curso,
            m.id_pacote,
            c.nome AS nome_curso,
            p1.nome AS nome_pacote_id,
            p2.nome AS nome_pacote_curso,
            a.nome AS nome_aluno,
            a.cpf AS cpf_aluno,
            a.email AS email_aluno,
            v.nome AS nome_vendedor,
            CASE
                WHEN m.status = 'Aguardando' THEN 'Nao Pago'
                WHEN m.status IN ('Matriculado', 'Finalizado') THEN 'Pago'
                ELSE 'Indefinido'
            END AS situacao_pagamento
        FROM matriculas m
        LEFT JOIN usuarios u ON u.id = m.aluno
        LEFT JOIN alunos a ON a.id = u.id_pessoa
        LEFT JOIN usuarios v ON v.id = a.usuario
        LEFT JOIN cursos c ON c.id = m.id_curso
        LEFT JOIN pacotes p1 ON p1.id = m.id_pacote
        LEFT JOIN pacotes p2 ON p2.id = m.id_curso
        WHERE 1=1
        AND (m.pacote = 'Sim' OR m.id_pacote IS NULL OR m.id_pacote = 0)";
$params = [];

if (!empty($data_inicial)) {
    $sql .= " AND m.data >= :data_inicial";
    $params[':data_inicial'] = $data_inicial;
}
if (!empty($data_final)) {
    $sql .= " AND m.data <= :data_final";
    $params[':data_final'] = $data_final;
}
if (!empty($status_filtro)) {
    if ($status_filtro === 'Pago') {
        $sql .= " AND m.status IN ('Matriculado', 'Finalizado')";
    } elseif ($status_filtro === 'Aguardando') {
        $sql .= " AND m.status = 'Aguardando'";
    }
}
if ($nivel_responsavel !== '') {
    $sql .= " AND v.nivel = :nivel_responsavel";
    $params[':nivel_responsavel'] = $nivel_responsavel;
}
if ($responsavel_id) {
    $sql .= " AND v.id = :responsavel_id";
    $params[':responsavel_id'] = $responsavel_id;
}

$sql .= " ORDER BY m.id DESC";

$query = $pdo->prepare($sql);
$query->execute($params);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$sql_totais = "SELECT 
                SUM(m.valor) AS valor_total,
                SUM(m.total_recebido) AS total_pago,
                SUM(CASE 
                    WHEN m.status = 'Aguardando' THEN m.valor - m.total_recebido
                    ELSE 0 
                END) AS total_pendente
            FROM matriculas m
            LEFT JOIN usuarios u ON u.id = m.aluno
            LEFT JOIN alunos a ON a.id = u.id_pessoa
            LEFT JOIN usuarios v ON v.id = a.usuario
            WHERE 1=1
            AND (m.pacote = 'Sim' OR m.id_pacote IS NULL OR m.id_pacote = 0)";
if (!empty($data_inicial)) {
    $sql_totais .= " AND m.data >= :data_inicial";
}
if (!empty($data_final)) {
    $sql_totais .= " AND m.data <= :data_final";
}
if (!empty($status_filtro)) {
    if ($status_filtro === 'Pago') {
        $sql_totais .= " AND m.status IN ('Matriculado', 'Finalizado')";
    } elseif ($status_filtro === 'Aguardando') {
        $sql_totais .= " AND m.status = 'Aguardando'";
    }
}
if ($nivel_responsavel !== '') {
    $sql_totais .= " AND v.nivel = :nivel_responsavel";
}
if ($responsavel_id) {
    $sql_totais .= " AND v.id = :responsavel_id";
}

$query_totais = $pdo->prepare($sql_totais);
$query_totais->execute($params);
$totais = $query_totais->fetch(PDO::FETCH_ASSOC);

$valor_total = (float) ($totais['valor_total'] ?? 0);
$total_pago = (float) ($totais['total_pago'] ?? 0);
$total_pendente = (float) ($totais['total_pendente'] ?? 0);

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
if ($status_filtro === 'Pago') {
    $statusText .= 'Pago';
} elseif ($status_filtro === 'Aguardando') {
    $statusText .= 'Aguardando';
} else {
    $statusText .= 'Todos';
}
$responsavelText = '';
if ($responsavel_id && $responsavel_nome !== '') {
    $responsavelText = trim($responsavel_nome);
    if ($nivel_responsavel !== '') {
        $responsavelText .= " ({$nivel_responsavel})";
    }
} elseif ($nivel_responsavel !== '') {
    $responsavelText = $nivel_responsavel;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Relatorio de Alunos</title>
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
        .muted { color: #777; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Relatorio de Alunos</h2>
        <p class="sub"><?php echo htmlspecialchars($periodoText); ?></p>
        <p class="sub"><?php echo htmlspecialchars($statusText); ?></p>
        <?php if ($responsavelText !== '') : ?>
            <p class="sub">Responsavel: <?php echo htmlspecialchars($responsavelText); ?></p>
        <?php endif; ?>
    </div>

    <div class="box">
        <table>
            <thead>
                <tr>
                    <th>Valor Total</th>
                    <th>Total Pago</th>
                    <th>Total Pendente</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($total_pendente, 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="box">
        <div style="margin-bottom: 8px;"><strong>Total de registros encontrados: <?php echo count($res); ?></strong></div>
        <?php if (count($res) > 0) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>CPF</th>
                        <th>Curso/Pacote</th>
                        <th>Valor</th>
                        <th>Forma Pgto</th>
                        <th>Status</th>
                        <th>Vendedor</th>
                        <th>Data Matricula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($res as $dado) : ?>
                        <?php
                        $isPacote = strtolower(trim($dado['pacote'] ?? '')) === 'sim' || (int) ($dado['id_pacote'] ?? 0) > 0;
                        if ($isPacote) {
                            $cursoNome = trim($dado['nome_pacote_id'] ?? '');
                            if ($cursoNome === '') {
                                $cursoNome = trim($dado['nome_pacote_curso'] ?? '');
                            }
                        } else {
                            $cursoNome = $dado['nome_curso'] ?? '';
                        }
                        $formaPgto = trim($dado['forma_pgto'] ?? '') !== '' ? $dado['forma_pgto'] : 'Ativacao Pacote';
                        $dataMatricula = $dado['data_matricula'] ? date('d/m/Y', strtotime($dado['data_matricula'])) : '';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dado['nome_aluno'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($dado['cpf_aluno'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($cursoNome); ?></td>
                            <td>R$ <?php echo number_format((float) ($dado['valor'] ?? 0), 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($formaPgto); ?></td>
                            <td><?php echo htmlspecialchars($dado['situacao_pagamento'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($dado['nome_vendedor'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($dataMatricula); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="muted">Nenhum registro encontrado com os filtros aplicados.</div>
        <?php endif; ?>
    </div>
</body>
</html>
