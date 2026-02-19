<?php
require_once('../conexao.php');
require_once('verificar.php');
require_once(__DIR__ . '/../../../helpers.php');
$pag = 'relatorio_alunos_responsavel';

@session_start();

if (
    @$_SESSION['nivel'] != 'Administrador' &&
    @$_SESSION['nivel'] != 'Secretario' &&
    @$_SESSION['nivel'] != 'Tesoureiro' &&
    @$_SESSION['nivel'] != 'Tutor' &&
    @$_SESSION['nivel'] != 'Parceiro' &&
    @$_SESSION['nivel'] != 'Professor' &&
    @$_SESSION['nivel'] != 'Vendedor'
) {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

$allowedLevels = ['Professor', 'Tutor', 'Parceiro', 'Vendedor', 'Secretario', 'Tesoureiro'];

$nivelSessao = $_SESSION['nivel'] ?? '';
$usuarioSessao = (int) ($_SESSION['id'] ?? 0);
$isRelatorioRestrito = in_array($nivelSessao, ['Vendedor', 'Tutor', 'Parceiro'], true);

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

if ($isRelatorioRestrito) {
    $nivel = $nivelSessao;
    $responsavel_id = $usuarioSessao;
}

$responsaveis = [];
$responsavelSessaoNome = '';
if ($nivel !== '') {
    if ($isRelatorioRestrito) {
        $stmtResponsavel = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE id = :id LIMIT 1");
        $stmtResponsavel->execute([':id' => $usuarioSessao]);
        $responsavelSessao = $stmtResponsavel->fetch(PDO::FETCH_ASSOC) ?: [];
        $responsavelSessaoNome = $responsavelSessao['nome'] ?? '';
        $responsaveis = $responsavelSessao ? [$responsavelSessao] : [];
    } else {
        $stmtResponsaveis = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE nivel = :nivel AND ativo = 'Sim' ORDER BY nome");
        $stmtResponsaveis->execute([':nivel' => $nivel]);
        $responsaveis = $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));
    $stmtResponsaveis = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE nivel IN ($placeholders) AND ativo = 'Sim' ORDER BY nome");
    $stmtResponsaveis->execute($allowedLevels);
    $responsaveis = $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC);
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

$itens_por_pagina = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$inicio = ($page - 1) * $itens_por_pagina;

$resumo = [];
$detalhes = [];
$total_registros = 0;
$paginas = 0;

$mostrarResultados = ($nivel !== '');
$exprResponsavel = tableHasColumn($pdo, 'alunos', 'responsavel_id')
    ? "COALESCE(NULLIF(a.responsavel_id, 0), a.usuario)"
    : "a.usuario";

if ($mostrarResultados) {
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

    $sqlCount = "SELECT COUNT(*)
        FROM matriculas m
        JOIN usuarios aluno ON aluno.id = m.aluno
        JOIN alunos a ON a.id = aluno.id_pessoa
        JOIN usuarios resp ON resp.id = {$exprResponsavel}
        WHERE $where";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_registros = (int) $stmtCount->fetchColumn();

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
        ORDER BY m.id DESC
        LIMIT :inicio, :itens";
    $stmtDetalhes = $pdo->prepare($sqlDetalhes);
    foreach ($params as $key => $value) {
        $stmtDetalhes->bindValue($key, $value);
    }
    $stmtDetalhes->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmtDetalhes->bindValue(':itens', $itens_por_pagina, PDO::PARAM_INT);
    $stmtDetalhes->execute();
    $detalhes = $stmtDetalhes->fetchAll(PDO::FETCH_ASSOC);

    $paginas = (int) ceil($total_registros / $itens_por_pagina);
}

$queryParams = [
    'pagina' => 'relatorio_alunos_responsavel',
    'data_inicial' => $data_inicial,
    'data_final' => $data_final,
    'nivel' => $nivel,
    'responsavel_id' => $responsavel_id ?: '',
    'status_filtro' => $status_filtro,
];
$baseQuery = http_build_query($queryParams);

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

?>

