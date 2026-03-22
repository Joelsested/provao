<?php
require_once __DIR__ . '/config/csrf.php';
csrf_start();
csrf_require(true);

if (empty($_SESSION['nivel']) || $_SESSION['nivel'] === 'Aluno') {
    http_response_code(401);
    echo 'Nao autorizado.';
    exit();
}


function formatNota($valor)
{
    $temAsterisco = false;

    if (is_string($valor) && str_starts_with($valor, '*')) {
        $temAsterisco = true;
        $valor = substr($valor, 1); // remove o *
    }

    $valor = $valor === '' ? 0 : (float) $valor;
    $notaFormatada = number_format($valor, 1, ',', '');

    return $temAsterisco ? '*' . $notaFormatada : $notaFormatada;
}

// Arquivo: processar_historico.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['acao']) && $input['acao'] === 'gerar_pdf') {
        try {
            // Função para formatar data
            function formatarData($data)
            {
                return date('d/m/Y', strtotime($data));
            }

            // Função para formatar sexo
            function formatarSexo($sexo)
            {
                return $sexo == 'Masculino' ? 'F ( ) M ( X )' : 'F ( X ) M ( )';
            }

            // Dados do aluno
            $dadosAluno = $input['dadosAluno'];
            $notas = $input['notas'];
            $dadosAdicionais = $input['dadosAdicionais'];

            // Data atual para o documento
            $dataAtual = date('d') . ' de ' .
                array(
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
                )[date('n')] .
                ' de ' . date('Y');

            // Template HTML completo com CSS otimizado para A4
            $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Escolar - ' . htmlspecialchars($dadosAluno['nome']) . '</title>
    <style>
        /* Reset e configurações básicas */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Configurações para impressão A4 */
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        @media print {
            body { 
                margin: 0; 
                padding: 0;
                width: 210mm;
                height: 297mm;
                overflow: hidden;
            }
            .no-print { 
                display: none !important; 
            }
            .document-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
            }
            .page-break {
                page-break-before: always;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            background-color: white;
            line-height: 1.1;
            width: 210mm;
            margin: 0 auto;
        }

        .document-container {
            width: 210mm;
            min-height: 297mm;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .logo-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
            margin-bottom: 8px;
        }

        .logo-header img {
            width: 45px;
            height: 45px;
            flex-shrink: 0;
        }

        .logo-header span {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 6px;
            table-layout: fixed;
        }

        td, th {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: top;
            text-align: left;
            font-size: 9px;
            line-height: 1.1;
            word-wrap: break-word;
            overflow: hidden;
        }

        .header-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .small-text {
            font-size: 8px;
        }

        .rotated-text {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            text-align: center;
            width: 20px;
            font-weight: bold;
            background-color: #f9f9f9;
            font-size: 8px;
        }

        .subject-area {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            writing-mode: vertical-lr;
            text-orientation: mixed;
            font-size: 8px;
            padding: 1px;
        }

        .grades-section td {
            text-align: center;
            font-weight: bold;
        }

        .signature-section {
            text-align: center;
            padding-top: 20px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 150px;
            display: inline-block;
            margin: 0 15px;
        }

        .signature-container {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .signature-box {
            text-align: center;
            flex: 1;
            min-width: 120px;
        }

        .signature-box p {
            margin: 3px 0;
            font-size: 9px;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }

        .footer-logo {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }

        .footer-logo img {
            width: 30px;
            height: 30px;
        }

        .footer-logo p {
            font-size: 8px;
            margin-top: 3px;
        }

        /* Ajustes específicos para tabelas */
        .info-table td {
            padding: 1px 2px;
        }

        .historico-title {
            font-size: 12px;
            padding: 8px;
            background-color: #f0f0f0;
        }

        .grade-cell {
            width: 25px;
            text-align: center;
        }

        .ch-cell {
            width: 35px;
            text-align: center;
        }

        .disciplina-cell {
            padding-left: 5px;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Imprimir</button>
    
    <div class="document-container">
        <!-- Cabeçalho com logo -->
        <div class="logo-header">
            <img src="https://sested-eja.com/img/logo.jpg" alt="Logo SESTED" />
            <span>SESTED - Sistema de Ensino Superior Tecnológico e Educacional</span>
        </div>

        <table class="info-table">
            <!-- Dados da Instituição -->
            <tr>
                <td colspan="6">
                    <span>NOME:</span>
                    <span class="bold">SESTED</span>
                </td>
                <td colspan="18">
                    <span>AUTORIZAÇÃO:</span>
                    <span class="bold">PARECER CEB/CEE/RO Nº 041/18 e RESOLUÇÃO CEB/CEE/RO N° 1296/21</span>
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    <span>MUNICÍPIO:</span>
                    <span class="bold">' . htmlspecialchars($dadosAdicionais['municipio']) . '</span>
                </td>
                <td colspan="12">
                    <span>CNPJ:</span>
                    <span class="bold">07.158.229/0001-06</span>
                </td>
            </tr>
            <tr>
                <td colspan="13">
                    <span>ENDEREÇO:</span>
                    <span class="bold">RUA NOVA UNIÃO - 2024 SETOR 02</span>
                </td>
                <td colspan="11">
                    <span>TEL.: </span>
                    <span class="bold">(99) 99999-9999</span>
                </td>
            </tr>

            <!-- Dados do Aluno -->
            <tr>
                <td colspan="17">
                    <span>ALUNO(a):</span>
                    <span class="bold">' . htmlspecialchars($dadosAluno['nome']) . '</span>
                </td>
                <td colspan="7">
                    <span>SEXO: </span>
                    <span class="bold">' . formatarSexo($dadosAluno['sexo']) . '</span>
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    <span>DATA DE NASC:</span>
                    <span class="bold">' . formatarData($dadosAluno['dataNasc']) . '</span>
                </td>
                <td colspan="12">
                    <span>NATURALIDADE:</span>
                    <span class="bold">' . htmlspecialchars($dadosAluno['naturalidade']) . '</span>
                </td>
            </tr>
            <tr>
                <td colspan="24">
                    <span>NACIONALIDADE:</span>
                    <span class="bold">BRASILEIRO</span>
                </td>
            </tr>
            <tr>
                <td colspan="24">
                    <span>CERTIDÃO DE NASCIMENTO:</span>
                    <span class="bold">***************************</span>
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    <span>CPF:</span>
                    <span class="bold">' . htmlspecialchars($dadosAluno['cpf']) . '</span>
                </td>
                <td colspan="5">
                    <span>RG:</span>
                    <span class="bold">' . htmlspecialchars($dadosAluno['rg']) . '</span>
                </td>
                <td colspan="7">
                    <span>ÓRGÃO EXP:</span>
                    <span class="bold">SESDEC/RO</span>
                </td>
                <td colspan="6">
                    <span>EMISSÃO:</span>
                    <span class="bold">' . date('d/m/Y') . '</span>
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    <span>PAI:</span>
                    <span class="bold">' . htmlspecialchars($dadosAluno['pai']) . '</span>
                </td>
                <td colspan="12">
                    <span>MÃE:</span>
                    <span class="bold">' . htmlspecialchars($dadosAluno['mae']) . '</span>
                </td>
            </tr>

            <!-- Título do Histórico -->
            <tr>
                <td colspan="24" class="bold center historico-title">
                    HISTÓRICO ESCOLAR DO ENSINO MÉDIO
                </td>
            </tr>

            <!-- Cabeçalho da tabela de notas -->
            <tr class="header-row">
                <td rowspan="3" class="rotated-text">Base Nacional</td>
                <td colspan="3" class="center">ÁREAS DE CONHECIMENTO</td>
                <td colspan="7" class="center">COMPONENTES CURRICULARES</td>
                <td colspan="13" class="center">ANOS/ CARGA HORÁRIA</td>
            </tr>
            <tr class="header-row">
                <td colspan="10"></td>
                <td colspan="4" class="center">1ª SÉRIE</td>
                <td colspan="4" class="center">2ª SÉRIE</td>
                <td colspan="5" class="center">3ª SÉRIE</td>
            </tr>
            <tr class="header-row">
                <td colspan="3" class="center">ÁREA</td>
                <td colspan="7" class="center">DISCIPLINA</td>
                <td colspan="2" class="center small-text">NOTA</td>
                <td colspan="2" class="center small-text">CH / DATA</td>
                <td colspan="2" class="center small-text">NOTA</td>
                <td colspan="2" class="center small-text">CH / DATA</td>
                <td colspan="2" class="center small-text">NOTA</td>
                <td colspan="3" class="center small-text">CH / DATA</td>
            </tr>';

            // Linguagens e Tecnologias
            $linguagens = ['Língua portuguesa', 'Arte', 'Língua inglesa', 'Língua espanhola', 'Educação física'];
            $rowspan = count($linguagens);

            for ($i = 0; $i < count($linguagens); $i++) {
                $materia = $linguagens[$i];
                $notasSerie = $notas[$materia] ?? ['serie1' => '0', 'serie2' => '0', 'serie3' => '0'];

                $html .= '<tr>';

                if ($i == 0) {
                    $html .= '<td rowspan="' . $rowspan . '" class="subject-area">LINGUAGENS E TECNOLOGIAS</td>';
                }

                $html .= '
        <td colspan="3" class="center">' . ($i + 1) . '</td>
        <td colspan="7" class="disciplina-cell">' . htmlspecialchars($materia) . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie1']) . '</td>
        <td colspan="2" class="center ch-cell">' . $notasSerie['data'] . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie2']) . '</td>
      <td colspan="2" class="center ch-cell">' . $notasSerie['data'] . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie3']) . '</td>
       <td colspan="3" class="center ch-cell">' . $notasSerie['data'] . '</td>
    </tr>';
            }

            // Matemática
            $notasMatematica = $notas['Matemática'] ?? ['serie1' => '0', 'serie2' => '0', 'serie3' => '0'];
            $html .= '<tr>
    <td class="subject-area">MATEMÁTICA</td>
    <td colspan="3" class="center">6</td>
    <td colspan="7" class="disciplina-cell">Matemática</td>
    <td colspan="2" class="center bold grade-cell">' . formatNota($notasMatematica['serie1']) . '</td>
    <td colspan="2" class="center ch-cell">' . $notasMatematica['data'] . '</td>
    <td colspan="2" class="center bold grade-cell">' . formatNota($notasMatematica['serie2']) . '</td>
    <td colspan="2" class="center ch-cell">' . $notasMatematica['data'] . '</td>
    <td colspan="2" class="center bold grade-cell">' . formatNota($notasMatematica['serie3']) . '</td>
    <td colspan="3" class="center ch-cell">' . $notasMatematica['data'] . '</td>
