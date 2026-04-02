<?php
require_once __DIR__ . '/config/csrf.php';
csrf_start();
csrf_require(true);
require_once __DIR__ . '/sistema/conexao.php';

if (empty($_SESSION['nivel']) || $_SESSION['nivel'] === 'Aluno') {
    http_response_code(401);
    echo 'Não autorizado.';
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
    $notasOriginais = $notas;
    $dadosAdicionais = $input['dadosAdicionais'];
    $retorno = isset($input['retorno']) ? $input['retorno'] : 'html';

    // Funcao para normalizar nomes
    function formatarNomeMateria($str)
    {
        $str = trim((string) $str);
        if ($str === '') {
            return '';
        }

        $str = mb_strtolower($str, 'UTF-8');
        if (class_exists('Normalizer')) {
            $str = Normalizer::normalize($str, Normalizer::FORM_D);
            $str = preg_replace('/\pM/u', '', $str);
        } else {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            if ($ascii !== false) {
                $str = $ascii;
            }
        }

        $str = preg_replace('/[^a-z0-9]+/', '_', (string) $str);
        $str = trim((string) $str, '_');
        $str = preg_replace('/_(fundamental|medio)$/', '', $str);

        $textoBusca = str_replace('_', ' ', (string) $str);
        $textoCompacto = preg_replace('/[^a-z0-9]+/', '', (string) $str);

        if (str_contains($textoBusca, 'portugues') || str_contains($textoCompacto, 'portugues')) {
            return 'lingua_portuguesa';
        }
        if (str_contains($textoBusca, 'ingles') || str_contains($textoCompacto, 'ingles')) {
            return 'lingua_inglesa';
        }
        if ((str_contains($textoBusca, 'educac') && str_contains($textoBusca, 'fisic'))
            || str_contains($textoCompacto, 'educacaofisica')) {
            return 'educacao_fisica';
        }
        if (str_contains($textoBusca, 'matemat') || str_contains($textoCompacto, 'matematica')) {
            return 'matematica';
        }
        if (str_contains($textoBusca, 'cienc') || str_contains($textoCompacto, 'ciencias')) {
            return 'ciencias';
        }
        if (str_contains($textoBusca, 'hist') || str_contains($textoCompacto, 'historia')) {
            return 'historia';
        }
        if (str_contains($textoBusca, 'geograf') || str_contains($textoCompacto, 'geografia')) {
            return 'geografia';
        }
        if (str_contains($textoBusca, 'arte') || str_contains($textoCompacto, 'arte')) {
            return 'arte';
        }

        if ($str === 'hist_ria') {
            $str = 'historia';
        }

        return $str;
    }

    function formatarNota($valor)
    {
        $valor = (float) $valor;
        $nota = $valor / 10;
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

$normalizarDataEntrada = static function ($data) {
    $data = trim((string) $data);
    $temAsterisco = str_starts_with($data, '*');
    $dataLimpa = ltrim($data, '*');

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataLimpa)) {
        [$dia, $mes, $ano] = explode('/', $dataLimpa);
        $dataLimpa = "{$ano}-{$mes}-{$dia}";
    } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dataLimpa)) {
        [$dia, $mes, $ano] = explode('-', $dataLimpa);
        $dataLimpa = "{$ano}-{$mes}-{$dia}";
    } elseif (preg_match('/^\d{8}$/', $dataLimpa)) {
        $dia = substr($dataLimpa, 0, 2);
        $mes = substr($dataLimpa, 2, 2);
        $ano = substr($dataLimpa, 4, 4);
        $dataLimpa = "{$ano}-{$mes}-{$dia}";
    }

    return $temAsterisco ? ('*' . $dataLimpa) : $dataLimpa;
};

$campoTemAsterisco = static function ($valor) {
    return is_string($valor) && str_starts_with(trim($valor), '*');
};

$temNotaPositiva = static function ($valor) {
    $texto = ltrim(trim((string) $valor), '*');
    $texto = str_replace(',', '.', $texto);
    if ($texto === '' || !is_numeric($texto)) {
        return false;
    }
    return (float) $texto > 0;
};

