<?php
require_once('lib/tcpdf/tcpdf.php');

// Verifica se os dados foram enviados
if (!isset($_POST['dados_aluno'])) {
    die('Dados do aluno não encontrados.');
}

$dadosAluno = json_decode($_POST['dados_aluno'], true);

// Função para formatar data
function formatarData($data)
{
    if (empty($data))
        return '';
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

// Função para determinar se foi aprovado
function determinarResultado($notas)
{
    foreach ($notas as $materia => $notasPorSerie) {
        foreach ($notasPorSerie as $serie => $nota) {
            if (floatval($nota) < 6.0) {
                return 'Reprovado(a)';
            }
        }
    }
    return 'Aprovado(a)';
}

// Criar novo PDF
class HistoricoPDF extends TCPDF
{
    public function Header()
    {
        // Não mostrar header padrão
    }

    public function Footer()
    {
        // Não mostrar footer padrão
    }
}

$pdf = new HistoricoPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Sistema Escolar SESTED');
$pdf->SetAuthor('SESTED');
$pdf->SetTitle('Histórico Escolar - ' . $dadosAluno['nome']);
$pdf->SetSubject('Histórico Escolar do Ensino Médio');

// Configurações da página
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Adicionar página
$pdf->AddPage();

// Definir fonte
$pdf->SetFont('helvetica', '', 8);

// Cabeçalho da escola
$html = '
<style>
    .header-table { border: 1px solid #000; width: 100%; }
    .header-table td { border: 1px solid #000; padding: 3px; font-size: 8px; }
    .bold { font-weight: bold; }
    .center { text-align: center; }
    .historico-table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    .historico-table th, .historico-table td { 
        border: 1px solid #000; 
        padding: 2px; 
        font-size: 7px;
        text-align: center;
    }
    .area-header { background-color: #f0f0f0; font-weight: bold; }
    .materia-name { text-align: left; padding-left: 5px; }
</style>

<table class="header-table">
    <tr>
        <td colspan="7"><strong>NOME: SESTED</strong></td>
        <td colspan="18"><strong>AUTORIZAÇÃO: PARECER CEB/CEE/RO Nº 041/18 e RESOLUÇÃO CEB/CEE/RO N° 1296/21</strong></td>
    </tr>
    <tr>
        <td colspan="4"><strong>CNPJ: 07.158.229/0001-06</strong></td>
        <td colspan="14"><strong>MUNICÍPIO: BURITIS/RO</strong></td>
        <td colspan="7"><strong>CNPJ: 07.158.229/0001-06</strong></td>
    </tr>
    <tr>
        <td colspan="13"><strong>ENDEREÇO: RUA NOVA UNIÃO – 2024 SETOR 02</strong></td>
        <td colspan="12"><strong>TEL.: 69 -9 - 92474696</strong></td>
    </tr>
    <tr>
        <td colspan="18"><strong>ALUNO (a): ' . strtoupper($dadosAluno['nome']) . '</strong></td>
        <td colspan="7">SEXO: F ( ' . ($dadosAluno['sexo'] == 'F' ? 'X' : ' ') . ' ) M ( ' . ($dadosAluno['sexo'] == 'M' ? 'X' : ' ') . ' )</td>
    </tr>
    <tr>
        <td colspan="9">DATA DE NASC: ' . formatarData($dadosAluno['dataNasc']) . '</td>
        <td colspan="8">NATURALIDADE: ' . strtoupper($dadosAluno['naturalidade']) . '</td>
        <td colspan="8"><strong>NACIONALIDADE: BRASILEIRO</strong></td>
    </tr>
    <tr>
        <td colspan="25">CERTIDÃO DE NASCIMENTO: ***************************</td>
    </tr>
    <tr>
        <td colspan="6">CPF: ' . $dadosAluno['cpf'] . '</td>
        <td colspan="5">RG: ' . $dadosAluno['rg'] . '</td>
        <td colspan="8">ORGÃO EXP: SESDEC/RO</td>
        <td colspan="6">EMISSÃO: ' . date('d/m/Y') . '</td>
    </tr>
    <tr>
        <td colspan="11">PAI: ' . strtoupper($dadosAluno['pai']) . '</td>
        <td colspan="14">MÃE: ' . strtoupper($dadosAluno['mae']) . '</td>
    </tr>
</table>

<br><br>

<div class="center bold" style="font-size: 10px; margin: 10px 0;">
    HISTÓRICO ESCOLAR DO ENSINO MÉDIO
</div>

<table class="historico-table">
    <tr>
        <th rowspan="3" style="width: 8%;">Base Nacional</th>
        <th rowspan="3" style="width: 20%;">ÁREAS DE CONHECIMENTO</th>
        <th rowspan="3" style="width: 25%;">COMPONENTES CURRICULARES</th>
        <th colspan="9">ANOS/ CARGA HORÁRIA</th>
    </tr>
    <tr>
        <th colspan="3">1ª SÉRIE</th>
        <th colspan="3">2ª SÉRIE</th>
        <th colspan="3">3ª SÉRIE</th>
    </tr>
    <tr>
        <th>NOTA</th>
        <th>CH</th>
        <th>DATA</th>
        <th>NOTA</th>
        <th>CH</th>
        <th>DATA</th>
        <th>NOTA</th>
        <th>CH</th>
        <th>DATA</th>
    </tr>';

// Matérias por área
$areas = [
    [
        'nome' => 'LINGUAGENS e TECNOLOGIAS',
        'materias' => [
            'Língua portuguesa',
            'Arte',
            'Língua inglesa',
            'Língua espanhola',
            'Educação física'
        ]
    ],
    ['nome' => 'Matemática', 'materias' => ['Matemática']],
    ['nome' => 'CIÊNCIAS DA NATUREZA', 'materias' => ['Química', 'Física', 'Biologia']],
    ['nome' => 'CIÊNCIAS HUMANAS', 'materias' => ['História', 'Geografia']],
    [
        'nome' => 'Parte diversificada',
        'materias' => [
            'Sociologia',
            'Filosofia',
            'História do Estado de Rondônia',
            'Geografia do Estado de Rondônia'
        ]
    ]
];

$dataAtual = date('d/m/Y');

foreach ($areas as $area) {
    $primeiraLinha = true;
    $rowspan = count($area['materias']);

    foreach ($area['materias'] as $materia) {
        $html .= '<tr>';

        if ($primeiraLinha) {
            $html .= '<td rowspan="' . $rowspan . '" class="area-header">' . $area['nome'] . '</td>';
            $primeiraLinha = false;
        }

        // Se é a primeira matéria da área, mostra o nome da área
        if ($area['nome'] == 'LINGUAGENS e TECNOLOGIAS' || $area['nome'] == 'CIÊNCIAS DA NATUREZA' || $area['nome'] == 'CIÊNCIAS HUMANAS') {
            if ($materia == $area['materias'][0]) {
                $html .= '<td rowspan="' . $rowspan . '" class="area-header">' . $area['nome'] . '</td>';
            }
        } else {
            $html .= '<td class="area-header">' . $area['nome'] . '</td>';
        }

        $html .= '<td class="materia-name">' . $materia . '</td>';

        // Notas das 3 séries
        for ($serie = 1; $serie <= 3; $serie++) {
            $nota = isset($dadosAluno['notas'][$materia][$serie]) ?
                number_format(floatval($dadosAluno['notas'][$materia][$serie]), 1) : '0,0';
            $html .= '<td><strong>' . $nota . '</strong></td>';
            $html .= '<td>-</td>'; // Carga horária
            $html .= '<td><strong>' . $dataAtual . '</strong></td>';
        }

        $html .= '</tr>';
    }
}

// Linhas de totais
$html .= '
    <tr>
        <td colspan="3"><strong>Dias Letivos</strong></td>
        <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
    </tr>
    <tr>
        <td colspan="3"><strong>Carga Horária Anual</strong></td>
        <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
    </tr>
    <tr>
        <td colspan="3"><strong>Carga Horária Total</strong></td>
        <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
    </tr>
    <tr>
        <td colspan="3"><strong>RESULTADO FINAL</strong></td>
        <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td colspan="3"><strong>' . determinarResultado($dadosAluno['notas']) . '</strong></td>
    </tr>
</table>

<br><br>

<table class="header-table">
    <tr>
        <th style="width: 10%;">Estudos Realizados</th>
        <th style="width: 15%;">ANO ESCOLAR</th>
        <th style="width: 10%;">ANO</th>
        <th style="width: 25%;">INSTITUIÇÃO DE ENSINO</th>
        <th style="width: 25%;">MUNICÍPIO</th>
        <th style="width: 15%;">UF</th>
    </tr>
    <tr>
        <td rowspan="3">Estudos Realizados</td>
        <td>1ª SÉRIE</td>
        <td>' . date('Y') . '</td>
        <td>SESTED</td>
        <td>BURITIS</td>
        <td>RO</td>
    </tr>
    <tr>
        <td>2ª SÉRIE</td>
        <td>' . date('Y') . '</td>
        <td>SESTED</td>
        <td>BURITIS</td>
        <td>RO</td>
    </tr>
    <tr>
        <td>3ª SÉRIE</td>
        <td>' . date('Y') . '</td>
        <td>SESTED</td>
        <td>BURITIS</td>
        <td>RO</td>
    </tr>
</table>

<br>

<div style="font-size: 8px;">
    <strong>SÍNTESE DO SISTEMA DE AVALIAÇÃO:</strong> Será aprovado quando obtiver média igual ou superior a 6,0(seis), nos Exames de Conclusão de Etapas do Ensino Fundamental e do Ensino Médio.
</div>

<br>

<div style="font-size: 8px;">
    <strong>OBSERVAÇÕES:</strong><br>
    O (a) Aluno (a) acima está com toda documentação arquivada.
</div>

<br><br>

<table style="width: 100%; border: none;">
    <tr>
        <td style="width: 40%; border: none; font-size: 8px;">
            Buritis/RO, ' . date('d') . ' de ' .
    [
        '',
        'janeiro',
        'fevereiro',
        'março',
        'abril',
        'maio',
        'junho',
        'julho',
        'agosto',
        'setembro',
        'outubro',
        'novembro',
        'dezembro'
    ][date('n')] .
    ' de ' . date('Y') . '.
        </td>
        <td style="width: 60%; border: none; text-align: center; font-size: 8px;">
            ______________________________ ______________________________<br>
            Laura Maria Jonjob de Souza &nbsp;&nbsp;&nbsp;&nbsp; Daniely Jonjob da Silva<br>
            RG: 757423 SESDEC/RO &nbsp;&nbsp;&nbsp;&nbsp; RG: 1480635 SESDEC/RO<br>
            <strong>Diretora &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Secretária</strong>
        </td>
    </tr>
</table>';

// Escrever o HTML no PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Saída do PDF
$nomeArquivo = 'Historico_' . preg_replace('/[^A-Za-z0-9]/', '_', $dadosAluno['nome']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($nomeArquivo, 'I');
?>