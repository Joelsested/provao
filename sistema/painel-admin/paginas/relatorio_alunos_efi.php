<?php
require_once('../conexao.php');
require_once('verificar.php');
require_once('../boleto.php');
$options = require_once('../options.php');
$pag = 'relatorio_alunos';
@session_start();
$id_user = @$_SESSION['id'];
$config = [
    'client_id' => $options['clientId'],
    'client_secret' => $options['clientSecret'],
    'certificate_path' => $options['certificate'], // Apenas para PIX
    'chave_pix' => $options['pixKey'] ?? '', // Sua chave PIX
    'sandbox' => $options['sandbox'] // true para teste, false para produção
];
$boletoPayment = new EFIBoletoPayment(
    $config['client_id'],
    $config['client_secret'],
    $config['sandbox']
);
if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Tutor' and @$_SESSION['nivel'] != 'Parceiro' and @$_SESSION['nivel'] != 'Professor' and @$_SESSION['nivel'] != 'Vendedor') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}
$data_inicial = @$_GET['data_inicial'] ?: date('Y-m-01'); // Primeiro dia do mês atual
$data_final = @$_GET['data_final'] ?: date('Y-m-d'); // Hoje
$status_filtro = @$_GET['status_filtro'];

// Buscar todos os alunos com seus vendedores
$sql_alunos = "
    SELECT 
        a.id AS id_aluno,
        a.nome AS nome_aluno,
        a.cpf AS cpf_aluno,
        a.email AS email_aluno,
        a.usuario AS id_vendedor,
        v.nome AS nome_vendedor
    FROM alunos a
    LEFT JOIN usuarios v ON v.id = a.usuario
    ORDER BY a.nome ASC
    LIMIT 50
";

$query_alunos = $pdo->prepare($sql_alunos);
$query_alunos->execute();
$alunos = $query_alunos->fetchAll(PDO::FETCH_ASSOC);

// Array para armazenar todos os resultados
$resultados = [];

// Totalizadores
$valor_total = 0;
$total_pago = 0;
$total_pendente = 0;

// Para cada aluno, buscar suas cobranças na API
foreach ($alunos as $aluno) {
    // Remover pontuação do CPF para a API
    $cpf_limpo = preg_replace('/[^0-9]/', '', $aluno['cpf_aluno']);
    
    // Montar parâmetros da API
    $params = "charge_type=billet";
    $params .= "&begin_date=" . $data_inicial;
    $params .= "&end_date=" . $data_final;
    $params .= "&customer_document=" . $cpf_limpo;
    
    try {
        // Buscar cobranças do aluno
        $cobrancas = $boletoPayment->getCharges($params);
        
        // Se houver cobranças, processar cada uma
        if (!empty($cobrancas) && isset($cobrancas['data'])) {
            foreach ($cobrancas['data'] as $cobranca) {
                // Aplicar filtro de status se existir
                $status_cobranca = $cobranca['status'] ?? '';
                
                if (!empty($status_filtro)) {
                    if ($status_filtro == 'Pago' && !in_array($status_cobranca, ['paid', 'confirmed'])) {
                        continue; // Pula esta cobrança
                    }
                    if ($status_filtro == 'Aguardando' && in_array($status_cobranca, ['paid', 'confirmed'])) {
                        continue; // Pula esta cobrança
                    }
                }
                
                // Determinar situação de pagamento
                $situacao_pagamento = 'Não Pago';
                if (in_array($status_cobranca, ['paid', 'confirmed'])) {
                    $situacao_pagamento = 'Pago';
                }
                
                // Valores
                $valor = floatval($cobranca['amount'] ?? 0) / 100; // API geralmente retorna em centavos
                $valor_pago = ($situacao_pagamento == 'Pago') ? $valor : 0;
                $valor_pendente = ($situacao_pagamento == 'Não Pago') ? $valor : 0;
                
                // Adicionar aos totalizadores
                $valor_total += $valor;
                $total_pago += $valor_pago;
                $total_pendente += $valor_pendente;
                
                // Montar registro
                $registro = [
                    'id_cobranca' => $cobranca['id'] ?? '',
                    'data_cobranca' => $cobranca['created_at'] ?? '',
                    'data_vencimento' => $cobranca['expire_at'] ?? '',
                    'status' => $status_cobranca,
                    'situacao_pagamento' => $situacao_pagamento,
                    'valor' => number_format($valor, 2, '.', ''),
                    'valor_pago' => number_format($valor_pago, 2, '.', ''),
                    'descricao' => $cobranca['description'] ?? '',
                    'forma_pgto' => 'Boleto',
                    // Dados do aluno
                    'id_aluno' => $aluno['id_aluno'],
                    'nome_aluno' => $aluno['nome_aluno'],
                    'cpf_aluno' => $aluno['cpf_aluno'],
                    'email_aluno' => $aluno['email_aluno'],
                    // Dados do vendedor
                    'id_vendedor' => $aluno['id_vendedor'],
                    'nome_vendedor' => $aluno['nome_vendedor']
                ];
                
                $resultados[] = $registro;
            }
        }
    } catch (Exception $e) {
        // Log de erro (opcional)
        // error_log("Erro ao buscar cobranças do aluno {$aluno['nome_aluno']}: " . $e->getMessage());
        continue;
    }
}

