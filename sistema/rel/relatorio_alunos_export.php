<?php
require_once('../conexao.php');
@session_start();

if (!in_array(@$_SESSION['nivel'], ['Administrador', 'Secretario', 'Tesoureiro', 'Tutor', 'Parceiro', 'Professor', 'Vendedor'], true)) {
    echo 'Sem permissao para exportar este relatorio.';
    exit();
}

$format = strtolower($_GET['format'] ?? 'csv');
if (!in_array($format, ['csv', 'excel'], true)) {
    $format = 'csv';
}

$data_inicial = $_POST['data_inicial'] ?? '';
$data_final = $_POST['data_final'] ?? '';
$status_filtro = $_POST['status_filtro'] ?? '';
$nivel_responsavel = trim($_POST['nivel_responsavel'] ?? '');
$niveis_responsavel = ['Vendedor', 'Tutor', 'Secretario', 'Tesoureiro'];
$responsavel_id = filter_input(INPUT_POST, 'responsavel_id', FILTER_VALIDATE_INT);

if (!in_array($nivel_responsavel, $niveis_responsavel, true)) {
    $nivel_responsavel = '';
}

if ($responsavel_id) {
    $stmtNivel = $pdo->prepare("SELECT nivel FROM usuarios WHERE id = :id LIMIT 1");
    $stmtNivel->execute([':id' => $responsavel_id]);
    $nivelDetectado = $stmtNivel->fetchColumn();
    if (in_array($nivelDetectado, $niveis_responsavel, true)) {
        $nivel_responsavel = $nivelDetectado;
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

if ($data_inicial !== '') {
    $sql .= " AND m.data >= :data_inicial";
    $params[':data_inicial'] = $data_inicial;
}
if ($data_final !== '') {
    $sql .= " AND m.data <= :data_final";
    $params[':data_final'] = $data_final;
}
if ($status_filtro !== '') {
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

$timestamp = date('Ymd_His');
$filename = $format === 'excel' ? "relatorio_alunos_{$timestamp}.xls" : "relatorio_alunos_{$timestamp}.csv";

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
} else {
    header('Content-Type: text/csv; charset=utf-8');
}
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

$header = ['Aluno', 'CPF', 'Curso/Pacote', 'Valor', 'Forma Pgto', 'Status', 'Vendedor', 'Data Matricula'];
fputcsv($output, $header, ';');

foreach ($res as $dado) {
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
    $valor = number_format((float) ($dado['valor'] ?? 0), 2, ',', '.');
    $dataMatricula = $dado['data_matricula'] ? date('d/m/Y', strtotime($dado['data_matricula'])) : '';

    $linha = [
        $dado['nome_aluno'] ?? '',
        $dado['cpf_aluno'] ?? '',
        $cursoNome,
        $valor,
        $formaPgto,
        $dado['situacao_pagamento'] ?? '',
        $dado['nome_vendedor'] ?? '',
        $dataMatricula
    ];
    fputcsv($output, $linha, ';');
}

fclose($output);
exit();