</tr>';

            // Ciências da Natureza
            $ciencias = ['Química', 'Física', 'Biologia'];
            $rowspan = count($ciencias);

            for ($i = 0; $i < count($ciencias); $i++) {
                $materia = $ciencias[$i];
                $notasSerie = $notas[$materia] ?? ['serie1' => '0', 'serie2' => '0', 'serie3' => '0'];

                $html .= '<tr>';

                if ($i == 0) {
                    $html .= '<td rowspan="' . $rowspan . '" class="subject-area">CIÊNCIAS DA NATUREZA</td>';
                }

                $html .= '
        <td colspan="3" class="center">' . (7 + $i) . '</td>
        <td colspan="7" class="disciplina-cell">' . htmlspecialchars($materia) . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie1']) . '</td>
        <td colspan="2" class="center ch-cell">' . $notasSerie['data'] . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie2']) . '</td>
        <td colspan="2" class="center ch-cell">' . $notasSerie['data'] . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie3']) . '</td>
        <td colspan="3" class="center ch-cell">' . $notasSerie['data'] . '</td>
    </tr>';
            }

            // Ciências Humanas
            $humanas = ['História', 'Geografia', 'Sociologia', 'Filosofia'];
            $rowspan = count($humanas);

            for ($i = 0; $i < count($humanas); $i++) {
                $materia = $humanas[$i];
                $notasSerie = $notas[$materia] ?? ['serie1' => '0', 'serie2' => '0', 'serie3' => '0'];

                $html .= '<tr>';

                if ($i == 0) {
                    $html .= '<td rowspan="' . $rowspan . '" class="subject-area">CIÊNCIAS HUMANAS</td>';
                }

                $html .= '
        <td colspan="3" class="center">' . (10 + $i) . '</td>
        <td colspan="7" class="disciplina-cell">' . htmlspecialchars($materia) . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie1']) . '</td>
        <td colspan="2" class="center ch-cell">' . $notasSerie['data'] . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie2']) . '</td>
        <td colspan="2" class="center ch-cell">' . $notasSerie['data'] . '</td>
        <td colspan="2" class="center bold grade-cell">' . formatNota($notasSerie['serie3']) . '</td>
        <td colspan="3" class="center ch-cell">' . $notasSerie['data'] . '</td>
    </tr>';
            }

            // Totalizadores
            $html .= '
            <tr class="header-row">
                <td colspan="11" class="bold center">TOTAIS</td>
                <td colspan="2" class="center bold">-</td>
                <td colspan="2" class="center bold">800h</td>
                <td colspan="2" class="center bold">-</td>
                <td colspan="2" class="center bold">800h</td>
                <td colspan="2" class="center bold">-</td>
                <td colspan="2" class="center bold">800h</td>
            </tr>
            <tr>
                <td colspan="11" class="bold">Dias Letivos</td>
                <td colspan="2" class="center">-</td>
                <td colspan="2" class="center">200</td>
                <td colspan="2" class="center">-</td>
                <td colspan="2" class="center">200</td>
                <td colspan="2" class="center">-</td>
                <td colspan="2" class="center">200</td>
            </tr>
            <tr>
                <td colspan="11" class="bold">Carga Horária Total do Curso</td>
                <td colspan="13" class="center bold">' . htmlspecialchars($dadosAdicionais['cargaHoraria']) . '</td>
            </tr>
            <tr>
                <td colspan="11" class="bold">RESULTADO FINAL</td>
                <td colspan="13" class="center bold">' . htmlspecialchars($dadosAdicionais['situacao']) . '</td>
            </tr>
        </table>

        <!-- Estudos Realizados -->
        <table>
            <tr class="header-row">
                <td colspan="24" class="center bold">ESTUDOS REALIZADOS</td>
            </tr>
            <tr class="header-row">
                <td colspan="3" class="center">ANO ESCOLAR</td>
                <td colspan="3" class="center">ANO</td>
                <td colspan="10" class="center">INSTITUIÇÃO DE ENSINO</td>
                <td colspan="6" class="center">MUNICÍPIO</td>
                <td colspan="2" class="center">UF</td>
            </tr>
            <tr>
                <td colspan="3" class="center">1ª SÉRIE</td>
                <td colspan="3" class="center">' . htmlspecialchars($dadosAdicionais['anoConclusao']) . '</td>
                <td colspan="10" class="center">' . htmlspecialchars($dadosAdicionais['escola']) . '</td>
                <td colspan="6" class="center">' . explode(' - ', $dadosAdicionais['municipio'])[0] . '</td>
                <td colspan="2" class="center">' . (explode(' - ', $dadosAdicionais['municipio'])[1] ?? 'RO') . '</td>
            </tr>
            <tr>
                <td colspan="3" class="center">2ª SÉRIE</td>
                <td colspan="3" class="center">' . htmlspecialchars($dadosAdicionais['anoConclusao']) . '</td>
                <td colspan="10" class="center">' . htmlspecialchars($dadosAdicionais['escola']) . '</td>
                <td colspan="6" class="center">' . explode(' - ', $dadosAdicionais['municipio'])[0] . '</td>
                <td colspan="2" class="center">' . (explode(' - ', $dadosAdicionais['municipio'])[1] ?? 'RO') . '</td>
            </tr>
            <tr>
                <td colspan="3" class="center">3ª SÉRIE</td>
                <td colspan="3" class="center">' . htmlspecialchars($dadosAdicionais['anoConclusao']) . '</td>
                <td colspan="10" class="center">' . htmlspecialchars($dadosAdicionais['escola']) . '</td>
                <td colspan="6" class="center">' . explode(' - ', $dadosAdicionais['municipio'])[0] . '</td>
                <td colspan="2" class="center">' . (explode(' - ', $dadosAdicionais['municipio'])[1] ?? 'RO') . '</td>
            </tr>
        </table>

        <!-- Observações -->
        <table>
            <tr>
                <td colspan="24" style="padding: 4px;">
                    <strong>SÍNTESE DO SISTEMA DE AVALIAÇÃO:</strong> Será aprovado quando obtiver média igual ou superior a 6,0 (seis), nos Exames de Conclusão de Etapas do Ensino Fundamental e do Ensino Médio.
                </td>
            </tr>
            <tr>
                <td colspan="24" style="padding: 4px;">
                    <strong>OBSERVAÇÕES:</strong><br>
                   ' . htmlspecialchars($dadosAdicionais['observacoes']) . '
                </td>
            </tr>
        </table>

        <!-- Data e Local -->
        <div style="margin: 15px 0; text-align: center; font-size: 10px;">
            <p>' . explode(' - ', $dadosAdicionais['municipio'])[0] . '/RO, ' . $dadosAdicionais['data_historico'] . '</p>
        </div>

        <!-- Assinaturas -->
        <div class="signature-section">
            <div class="signature-container">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <p><strong>Laura Maria Jonjob de Souza1</strong></p>
                    <p>RG: 757423 SESDEC/RO</p>
                    <p><strong>Diretora</strong></p>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <p><strong>Daniely Jonjob da Silva1</strong></p>
                    <p>RG: 1480635 SESDEC/RO</p>
                    <p><strong>Secretária</strong></p>
                </div>
            </div>
        </div>

        <!-- Logo rodapé -->
        <div class="footer-logo">
            <img src="https://sested-eja.com/img/logo.jpg" alt="Logo SESTED" />
            <p>SESTED - Sistema de Ensino Superior Tecnológico e Educacional</p>
        </div>
    </div>
