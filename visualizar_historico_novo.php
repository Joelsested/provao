<?php
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil');

if (!function_exists('h')) {
    function h($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('normalizar_data_iso')) {
    function normalizar_data_iso($valor): string
    {
        $data = trim((string) $valor);
        $data = ltrim($data, '*');
        if ($data === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $data;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
            [$dia, $mes, $ano] = explode('/', $data);
            return "{$ano}-{$mes}-{$dia}";
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $data)) {
            [$dia, $mes, $ano] = explode('-', $data);
            return "{$ano}-{$mes}-{$dia}";
        }

        if (preg_match('/^\d{8}$/', $data)) {
            $dia = substr($data, 0, 2);
            $mes = substr($data, 2, 2);
            $ano = substr($data, 4, 4);
            return "{$ano}-{$mes}-{$dia}";
        }

        return '';
    }
}

if (!function_exists('formatar_data_br')) {
    function formatar_data_br($valor, string $fallback = ''): string
    {
        $iso = normalizar_data_iso($valor);
        if ($iso === '') {
            return $fallback;
        }
        return substr($iso, 8, 2) . '/' . substr($iso, 5, 2) . '/' . substr($iso, 0, 4);
    }
}

if (!function_exists('formatar_data_extenso')) {
    function formatar_data_extenso($valor): string
    {
        $iso = normalizar_data_iso($valor);
        if ($iso === '') {
            return '';
        }
        $meses = [
            1 => 'janeiro',
            2 => 'fevereiro',
            3 => 'março',
            4 => 'abril',
            5 => 'maio',
            6 => 'junho',
            7 => 'julho',
            8 => 'agosto',
            9 => 'setembro',
            10 => 'outubro',
            11 => 'novembro',
            12 => 'dezembro',
        ];
        $dia = (int) substr($iso, 8, 2);
        $mes = (int) substr($iso, 5, 2);
        $ano = (int) substr($iso, 0, 4);
        return $dia . ' de ' . ($meses[$mes] ?? '') . ' de ' . $ano;
    }
}

if (!function_exists('extrair_nota_materia')) {
    function extrair_nota_materia(array $item): string
    {
        $ordem = ['serie1', 'serie2', 'serie3', 'nota'];
        foreach ($ordem as $campo) {
            if (!array_key_exists($campo, $item)) {
                continue;
            }
            $valor = ltrim(trim((string) $item[$campo]), '*');
            if ($valor === '' || $valor === '-') {
                continue;
            }

            $numero = str_replace(',', '.', $valor);
            if (is_numeric($numero)) {
                return number_format((float) $numero, 1, ',', '');
            }
            return $valor;
        }
        return '';
    }
}

if (!function_exists('chave_materia_canonica')) {
    function chave_materia_canonica($valor): string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '';
        }

        $texto = mb_strtolower($texto, 'UTF-8');
        if (class_exists('Normalizer')) {
            $texto = Normalizer::normalize($texto, Normalizer::FORM_D);
            $texto = preg_replace('/\pM/u', '', $texto);
        } else {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($ascii !== false) {
                $texto = $ascii;
            }
        }

        $texto = preg_replace('/[^a-z0-9]+/', '', (string) $texto);
        return $texto;
    }
}

$dadosAluno = is_array($dadosAluno ?? null) ? $dadosAluno : [];
$dadosAdicionais = is_array($dadosAdicionais ?? null) ? $dadosAdicionais : [];
$notas = is_array($notas ?? null) ? $notas : [];
$notasCanonicas = [];
foreach ($notas as $chaveMateria => $dadosMateriaTmp) {
    $canon = chave_materia_canonica($chaveMateria);
    if ($canon !== '' && is_array($dadosMateriaTmp)) {
        $notasCanonicas[$canon] = $dadosMateriaTmp;
    }
}

