<?php
require_once __DIR__ . '/config/csrf.php';
csrf_start();
csrf_require(true);
require_once __DIR__ . '/sistema/conexao.php';

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
    $notasOriginais = $notas;
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
        $valor = (float) $valor;
        $nota = $valor / 10; // transforma em escala 0-10
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

    $erros = [];
    $hoje = new DateTimeImmutable('today');
    $dataHistoricoIso = $dadosAdicionais['data_historico_iso'] ?? '';

    $validarData = static function ($data) use ($hoje, &$erros) {
        if ($data === '') {
            return null;
        }
        $dataLimpa = ltrim($data, '*');
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dataLimpa);
        $dataValida = $dt && $dt->format('Y-m-d') === $dataLimpa;
        if (!$dataValida) {
            $erros[] = "Data inválida: {$dataLimpa}.";
            return null;
        }
        if ($dt > $hoje) {
            $erros[] = "Data no futuro não permitida: {$dataLimpa}.";
            return null;
        }
        return $dataLimpa;
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

        if (($notaTemValor($serie1) || $notaTemValor($serie2) || $notaTemValor($serie3)) && $dataMateria === '') {
            $erros[] = "Data obrigatória para a matéria {$materia}.";
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
            'mensagem' => 'Historico gerado com sucesso.'
        ]);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}


?>
