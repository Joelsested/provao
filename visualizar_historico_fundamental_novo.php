<?php
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
ini_set('default_charset', 'UTF-8');

if (!function_exists('normalizar_chave_historico')) {
    function normalizar_chave_historico($chave)
    {
        $chave = mb_strtolower((string) $chave, 'UTF-8');
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $chave);
        if ($normalizado !== false) {
            $chave = $normalizado;
        }
        $chave = preg_replace('/[^a-z0-9]+/', '_', $chave);
        return trim((string) $chave, '_');
    }
}

if (!function_exists('data_nota')) {
    function data_nota(array $notas, $chave)
    {
        $raw = (string) ($notas[$chave]['data'] ?? '');
        $raw = ltrim($raw, '*');
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        return $ts ? date('d-m-Y', $ts) : '';
    }
}

if (!function_exists('valor_nota')) {
    function valor_nota(array $notas, $chave, $serie)
    {
        $valor = (string) ($notas[$chave][$serie] ?? '0.0');
        return ltrim($valor, '*');
    }
}

$notas = isset($notas) && is_array($notas) ? $notas : [];
$notasNormalizadas = [];
foreach ($notas as $materia => $dadosMateria) {
    if (!is_array($dadosMateria)) {
        continue;
    }
    $chaveNormalizada = normalizar_chave_historico($materia);
    if ($chaveNormalizada === '') {
        continue;
    }
    $notasNormalizadas[$chaveNormalizada] = [
        'serie1' => (string) ($dadosMateria['serie1'] ?? '0.0'),
        'serie2' => (string) ($dadosMateria['serie2'] ?? '0.0'),
        'serie3' => (string) ($dadosMateria['serie3'] ?? '0.0'),
        'data' => (string) ($dadosMateria['data'] ?? ''),
    ];
}

$materiasEsperadas = [
    'lingua_portuguesa',
    'arte',
    'educacao_fisica',
    'matematica',
    'ciencias',
    'historia',
    'geografia',
    'lingua_inglesa',
];

foreach ($materiasEsperadas as $materia) {
    if (!isset($notasNormalizadas[$materia])) {
        $notasNormalizadas[$materia] = [
            'serie1' => '0.0',
            'serie2' => '0.0',
            'serie3' => '0.0',
            'data' => '',
        ];
    }
}

