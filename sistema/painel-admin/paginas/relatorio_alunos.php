<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'relatorio_alunos';

@session_start();

$id_user = @$_SESSION['id'];

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Tutor' and @$_SESSION['nivel'] != 'Parceiro' and @$_SESSION['nivel'] != 'Professor' and @$_SESSION['nivel'] != 'Vendedor') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}

// Capturar filtros
$data_inicial = @$_GET['data_inicial'];
$data_final = @$_GET['data_final'];
$status_filtro = @$_GET['status_filtro'];

$itens_por_pagina = 10;
$pagina = intval($_GET['pagina'] ?? 1);
$inicio = ($pagina - 1) * $itens_por_pagina;

// Construir query com filtros
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
            c.nome AS nome_curso,
            a.nome AS nome_aluno,
            a.cpf AS cpf_aluno,
            a.email AS email_aluno,
            v.nome AS nome_vendedor,
            CASE
                WHEN m.status = 'Aguardando' THEN 'Não Pago'
                WHEN m.status IN ('Matriculado', 'Finalizado') THEN 'Pago'
                ELSE 'Indefinido'
            END AS situacao_pagamento
        FROM matriculas m
        LEFT JOIN usuarios u ON u.id = m.aluno
        LEFT JOIN alunos a ON a.id = u.id_pessoa
        LEFT JOIN usuarios v ON v.id = a.usuario
        LEFT JOIN cursos c ON c.id = m.id_curso
        WHERE 1=1";
$params = [];

// Aplicar filtro de data
if (!empty($data_inicial)) {
    $sql .= " AND m.data >= :data_inicial";
    $params[':data_inicial'] = $data_inicial;
}
if (!empty($data_final)) {
    $sql .= " AND m.data <= :data_final";
    $params[':data_final'] = $data_final;
}

// Aplicar filtro de status
if (!empty($status_filtro)) {
    if ($status_filtro == 'Pago') {
        $sql .= " AND m.status IN ('Matriculado', 'Finalizado')";
    } elseif ($status_filtro == 'Aguardando') {
        $sql .= " AND m.status = 'Aguardando'";
    }
}

$sql .= " ORDER BY m.id DESC";

$query = $pdo->prepare($sql);
$query->execute($params);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// Contar total com filtros
$sql_count = "SELECT COUNT(*) AS total FROM matriculas m WHERE 1=1";
if (!empty($data_inicial)) {
    $sql_count .= " AND m.data >= :data_inicial";
}
if (!empty($data_final)) {
    $sql_count .= " AND m.data <= :data_final";
}
if (!empty($status_filtro)) {
    if ($status_filtro == 'Pago') {
        $sql_count .= " AND m.status IN ('Matriculado', 'Finalizado')";
    } elseif ($status_filtro == 'Aguardando') {
        $sql_count .= " AND m.status = 'Aguardando'";
    }
}

$total_query = $pdo->prepare($sql_count);
$total_query->execute($params);
$total = $total_query->fetch(PDO::FETCH_ASSOC)['total'];
$paginas = ceil($total / $itens_por_pagina);

// CALCULAR TOTALIZADORES
$sql_totais = "SELECT 
                SUM(m.valor) AS valor_total,
                SUM(m.total_recebido) AS total_pago,
                SUM(CASE 
                    WHEN m.status = 'Aguardando' THEN m.valor - m.total_recebido
                    ELSE 0 
                END) AS total_pendente
            FROM matriculas m
            WHERE 1=1";

// Aplicar os mesmos filtros
if (!empty($data_inicial)) {
    $sql_totais .= " AND m.data >= :data_inicial";
}
if (!empty($data_final)) {
    $sql_totais .= " AND m.data <= :data_final";
}
if (!empty($status_filtro)) {
    if ($status_filtro == 'Pago') {
        $sql_totais .= " AND m.status IN ('Matriculado', 'Finalizado')";
    } elseif ($status_filtro == 'Aguardando') {
        $sql_totais .= " AND m.status = 'Aguardando'";
    }
}

$query_totais = $pdo->prepare($sql_totais);
$query_totais->execute($params);
$totais = $query_totais->fetch(PDO::FETCH_ASSOC);

$valor_total = $totais['valor_total'] ?? 0;
$total_pago = $totais['total_pago'] ?? 0;
$total_pendente = $totais['total_pendente'] ?? 0;

?>

<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i>Exportar Relatório</button>

<!-- Filtros -->
<div class="bs-example widget-shadow" style="padding:15px; margin-top: 10px;">
    <form method="GET" action="index.php" id="formFiltros">
        <input type="hidden" name="pagina" value="relatorio_alunos">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Data Inicial</label>
                    <input type="date" class="form-control" name="data_inicial" id="data_inicial" value="<?php echo $data_inicial ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label>Data Final</label>
                    <input type="date" class="form-control" name="data_final" id="data_final" value="<?php echo $data_final ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status Pagamento</label>
                    <select class="form-control" name="status_filtro" id="status_filtro">
                        <option value="">Todos</option>
                        <option value="Pago" <?php echo ($status_filtro == 'Pago') ? 'selected' : '' ?>>Pago</option>
                        <option value="Aguardando" <?php echo ($status_filtro == 'Aguardando') ? 'selected' : '' ?>>Aguardando</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="submit" class="btn btn-success"><i class="fa fa-filter"></i> Filtrar</button>
                    <a href="index.php?pagina=relatorio_alunos" class="btn btn-warning"><i class="fa fa-eraser"></i> Limpar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- TOTALIZADORES -->