// Reforco: completa notas/datas oficiais direto do banco para evitar lacunas
// causadas por variacoes de codificacao no nome da materia.
if (!empty($dadosAluno['id_aluno'])) {
    $idPessoaAluno = (int) $dadosAluno['id_aluno'];

    $stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Aluno' ORDER BY id DESC LIMIT 1");
    $stmtUsuario->execute([':id_pessoa' => $idPessoaAluno]);
    $usuarioLinha = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!empty($usuarioLinha['id'])) {
        $usuarioId = (int) $usuarioLinha['id'];
        $stmtMatriculas = $pdo->prepare("
            SELECT C.nome AS nome_curso, C.data_certificado, M.nota
            FROM matriculas M
            INNER JOIN cursos C ON C.id = M.id_curso
            WHERE M.aluno = :aluno
              AND M.pacote != 'Sim'
              AND C.categoria = 7
        ");
        $stmtMatriculas->execute([':aluno' => $usuarioId]);
        $matriculasOficiais = $stmtMatriculas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matriculasOficiais as $linhaCurso) {
            $chaveMateria = formatarNomeMateria($linhaCurso['nome_curso'] ?? '');
            if ($chaveMateria === '') {
                continue;
            }

            $notaEscala10 = ((float) ($linhaCurso['nota'] ?? 0)) / 10;
            $notaBanco = number_format($notaEscala10, 1, '.', '');

            $dataBanco = $normalizarDataEntrada((string) ($linhaCurso['data_certificado'] ?? ''));
            $dataBanco = ltrim($dataBanco, '*');

            if (!isset($notas[$chaveMateria]) || !is_array($notas[$chaveMateria])) {
                $notas[$chaveMateria] = [];
            }

            $item = $notas[$chaveMateria];
            $bloqueadoManual = $campoTemAsterisco($item['serie1'] ?? '')
                || $campoTemAsterisco($item['serie2'] ?? '')
                || $campoTemAsterisco($item['serie3'] ?? '')
                || $campoTemAsterisco($item['data'] ?? '');

            if ($bloqueadoManual) {
                continue;
            }

            if ($notaEscala10 > 0) {
                $notas[$chaveMateria]['serie1'] = $notaBanco;
                $notas[$chaveMateria]['serie2'] = $notaBanco;
                $notas[$chaveMateria]['serie3'] = $notaBanco;
            }

            if ($dataBanco !== '') {
                $notas[$chaveMateria]['data'] = $dataBanco;
            }
        }
    }
}

$formatarDataBr = static function ($dataIso) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataIso)) {
        return substr($dataIso, 8, 2) . '/' . substr($dataIso, 5, 2) . '/' . substr($dataIso, 0, 4);
    }
    return $dataIso;
};

date_default_timezone_set('America/Porto_Velho');
$erros = [];
$hoje = new DateTimeImmutable('today');
$dataHistoricoIso = $dadosAdicionais['data_historico_iso'] ?? '';