<div class="bs-example widget-shadow" style="padding:15px;">
    <form method="GET" action="index.php">
        <input type="hidden" name="pagina" value="relatorio_alunos_responsavel">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Data Inicial</label>
                    <input type="date" class="form-control" name="data_inicial" value="<?php echo htmlspecialchars($data_inicial); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Data Final</label>
                    <input type="date" class="form-control" name="data_final" value="<?php echo htmlspecialchars($data_final); ?>">
                </div>
            </div>
            <?php if ($isRelatorioRestrito) : ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Responsavel</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim($responsavelSessaoNome . ' (' . $nivelSessao . ')')); ?>" readonly>
                        <input type="hidden" name="nivel" value="<?php echo htmlspecialchars($nivel); ?>">
                        <input type="hidden" name="responsavel_id" value="<?php echo htmlspecialchars((string) ($responsavel_id ?: '')); ?>">
                    </div>
                </div>
            <?php else : ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Nivel do Responsavel</label>
                        <select class="form-control" name="nivel">
                            <option value="">Selecione</option>
                            <?php foreach ($allowedLevels as $nivelOpt) : ?>
                                <option value="<?php echo htmlspecialchars($nivelOpt); ?>" <?php echo ($nivelOpt === $nivel) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nivelOpt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Responsavel</label>
                        <select class="form-control" name="responsavel_id">
                            <option value="">Todos</option>
                            <?php foreach ($responsaveis as $resp) : ?>
                                <option value="<?php echo (int) $resp['id']; ?>" <?php echo ((int) $resp['id'] === (int) $responsavel_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($resp['nome']); ?> (<?php echo htmlspecialchars($resp['nivel']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status da Matricula</label>
                    <select class="form-control" name="status_filtro">
                        <option value="">Todos</option>
                        <option value="matriculados" <?php echo ($status_filtro === 'matriculados') ? 'selected' : ''; ?>>Matriculados/Aprovados</option>
                        <option value="pendentes" <?php echo ($status_filtro === 'pendentes') ? 'selected' : ''; ?>>Inscritos/Pendentes</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 5px;">
            <div class="col-md-6">
                <button type="submit" class="btn btn-success"><i class="fa fa-filter"></i> Filtrar</button>
                <a href="index.php?pagina=relatorio_alunos_responsavel" class="btn btn-warning"><i class="fa fa-eraser"></i> Limpar</a>
            </div>
            <div class="col-md-6" style="text-align: right;">
                <button type="submit"
                    class="btn btn-primary"
                    formaction="../rel/relatorio_alunos_responsavel_class.php"
                    formmethod="POST"
                    formtarget="_blank">
                    <i class="fa fa-file-pdf-o"></i> Exportar PDF
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (!$mostrarResultados) : ?>
    <div class="bs-example widget-shadow" style="padding:15px; margin-top: 10px;">
        <strong>Selecione o nivel do responsavel para gerar o relatorio.</strong>
    </div>
<?php else : ?>
    <div class="bs-example widget-shadow" style="padding:15px; margin-top: 10px;">
        <div style="margin-bottom: 10px;">
            <strong>Resumo por Responsavel</strong>
        </div>
        <?php if (!$hasComissoesRecebimentos) : ?>
            <div style="margin-bottom: 10px; color: #a94442;">
                Tabela de comissoes do banco nao encontrada. Execute o script de criacao para ver valores.
            </div>
        <?php endif; ?>
        <?php if (count($resumo) > 0) : ?>
            <table class="table table-bordered table-striped">
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
            <div>Nenhum registro encontrado para o filtro informado.</div>
        <?php endif; ?>
    </div>

    <div class="bs-example widget-shadow" style="padding:15px; margin-top: 10px;">
        <div style="margin-bottom: 10px;">
            <strong>Total de registros encontrados: <?php echo $total_registros; ?></strong>
        </div>
        <table class="table table-bordered table-striped">
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
                <?php if (count($detalhes) > 0) : ?>
                    <?php foreach ($detalhes as $row) : ?>
                        <?php
                        $pacote = trim($row['pacote'] ?? '');
                        $isPacote = ($pacote === 'Sim');
                        $nomeItem = $isPacote ? ($row['nome_pacote'] ?? '') : ($row['nome_curso'] ?? '');
                        if ($nomeItem === '' || $nomeItem === null) {
                            $nomeItem = $isPacote ? 'Pacote' : 'Curso';
                        }
                        $valorRecebido = (float) ($row['comissao_recebida'] ?? 0);
                        $valorPendente = (float) ($row['comissao_pendente'] ?? 0);
                        $dataMatricula = $row['data'] ? date('d/m/Y', strtotime($row['data'])) : '-';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nome_responsavel'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['nivel_responsavel'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['nome_aluno'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($dataMatricula); ?></td>
                            <td><?php echo htmlspecialchars($nomeItem); ?></td>
                            <td><?php echo $isPacote ? 'Pacote' : 'Curso'; ?></td>
                            <td><?php echo 'R$ ' . number_format($valorRecebido, 2, ',', '.'); ?></td>
                            <td><?php echo 'R$ ' . number_format($valorPendente, 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" align="center">Nenhum registro encontrado com os filtros aplicados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($paginas > 1) : ?>
            <nav aria-label="Paginacao">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $paginas; $i++) : ?>
                        <li class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a href="index.php?<?php echo $baseQuery; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