$nome = trim((string) ($dadosAluno['nome'] ?? ''));
$pai = trim((string) ($dadosAluno['pai'] ?? ''));
$mae = trim((string) ($dadosAluno['mae'] ?? ''));
$dataNascimento = formatar_data_br($dadosAluno['dataNasc'] ?? ($dadosAluno['nascimento'] ?? ''), '00/00/0000');
$naturalidade = trim((string) ($dadosAluno['naturalidade'] ?? ''));
$rg = trim((string) ($dadosAluno['rg'] ?? ''));
$documentoIdentificacao = trim((string) ($dadosAluno['documento_identificacao'] ?? ''));
if ($documentoIdentificacao === '') {
    $documentoIdentificacao = $rg;
}
$orgaoEmissor = trim((string) ($dadosAluno['orgao_expedidor'] ?? ($dadosAluno['orgao_emissor'] ?? '')));
$dataExpedicao = formatar_data_br($dadosAluno['expedicao'] ?? '', '00/00/0000');
$cpf = trim((string) ($dadosAluno['cpf'] ?? ''));
$anoConclusao = trim((string) ($dadosAluno['anoConclusao'] ?? ($dadosAluno['ano_conclusao'] ?? ($dadosAdicionais['anoConclusao'] ?? ''))));
if ($anoConclusao === '') {
    $anoConclusao = substr((string) date('Y'), 0, 4);
}

$filiacao = '';
if ($pai !== '' && $mae !== '') {
    $filiacao = $pai . ' / ' . $mae;
} elseif ($pai !== '') {
    $filiacao = $pai;
} elseif ($mae !== '') {
    $filiacao = $mae;
} else {
    $filiacao = 'NAO INFORMADO';
}

$municipioData = trim((string) ($dadosAdicionais['municipio'] ?? 'Buritis'));
$dataHistorico = trim((string) ($dadosAdicionais['data_historico_iso'] ?? ''));
if ($dataHistorico === '') {
    $dataHistorico = trim((string) ($dadosAdicionais['data_historico'] ?? ''));
}
$dataHistoricoExtenso = formatar_data_extenso($dataHistorico);
if ($dataHistoricoExtenso === '') {
    $dataHistoricoExtenso = trim((string) ($dadosAdicionais['data_historico_extenso'] ?? ''));
}

$observacoes = trim((string) ($dadosAdicionais['observacoes'] ?? ''));
$observacoesFixasMedio = '• Conclusão do Ensino Médio mediante Exames de Conclusão da EJA, conforme Art. 38 da Lei Federal nº 9.394/96. • A carga horária registrada representa equivalência legal ao ensino regular, NÃO cursada, conforme estabelecido pela legislação vigente. • Frequência: "Dispensa" (sem exigência), conforme Resolução CNE/CEB nº 3/2025. • Critério de aprovação: Nota mínima 5,0 (cinco) em escala de 0 a 10, ou 50% de acertos nas avaliações. • Componentes curriculares de História e Geografia incluem, respectivamente, História de Rondônia e Cultura Afro-Brasileira, e Geografia de Rondônia. • Este certificado habilita o portador ao prosseguimento de estudos em nível superior, conforme Art. 44, II da Lei nº 9.394/96.';
$observacoes = $observacoesFixasMedio;
$aproveitamentoEstudosAnteriores = trim((string) ($dadosAdicionais['aproveitamento_estudos_anteriores'] ?? ''));
$situacao = strtoupper(trim((string) ($dadosAdicionais['situacao'] ?? 'APROVADO')));
if ($situacao === '') {
    $situacao = 'APROVADO';
}

$cargaHorariaTexto = '1200h';

$marcaDagua = strtolower(trim((string) ($dadosAdicionais['marca_dagua'] ?? 'sim'))) !== 'nao';
$classeMarcaDagua = $marcaDagua ? '' : ' sem-marca-dagua';
$caminhoFundoHistorico = __DIR__ . '/sistema/img/historico_medio_fundo.jpg';
$urlFundoHistorico = '';
if (is_file($caminhoFundoHistorico)) {
    $conteudoFundo = @file_get_contents($caminhoFundoHistorico);
    if ($conteudoFundo !== false && $conteudoFundo !== '') {
        $urlFundoHistorico = 'data:image/jpeg;base64,' . base64_encode($conteudoFundo);
    }
}
if ($urlFundoHistorico === '') {
    $baseUrlSistema = rtrim((string) ($url_sistema ?? ''), '/');
    if ($baseUrlSistema === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $baseUrlSistema = $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
    }
    $urlFundoHistorico = $baseUrlSistema . '/sistema/img/historico_medio_fundo.jpg';
}