$validarData = static function ($data) use ($hoje, &$erros, $normalizarDataEntrada, $formatarDataBr) {
    if ($data === '') {
        return null;
    }
    $data = $normalizarDataEntrada($data);
    $dataLimpa = ltrim($data, '*');
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dataLimpa);
    $dataValida = $dt && $dt->format('Y-m-d') === $dataLimpa;
    if (!$dataValida) {
        $erros[] = "Data inválida: {$dataLimpa}. Use o formato DDMMAAAA ou DD/MM/AAAA.";
        return null;
    }
    if ($dt > $hoje) {
        $erros[] = "Data no futuro não permitida: " . $formatarDataBr($dataLimpa) . ".";
        return null;
    }
    return $dataLimpa;
};

    $formatarMateriaExibicao = static function ($materia) {
        $materiaOriginal = trim((string) $materia);
        if ($materiaOriginal === '') {
            return 'não informada';
        }

        $chave = mb_strtolower($materiaOriginal, 'UTF-8');
        $chaveAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $chave);
        if ($chaveAscii !== false) {
            $chave = $chaveAscii;
        }
        $chave = preg_replace('/[^a-z0-9]+/', '_', $chave);
        $chave = trim((string) $chave, '_');

        $mapaMaterias = [
            'hist_ria' => 'Historia',
            'historia' => 'Historia',
            'geografia' => 'Geografia',
            'lingua_portuguesa' => 'Lingua Portuguesa',
            'lingua_inglesa' => 'Lingua Inglesa',
            'educacao_fisica' => 'Educacao Fisica',
            'ciencias' => 'Ciencias',
            'matematica' => 'Matematica',
            'arte' => 'Arte',
            'quimica' => 'Quimica',
            'fisica' => 'Fisica',
            'biologia' => 'Biologia',
            'filosofia' => 'Filosofia',
            'sociologia' => 'Sociologia',
        ];

        if (isset($mapaMaterias[$chave])) {
            return $mapaMaterias[$chave];
        }

        $texto = str_replace('_', ' ', $materiaOriginal);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return mb_convert_case(trim((string) $texto), MB_CASE_TITLE, 'UTF-8');
    };

    if ($dataHistoricoIso === '') {
        $erros[] = "Data do histórico é obrigatória.";
    } else {
        $validarData($dataHistoricoIso);
    }

    foreach ($notas as $materia => $valores) {
        $dataMateria = $valores['data'] ?? '';
        $serie1 = $valores['serie1'] ?? '';
        $serie2 = $valores['serie2'] ?? '';
        $serie3 = $valores['serie3'] ?? '';

        $notaTemValor = static function ($valor) {
            $valor = ltrim((string) $valor, '*');
            $valor = str_replace(',', '.', $valor);
            if ($valor === '' || $valor === '0' || $valor === '0.0') {
                return false;
            }
            return (float) $valor > 0;
        };

        $temNota = ($notaTemValor($serie1) || $notaTemValor($serie2) || $notaTemValor($serie3));
        if ($dataMateria === '' && $dataHistoricoIso !== '') {
            // Usa a data do histórico como fallback para evitar datas vazias nas matérias.
            $dataMateria = $dataHistoricoIso;
            $notas[$materia]['data'] = $dataMateria;
        }

        if ($temNota && $dataMateria === '') {
            $materiaExibicao = $formatarMateriaExibicao($materia);
            $erros[] = "Data obrigatória para a matéria {$materiaExibicao}.";
        }

        if ($dataMateria !== '') {
            $validarData($dataMateria);
        }
    }

    if (!empty($erros)) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'erro' => true,
            'mensagens' => $erros
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Renderiza HTML
    ob_start();
    include('visualizar_historico_fundamental_novo.php');
    $html = ob_get_clean();

    $categoria = isset($input['dadosAluno']['categoria']) ? strtolower($input['dadosAluno']['categoria']) : 'fundamental';

    // Criar nome do arquivo
    $nomeArquivo = 'HISTORICO_'
        . $categoria . '_'
        . preg_replace('/[^A-Za-z0-9_\-]/', '_', $input['dadosAluno']['nome'])
        . '_' . date('YmdHis') . '.html';

    // Diretorio base
    $dirBase = __DIR__ . '/historicos';

    // Criar subpasta com o ID do aluno
    $idAluno = intval($input['dadosAluno']['id_aluno']); // garante que e numero
    $dirAluno = $dirBase . '/' . $idAluno . '/' . $categoria;

    if (!is_dir($dirAluno)) {
        mkdir($dirAluno, 0777, true);
    }

    $caminhoCompleto = $dirAluno . '/' . $nomeArquivo;

    // Salvar HTML no servidor
    file_put_contents($caminhoCompleto, $html);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS historicos_versionados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            categoria VARCHAR(20) NOT NULL,
            versao INT NOT NULL,
            dados_json LONGTEXT NOT NULL,
            criado_em DATETIME NOT NULL,
            criado_por INT NULL,
            ip VARCHAR(45) NULL,
            INDEX idx_aluno_categoria (aluno_id, categoria, versao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documentos_emitidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            tipo VARCHAR(30) NOT NULL,
            categoria VARCHAR(30) NULL,
            versao INT NULL,
            arquivo_relativo VARCHAR(255) NOT NULL,
            criado_em DATETIME NOT NULL,
            criado_por INT NULL,
            ip VARCHAR(45) NULL,
            INDEX idx_aluno_tipo (aluno_id, tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmtVersao = $pdo->prepare("SELECT MAX(versao) AS versao FROM historicos_versionados WHERE aluno_id = :aluno_id AND categoria = :categoria");
    $stmtVersao->execute([
        ':aluno_id' => intval($dadosAluno['id_aluno']),
        ':categoria' => $categoria
    ]);
    $ultimaVersao = (int) ($stmtVersao->fetch(PDO::FETCH_ASSOC)['versao'] ?? 0);
    $novaVersao = $ultimaVersao + 1;

    $payloadVersionado = [
        'dadosAluno' => $dadosAluno,
        'notas' => $notas,
        'notas_raw' => $notasOriginais,
        'dadosAdicionais' => $dadosAdicionais,
        'criado_em' => date('c'),
        'criado_por' => $_SESSION['id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];

    $stmtInsert = $pdo->prepare("
        INSERT INTO historicos_versionados (aluno_id, categoria, versao, dados_json, criado_em, criado_por, ip)
        VALUES (:aluno_id, :categoria, :versao, :dados_json, :criado_em, :criado_por, :ip)
    ");
    $stmtInsert->execute([
        ':aluno_id' => intval($dadosAluno['id_aluno']),
        ':categoria' => $categoria,
        ':versao' => $novaVersao,
        ':dados_json' => json_encode($payloadVersionado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':criado_em' => date('Y-m-d H:i:s'),
        ':criado_por' => $_SESSION['id'] ?? null,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    $arquivoRelativo = '/historicos/' . $idAluno . '/' . $categoria . '/' . $nomeArquivo;
    $stmtDoc = $pdo->prepare("
        INSERT INTO documentos_emitidos (aluno_id, tipo, categoria, versao, arquivo_relativo, criado_em, criado_por, ip)
        VALUES (:aluno_id, :tipo, :categoria, :versao, :arquivo_relativo, :criado_em, :criado_por, :ip)
    ");
    $stmtDoc->execute([
        ':aluno_id' => $idAluno,
        ':tipo' => 'historico',
        ':categoria' => $categoria,
        ':versao' => $novaVersao,
        ':arquivo_relativo' => $arquivoRelativo,
        ':criado_em' => date('Y-m-d H:i:s'),
        ':criado_por' => $_SESSION['id'] ?? null,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    if ($retorno === 'json') {
        $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => true,
            'arquivo_html' => $arquivoRelativo,
            'url_visualizacao' => $baseUrl . $arquivoRelativo,
            'mensagem' => 'Histórico gerado com sucesso.'
        ]);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}


?>