</body>
</html>';


            // Categoria (medio ou fundamental)
            $categoria = isset($input['dadosAluno']['categoria']) ? strtolower($input['dadosAluno']['categoria']) : 'FUNDAMENTAL';

            // Criar nome do arquivo
            $nomeArquivo = 'HISTORICO_'
                . $categoria . '_'
                . preg_replace('/[^A-Za-z0-9_\-]/', '_', $input['dadosAluno']['nome'])
                . '_' . date('YmdHis') . '.html';

            // Diretório base
            $dirBase = __DIR__ . '/historicos';

            // Criar subpasta com o ID do aluno
            $idAluno = intval($input['dadosAluno']['id_aluno']); // garante que é número
            $dirAluno = $dirBase . '/' . $idAluno;

            if (!is_dir($dirAluno)) {
                mkdir($dirAluno, 0777, true);
            }

            $caminhoCompleto = $dirAluno . '/' . $nomeArquivo;

            // Salvar HTML no servidor
            file_put_contents($caminhoCompleto, $html);

            // Retornar caminho relativo para o frontend
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'arquivo_html' => 'historicos/' . $idAluno . '/' . $nomeArquivo,
                'mensagem' => 'Histórico HTML gerado com sucesso!',
                'url_visualizacao' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/historicos/' . $idAluno . '/' . $nomeArquivo
            ]);



        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao gerar HTML: ' . $e->getMessage()
            ]);
        }
    }
    exit;
}
?>