$notas = $notasNormalizadas;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Escolar - SESTED</title>
    <style>
        @page {
            size: A4;
            margin: 4mm;
        }

        * {
            box-sizing: border-box;
        }



        .document-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
        }

        .logo-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 20px;
            padding-bottom: 4px;
            margin-top: 8px;
            margin-bottom: 10px;
        }

        .logo-header img {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
        }

        .logo-header span {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            font-size: 12px;
        }

        td,
        th {
            border: 0.5px solid black;
            padding: 1px 2px;
            /* vertical-align: top; */
            text-align: left;
            line-height: 1.1;
        }

        .header-row {
            background-color: #f0f0f0;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .small-text {
            font-size: 9px;
        }

        .rotated-text {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            text-align: center;
            width: 18px;
            font-size: 9px;
            padding: 1px;
        }

        .grades-section td {
            text-align: center;
            font-weight: bold;
        }

        .ch-data-head,
        .ch-data-cell {
            white-space: nowrap;
            text-align: center;
        }

        .subject-area {
            background-color: #f9f9f9;
            font-weight: bold;
            text-align: center;
            writing-mode: vertical-lr;
            text-orientation: mixed;
            font-size: 9px;
            width: 22px;
        }

        .signature-section {
            text-align: center;
            padding-top: 3px;
        }

        .signature-section p {
            margin: 2px 0;
            font-size: 9px;
        }

        .final-logo {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 5px;
            margin: 2px 0;
        }

        .final-logo img {
            width: 25px;
            height: 25px;
        }

        .final-logo span {
            font-size: 10px;
            font-weight: bold;
        }

        /* Media query para impressão */


        .uppercase {
            text-transform: uppercase;
        }

        .historic-title {
            padding: 20px;
        }

        .data-display {
            font-size: 8px;
            padding: 1px;
            font-weight: bold;

        }

        tr td.data-display:last-of-type {
            /* background: yellow; */
            /* exemplo */
        }

        .historico-carimbo {
            border: 1px solid #000;
            text-align: center;
            font-weight: 700;
            line-height: 1.05;
            padding: 6px 4px;
            margin-bottom: 8px;
        }

        .historico-carimbo .titulo {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .historico-carimbo .linha {
            font-size: 12px;
        }

        .historico-data-texto {
            font-size: 13px;
            text-align: left;
            margin-top: 4px;
        }

        .print-toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            display: flex;
            gap: 8px;
        }

        .print-btn {
            border: 1px solid #1f3b57;
            background: #1f3b57;
            color: #fff;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            border-radius: 4px;
        }

        @media print {
            .print-toolbar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="print-toolbar">
        <button type="button" class="print-btn" onclick="imprimirHistorico()">Imprimir / Salvar em PDF</button>
    </div>
    <div class="document-container" id="conteudoModal">
        <div class="logo-header">
            <img src="https://sested-eja.com/img/logo.jpg" alt="Logo SESTED" />
            <span style="font-size: 17px;">SESTED - Sistema de Ensino Superior Tecnológico e Educacional</span>
        </div>
        <table>
            <tr>
                <td colspan="3" class="" style="padding: 3px;">NOME:
                    <b><?php echo $dadosAdicionais['escola']; ?></b>
                </td>
                <td colspan="22" class="">
                    AUTORIZAÇÃO: <b>PARECER CEB/CEE/RO Nº 041/18 e RESOLUÇÃO CEB/CEE/RO Nº 1296/21</b>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="" style="padding: 3px;">CNPJ: <b>07.158.229/0001-06</b></td>
                <td colspan="14" class="">MUNICÍPIO: <b>BURITIS/RO</b></td>
                <td colspan="7" class="">CNPJ: <b>07.158.229/0001-06</b></td>
            </tr>
            <tr>
                <td colspan="17" class="" style="padding: 3px;">ENDEREÇO: <b>RUA NOVA UNIÃO - 2024 SETOR 02</b></td>
                <td colspan="8" class="">TEL.: <b>69 -9-92474696</b></td>
            </tr>
            <tr>
                <td colspan="17" class="uppercase" style="padding: 3px;">ALUNO (a):
                    <b><?php echo $dadosAluno['nome']; ?></b>
                </td>
                <td colspan="8">SEXO: <b><?php echo $dadosAluno['sexo']; ?></b></td>
            </tr>
            <tr>
                <td colspan="4" class="uppercase" style="padding: 3px;">DATA DE NASC:
                    <b><?php echo $dadosAluno['dataNasc']; ?></b>
                </td>
                <td colspan="14" class="uppercase">NATURALIDADE: <b><?php echo $dadosAluno['naturalidade']; ?></b></td>
                <td colspan="8" class="">NACIONALIDADE: <b>BRASILEIRO(A)</b></td>
            </tr>
            <tr>
                <td colspan="10" style="padding: 3px;">CERTIDÃO DE NASCIMENTO:
                    <b><?php echo $dadosAdicionais['certidao_nascimento']; ?></b>
                </td>
                <td colspan="14" style="padding: 3px;">CPF: <b><?php echo $dadosAluno['cpf']; ?></b></td>
            </tr>
            <tr>

                <td colspan="10">RG: <b><?php echo $dadosAluno['rg']; ?></b></td>
                <td colspan="10">ÓRGÃO EXP: <b><?php echo $dadosAluno['orgao_emissor'] ?? ''; ?></b></td>
                <td colspan="4">EMISSÃO: <b><?php echo $dadosAluno['expedicao'] ?? ''; ?></b></td>
            </tr>
            <tr>
                <td colspan="12" class="uppercase" style="padding: 3px;">PAI: <b><?php echo $dadosAluno['pai']; ?></b>
                </td>
                <td colspan="12" class="uppercase">MÃE: <b><?php echo $dadosAluno['mae']; ?></b></td>
            </tr>

            <tr>
                <td colspan="25" class="bold center" style="font-size: 14px; padding: 10px;">HISTÓRICO ESCOLAR DO ENSINO
                    FUNDAMENTAL</td>
            </tr>


            <tr>
                <th colspan="12" style="padding: 10px; text-align: center; background-color:rgb(238, 238, 238);">ÁREAS
                    DE CONHECIMENTO</th>
                <th colspan="14" style="padding: 10px; text-align: center; background-color:rgb(238, 238, 238);">ANOS /
                    CARGA HORÁRIA</th>
            </tr>
            <tr>
                <th rowspan="2" colspan="14" style="font-size: 9px; text-align: center;">RESULTADOS FINAIS = RF CARGAS
                    HORÁRIAS = CH TOTAL DE FALTAS = TF
                    COMPONENTES
                    CURRICULARES</th>
                <th style="padding: 12px; text-align: center; background-color: #f0f0f0;" colspan="2">6º Ano</th>
                <th style="padding: 12px; text-align: center; background-color: #f0f0f0;" colspan="2">7º Ano</th>
                <th style="padding: 12px; text-align: center; background-color: #f0f0f0;" colspan="3">8º Ano</th>
                <th style="padding: 12px; text-align: center; background-color: #f0f0f0;" colspan="3">9º Ano</th>
            </tr>
            <tr>
                <th colspan="1" style="padding: 4px; background-color: #f0f0f0; font-size: 10px; text-align: center;">
                    NOTA</th>
                <th colspan="1" class="ch-data-head" style="padding: 4px; background-color: #f0f0f0; font-size: 10px;">CH/DATA</th>
                <th colspan="1" style="padding: 4px; background-color: #f0f0f0; font-size: 10px; text-align: center;">
                    NOTA</th>
                <th colspan="1" class="ch-data-head" style="padding: 4px; background-color: #f0f0f0; font-size: 10px;">CH/DATA</th>
                <th colspan="1" style="padding: 4px; background-color: #f0f0f0; font-size: 10px; text-align: center;">
                    NOTA</th>
                <th colspan="2" class="ch-data-head" style="padding: 4px; background-color: #f0f0f0; font-size: 10px;">CH/DATA</th>
                <th colspan="1" style="padding: 4px; background-color: #f0f0f0; font-size: 10px; text-align: center;">
                    NOTA</th>
                <th colspan="3" class="ch-data-head" style="padding: 4px; background-color: #f0f0f0; font-size: 10px;">CH/DATA</th>
            </tr>
            <tr>
                <td rowspan="7" colspan="3" class="area-name"
                    style="text-align: center; width: 20%; background-color:rgb(238, 238, 238);"><b>BASE
                        NACIONAL</b></td>
                <td colspan="11" class="subject-name">Língua Portuguesa</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_portuguesa', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px; white-space: nowrap;" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_portuguesa'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_portuguesa', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px; white-space: nowrap;" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_portuguesa'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_portuguesa', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px; white-space: nowrap;" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_portuguesa'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_portuguesa', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px; white-space: nowrap;" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_portuguesa'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="11" class="subject-name">Arte</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'arte', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'arte'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'arte', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'arte'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'arte', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'arte'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'arte', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'arte'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="11" class="subject-name">Educação Física</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'educacao_fisica', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'educacao_fisica'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'educacao_fisica', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'educacao_fisica'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'educacao_fisica', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'educacao_fisica'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'educacao_fisica', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'educacao_fisica'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="11" class="subject-name">Matemática</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'matematica', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'matematica'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'matematica', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'matematica'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'matematica', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'matematica'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'matematica', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'matematica'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="11" class="subject-name">Ciências</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'ciencias', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'ciencias'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'ciencias', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'ciencias'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'ciencias', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'ciencias'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'ciencias', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'ciencias'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="11" class="subject-name">História</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'historia', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'historia'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'historia', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'historia'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'historia', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'historia'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'historia', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'historia'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="11" class="subject-name">Geografia</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'geografia', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'geografia'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'geografia', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'geografia'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'geografia', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'geografia'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'geografia', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'geografia'); ?>
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="area-name" style="text-align: center; background-color:rgb(238, 238, 238);">
                    <b>SUB-TOTAL</b>
                </td>
                <td colspan="11" class="subject-name" style="text-align: center;">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
            </tr>
            <tr>
                <td colspan="3" class="area-name" style="text-align: center; background-color:rgb(238, 238, 238);">
                    <b>PARTE DIVERSIFICADA</b>
                </td>
                <td colspan="11" class="subject-name">Língua Inglesa</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_inglesa', 'serie1'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_inglesa'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_inglesa', 'serie2'); ?>
                </td>
                <td style="text-align: center; padding: 2px" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_inglesa'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_inglesa', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_inglesa'); ?>
                </td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">
                    <?php echo valor_nota($notas, 'lingua_inglesa', 'serie3'); ?>
                </td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell ch-data-cell">
                    <?php echo data_nota($notas, 'lingua_inglesa'); ?>
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="area-name" style="text-align: center; background-color:rgb(238, 238, 238);">
                    <b>SUB-TOTAL</b>
                </td>
                <td colspan="11" class="subject-name" style="text-align: center;">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="area-name" style="text-align: center; background-color:rgb(238, 238, 238);">
                    <b>TOTAL GERAL</b>
                </td>
                <td colspan="11" class="subject-name" style="text-align: center;">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
            </tr>





            <!-- Totalizadores -->
            <tr>
                <td colspan="14" class="bold center">Dias Letivos</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
            </tr>

            <tr>
                <td colspan="14" class="bold center">Carga Horária Anual</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
            </tr>

            <tr>
                <td colspan="14" class="bold center">Carga Horária Total</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
            </tr>

            <tr>
                <td colspan="14" class="bold center">RESULTADO FINAL</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px" colspan="2" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" class="grade-cell">-</td>
                <td style="text-align: center; padding: 2px; font-weight: bold;" colspan="2" class="grade-cell">
                    <?php echo $dadosAdicionais['situacao'] ?? '' ?>
                </td>

            </tr>

            <!-- Estudos Realizados -->
            <tr>
                <td rowspan="6" colspan="2" class="rotated-text bold">Estudos Realizados</td>
                <td colspan="2" class="center bold">ANO ESCOLAR</td>
                <td colspan="3" class="center bold">ANO</td>
                <td colspan="9" class="center bold">INSTITUIÇÃO DE ENSINO</td>
                <td colspan="7" class="center bold">MUNICÍPIO</td>
                <td colspan="1" class="center bold">UF</td>
            </tr>
            <tr>
                <td colspan="2" class="center" style="padding: 4px;">6º Ano</td>
                <td colspan="3" class="center">
                    <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
                </td>
                <td colspan="9" class="center">
                    <?php echo $dadosAdicionais['escola'] ?? '' ?>
                </td>
                <td colspan="7" class="center">
                    <?php echo $dadosAdicionais['municipio'] ?? '' ?>
                </td>
                <td colspan="1" class="center">
                    <?php echo $dadosAdicionais['uf'] ?? '' ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="center" style="padding: 4px;">7º Ano</td>
                <td colspan="3" class="center">
                    <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
                </td>
                <td colspan="9" class="center">
                    <?php echo $dadosAdicionais['escola'] ?? '' ?>
                </td>
                <td colspan="7" class="center">
                    <?php echo $dadosAdicionais['municipio'] ?? '' ?>
                </td>
                <td colspan="1" class="center">
                    <?php echo $dadosAdicionais['uf'] ?? '' ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="center" style="padding: 4px;">8º Ano</td>
                <td colspan="3" class="center">
                    <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
                </td>
                <td colspan="9" class="center">
                    <?php echo $dadosAdicionais['escola'] ?? '' ?>
                </td>
                <td colspan="7" class="center">
                    <?php echo $dadosAdicionais['municipio'] ?? '' ?>
                </td>
                <td colspan="1" class="center">
                    <?php echo $dadosAdicionais['uf'] ?? '' ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="center" style="padding: 4px;">9º Ano</td>
                <td colspan="3" class="center">
                    <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
                </td>
                <td colspan="9" class="center">
                    <?php echo $dadosAdicionais['escola'] ?? '' ?>
                </td>
                <td colspan="7" class="center">
                    <?php echo $dadosAdicionais['municipio'] ?? '' ?>
                </td>
                <td colspan="1" class="center">
                    <?php echo $dadosAdicionais['uf'] ?? '' ?>
                </td>
            </tr>


            <tr></tr>

            <!-- Síntese e Observações -->
            <tr>
                <td style="padding: 4px;" colspan="25"><b>SÍNTESE DO SISTEMA DE AVALIAÇÃO:</b> Será aprovado quando
                    obtiver
                    média igual ou superior a
                    6,0(seis), nos Exames de Conclusão de Etapas do Ensino Fundamental e do Ensino Médio.</td>
            </tr>
            <tr>
                <td colspan="25" style="padding: 2px;">
                    <p><strong>OBSERVAÇÕES:</strong></p>
                    <p>
                        <?php echo $dadosAdicionais['observacoes'] ?? '' ?>
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="25"
                    style="padding: 2px; text-align: center; font-weight: bold; background-color:rgb(212, 212, 212);">
                    Certificação</td>
            </tr>
            <tr>
                <td colspan="25" style="padding: 2px;">
                    <p>
                        O Diretor da Instituição de ensino Escolar SESTED (SISTEMA DE ENSINO SUPERIOR TECNOLÓGICO E
                        EDUCACIONAL), CERTIFICA, nos termos do Inciso VII, Artigo 24 da Lei Federal 9394/96, que o(a)
                        referido(a) aluno(a) concluiu o Ensino Fundamental, no ano de 2026.
                    </p>
                </td>
            </tr>



            <!-- Data e Assinaturas -->
            <tr>
                <td colspan="4" style="text-align: left; padding: 5px; width: 30% !important;">
                    <div class="historico-carimbo">
                        <div class="titulo">SESTED</div>
                        <div class="linha">Autorização de Funcionamento</div>
                        <div class="linha">Parecer CEB/CEE/RO n&ordm; 003/24</div>
                        <div class="linha">Resolução n&ordm; 011/23 - CEE/RO</div>
                        <div class="linha">CNPJ 07.158.229/0001-06</div>
                        <div class="linha">BURITIS - RO</div>
                    </div>
                    <div class="historico-data-texto">
                        <?php
                        $municipioData = trim((string) ($dadosAdicionais['municipio'] ?? ''));
                        $dataHistoricoTxt = (string) ($dadosAdicionais['data_historico_extenso'] ?? ($dadosAdicionais['data_historico'] ?? ''));
                        echo ($municipioData !== '' ? ($municipioData . ' - ') : '') . $dataHistoricoTxt;
                        ?>
                    </div>
                </td>
                <td colspan="22">
                    <table style="width: 100%; text-align: center; border: none; margin: auto;">
                        <tr>
                            <td style="border: none; width: 50%; text-align: center; padding: 10px; padding-top: 32px;">
                                ______________________________<br>
                                Laura Maria Jonjob de Souza<br>
                                RG: 757423 SESDEC/RO<br>
                                <strong>Diretora</strong>
                            </td>
                            <td style="border: none; width: 50%; text-align: center; padding-top: 32px;">
                                ______________________________<br>
                                Daniely Jonjob da Silva<br>
                                RG: 1480635 SESDEC/RO<br>
                                <strong>Secretária</strong>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

        </table>
    </div>
    <script>
        function imprimirHistorico() {
            window.print();
        }

        window.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get("download") === "1" || urlParams.get("print") === "1") {
                setTimeout(() => {
                    imprimirHistorico();
                }, 250);
            }
        });
    </script>

</body>

</html>
