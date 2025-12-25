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
        $valor = substr($valor, 1);
    }

    $valor = $valor === '' ? 0 : (float) $valor;
    $notaFormatada = number_format($valor, 1, ',', '');
    return $temAsterisco ? '*' . $notaFormatada : $notaFormatada;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_UNESCAPED_UNICODE);

    $dadosAluno = $input['dadosAluno'];
    $notas = $input['notas'];
    $dadosAdicionais = $input['dadosAdicionais'];
    $retorno = isset($input['retorno']) ? $input['retorno'] : 'html';

    // Função para normalizar nomes
    function formatarNomeMateria($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        if (class_exists('Normalizer')) {
            $str = Normalizer::normalize($str, Normalizer::FORM_D);
            $str = preg_replace('/\pM/u', '', $str);
        } else {
            $acentos = [
                'á' => 'a',
                'à' => 'a',
                'ã' => 'a',
                'â' => 'a',
                'é' => 'e',
                'ê' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'õ' => 'o',
                'ô' => 'o',
                'ú' => 'u',
                'ü' => 'u',
                'ç' => 'c'
            ];
            $str = strtr($str, $acentos);
        }
        $str = preg_replace('/[^a-z0-9]+/', '_', $str);
        return trim($str, '_');
    }

    // Função para formatar nota (0-100 → "0,0" até "10,0")
    function formatarNota($valor)
    {
        return $valor;
        $valor = (float) $valor;
        $nota = $valor / 10; // transforma em escala 0-10

        // Se for 10, retorna "10" sem vírgula
        if ($nota == 10) {
            return "10";
        }

        // Caso contrário, sempre retorna com ",0"
        return number_format($nota, 1, ',', '');
    }


    // Recria array de notas
    $notasFormatadas = [];
    foreach ($notas as $materia => $valores) {
        $novaChave = formatarNomeMateria($materia);

        $valoresFormatados = [];
        foreach ($valores as $k => $v) {
            if ($k !== 'data') {
                $valoresFormatados[$k] = $v;
            } else {
                $valoresFormatados[$k] = $v;
            }
        }

        $notasFormatadas[$novaChave] = $valoresFormatados;
    }

    $notas = $notasFormatadas;

    // Renderiza HTML
    ob_start();
    include('visualizar_historico_novo.php');
    $html = ob_get_clean();

    $categoria = isset($input['dadosAluno']['categoria']) ? strtolower($input['dadosAluno']['categoria']) : 'medio';

    // Criar nome do arquivo
    $nomeArquivo = 'HISTORICO_'
        . $categoria . '_'
        . preg_replace('/[^A-Za-z0-9_\-]/', '_', $input['dadosAluno']['nome'])
        . '_' . date('YmdHis') . '.html';

    // Diretório base
    $dirBase = __DIR__ . '/historicos';

    // Criar subpasta com o ID do aluno
    $idAluno = intval($input['dadosAluno']['id_aluno']); // garante que é número
    $dirAluno = $dirBase . '/' . $idAluno . '/' . $categoria;

    if (!is_dir($dirAluno)) {
        mkdir($dirAluno, 0777, true);
    }

    $caminhoCompleto = $dirAluno . '/' . $nomeArquivo;

    // Salvar HTML no servidor
    file_put_contents($caminhoCompleto, $html);

    if ($retorno === 'json') {
        $arquivoRelativo = '/historicos/' . $idAluno . '/' . $categoria . '/' . $nomeArquivo;
        $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => true,
            'arquivo_html' => $arquivoRelativo,
            'url_visualizacao' => $baseUrl . $arquivoRelativo,
            'mensagem' => 'Historico gerado com sucesso.'
        ]);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}


?>