$componentes = [
    [
        'area' => 'LINGUAGENS E SUAS TECNOLOGIAS',
        'itens' => [
            ['key' => 'lingua_portuguesa', 'nome' => 'Lingua Portuguesa / Literatura', 'ch' => '120'],
            ['key' => 'lingua_inglesa', 'nome' => 'Lingua Inglesa', 'ch' => '80'],
            ['key' => 'arte', 'nome' => 'Arte', 'ch' => '80'],
            ['key' => 'educacao_fisica', 'nome' => 'Educacao Fisica', 'ch' => '80'],
        ],
    ],
    [
        'area' => 'MATEMATICA E SUAS TECNOLOGIAS',
        'itens' => [
            ['key' => 'matematica', 'nome' => 'Matematica', 'ch' => '120'],
        ],
    ],
    [
        'area' => 'NATUREZA E SUAS TECNOLOGIAS',
        'itens' => [
            ['key' => 'biologia', 'nome' => 'Biologia', 'ch' => '100'],
            ['key' => 'fisica', 'nome' => 'Fisica', 'ch' => '100'],
            ['key' => 'quimica', 'nome' => 'Quimica', 'ch' => '100'],
        ],
    ],
    [
        'area' => 'CIENCIAS HUMANAS E SOCIAIS APLICADAS',
        'itens' => [
            ['key' => 'historia', 'nome' => 'Historia', 'ch' => '100'],
            ['key' => 'geografia', 'nome' => 'Geografia', 'ch' => '100'],
            ['key' => 'filosofia', 'nome' => 'Filosofia', 'ch' => '80'],
            ['key' => 'sociologia', 'nome' => 'Sociologia', 'ch' => '140'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historico Escolar - SESTED</title>
  <style>
    @page { size: A4; margin: 5mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Times New Roman", serif; background: #f2f2f2; }
    .print-toolbar { position: fixed; top: 8px; right: 8px; z-index: 9999; }
    .print-btn {
      border: 1px solid #1f3b57; background: #1f3b57; color: #fff;
      padding: 8px 12px; border-radius: 4px; font-size: 13px; cursor: pointer;
    }
    .pagina {
      width: 210mm; min-height: 297mm; margin: 8px auto; background: #fff;
      border: 1px solid #cfcfcf; padding: 10mm; position: relative; overflow: hidden;
    }
    .fundo-historico {
      position: absolute;
      left: 50%;
      top: 73%;
      transform: translate(-50%, -50%);
      width: 88%;
      opacity: 0.35;
      pointer-events: none;
      z-index: 0;
    }
    .conteudo { position: relative; z-index: 1; }

    .cabecalho-topo { text-align: center; }
    .cabecalho-topo img { width: 140px; height: 100px; margin-bottom: 3px; }
    .cabecalho-topo .titulo {
      font-weight: 700;
      font-size: 14px;
      letter-spacing: 0.2px;
      line-height: 1.05;
      margin-bottom: 4px;
    }
    .cabecalho-topo .linha { font-size: 14px; margin-top: 1px; }
    .titulo-doc { text-align: center; margin: 8px 0 6px; }
    .titulo-doc .t1 { font-size: 1px; font-weight: 600; }
    .titulo-doc .t2 { font-size: 16px; font-weight: 700; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }
    .dados td, .desempenho td, .desempenho th {
      border: 1px solid #000; padding: 2px 4px; font-size: 12px; line-height: 1.1;
    }
    .dados .label { font-weight: 700; white-space: nowrap; }
    .dados .valor { text-transform: uppercase; }

    .bloco-titulo {
      text-align: center; font-weight: 700; font-size: 12px; margin: 12px 0 4px;
    }
    .desempenho th {
      font-weight: 700; background: #e9e9e9; text-align: center;
      font-size: 11px;
    }
    .area {
      text-align: center; font-weight: 600; font-size: 10px; width: 10%;
      vertical-align: middle; line-height: 1.05;
    }
    .comp { width: 33%; }
    .nota, .ch, .data, .freq, .res { text-align: center; }
    .nota { width: 10%; }
    .ch { width: 10%; }
    .data { width: 10%; }
    .freq { width: 10%; color: #000; text-decoration: none; }
    .res { width: 10%; }
    .totais td { font-weight: 700; }
    .totais .valor { text-align: center; }
    .obs-caixa {
      text-align: left;
      vertical-align: top;
      padding: 6px 8px !important;
      min-height: 42px;
      line-height: 1.2;
      font-weight: 400 !important;
    }
    .obs-caixa strong {
      display: block;
      margin-bottom: 3px;
      font-weight: 700;
    }

    .local-data {
      text-align: right;
      margin-top: 10px;
      font-size: 14px;
      padding-right: 14mm;
    }

    .assinaturas {
      width: 100%; margin-top: 105px; border-collapse: collapse;
    }
    .assinaturas td {
      width: 50%; text-align: center; border: none; font-size: 14px;
      vertical-align: top;
    }
    .linha-assinatura {
      display: inline-block; width: 80%; border-top: 1px solid #000; margin-bottom: 4px;
    }
    .cargo { font-weight: 700; margin-top: 2px; }

    .rodape-legal {
      margin-top: 16px; text-align: left; font-size: 12px; font-style: italic;
    }

    @media print {
      body { background: #fff; }
      .print-toolbar { display: none !important; }
      .pagina { margin: 0; border: none; }
    }
  </style>
</head>
<body>
  <div class="print-toolbar">
    <button type="button" class="print-btn" onclick="window.print()">Imprimir / Salvar em PDF</button>
  </div>

  <div class="pagina<?php echo $classeMarcaDagua; ?>">
    <?php if ($marcaDagua) { ?>
      <img class="fundo-historico" src="<?php echo h($urlFundoHistorico); ?>" alt="">
    <?php } ?>
    <div class="conteudo">
      <div class="cabecalho-topo">
        <img src="https://sested-eja.com/img/logo.jpg" alt="Logo SESTED">
        <div class="titulo">SISTEMA DE ENSINO SUPERIOR TECNOLÓGICO E EDUCACIONAL - SESTED</div>
        <div class="linha">Mantenedora: SESTED - Sistema de Ensino Superior Tecnologico e Educacional - ME</div>
        <div class="linha">CNPJ: 07.158.229/0001-06</div>
        <div class="linha">Rua Nova Uniao, n 2024, Setor 02, Buritis/RO - CEP 76880-000</div>
        <div class="linha">e-mail: sestedcursos@gmail.com | Tel. (69) 99694-538</div>
        <div class="linha">Credenciado pelo Parecer CEB/CEE/RO n 003/24 e Resolucao CEB/CEE/RO n 909/24</div>
      </div>

      <div class="titulo-doc">
        <div class="t1">HISTORICO ESCOLAR</div>
        <div class="t2">EDUCAÇÃO DE JOVENS E ADULTOS - EJA - ENSINO MEDIO - 3º SEGMENTO</div>
      </div>

      <table class="dados">
        <tr>
          <td class="label">Nome do Candidato:</td>
          <td class="valor" colspan="5"><?php echo h($nome); ?></td>
        </tr>
        <tr>
          <td class="label">Filiação:</td>
          <td class="valor" colspan="5"><?php echo h($filiacao); ?></td>
        </tr>
        <tr>
          <td class="label">Data de Nascimento:</td>
          <td class="valor"><?php echo h($dataNascimento); ?></td>
          <td class="label">Naturalidade:</td>
          <td class="valor" colspan="3"><?php echo h($naturalidade); ?></td>
        </tr>
        <tr>
          <td class="label">Documento de Identificação:</td>
          <td class="valor"><?php echo h($documentoIdentificacao); ?></td>
          <td class="label">Orgão Expedidor:</td>
          <td class="valor"><?php echo h($orgaoEmissor); ?></td>
          <td class="label">Data de Expedição:</td>
          <td class="valor"><?php echo h($dataExpedicao); ?></td>
        </tr>
        <tr>
          <td class="label">CPF:</td>
          <td class="valor"><?php echo h($cpf); ?></td>
          <td class="label">Ano de Conclusão:</td>
          <td class="valor"><?php echo h($anoConclusao); ?></td>
          <td class="label">Nacionalidade:</td>
          <td class="valor">Brasileira</td>
        </tr>
        <tr>
          <td class="label">Base Legal:</td>
          <td colspan="5">Lei Federal n 9.394/96 (Arts. 24, 35, 36 e 38) | Resolucao CEB/CEE/RO n 909/24 e n 1.334/23</td>
        </tr>
      </table>

      <div class="bloco-titulo">REGISTRO DE DESEMPENHO ESCOLAR - EXAMES DE CONCLUSÃO</div>

      <table class="desempenho">
        <tr>
          <th>AREA DO CONHECIMENTO</th>
          <th>COMPONENTES CURRICULARES</th>
          <th>NOTA</th>
          <th>C.H</th>
          <th>DATA DO EXAME</th>
          <th>FREQUENCIA</th>
          <th>RESULTADO</th>
        </tr>
        <?php foreach ($componentes as $grupo): ?>
          <?php $qtd = count($grupo['itens']); ?>
          <?php foreach ($grupo['itens'] as $indice => $item): ?>
            <?php
              $dadosMateria = [];
              if (is_array($notas[$item['key']] ?? null)) {
                  $dadosMateria = $notas[$item['key']];
              } else {
                  $chaveCanonicaItem = chave_materia_canonica($item['key']);
                  if ($chaveCanonicaItem !== '' && is_array($notasCanonicas[$chaveCanonicaItem] ?? null)) {
                      $dadosMateria = $notasCanonicas[$chaveCanonicaItem];
                  }
              }
              $nota = extrair_nota_materia($dadosMateria);
              $dataExame = formatar_data_br($dadosMateria['data'] ?? '', '00/00/0000');
            ?>
            <tr>
              <?php if ($indice === 0): ?>
                <td class="area" rowspan="<?php echo $qtd; ?>"><?php echo h($grupo['area']); ?></td>
              <?php endif; ?>
              <td class="comp"><?php echo h($item['nome']); ?></td>
              <td class="nota"><?php echo h($nota); ?></td>
              <td class="ch"><?php echo h($item['ch']); ?></td>
              <td class="data"><?php echo h($dataExame); ?></td>
              <td class="freq">Dispensa</td>
              <td class="res"><?php echo h($situacao); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <tr class="totais">
          <td colspan="3">CARGA HORARIA TOTAL</td>
          <td class="valor"><?php echo h($cargaHorariaTexto); ?></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="7" class="obs-caixa">
            <strong></strong>
            <?php echo h($observacoes); ?>
          </td>
        </tr>
        <tr>
          <td colspan="7" class="obs-caixa">
            <strong>APROVEITAMENTO DE ESTUDOS ANTERIORES:</strong>
            <?php echo h($aproveitamentoEstudosAnteriores); ?>
          </td>
        </tr>
      </table>

      <div class="local-data">
        <?php
          $localData = trim($municipioData) !== '' ? $municipioData . ' - ' : '';
          $localData .= $dataHistoricoExtenso !== '' ? $dataHistoricoExtenso : '____ de __________ de ________';
          echo h($localData);
        ?>
      </div>

      <table class="assinaturas">
        <tr>
          <td>
            <span class="linha-assinatura"></span><br>
            Laura Maria Jonjob de Souza<br>
            RG: 757423 SESDEC/RO<br>
            <span class="cargo">Diretora</span>
          </td>
          <td>
            <span class="linha-assinatura"></span><br>
            Daniely Jonjob da Silva<br>
            RG: 1480635 SESDEC/RO<br>
            <span class="cargo">Secretaria Escolar</span>
          </td>
        </tr>
      </table>

      <div class="rodape-legal">
       Este documento possui validade nacional, conforme Art. 38, § 1º da Lei Federal nº 9.394/96.
      </div>
    </div>
  </div>
</body>
</html>
