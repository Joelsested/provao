<?php
$id = $_GET['id'] ?? null;
$data_emissao = $_GET['data'] ?? null;

include('../conexao.php');

date_default_timezone_set('America/Porto_Velho');

function limpar_numero($valor)
{
    return preg_replace('/\D+/', '', (string) $valor);
}

function formatar_cpf($cpf)
{
    $cpf = limpar_numero($cpf);
    if (strlen($cpf) !== 11) {
        return (string) $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function formatar_data_ddmmaaaa($data)
{
    $ts = strtotime((string) $data);
    if ($ts === false) {
        return '';
    }
    return date('d/m/Y', $ts);
}

function normalizar_texto($texto)
{
    $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
    $textoAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($textoAscii !== false) {
        $texto = $textoAscii;
    }
    $texto = str_replace(['-', '/', '_'], ' ', $texto);
    $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
    $texto = preg_replace('/\s+/', ' ', (string) $texto);
    return trim((string) $texto);
}

function formatar_nota($notaBruta)
{
    if ($notaBruta === null || $notaBruta === '') {
        return '';
    }
    $valor = str_replace(',', '.', trim((string) $notaBruta));
    if (!is_numeric($valor)) {
        return '';
    }
    $valorNumerico = (float) $valor;

    // Padroniza para escala de 0 a 10.
    if ($valorNumerico > 10) {
        $valorNumerico = $valorNumerico / 10;
    }

    if ($valorNumerico < 0) {
        $valorNumerico = 0;
    }
    if ($valorNumerico > 10) {
        $valorNumerico = 10;
    }

    return number_format($valorNumerico, 1, ',', '');
}

function nota_para_extenso($notaFormatada)
{
    if ($notaFormatada === '') {
        return '';
    }

    $palavras = [
        '0' => 'zero',
        '1' => 'um',
        '2' => 'dois',
        '3' => 'trÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âªs',
        '4' => 'quatro',
        '5' => 'cinco',
        '6' => 'seis',
        '7' => 'sete',
        '8' => 'oito',
        '9' => 'nove',
        '10' => 'dez',
    ];

    $partes = explode(',', $notaFormatada);
    $inteiro = $partes[0] ?? '';
    if (!isset($palavras[$inteiro])) {
        return '';
    }

    $resultado = $palavras[$inteiro];
    $decimal = $partes[1] ?? '';
    if ($decimal !== '' && preg_replace('/0/', '', $decimal) !== '') {
        $digitos = [];
        foreach (str_split($decimal) as $digito) {
            if (isset($palavras[$digito])) {
                $digitos[] = $palavras[$digito];
            }
        }
        if (count($digitos) > 0) {
            $resultado .= ' vÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­rgula ' . implode(' ', $digitos);
        }
    }

    return $resultado;
}

function identificar_componente_por_curso($nomeCurso, $mapaAliases)
{
    $nomeNormalizado = normalizar_texto($nomeCurso);
    foreach ($mapaAliases as $chave => $aliases) {
        foreach ($aliases as $alias) {
            if (strpos($nomeNormalizado, normalizar_texto($alias)) !== false) {
                return $chave;
            }
        }
    }
    return null;
}

function obter_partes_data($data)
{
    $ts = strtotime((string) $data);
    if ($ts === false) {
        $ts = strtotime('today');
    }

    $meses = [
        1 => 'janeiro',
        2 => 'fevereiro',
        3 => 'mar&ccedil;o',
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

    return [
        'dia' => date('d', $ts),
        'mes' => $meses[(int) date('n', $ts)] ?? '',
        'ano' => date('Y', $ts),
    ];
}

function resolver_nome_fundo($candidatos)
{
    foreach ($candidatos as $nome) {
        $caminhoLocal = __DIR__ . '/../img/' . $nome;
        if (file_exists($caminhoLocal)) {
            return $nome;
        }
    }
    return $candidatos[0];
}

function localizar_fundo_eliminacao_medio()
{
    $pasta = __DIR__ . '/../img/';
    if (!is_dir($pasta)) {
        return null;
    }

    $arquivos = @scandir($pasta);
    if (!is_array($arquivos)) {
        return null;
    }

    foreach ($arquivos as $arquivo) {
        if ($arquivo === '.' || $arquivo === '..') {
            continue;
        }

        $ext = strtolower((string) pathinfo($arquivo, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            continue;
        }

        $normalizado = normalizar_texto($arquivo);
        if (strpos($normalizado, 'elimin') !== false && strpos($normalizado, 'medio') !== false) {
            return $arquivo;
        }
    }

    return null;
}

$query = $pdo->prepare("SELECT * FROM usuarios WHERE id_pessoa = :id_pessoa ORDER BY id DESC LIMIT 1");
$query->execute([':id_pessoa' => $id]);
$usuario = $query->fetch(PDO::FETCH_ASSOC) ?: [];

$nome = (string) ($usuario['nome'] ?? '');
$idUsuario = (int) ($usuario['id'] ?? 0);
$pessoa = (int) ($usuario['id_pessoa'] ?? 0);

$query = $pdo->prepare("SELECT * FROM alunos WHERE id = :id LIMIT 1");
$query->execute([':id' => $pessoa]);
$aluno = $query->fetch(PDO::FETCH_ASSOC) ?: [];

$cpf = formatar_cpf($aluno['cpf'] ?? '');
$rg = (string) ($aluno['rg'] ?? '');
$pai = (string) ($aluno['pai'] ?? '');
$mae = (string) ($aluno['mae'] ?? '');

$partesData = obter_partes_data($data_emissao ?: date('Y-m-d'));

$dadosNotas = [
    'lingua_portuguesa' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'lingua_inglesa' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'arte' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'educacao_fisica' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'matematica' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'biologia' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'fisica' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'quimica' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'historia' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'geografia' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'filosofia' => ['nota' => '', 'extenso' => '', 'data' => ''],
    'sociologia' => ['nota' => '', 'extenso' => '', 'data' => ''],
];

$mapaAliasesComponentes = [
    'lingua_portuguesa' => ['lingua portuguesa', 'portugues', 'literatura'],
    'lingua_inglesa' => ['lingua inglesa', 'ingles'],
    'arte' => ['arte'],
    'educacao_fisica' => ['educacao fisica'],
    'matematica' => ['matematica'],
    'biologia' => ['biologia'],
    'fisica' => ['fisica'],
    'quimica' => ['quimica'],
    'historia' => ['historia'],
    'geografia' => ['geografia'],
    'filosofia' => ['filosofia'],
    'sociologia' => ['sociologia'],
];

if ($idUsuario > 0) {
    $queryNotas = $pdo->prepare("
        SELECT m.nota, m.data, c.nome AS nome_curso
        FROM matriculas m
        INNER JOIN cursos c ON c.id = m.id_curso
        WHERE m.aluno = :aluno
          AND (m.pacote != 'Sim' OR m.pacote IS NULL OR m.pacote = '')
        ORDER BY m.id DESC
    ");
    $queryNotas->execute([':aluno' => $idUsuario]);
    $linhasNotas = $queryNotas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($linhasNotas as $linhaNota) {
        $chaveComponente = identificar_componente_por_curso($linhaNota['nome_curso'] ?? '', $mapaAliasesComponentes);
        if ($chaveComponente === null) {
            continue;
        }
        if (($dadosNotas[$chaveComponente]['nota'] ?? '') !== '') {
            continue;
        }

        $notaFormatada = formatar_nota($linhaNota['nota'] ?? '');
        if ($notaFormatada === '') {
            continue;
        }

        $dadosNotas[$chaveComponente]['nota'] = $notaFormatada;
        $dadosNotas[$chaveComponente]['extenso'] = nota_para_extenso($notaFormatada);
        $dadosNotas[$chaveComponente]['data'] = formatar_data_ddmmaaaa($linhaNota['data'] ?? '');
    }
}

$fundoDetectado = localizar_fundo_eliminacao_medio();
$fundoFrenteNome = $fundoDetectado ?: resolver_nome_fundo([
    'eliminacao medio.jpg',
    'eliminacao medio_frente.jpg',
    'eliminacao_medio_frente.jpg',
]);
$fundo_frente = rtrim((string) $url_sistema, '/') . '/sistema/img/' . rawurlencode($fundoFrenteNome);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 0; }
html, body { margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; color: #111; }
.documento {
    width: 188mm;
    min-height: 276mm;
    margin: 10.5mm auto;
    padding: 6mm 7mm 5mm;
    box-sizing: border-box;
    background-image: url('<?php echo $fundo_frente; ?>');
    background-repeat: no-repeat;
    background-position: center top;
    background-size: 188mm 276mm;
}
.conteudo-principal,
.rodape-documento,
.assinaturas,
.observacoes {
    page-break-inside: avoid;
}
.logo { text-align: center; margin-bottom: 1.2mm; }
.logo-sested { width: 140px; height: 100px; object-fit: contain; }
.cabecalho { text-align: center; font-size: 10px; line-height: 1.1; }
.cabecalho .nome {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 1.8mm;
}
.titulo {
    text-align: center;
    font-weight: 700;
    margin: 4.5mm 0 3.2mm;
    font-size: 3.9mm;
    white-space: nowrap;
}
.texto {
    text-align: justify;
    font-size: 3.8mm;
    line-height: 1.14;
}
.tabela { width: 100%; border-collapse: collapse; margin-top: 3.2mm; font-size: 2.9mm; }
.tabela th, .tabela td { border: 1px solid #111; padding: 0.6mm 1.1mm; }
.tabela th { text-align: center; }
.tabela .area { width: 22%; text-align: center; }
.tabela .comp { width: 34%; }
.tabela .nota { width: 12%; text-align: center; }
.tabela .ext { width: 20%; }
.tabela .data { width: 12%; text-align: center; }
.rodape-documento { margin-top: 2.4mm; }
.observacoes {
    margin-top: 0;
    border: 1px solid #111;
    padding: 1.2mm 1.8mm;
    font-size: 2.35mm;
    line-height: 1.12;
    text-align: justify;
}
.data-emissao {
    margin-top: 2.8mm;
    text-align: right;
    font-size: 3.4mm;
}
.assinaturas {
    margin-top: 44mm;
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}
.assinaturas td {
    width: 50%;
    vertical-align: top;
    text-align: center;
    font-size: 2.8mm;
    padding: 0 3mm;
}
.ass-linha {
    display: block;
    border-top: 1px solid #111;
    margin: 0 auto 1mm;
    width: 58mm;
}
.ass-nome { font-weight: 700; }
.ass-rg { font-size: 2.2mm; }
.ass-cargo { font-weight: 700; }
</style>
</head>
<body>
<div class="documento">
    <div class="conteudo-principal">
        <div class="logo"><img class="logo-sested" src="<?php echo $url_sistema; ?>img/logo.jpg" alt="Logo"></div>

        <div class="cabecalho">
            <div class="nome">SISTEMA DE ENSINO SUPERIOR TECNOLOGICO E EDUCACIONAL - SESTED</div>
            <div>Mantenedora: SESTED - Sistema de Ensino Superior Tecnologico e Educacional - ME</div>
            <div>CNPJ: 07.158.229/0001-06</div>
            <div>Rua Nova Uniao, n&ordm; 2024, Setor 02, Buritis/RO - CEP 76880-000</div>
            <div>e-mail: sestedcursos@gmail.com | Tel. (69) 99694-538</div>
            <div>Credenciado pelo Parecer CEB/CEE/RO n&ordm; 003/24 e Resolu&ccedil;&atilde;o CEB/CEE/RO n&ordm; 909/24</div>
        </div>

        <div class="titulo">ATESTADO DE ELIMINA&Ccedil;&Atilde;O - EXAMES DE CONCLUS&Atilde;O DO ENSINO M&Eacute;DIO</div>

        <div class="texto">
            A Diretora do <b>SESTED - Sistema de Ensino Superior Tecnologico e Educacional</b>,
            em conformidade com o Art. 37 e &sect; 1&ordm;, do inciso I, do Art. 38 da Lei Federal n&ordm; 9.394,
            de 20 de dezembro de 1996, Resolu&ccedil;&atilde;o n&ordm; 1.334/2023-CEE/RO, Art. 37 da Resolu&ccedil;&atilde;o n&ordm; 1.314-2021/CEE/RO,
            Resolu&ccedil;&atilde;o n&ordm; 909/2024-CEE/RO e Parecer n&ordm; 003/2024-CEE/RO, considerando os resultados obtidos nos
            Exames de Conclus&atilde;o da EJA na etapa de Ensino M&eacute;dio - 3&ordm; Segmento, bem como o cumprimento dos demais requisitos
            legais, <b>ATESTAMOS</b> para os devidos fins, que <b><?php echo htmlspecialchars($nome); ?></b>,
            CPF sob o n&ordm;, <?php echo htmlspecialchars($cpf); ?>,
            filho(a) de <?php echo htmlspecialchars($pai); ?> e <?php echo htmlspecialchars($mae); ?>,
            realizou os exames gerais e obteve os seguintes resultados:
        </div>

        <table class="tabela">
            <thead>
                <tr>
                    <th>&Aacute;REA DO CONHECIMENTO</th>
                    <th>COMPONENTE CURRICULAR</th>
                    <th>NOTA</th>
                    <th>NOTA POR EXTENSO</th>
                    <th>DATA</th>
                </tr>
            </thead>
            <tbody>
<?php
$gruposTabela = [
    [
        'area' => 'LINGUAGENS E SUAS TECNOLOGIAS',
        'linhas' => [
            ['chave' => 'lingua_portuguesa', 'componente' => 'L&iacute;ngua Portuguesa / Literatura'],
            ['chave' => 'lingua_inglesa', 'componente' => 'L&iacute;ngua Inglesa'],
            ['chave' => 'arte', 'componente' => 'Arte'],
            ['chave' => 'educacao_fisica', 'componente' => 'Educa&ccedil;&atilde;o F&iacute;sica'],
        ],
    ],
    [
        'area' => 'MATEM&Aacute;TICA E SUAS TECNOLOGIAS',
        'linhas' => [
            ['chave' => 'matematica', 'componente' => 'Matem&aacute;tica'],
        ],
    ],
    [
        'area' => 'NATUREZA E SUAS TECNOLOGIAS',
        'linhas' => [
            ['chave' => 'biologia', 'componente' => 'Biologia'],
            ['chave' => 'fisica', 'componente' => 'F&iacute;sica'],
            ['chave' => 'quimica', 'componente' => 'Qu&iacute;mica'],
        ],
    ],
    [
        'area' => 'CI&Ecirc;NCIAS HUMANAS',
        'linhas' => [
            ['chave' => 'historia', 'componente' => 'Hist&oacute;ria*'],
            ['chave' => 'geografia', 'componente' => 'Geografia**'],
            ['chave' => 'filosofia', 'componente' => 'Filosofia'],
        ],
    ],
    [
        'area' => 'SOCIAIS APLICADAS',
        'linhas' => [
            ['chave' => 'sociologia', 'componente' => 'Sociologia'],
        ],
    ],
];

foreach ($gruposTabela as $grupo) {
    $qtdLinhas = count($grupo['linhas']);
    foreach ($grupo['linhas'] as $indiceLinha => $linha) {
        $chave = $linha['chave'];
        $nota = htmlspecialchars((string) ($dadosNotas[$chave]['nota'] ?? ''), ENT_QUOTES, 'UTF-8');
        $extenso = htmlspecialchars((string) ($dadosNotas[$chave]['extenso'] ?? ''), ENT_QUOTES, 'UTF-8');
        $dataLinha = htmlspecialchars((string) ($dadosNotas[$chave]['data'] ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<tr>';
        if ($indiceLinha === 0) {
            echo '<td class="area" rowspan="' . $qtdLinhas . '">' . $grupo['area'] . '</td>';
        }
        echo '<td class="comp">' . $linha['componente'] . '</td>';
        echo '<td class="nota">' . $nota . '</td>';
        echo '<td class="ext">' . $extenso . '</td>';
        echo '<td class="data">' . $dataLinha . '</td>';
        echo '</tr>';
    }
}
?>
            </tbody>
        </table>
    </div>

    <div class="rodape-documento">
        <div class="observacoes">
            Observa&ccedil;&otilde;es: Crit&eacute;rio de aprova&ccedil;&atilde;o: nota m&iacute;nima 5,0 (cinco) em escala de 0 a 10, ou 50% de acertos nas avalia&ccedil;&otilde;es, em conformidade com o Art. 124 do Regimento Escolar.<br>
            * No componente curricular de Hist&oacute;ria foram inclu&iacute;dos conte&uacute;dos de Hist&oacute;ria do Estado de Rond&ocirc;nia, Hist&oacute;ria e Cultura Africana, Afro-brasileira e Ind&iacute;gena.<br>
            ** No componente curricular de Geografia foram inclu&iacute;dos conte&uacute;dos de Geografia do Estado de Rond&ocirc;nia.
        </div>

        <div class="data-emissao">Buritis - RO, <?php echo $partesData['dia']; ?> de <?php echo $partesData['mes']; ?> de <?php echo $partesData['ano']; ?>.</div>

        <table class="assinaturas">
            <tr>
                <td>
                    <span class="ass-linha"></span>
                    <div class="ass-nome">Daniely Jonjob da Silva</div>
                    <div class="ass-rg">RG: 1480635 SESDEC/RO</div>
                    <div class="ass-cargo">Secretaria Escolar</div>
                </td>
                <td>
                    <span class="ass-linha"></span>
                    <div class="ass-nome">Laura Maria Jonjob de Souza</div>
                    <div class="ass-rg">RG: 757423 SESDEC/RO</div>
                    <div class="ass-cargo">Diretora</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
