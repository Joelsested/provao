<?php



require_once('../../vendor/autoload.php');

require_once("../conexao.php");
require_once("../../config/env.php");





@session_start();

function formatarDataPagamento($data): string
{
    $valor = trim((string) $data);
    if ($valor === '' || $valor === '0000-00-00' || $valor === '0000-00-00 00:00:00') {
        return '-';
    }

    try {
        $dt = new DateTime($valor);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '-';
    }
}

$id_do_aluno = @$_SESSION['id'];
$temRecorrenciaEfi = false;
try {
    $temAssinaturas = $pdo->query("SHOW TABLES LIKE 'efi_assinaturas_cartao'")->fetch(PDO::FETCH_NUM);
    $temParcelas = $pdo->query("SHOW TABLES LIKE 'efi_assinaturas_cartao_parcelas'")->fetch(PDO::FETCH_NUM);
    $temRecorrenciaEfi = (bool) $temAssinaturas && (bool) $temParcelas;
} catch (Throwable $e) {
    $temRecorrenciaEfi = false;
}


$stmt = $pdo->prepare("
    SELECT 
        parcelas_geradas_por_boleto.*,
        JSON_UNQUOTE(JSON_EXTRACT(parcelas_geradas_por_boleto.payload, '$.item_nome')) AS curso
    FROM parcelas_geradas_por_boleto
    JOIN matriculas ON matriculas.id = parcelas_geradas_por_boleto.id_matricula
    WHERE matriculas.aluno = :id_aluno
");

$stmt->execute(['id_aluno' => $id_do_aluno]);
$resposta_consulta_boleto_parcelado = $stmt->fetchAll(PDO::FETCH_ASSOC);

$consulta_matricula = $pdo->prepare("SELECT id, forma_pgto, pacote, id_curso FROM matriculas WHERE aluno = :aluno");
$consulta_matricula->execute(['aluno' => $id_do_aluno]);
$resposta_consulta = $consulta_matricula->fetchAll(PDO::FETCH_ASSOC);


// echo '<pre>';
// echo json_encode($resposta_consulta, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;

$transactions = [];

foreach ($resposta_consulta as $matricula) {
    $id = $matricula['id'];
    $forma_pgto = $matricula['forma_pgto'];
    $pacote = $matricula['pacote'];
    $id_curso = $matricula['id_curso'];

    // Busca o nome do curso ou pacote baseado no valor da coluna "pacote"
    $nome_curso_pacote = '';
    if ($pacote == 'Sim') {
        // Se for pacote, busca na tabela "pacotes"
        $consulta_nome = $pdo->prepare("SELECT nome FROM pacotes WHERE id = :id");
        $consulta_nome->execute(['id' => $id_curso]);
        $resultado_nome = $consulta_nome->fetch(PDO::FETCH_ASSOC);
        $nome_curso_pacote = $resultado_nome ? $resultado_nome['nome'] : '';
    } elseif ($pacote == 'Não') {
        // Se não for pacote, busca na tabela "cursos"
        $consulta_nome = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id");
        $consulta_nome->execute(['id' => $id_curso]);
        $resultado_nome = $consulta_nome->fetch(PDO::FETCH_ASSOC);
        $nome_curso_pacote = $resultado_nome ? $resultado_nome['nome'] : '';
    }

    // Decodifica caracteres Unicode para exibição correta em PT-BR
    if (!empty($nome_curso_pacote)) {
        $nome_curso_pacote = json_decode('"' . $nome_curso_pacote . '"');
        // Alternativa usando html_entity_decode se necessário:
        // $nome_curso_pacote = html_entity_decode($nome_curso_pacote, ENT_QUOTES, 'UTF-8');
    }

    // Determina a tabela de consulta baseada na forma de pagamento
    if ($forma_pgto == 'BOLETO' || $forma_pgto == 'BOLETO_PARCELADO') {
        $tabela_pagamentos = 'pagamentos_boleto';
    } else {
        // Caso haja outras formas de pagamento ou valor nulo
        continue; // Pula para a próxima iteração
    }

    // Executa a consulta na tabela apropriada
    $consulta_pagamentos = $pdo->prepare("SELECT * FROM {$tabela_pagamentos} WHERE id_matricula = :id_matricula");
    $consulta_pagamentos->execute(['id_matricula' => $id]);
    $resposta_pagamentos = $consulta_pagamentos->fetchAll(PDO::FETCH_ASSOC);

    // Adiciona o resultado se houver dados
    if (!empty($resposta_pagamentos)) {
        // Adiciona o nome do curso/pacote ao resultado
        $resposta_pagamentos[0]['nome_curso_pacote'] = $nome_curso_pacote;
        array_push($transactions, $resposta_pagamentos[0]);
    }
}

$consulta_parcelas = $pdo->prepare("
    SELECT 
        parcelas_geradas_por_boleto.*, 
        CASE 
            WHEN matriculas.pacote = 'sim' THEN pacotes.nome 
            ELSE cursos.nome 
        END as curso 
    FROM parcelas_geradas_por_boleto 
    JOIN boletos_parcelados ON boletos_parcelados.id = parcelas_geradas_por_boleto.id_boleto_parcelado 
    JOIN matriculas ON matriculas.id = boletos_parcelados.id_matricula 
    LEFT JOIN cursos ON cursos.id = matriculas.id_curso 
    LEFT JOIN pacotes ON pacotes.id = matriculas.id_curso 
    WHERE matriculas.aluno = :aluno
");

$consulta_parcelas->execute(['aluno' => $id_do_aluno]);
$resposta_consulta = $consulta_parcelas->fetchAll(PDO::FETCH_ASSOC);



$consulta_matriculas_boleto = $pdo->prepare("

    SELECT 

        matriculas.*, 

        cursos.nome AS nome_curso,

        usuarios.nome AS nome_professor

    FROM matriculas 

    JOIN cursos ON cursos.id = matriculas.id_curso 

    JOIN usuarios ON usuarios.id = matriculas.professor

    WHERE matriculas.aluno = :aluno

    AND matriculas.forma_pgto = 'BOLETO'

");



$consulta_matriculas_boleto->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_boleto = $consulta_matriculas_boleto->fetchAll(PDO::FETCH_ASSOC);



$temLogsPagamentos = (bool) $pdo->query("SHOW TABLES LIKE 'logs_pagamentos'")->fetch(PDO::FETCH_NUM);
$selectDataPagamentoCartao = $temLogsPagamentos
    ? "(SELECT MAX(lp.data) FROM logs_pagamentos lp WHERE lp.id_matricula = matriculas.id AND UPPER(COALESCE(lp.status, '')) IN ('PAID','APPROVED','SETTLED','ACTIVE')) AS data_pagamento_cartao"
    : "NULL AS data_pagamento_cartao";
$selectRecorrenciaCartao = "1 AS quantidade_parcelas, 0 AS parcelas_pagas, 0 AS parcelas_pendentes, 0 AS subscription_id, {$selectDataPagamentoCartao}";
$joinRecorrenciaCartao = "";
if ($temRecorrenciaEfi) {
    $partesDataPgtoCartao = [
        "(SELECT MAX(ep.data_pagamento) FROM efi_assinaturas_cartao_parcelas ep WHERE ep.id_matricula = matriculas.id AND ep.status = 'PAGA')",
    ];
    if ($temLogsPagamentos) {
        $partesDataPgtoCartao[] = "(SELECT MAX(lp.data) FROM logs_pagamentos lp WHERE lp.id_matricula = matriculas.id AND UPPER(COALESCE(lp.status, '')) IN ('PAID','APPROVED','SETTLED','ACTIVE'))";
    }
    $selectDataPagamentoCartao = "COALESCE(" . implode(', ', $partesDataPgtoCartao) . ") AS data_pagamento_cartao";

    $selectRecorrenciaCartao = "
        COALESCE(ea.quantidade_parcelas, 1) AS quantidade_parcelas,
        COALESCE(ea.subscription_id, 0) AS subscription_id,
        (
            SELECT COUNT(*)
            FROM efi_assinaturas_cartao_parcelas ep
            WHERE ep.id_matricula = matriculas.id
              AND ep.status = 'PAGA'
        ) AS parcelas_pagas,
        (
            SELECT COUNT(*)
            FROM efi_assinaturas_cartao_parcelas ep
            WHERE ep.id_matricula = matriculas.id
              AND ep.status IN ('PENDENTE', 'ATRASADA')
        ) AS parcelas_pendentes,
        {$selectDataPagamentoCartao}
    ";
    $joinRecorrenciaCartao = "LEFT JOIN efi_assinaturas_cartao ea ON ea.id_matricula = matriculas.id";
}

$consulta_matriculas_cartao_p = $pdo->prepare("

    SELECT 

        matriculas.*, 

        pacotes.nome AS nome_curso,

        usuarios.nome AS nome_professor,
        {$selectRecorrenciaCartao}

    FROM matriculas 

    JOIN pacotes ON pacotes.id = matriculas.id_curso 

    JOIN usuarios ON usuarios.id = matriculas.professor
    {$joinRecorrenciaCartao}

    WHERE matriculas.aluno = :aluno

    AND matriculas.forma_pgto IN ('CARTAO_DE_CREDITO', 'CARTAO_RECORRENTE')

");



$consulta_matriculas_cartao_p->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_cartao_p = $consulta_matriculas_cartao_p->fetchAll(PDO::FETCH_ASSOC);




$consulta_matriculas_cartao_c = $pdo->prepare("

    SELECT 

        matriculas.*, 

        cursos.nome AS nome_curso,

        usuarios.nome AS nome_professor,
        {$selectRecorrenciaCartao}

    FROM matriculas 

    JOIN cursos ON cursos.id = matriculas.id_curso 

    JOIN usuarios ON usuarios.id = matriculas.professor
    {$joinRecorrenciaCartao}

    WHERE matriculas.aluno = :aluno

    AND matriculas.forma_pgto IN ('CARTAO_DE_CREDITO', 'CARTAO_RECORRENTE')

");



$consulta_matriculas_cartao_c->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_cartao_c = $consulta_matriculas_cartao_c->fetchAll(PDO::FETCH_ASSOC);

$resposta_consulta_matriculas_cartao = array_merge($resposta_consulta_matriculas_cartao_c, $resposta_consulta_matriculas_cartao_p);

// echo '<pre>';
// echo json_encode($resposta_consulta_matriculas_cartao, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;

$cartao_transactions = null;

if ($resposta_consulta_matriculas_cartao) {
    $cartao_transactions = $resposta_consulta_matriculas_cartao;
}

$parcelasRecorrenciaPorMatricula = [];
if ($temRecorrenciaEfi) {
    try {
        $pdo->exec("
            UPDATE efi_assinaturas_cartao_parcelas
            SET status = 'ATRASADA',
                updated_at = CURRENT_TIMESTAMP
            WHERE status = 'PENDENTE'
              AND vencimento IS NOT NULL
              AND vencimento < CURDATE()
        ");
    } catch (Throwable $e) {
        // Nao interrompe a tela por falha de saneamento de status.
    }

    $idsMatriculasRecorrentes = [];
    foreach ((array) $cartao_transactions as $registroRec) {
        if (($registroRec['forma_pgto'] ?? '') === 'CARTAO_RECORRENTE') {
            $idsMatriculasRecorrentes[] = (int) ($registroRec['id'] ?? 0);
        }
    }
    $idsMatriculasRecorrentes = array_values(array_filter(array_unique($idsMatriculasRecorrentes)));

    if (!empty($idsMatriculasRecorrentes)) {
        $placeholders = implode(',', array_fill(0, count($idsMatriculasRecorrentes), '?'));
        $stmtParcelasRec = $pdo->prepare("
            SELECT
                id_matricula,
                numero_parcela,
                valor_parcela,
                vencimento,
                status,
                data_pagamento
            FROM efi_assinaturas_cartao_parcelas
            WHERE id_matricula IN ($placeholders)
            ORDER BY id_matricula ASC, numero_parcela ASC
        ");
        $stmtParcelasRec->execute($idsMatriculasRecorrentes);
        $linhasParcelasRec = $stmtParcelasRec->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linhasParcelasRec as $linhaParcela) {
            $idMatParcela = (int) ($linhaParcela['id_matricula'] ?? 0);
            if ($idMatParcela <= 0) {
                continue;
            }
            if (!isset($parcelasRecorrenciaPorMatricula[$idMatParcela])) {
                $parcelasRecorrenciaPorMatricula[$idMatParcela] = [];
            }
            $parcelasRecorrenciaPorMatricula[$idMatParcela][] = $linhaParcela;
        }
    }
}



$boleto_transactions = [];

// Regra bancaria do cartao: tarifa fixa + percentual + juros mensal por parcela.
$taxaFixaCartao = (float) env('EFI_CARD_FEE_FIXED', '0.29');
$taxaPercentualCartao = ((float) env('EFI_CARD_FEE_PERCENT', '4.99')) / 100;
$jurosMensalParcelado = ((float) env('EFI_CARD_INTEREST_MONTHLY', '1.99')) / 100;

$calcularTotalCartaoCliente = static function (
    float $valorLiquido,
    int $parcelas,
    float $taxaFixa,
    float $taxaPercentual,
    float $jurosMensal
): float {
    $parcelas = max(1, $parcelas);
    $denominador = 1 - $taxaPercentual;
    $baseBruta = $denominador > 0 ? (($valorLiquido + $taxaFixa) / $denominador) : $valorLiquido;
    if ($parcelas > 1) {
        $baseBruta *= pow(1 + $jurosMensal, $parcelas - 1);
    }
    return round(max($baseBruta, 0), 2);
};

foreach ($transactions as $registro) {
    $eh_boleto = isset($registro['nosso_numero']);

    if ($eh_boleto) {
        $boleto_transactions[] = $registro;
    }
}


// echo json_encode($pix_transactions);
// return;

?>



<style>
    .bs-example {

        padding: 15px;

        margin-top: -10px;

        border: 1px solid #ddd;

        border-radius: 4px;

    }



    table {

        width: 100%;

        border-collapse: collapse;

    }



    th,

    td {

        border: 1px solid #ddd;

        padding: 8px;

        text-align: left;

    }



    th {

        background-color: #f4f4f4;

    }



    tr:nth-child(even) {

        background-color: #f9f9f9;

    }
</style>



<div class="bs-example widget-shadow margem-mobile">


    <div>


        <!-- TABELA CARTAO -->
        <?php if ($cartao_transactions !== null): ?>
            <div style="margin-top: 20px;">

                <h3>Pagamentos Cartão EFY</h3>
            </div>

            <br>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Curso / Pacote</th>
                        <th>Data Pagamento</th>
                        <th>Forma de pagamento</th>
                        <th>Parcelas</th>
                        <th>Valor</th>
                        <th>Situação</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartao_transactions as $registro): ?>
                    
                    
                    <?php
                    
                    $valorBase = (float) ($registro['subtotal'] ?? 0);
                    if ($valorBase <= 0) {
                        $valorBase = (float) ($registro['valor'] ?? 0);
                    }
                    $valorComDesconto = max($valorBase, 0);

                    $parcelasTabela = 1;
                    $ehRecorrenteLinha = (($registro['forma_pgto'] ?? '') === 'CARTAO_RECORRENTE');
                    if ($ehRecorrenteLinha) {
                        if (!empty($registro['quantidade_parcelas'])) {
                            $parcelasTabela = (int) $registro['quantidade_parcelas'];
                        } elseif (!empty($registro['qtd_parcelas_cartao'])) {
                            $parcelasTabela = (int) $registro['qtd_parcelas_cartao'];
                        } elseif (!empty($registro['qtd_parcelas'])) {
                            $parcelasTabela = (int) $registro['qtd_parcelas'];
                        } elseif (!empty($registro['parcelas'])) {
                            $parcelasTabela = (int) $registro['parcelas'];
                        }
                    } else {
                        if (!empty($registro['qtd_parcelas_cartao'])) {
                            $parcelasTabela = (int) $registro['qtd_parcelas_cartao'];
                        } elseif (!empty($registro['qtd_parcelas'])) {
                            $parcelasTabela = (int) $registro['qtd_parcelas'];
                        } elseif (!empty($registro['parcelas'])) {
                            $parcelasTabela = (int) $registro['parcelas'];
                        } elseif (!empty($registro['quantidade_parcelas'])) {
                            $parcelasTabela = (int) $registro['quantidade_parcelas'];
                        }
                    }
                    if ($parcelasTabela < 1) {
                        $parcelasTabela = 1;
                    }
                    $valorTotalCartaoSalvo = (float) ($registro['valor_total_cartao'] ?? 0);
                    if (!$ehRecorrenteLinha && $valorTotalCartaoSalvo > 0) {
                        $valorFinalCartao = round($valorTotalCartaoSalvo, 2);
                    } else {
                        $valorFinalCartao = $calcularTotalCartaoCliente(
                            $valorComDesconto,
                            $parcelasTabela,
                            $taxaFixaCartao,
                            $taxaPercentualCartao,
                            $jurosMensalParcelado
                        );
                    }
                    $parcelasDetalhesLinha = $parcelasRecorrenciaPorMatricula[(int) ($registro['id'] ?? 0)] ?? [];
                    $situacaoLinha = strtoupper(trim((string) ($registro['status'] ?? 'PENDENTE')));
                    if ($ehRecorrenteLinha && !empty($parcelasDetalhesLinha)) {
                        $temAtrasadaTmp = false;
                        $temPendenteTmp = false;
                        foreach ($parcelasDetalhesLinha as $parcelaTmp) {
                            $stTmp = strtoupper((string) ($parcelaTmp['status'] ?? ''));
                            if ($stTmp === 'ATRASADA') {
                                $temAtrasadaTmp = true;
                            } elseif ($stTmp === 'PENDENTE') {
                                $temPendenteTmp = true;
                            }
                        }
                        if ($temAtrasadaTmp) {
                            $situacaoLinha = 'EM ATRASO';
                        } elseif ($temPendenteTmp) {
                            $situacaoLinha = 'ATIVA';
                        } else {
                            $situacaoLinha = 'CONCLUIDA';
                        }
                    } elseif (!$ehRecorrenteLinha) {
                        $statusBaseCartao = strtoupper(trim((string) ($registro['status'] ?? '')));
                        if ($statusBaseCartao !== 'AGUARDANDO') {
                            $situacaoLinha = 'PAGO';
                        }
                    }
                     
                     
                     
                    ?>
                        <tr>
                            <td><?php echo $registro['id']; ?></td>
                            <td style="max-width: 130px; overflow: hidden;"><?php echo json_decode('"' . $registro['nome_curso'] . '"');
                            ; ?>
                            </td>
                            <?php
                            $dataPagamentoCartao = formatarDataPagamento((string) ($registro['data_pagamento_cartao'] ?? ''));
                            if ($dataPagamentoCartao === '-') {
                                $dataPagamentoCartao = formatarDataPagamento((string) ($registro['data'] ?? ''));
                            }
                            ?>
                            <td><?php echo htmlspecialchars($dataPagamentoCartao, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="max-width: 130px;">
                                <?php echo ($registro['forma_pgto'] ?? '') === 'CARTAO_RECORRENTE' ? 'Cartão recorrente' : 'Cartão de crédito'; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php
                                $parcelasPagas = (int) ($registro['parcelas_pagas'] ?? 0);
                                $parcelasPendentes = (int) ($registro['parcelas_pendentes'] ?? 0);
                                if ($ehRecorrenteLinha) {
                                    echo $parcelasPagas . '/' . $parcelasTabela . ' pagas';
                                    if ($parcelasPendentes > 0) {
                                        echo ' (' . $parcelasPendentes . ' pendentes)';
                                    }
                                    if (!empty($parcelasDetalhesLinha)) {
                                        echo '<div style="margin-top:6px; font-size:12px; line-height:1.35;">';
                                        foreach ($parcelasDetalhesLinha as $parcelaLinha) {
                                            $statusParcela = strtoupper((string) ($parcelaLinha['status'] ?? 'PENDENTE'));
                                            $statusCor = '#777';
                                            if ($statusParcela === 'PAGA') {
                                                $statusCor = '#1a7f37';
                                            } elseif ($statusParcela === 'ATRASADA') {
                                                $statusCor = '#b42318';
                                            } elseif ($statusParcela === 'PENDENTE') {
                                                $statusCor = '#b54708';
                                            }
                                            $numeroParcelaLinha = (int) ($parcelaLinha['numero_parcela'] ?? 0);
                                            $valorParcelaLinha = number_format((float) ($parcelaLinha['valor_parcela'] ?? 0), 2, ',', '.');
                                            $vencimentoParcelaLinha = (string) ($parcelaLinha['vencimento'] ?? '');
                                            $vencimentoFmtLinha = $vencimentoParcelaLinha !== '' ? implode('/', array_reverse(explode('-', $vencimentoParcelaLinha))) : '-';
                                            echo '<div>Parcela ' . $numeroParcelaLinha . ': R$ ' . $valorParcelaLinha . ' | Venc. ' . $vencimentoFmtLinha . ' | <b style="color:' . $statusCor . ';">' . $statusParcela . '</b></div>';
                                        }
                                        echo '</div>';
                                    }
                                } else {
                                    $valorParcelaCredito = $parcelasTabela > 0 ? ($valorFinalCartao / $parcelasTabela) : $valorFinalCartao;
                                    echo $parcelasTabela . 'x';
                                    echo '<div style="margin-top:6px; font-size:12px; color:#555;">R$ ' . number_format($valorParcelaCredito, 2, ',', '.') . ' por parcela</div>';
                                }
                                ?>
                            </td>
                            <!--<td><?php echo 'R$ ' . number_format($registro['valor'], 2, ',', '.'); ?></td>-->
                            
                            <td>
    <?php echo 'R$ ' . number_format($valorFinalCartao, 2, ',', '.'); ?>
</td>
                            
                            
                            
                            
                            <td class="esc" style="text-transform: uppercase; max-width: 120px;">
                                <?php echo htmlspecialchars($situacaoLinha, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php
                                $subscriptionIdLinha = (int) ($registro['subscription_id'] ?? 0);
                                $temParcelaAbertaLinha = false;
                                if (!empty($parcelasDetalhesLinha)) {
                                    foreach ($parcelasDetalhesLinha as $parcelaLinhaAcao) {
                                        $statusParcelaAcao = strtoupper((string) ($parcelaLinhaAcao['status'] ?? ''));
                                        if (in_array($statusParcelaAcao, ['PENDENTE', 'ATRASADA'], true)) {
                                            $temParcelaAbertaLinha = true;
                                            break;
                                        }
                                    }
                                }
                                if ($ehRecorrenteLinha) {
                                    if ($subscriptionIdLinha > 0 && $temParcelaAbertaLinha) {
                                        ?>
                                        <button
                                            onclick='realizarPagamentoRecorrente(<?php echo (int) $registro["id"]; ?>, <?php echo (int) $id_do_aluno; ?>, <?php echo json_encode((string) ($registro["nome_curso"] ?? ""), JSON_UNESCAPED_UNICODE); ?>, <?php echo $subscriptionIdLinha; ?>)'>
                                            <i class="fa fa-credit-card" aria-hidden="true"></i>
                                            Regularizar / Trocar cartão
                                        </button>
                                        <?php
                                    } else {
                                        echo '<button type="button" disabled><i class="fa fa-check" aria-hidden="true"></i> Sem parcela pendente</button>';
                                    }
                                } else {
                                    ?>
                                    <?php if ($situacaoLinha === 'PAGO') { ?>
                                        <span style="color:#1a7f37; font-weight:600;">
                                            <i class="fa fa-check" aria-hidden="true"></i>
                                            Pagamento efetuado
                                        </span>
                                    <?php } else { ?>
                                        <button
                                            onclick='realizarPagamentoCartao(<?php echo (int) $registro["id"]; ?>, <?php echo (int) $id_do_aluno; ?>, <?php echo json_encode((string) ($registro["nome_curso"] ?? ""), JSON_UNESCAPED_UNICODE); ?>)'>
                                            <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                            Realizar Pagamento
                                        </button>
                                    <?php } ?>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
        <?php endif; ?>

        <?php if (empty($cartao_transactions)): ?>
            <center><span>Nenhum registro encontrado.</span></center>
        <?php endif; ?>
    </div>

    <br>


    <br>

</div>





<div class="modal fade" id="detalhesPagamento" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span></h4>

                <button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="margin-top: -20px">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>



            <div class="modal-body">

                <!-- <h1 id="textTitle"></h1> -->

                <div id="statusScreenBrick_container"></div>

            </div>





        </div>

    </div>

</div>

<style>
    /* Customização do SweetAlert2 */
    .financial-modal .swal2-popup {
        background: linear-gradient(135deg, #1a2035 0%, #121625 100%);
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(83, 92, 136, 0.3);
        padding: 0;
    }

    .financial-modal .swal2-title {
        color: #000;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        /*padding: 1.5rem 1.5rem 0.5rem;*/
        /*font-size: 1.5rem;*/
        border-bottom: 1px solid rgba(83, 92, 136, 0.2);
        margin: 0;
    }

    .financial-modal .swal2-html-container {
        padding: 0;
        margin: 0;
    }

    .financial-modal .swal2-actions {
        margin: 0;
        padding: 1rem;
        border-top: 1px solid rgba(83, 92, 136, 0.2);
    }

    .financial-modal .swal2-styled.swal2-confirm {
        background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
        border-radius: 8px;
        font-weight: 600;
        padding: 0.75rem 2rem;
        box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
        border: none;
    }

    .financial-modal .swal2-styled.swal2-confirm:hover {
        background: linear-gradient(135deg, #2a6ac4 0%, #00b3ee 100%);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .financial-modal .swal2-icon {
        margin: 1.5rem auto 0.5rem;
    }

    /* Conteúdo do Modal */
    .matricula-card {
        background-color: transparent;
        color: #fff;
        font-family: 'Poppins', sans-serif;
    }

    .header-info {
        background: linear-gradient(90deg, rgba(88, 103, 221, 0.1) 0%, rgba(0, 210, 255, 0.1) 100%);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-info::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #586BDD 0%, #00D2FF 100%);
    }

    .status-indicator {
        height: 60px;
        width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-pago {
        background: linear-gradient(135deg, rgba(56, 196, 138, 0.2) 0%, rgba(56, 196, 138, 0.05) 100%);
        border: 1px solid rgba(56, 196, 138, 0.3);
        color: #38C48A;
    }

    .bg-pendente {
        background: linear-gradient(135deg, rgba(255, 184, 34, 0.2) 0%, rgba(255, 184, 34, 0.05) 100%);
        border: 1px solid rgba(255, 184, 34, 0.3);
        color: #FFB822;
    }

    .bg-vencido {
        background: linear-gradient(135deg, rgba(244, 81, 108, 0.2) 0%, rgba(244, 81, 108, 0.05) 100%);
        border: 1px solid rgba(244, 81, 108, 0.3);
        color: #F4516C;
    }

    .bg-concluido {
        background: linear-gradient(135deg, rgba(85, 120, 235, 0.2) 0%, rgba(85, 120, 235, 0.05) 100%);
        border: 1px solid rgba(85, 120, 235, 0.3);
        color: #5578EB;
    }

    .status-indicator i {
        font-size: 1.5rem;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-pago {
        background: linear-gradient(135deg, #38C48A 0%, #28A745 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(56, 196, 138, 0.3);
    }

    .badge-pendente {
        background: blue;
        color: white;
        box-shadow: 0 2px 8px rgba(255, 184, 34, 0.3);
    }

    .badge-vencido {
        background: linear-gradient(135deg, #F4516C 0%, #E53935 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(244, 81, 108, 0.3);
    }

    .badge-concluido {
        background: linear-gradient(135deg, #5578EB 0%, #4E73DF 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(85, 120, 235, 0.3);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        padding: 0 1.25rem;
    }

    .info-card {
        background: rgba(83, 92, 136, 0.1);
        border-radius: 8px;
        padding: 1rem;
        position: relative;
        border: 1px solid rgba(83, 92, 136, 0.2);
    }

    .info-card .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8a94a6;
        margin-bottom: 0.3rem;
    }

    .info-card .value {
        font-size: 1rem;
        font-weight: 600;
        color: #000;
    }

    .highlight-box {
        background: linear-gradient(135deg, rgba(88, 103, 221, 0.15) 0%, rgba(0, 210, 255, 0.05) 100%);
        border: 1px solid rgba(83, 92, 136, 0.2);
        border-radius: 10px;
        padding: 1.5rem;
        margin: 2rem 1.25rem 1.5rem;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .highlight-box .icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        font-size: 2rem;
        color: rgba(0, 210, 255, 0.2);
    }

    .highlight-box .label {
        font-size: 0.85rem;
        color: #8a94a6;
        margin-bottom: 0.5rem;
    }

    .highlight-box .value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(90deg, #586BDD 0%, #00D2FF 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .matricula-id {
        font-size: 0.85rem;
        opacity: 0.7;
        margin-top: 0.5rem;
    }

    .curso-nome {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Animações */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.5s ease forwards;
    }

    .delay-1 {
        animation-delay: 0.1s;
    }

    .delay-2 {
        animation-delay: 0.2s;
    }

    .delay-3 {
        animation-delay: 0.3s;
    }

    .delay-4 {
        animation-delay: 0.4s;
    }
</style>

<script>

    window.addEventListener('message', function (event) {
        const data = event && event.data ? event.data : null;
        if (!data || data.type !== 'efi-payment-success') {
            return;
        }
        setTimeout(function () {
            window.location.reload();
        }, 700);
    });

    function realizarPagamentoCartao(id, id_aluno, nome_curso) {
        const nomeSeguro = encodeURIComponent(nome_curso || '');
        const url = `<?php echo $url_sistema ?>efi/credit_card.php?id=${id}&id_aluno=${id_aluno}&nome_curso=${nomeSeguro}`;

        Swal.fire({
            title: 'Realizar Pagamento',
            html: `<iframe src="${url}" width="100%" height="600px" style="border: none; background: #fff;"></iframe>`,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });
    }

    function realizarPagamentoRecorrente(id, id_aluno, nome_curso, subscription_id) {
        const nomeSeguro = encodeURIComponent(nome_curso || '');
        const url = `<?php echo $url_sistema ?>efi/credit_card.php?id=${id}&id_aluno=${id_aluno}&nome_curso=${nomeSeguro}&modo=recorrente&subscription_id=${subscription_id}`;
        Swal.fire({
            title: 'Regularizar Parcela / Trocar Cartao',
            html: `<iframe src="${url}" width="100%" height="600px" style="border: none; background: #fff;"></iframe>`,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });
    }

    function openBoleto(boleto) {
        Swal.fire({

            title: 'Visualizar Boleto',

            html: `<iframe src="${boleto}" width="100%" height="400px" style="border: none; background: #fff;"></iframe>`,

            width: '80%',
            theme: 'dark',

            showCloseButton: true,

            showConfirmButton: false

        });

    }

    function visualizarQR2(qrcode, texto_copia_cola, valor) {
        Swal.fire({
            html: `
                <div>
                     <span>Descrição</span>
                    <span>Valor do pagamento: ${valor}</span>
                   <img src="${qrcode}"  />
                        <br>
                    <span style="font-size: 12pt;">${texto_copia_cola}</span>
                </div>
            `
        })
    }

    function visualizarQR(qrcode, texto_copia_cola, valor, data_criacao, status) {
        // Formatar data e valor para exibição
        const dataFormatada = new Date(data_criacao).toLocaleDateString('pt-BR');
        const valorFormatado = parseFloat(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Definir classes e ícones de acordo com o status
        let statusClass = '';
        let iconClass = '';
        let badgeClass = '';

        switch (status) {
            case 'pago':
                statusClass = 'bg-pago';
                iconClass = 'fa-check-circle';
                badgeClass = 'badge-pago';
                break;
            case 'pendente':
                statusClass = 'bg-pendente';
                iconClass = 'fa-clock';
                badgeClass = 'badge-pendente';
                break;
            case 'vencido':
                statusClass = 'bg-vencido';
                iconClass = 'fa-exclamation-circle';
                badgeClass = 'badge-vencido';
                break;
            default:
                statusClass = 'bg-secondary';
                iconClass = 'fa-question-circle';
                badgeClass = 'badge-secondary';
        }

        // Montar o HTML para o conteúdo do SweetAlert
        const conteudoHtml = `
    <div class="matricula-card">


      <div class="highlight-box animate-fadeInUp delay-1">
        <img src="${qrcode}" width="250px" style="align-self: center;"/>
        <i class="fas fa-money-bill-wave icon"></i>
        <div class="label">COPIA E COLA</div>
    <input type="text" id="pix-code" value="${texto_copia_cola}" readonly style="border: none; color: #000; font-size: 10pt;" />
        <button onclick="copiarCodigo()"  class="status-badge ${badgeClass} mt-3">Copiar</button>
<br>

<div class="info-card animate-fadeInUp delay-3">
          <div class="label">Valor</div>
          <div class="value">${valorFormatado}</div>
        </div>

        <div class="info-card animate-fadeInUp delay-2">
          <div class="label">Data</div>
          <div class="value">${dataFormatada}</div>
        </div>


      </div>

      <div class="info-grid">

      </div>
    </div>
  `;

        // Configurar e exibir o SweetAlert com design personalizado
        Swal.fire({
            title: 'Realizar Pagamento',
            html: conteudoHtml,
            showConfirmButton: true,
            confirmButtonText: 'Fechar',
            customClass: {
                popup: 'swal2-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                actions: 'swal2-actions',
                confirmButton: 'swal2-styled swal2-confirm',
                container: 'financial-modal'
            },
            showClass: {
                popup: 'animate__animated animate__fadeIn'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOut'
            },
            width: '550px',
            padding: 0,
            background: '#fff',
            backdrop: `rgba(0, 0, 0, 0.6)`,
            allowOutsideClick: true
        });
    }


</script>

<script>
    function copiarCodigo() {
        var codigoInput = document.getElementById("pix-code");
        codigoInput.select();
        codigoInput.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("Código copiado para a área de transferência!");
    }
</script>

<script>

    function fazerPagamento(registro) {

        // const id_do_curso_pag = encodeURIComponent(registro['id_do_curso']);

        // const nome_curso_titulo = encodeURIComponent(registro['professor']);

        // const formaDePagamento = encodeURIComponent('cartao_de_credito'); // Definido fixo como no exemplo

        // const quantidadeParcelas = encodeURIComponent('1'); // Definido fixo como no exemplo



        // // Monta a URL com os parâmetros

        // const url = `http://sested.local/pagamentos_novo/index.php?formaDePagamento=${formaDePagamento}&quantidadeParcelas=${quantidadeParcelas}&id_do_curso=${id_do_curso_pag}&nome_do_curso=${nome_curso_titulo}`;



        // // Abre a URL em uma nova guia

        // window.open(url, '_blank');

    }











    let hasPayId = false;

    let statusScreenBrickController = null; // Para armazenar a instância do Brick



    async function verDetalhes(registro) {

        Swal.fire({
            title: 'Detalhes do Pagamento',
            text: 'Caregando...'
        })
        return;
        const payId = registro['ref_api']; // Obtém o novo payId

        if (!payId) return; // Se não houver payId, não faz nada



        $('#textTitle').text(payId);

        $('#detalhesPagamento').modal('show');

        hasPayId = true;



        // Remove o Brick anterior antes de renderizar o novo

        if (statusScreenBrickController) {

            await statusScreenBrickController.unmount();

            statusScreenBrickController = null;

        }



        // Agora renderiza apenas se houver payId

        renderStatusScreenBrick(bricksBuilder, payId);

    }



    const bricksBuilder = null;



    const renderStatusScreenBrick = async (bricksBuilder, payId) => {
        if (!bricksBuilder) {
            return;
        }

        const settings = {

            initialization: {

                paymentId: payId, // Payment identifier, from which the status will be checked

            },

            customization: {

                visual: {

                    hideStatusDetails: false,

                    hideTransactionDate: false,

                    style: {

                        theme: 'dark', // 'default' | 'dark' | 'bootstrap' | 'flat'

                    },

                },

                backUrls: {}

            },

            callbacks: {

                onReady: () => {

                    console.log('ready');

                },

                onError: (error) => {

                    console.log('error', error);

                },

            },

        };



        // Cria e armazena a nova instância do Brick

        statusScreenBrickController = await bricksBuilder.create('statusScreen', 'statusScreenBrick_container', settings);

    };

</script>