<div class="bs-example widget-shadow" style="padding:15px; margin-top: 10px; background-color: #f9f9f9;">
    <div class="row">
        <div class="col-md-4">
            <div style="padding: 15px; background-color: #3498db; color: white; border-radius: 5px; text-align: center;">
                <h4 style="margin: 0 0 10px 0;">Valor Total</h4>
                <h3 style="margin: 0; font-weight: bold;">R$ <?php echo number_format($valor_total, 2, ',', '.') ?></h3>
            </div>
        </div>
        
        <div class="col-md-4">
            <div style="padding: 15px; background-color: #27ae60; color: white; border-radius: 5px; text-align: center;">
                <h4 style="margin: 0 0 10px 0;">Total Pago</h4>
                <h3 style="margin: 0; font-weight: bold;">R$ <?php echo number_format($total_pago, 2, ',', '.') ?></h3>
            </div>
        </div>
        
        <div class="col-md-4">
            <div style="padding: 15px; background-color: #e74c3c; color: white; border-radius: 5px; text-align: center;">
                <h4 style="margin: 0 0 10px 0;">Total Pendente</h4>
                <h3 style="margin: 0; font-weight: bold;">R$ <?php echo number_format($total_pendente, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="bs-example widget-shadow" style="padding:15px" id="listar">
    <div style="margin-bottom: 10px;">
        <strong>Total de registros encontrados: <?php echo $total ?></strong>
    </div>
    
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <!--<th>ID</th>-->
                <th>Aluno</th>
                <th>CPF</th>
                <th>Curso</th>
                <th>Valor</th>
                <!--<th>Recebido</th>-->
                <th>Forma Pgto</th>
                <th>Status</th>
                <th>Vendedor</th>
                <th>Data Matrícula</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($res) > 0): ?>
            <?php foreach ($res as $dado): ?>
                <tr>
                    <!--<td><?= $dado['id_matricula'] ?></td>-->
                    <td><?= htmlspecialchars($dado['nome_aluno']) ?></td>
                    <td><?= htmlspecialchars($dado['cpf_aluno']) ?></td>
                    <td><?= htmlspecialchars(trim($dado['nome_curso'] ?? '') !== '' ? $dado['nome_curso'] : 'Pacote') ?></td>

                    <td>R$ <?= number_format($dado['valor'], 2, ',', '.') ?></td>
                    <!--<td>R$ <?= number_format($dado['total_recebido'], 2, ',', '.') ?></td>-->
                    <!--<td><?= htmlspecialchars($dado['forma_pgto']) ?></td>-->
                        <td><?= htmlspecialchars(trim($dado['forma_pgto'] ?? '') !== '' ? $dado['forma_pgto'] : 'Ativação Pacote') ?></td>
                    <td>
                        <?php if ($dado['situacao_pagamento'] === 'Pago'): ?>
                            <span class="label label-success">Pago</span>
                        <?php elseif ($dado['situacao_pagamento'] === 'Não Pago'): ?>
                            <span class="label label-warning">Aguardando</span>
                        <?php else: ?>
                            <span class="label label-default">Indefinido</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($dado['nome_vendedor']) ?></td>
                    <td><?= date('d/m/Y', strtotime($dado['data_matricula'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" align="center">Nenhum registro encontrado com os filtros aplicados</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modais (mantidos do código original) -->
<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal"></h4>
                <button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nome do Aluno*</label>
                                <input type="text" class="form-control" name="nome" id="nome" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Cpf*</label>
                                <input type="text" class="form-control" name="cpf" id="cpf" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Email*</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Telefone:</label>
                                <input type="text" class="form-control" name="telefone" id="telefone">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="id" id="id">
                    <small>
                        <div id="mensagem" align="center" class="mt-3"></div>
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Arquivos -->
<div class="modal fade" id="modalArquivos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal">Gestão de Arquivos - <span id="nome_arquivo"> </span></h4>
                <button id="btn-fechar-arquivos" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-arquivos" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Arquivo</label>
                                <input class="form-control" type="file" name="arquivo_conta" onChange="carregarImgArquivos();" id="arquivo_conta">
                            </div>
                        </div>
                        <div class="col-md-4" style="margin-top:-10px">
                            <div id="divImgArquivos">
                                <img src="images/arquivos/sem-foto.png" width="60px" id="target-arquivos">
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:-40px">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="nome_arq" id="nome_arq" placeholder="Nome do Arquivo * " required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Inserir</button>
                        </div>
                    </div>
                    <hr>
                    <small>
                        <div id="listar_arquivos"></div>
                    </small>
                    <br>
                    <small>
                        <div align="center" id="mensagem_arquivo"></div>
                    </small>
                    <input type="hidden" class="form-control" name="id_arquivo" id="id_arquivo">
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span></h4>
                <button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row" style="border-bottom: 1px solid #cac7c7;">
                    <div class="col-md-3">
                        <span><b>CPF: </b></span>
                        <span id="cpf_mostrar"></span>
                    </div>
                    <div class="col-md-5">
                        <span><b>Email: </b></span>
                        <span id="email_mostrar"></span>
                    </div>
                    <div class="col-md-4">
                        <span><b>RG </b></span>
                        <span id="rg_mostrar"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12" align="center">
                        <img width="200px" id="target_mostrar">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