// Ordenar resultados por data (mais recentes primeiro)
usort($resultados, function($a, $b) {
    return strtotime($b['data_cobranca']) - strtotime($a['data_cobranca']);
});

// Paginação
$itens_por_pagina = 10;
$pagina_atual = intval($_GET['pag_num'] ?? 1); // Mudei de 'pagina' para 'pag_num'
$total = count($resultados);
$total_paginas = ceil($total / $itens_por_pagina);
$inicio = ($pagina_atual - 1) * $itens_por_pagina;

// Aplicar paginação
$resultados_paginados = array_slice($resultados, $inicio, $itens_por_pagina);
?>

<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i>Exportar Relatório EFI</button>

<!-- Filtros -->
<div class="bs-example widget-shadow" style="padding:15px; margin-top: 10px;">
    <form method="GET" action="index.php" id="formFiltros">
        <input type="hidden" name="pagina" value="relatorio_alunos_efi">
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
                    <a href="index.php?pagina=relatorio_alunos_efi" class="btn btn-warning"><i class="fa fa-eraser"></i> Limpar</a>
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
        <span style="margin-left: 15px;">Página <?php echo $pagina_atual ?> de <?php echo $total_paginas ?></span>
    </div>
    
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID Cobrança</th>
                <th>Aluno</th>
                <th>CPF</th>
                <th>Email</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Forma Pgto</th>
                <th>Status</th>
                <th>Vendedor</th>
                <th>Data Cobrança</th>
                <th>Vencimento</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($resultados_paginados) > 0): ?>
            <?php foreach ($resultados_paginados as $dado): ?>
                <tr>
                    <td><?= htmlspecialchars($dado['id_cobranca']) ?></td>
                    <td><?= htmlspecialchars($dado['nome_aluno']) ?></td>
                    <td><?= htmlspecialchars($dado['cpf_aluno']) ?></td>
                    <td><?= htmlspecialchars($dado['email_aluno']) ?></td>
                    <td><?= htmlspecialchars($dado['descricao']) ?></td>
                    <td>R$ <?= $dado['valor'] ?></td>
                    <td><?= htmlspecialchars($dado['forma_pgto']) ?></td>
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
                    <td>
                        <?php 
                        if (!empty($dado['data_cobranca'])) {
                            $data = date_create($dado['data_cobranca']);
                            echo $data ? date_format($data, 'd/m/Y H:i') : '-';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if (!empty($dado['data_vencimento'])) {
                            $data = date_create($dado['data_vencimento']);
                            echo $data ? date_format($data, 'd/m/Y') : '-';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" align="center">Nenhum registro encontrado com os filtros aplicados</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <!-- Botão Anterior -->
            <?php if ($pagina_atual > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?pagina=relatorio_alunos_efi&data_inicial=<?= $data_inicial ?>&data_final=<?= $data_final ?>&status_filtro=<?= $status_filtro ?>&pag_num=<?= ($pagina_atual - 1) ?>">
                    « Anterior
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">« Anterior</span>
            </li>
            <?php endif; ?>
            
            <!-- Números das páginas -->
            <?php 
            $inicio_pag = max(1, $pagina_atual - 2);
            $fim_pag = min($total_paginas, $pagina_atual + 2);
            
            // Se está no início, mostrar mais páginas à frente
            if ($pagina_atual <= 3) {
                $fim_pag = min($total_paginas, 5);
            }
            
            // Se está no final, mostrar mais páginas atrás
            if ($pagina_atual > $total_paginas - 3) {
                $inicio_pag = max(1, $total_paginas - 4);
            }
            
            // Primeira página
            if ($inicio_pag > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=relatorio_alunos_efi&data_inicial=<?= $data_inicial ?>&data_final=<?= $data_final ?>&status_filtro=<?= $status_filtro ?>&pag_num=1">1</a>
                </li>
                <?php if ($inicio_pag > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $inicio_pag; $i <= $fim_pag; $i++): ?>
            <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=relatorio_alunos_efi&data_inicial=<?= $data_inicial ?>&data_final=<?= $data_final ?>&status_filtro=<?= $status_filtro ?>&pag_num=<?= $i ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <!-- Última página -->
            <?php if ($fim_pag < $total_paginas): ?>
                <?php if ($fim_pag < $total_paginas - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=relatorio_alunos_efi&data_inicial=<?= $data_inicial ?>&data_final=<?= $data_final ?>&status_filtro=<?= $status_filtro ?>&pag_num=<?= $total_paginas ?>"><?= $total_paginas ?></a>
                </li>
            <?php endif; ?>
            
            <!-- Botão Próximo -->
            <?php if ($pagina_atual < $total_paginas): ?>
            <li class="page-item">
                <a class="page-link" href="?pagina=relatorio_alunos_efi&data_inicial=<?= $data_inicial ?>&data_final=<?= $data_final ?>&status_filtro=<?= $status_filtro ?>&pag_num=<?= ($pagina_atual + 1) ?>">
                    Próximo »
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Próximo »</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Modais permanecem iguais -->
