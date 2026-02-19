<?php



require_once('../../vendor/autoload.php');

require_once("../conexao.php");
require_once("../../config/env.php");

$mp_enabled = filter_var(env('MP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);





@session_start();

$id_do_aluno = @$_SESSION['id'];

// $consulta_parcelas_boleto_parcelado = $pdo->query("SELECT parcelas_geradas_por_boleto.*, cursos.nome as curso FROM parcelas_geradas_por_boleto JOIN boletos_parcelados ON boletos_parcelados.id = parcelas_geradas_por_boleto.id_boleto_parcelado JOIN matriculas ON matriculas.id = boletos_parcelados.id_matricula JOIN cursos ON cursos.id = matriculas.id_curso WHERE matriculas.aluno = '$id_do_aluno'");
// $resposta_consulta_boleto_parcelado = $consulta_parcelas_boleto_parcelado->fetchAll(PDO::FETCH_ASSOC);

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

// echo '<pre>';
// echo json_encode($resposta_consulta_boleto_parcelado, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;


//  $consulta_matricula_pix_efi = $pdo->query("SELECT id FROM matriculas WHERE aluno = '$id_do_aluno'");

//  $resposta_consulta_efi = $consulta_matricula_pix_efi->fetchAll(PDO::FETCH_ASSOC);

// $pix_transactions = [];

// foreach ($resposta_consulta_efi as $efi_pix) {
//     $id = $efi_pix['id']; // Aqui você pega apenas o valor do ID

//     $consulta_pagamentos_pix = $pdo->query("SELECT * FROM pagamentos_pix WHERE id_matricula = '$id'");
//     $resposta_consulta_pagamentos_pix = $consulta_pagamentos_pix->fetchAll(PDO::FETCH_ASSOC);

//     array_push($pix_transactions, $resposta_consulta_pagamentos_pix[0]);
// }

// echo '<pre>';
// echo json_encode($resposta_consulta_boleto_parcelado, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;


// $consulta_matricula = $pdo->query("SELECT id, forma_pgto FROM matriculas WHERE aluno = '$id_do_aluno'");
// $resposta_consulta = $consulta_matricula->fetchAll(PDO::FETCH_ASSOC);

// $transactions = [];

// foreach ($resposta_consulta as $matricula) {
//     $id = $matricula['id'];
//     $forma_pgto = $matricula['forma_pgto'];

//     // Determina a tabela de consulta baseada na forma de pagamento
//     if ($forma_pgto == 'PIX') {
//         $tabela_pagamentos = 'pagamentos_pix';
//     } elseif ($forma_pgto == 'BOLETO') {
//         $tabela_pagamentos = 'pagamentos_boleto';
//     } else {
//         // Caso haja outras formas de pagamento ou valor nulo
//         continue; // Pula para a próxima iteração
//     }

//     // Executa a consulta na tabela apropriada
//     $consulta_pagamentos = $pdo->query("SELECT * FROM $tabela_pagamentos WHERE id_matricula = '$id'");
//     $resposta_pagamentos = $consulta_pagamentos->fetchAll(PDO::FETCH_ASSOC);

//     // Adiciona o resultado se houver dados
//     if (!empty($resposta_pagamentos)) {
//         array_push($transactions, $resposta_pagamentos[0]);
//     }
// }

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
    if ($forma_pgto == 'PIX') {
        $tabela_pagamentos = 'pagamentos_pix';
    } elseif ($forma_pgto == 'BOLETO') {
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



$consulta_matriculas = $pdo->prepare("

    SELECT 

        matriculas.*, 

        cursos.nome AS nome_curso,

        usuarios.nome AS nome_professor

    FROM matriculas 

    JOIN cursos ON cursos.id = matriculas.id_curso 

    JOIN usuarios ON usuarios.id = matriculas.professor

    WHERE matriculas.aluno = :aluno

    AND matriculas.forma_pgto = 'MP'

");



$consulta_matriculas->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas = $consulta_matriculas->fetchAll(PDO::FETCH_ASSOC);




// $consulta_matriculas_pix = $pdo->query("

//     SELECT 

//         matriculas.*, 

//         cursos.nome AS nome_curso,

//         usuarios.nome AS nome_professor

//     FROM matriculas 

//     JOIN cursos ON cursos.id = matriculas.id_curso 

//     JOIN usuarios ON usuarios.id = matriculas.professor

//     WHERE matriculas.aluno = '$id_do_aluno'

//     AND matriculas.forma_pgto = 'PIX'

// ");



// $resposta_consulta_matriculas_pix = $consulta_matriculas_pix->fetchAll(PDO::FETCH_ASSOC);


$consulta_matriculas_pix = $pdo->prepare("
    SELECT 
        matriculas.*, 
        CASE 
            WHEN matriculas.pacote = 'sim' THEN pacotes.nome 
            ELSE cursos.nome 
        END AS nome_curso,
        usuarios.nome AS nome_professor
    FROM matriculas 
    LEFT JOIN cursos ON cursos.id = matriculas.id_curso 
    LEFT JOIN pacotes ON pacotes.id = matriculas.id_curso 
    JOIN usuarios ON usuarios.id = matriculas.professor
    WHERE matriculas.aluno = :aluno
    AND matriculas.forma_pgto = 'PIX'
");

$consulta_matriculas_pix->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_pix = $consulta_matriculas_pix->fetchAll(PDO::FETCH_ASSOC);


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



$consulta_matriculas_cartao_p = $pdo->prepare("

    SELECT 

        matriculas.*, 

        pacotes.nome AS nome_curso,

        usuarios.nome AS nome_professor

    FROM matriculas 

    JOIN pacotes ON pacotes.id = matriculas.id_curso 

    JOIN usuarios ON usuarios.id = matriculas.professor

    WHERE matriculas.aluno = :aluno

    AND matriculas.forma_pgto = 'CARTAO_DE_CREDITO'

");



$consulta_matriculas_cartao_p->execute(['aluno' => $id_do_aluno]);
$resposta_consulta_matriculas_cartao_p = $consulta_matriculas_cartao_p->fetchAll(PDO::FETCH_ASSOC);




$consulta_matriculas_cartao_c = $pdo->prepare("

    SELECT 

        matriculas.*, 

        cursos.nome AS nome_curso,

        usuarios.nome AS nome_professor

    FROM matriculas 

    JOIN cursos ON cursos.id = matriculas.id_curso 

    JOIN usuarios ON usuarios.id = matriculas.professor

    WHERE matriculas.aluno = :aluno

    AND matriculas.forma_pgto = 'CARTAO_DE_CREDITO'

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



$pix_transactions = [];
$boleto_transactions = [];


foreach ($transactions as $registro) {
    $eh_pix = isset($registro['txid']);
    $eh_boleto = isset($registro['nosso_numero']);

    if ($eh_pix) {
        $pix_transactions[] = $registro;
    } elseif ($eh_boleto) {
        $boleto_transactions[] = $registro;
    }
}


//BUSCA VALOR ACRESCIMO CARTAO
$queryAcrescimoCartao = $pdo->query("SELECT acrescimo_cartao_credito FROM config");
$resAcrescimoCartao = $queryAcrescimoCartao->fetchColumn();

// echo json_encode($pix_transactions);
// return;

?>



<head>

    <?php if ($mp_enabled) { ?>
        <script src="https://sdk.mercadopago.com/js/v2"></script>
    <?php } ?>



</head>



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




  

    <!-- <h3>BOLETOS</h3>

    <br>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ID Matrícula</th>
                <th>Data</th>
                <th>Identificador</th>
                <th>Valor</th>
                <th>Situação</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $registro): ?>
                <?php
                // Determina o tipo de pagamento baseado nos campos disponíveis
                $eh_pix = isset($registro['txid']);
                $eh_boleto = isset($registro['nosso_numero']);

                // Define campos específicos baseado no tipo
                if ($eh_pix) {
                    $data_campo = $registro['data_criacao'];
                    $identificador = $registro['txid'];
                    $tipo_pagamento = 'PIX';
                } elseif ($eh_boleto) {
                    $data_campo = $registro['criado_em'];
                    $identificador = $registro['nosso_numero'];
                    $tipo_pagamento = 'BOLETO';
                } else {
                    continue; // Pula registros sem tipo identificável
                }
                ?>
                <tr>
                    <td><?php echo $registro['id']; ?></td>
                    <td style="width: 130px;"><?php echo $registro['id_matricula']; ?></td>
                    <td><?php echo (new DateTime($data_campo))->format('d/m/Y'); ?></td>
                    <td>
                        <small><?php echo $tipo_pagamento; ?>:</small>
                        <?php echo htmlspecialchars($identificador); ?>
                    </td>
                    <td><?php echo 'R$ ' . number_format($registro['valor'], 2, ',', '.'); ?></td>
                    <td class="esc" style="text-transform: uppercase;">
                        <?php echo $registro['status'] === '' ? 'pendente' : 'pago'; ?>
                    </td>
                    <td>
                        <?php if ($registro['status'] === ''): ?>
                            <?php if ($eh_pix): ?>



                            <?php elseif ($eh_boleto): ?>
                                <button
                                    onclick="openBoleto('<?php echo htmlspecialchars($registro['url_boleto'], ENT_QUOTES); ?>')">
                                    <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                    Pagar Boleto
                                </button>
                            <?php endif; ?>
                        <?php elseif ($registro['status'] === 'paid'): ?>
                            <button
                                onclick="window.open('<?php echo htmlspecialchars($registro['url_boleto'], ENT_QUOTES); ?>', '_blank')">
                                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                Ver Detalhes
                            </button>

                        <?php elseif ($registro['status'] === 'vencido'): ?>
                            <?php if ($eh_boleto): ?>
                                <button type="button" onclick="gerarNovoBoleto(<?php echo $registro['id_matricula']; ?>)">
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                    Gerar Novo
                                </button>
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                                    Vencido
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table> -->


    <div>


        <!-- TABELA CARTAO -->
        <?php if ($cartao_transactions !== null): ?>
            <div style="margin-top: 20px;">

                <h3>Pagamentos Cartão de Crédito</h3>
            </div>

            <br>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Curso / Pacote</th>
                        <th>Data</th>
                        <th>Forma de pagamento</th>
                        <th>Valor</th>
                        <th>Situação</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartao_transactions as $registro): ?>
                    
                    
                    <?php
                    
                    $valorA = (int) $registro['valor']; 
                    $percentual = isset($resAcrescimoCartao) ? (float) $resAcrescimoCartao : 0; 
                    
                    if ($percentual > 0) {
    
    $valorA += ceil($valorA * ($percentual / 100));

}
                    
                    
                    
                    ?>
                        <tr>
                            <td><?php echo $registro['id']; ?></td>
                            <td style="max-width: 130px; overflow: hidden;"><?php echo json_decode('"' . $registro['nome_curso'] . '"');
                            ; ?>
                            </td>
                            <td><?php echo (new DateTime($registro['data']))->format('d/m/Y'); ?></td>
                            <td style="max-width: 130px;">
                                Cartão de crédito
                            </td>
                            <!--<td><?php echo 'R$ ' . number_format($registro['valor'], 2, ',', '.'); ?></td>-->
                            
                            <td>
    <?php
        $valor = $valorA;
        $desconto = !empty($registro['valor_cupom']) ? (float)$registro['valor_cupom'] : 0;
        $valor_final = $valor - $desconto;

        echo 'R$ ' . number_format($valor_final, 2, ',', '.');
    ?>
</td>
                            
                            
                            
                            
                            <td class="esc" style="text-transform: uppercase; max-width: 50px;">
                                <?php echo $registro['status'] === '' ? 'pendente' : $registro['status']; ?>
                            </td>
                            <td>
                                <button
                                    onclick="realizarPagamentoCartao(<?php echo $registro['id']; ?>, <?php echo $id_do_aluno; ?>, '<?php echo $registro['nome_curso']; ?>')">
                                    <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                    Realizar Pagamento
                                </button>
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

    function realizarPagamentoCartao(id, id_aluno, nome_curso) {
       

        Swal.fire({
            title: 'Realizar Pagamento',
            html: `<iframe src="<?php echo $url_sistema ?>efi/credit_card.php?id=${id}&id_aluno=${id_aluno}&nome_curso=${nome_curso}" width="100%" height="600px" style="border: none; background: #fff;"></iframe>`,
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
        alert("Código PIX copiado para a área de transferência!");
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



    // Inicializa o MercadoPago
    const mpEnabled = <?php echo $mp_enabled ? 'true' : 'false'; ?>;
    const mpPublicKey = '<?php echo $mp_enabled ? env('MP_PUBLIC_KEY', '') : ''; ?>';
    const mp = (mpEnabled && mpPublicKey !== '') ? new MercadoPago(mpPublicKey, { locale: 'pt' }) : null;
    const bricksBuilder = mp ? mp.bricks() : null;



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
