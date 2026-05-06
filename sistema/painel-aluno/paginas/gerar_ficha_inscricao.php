<?php
require_once __DIR__ . '/../../conexao.php';
require_once __DIR__ . '/../verificar.php';
require_once __DIR__ . '/../../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

@session_start();

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'Aluno') {
    http_response_code(403);
    echo 'Acesso negado.';
    exit();
}

$idUsuario = (int) ($_SESSION['id'] ?? 0);
if ($idUsuario <= 0) {
    http_response_code(403);
    echo 'Aluno não identificado.';
    exit();
}

$stmtUsuario = $pdo->prepare("SELECT id_pessoa, nome, cpf FROM usuarios WHERE id = :id LIMIT 1");
$stmtUsuario->execute([':id' => $idUsuario]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC) ?: [];
$idPessoaAluno = (int) ($usuario['id_pessoa'] ?? 0);

if ($idPessoaAluno <= 0) {
    http_response_code(403);
    echo 'Aluno não identificado.';
    exit();
}

$stmtAluno = $pdo->prepare("SELECT * FROM alunos WHERE id = :id LIMIT 1");
$stmtAluno->execute([':id' => $idPessoaAluno]);
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];

$limpar = static function ($valor): string {
    return trim((string) $valor);
};

$ouPadrao = static function (string $valor): string {
    $valor = trim($valor);
    return $valor !== '' ? $valor : '--';
};

$nome = $limpar($usuario['nome'] ?? '');
$cpf = $limpar($usuario['cpf'] ?? '');
$rg = $limpar($aluno['rg'] ?? '');
$dataNascimentoIso = $limpar($aluno['nascimento'] ?? '');
$dataNascimento = '';
$idade = '';

if ($dataNascimentoIso !== '') {
    $dtNasc = false;
    $formatos = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'];
    foreach ($formatos as $formato) {
        $dtNasc = DateTime::createFromFormat($formato, $dataNascimentoIso);
        if ($dtNasc instanceof DateTime) {
            break;
        }
    }
    if ($dtNasc instanceof DateTime) {
        $dataNascimento = $dtNasc->format('d/m/Y');
        $hoje = new DateTime('today');
        $idade = (string) $dtNasc->diff($hoje)->y;
    }
}

$telefone = $limpar($aluno['telefone'] ?? '');
$cep = $limpar($aluno['cep'] ?? '');
$endereco = $limpar($aluno['endereco'] ?? '');
$numero = $limpar($aluno['numero'] ?? '');
$bairro = $limpar($aluno['bairro'] ?? '');
$uf = $limpar($aluno['estado'] ?? '');
$naturalidade = $limpar($aluno['naturalidade'] ?? '');
$complemento = '';

$diaAtual = date('d');
$mesAtual = date('m');
$anoAtual = date('Y');

$stmtDataCompra = $pdo->prepare("SELECT data FROM matriculas WHERE aluno = :aluno AND status != 'Aguardando' AND data IS NOT NULL ORDER BY id DESC LIMIT 1");
$stmtDataCompra->execute([':aluno' => $idUsuario]);
$dataCompraRaw = (string) ($stmtDataCompra->fetchColumn() ?: '');

if ($dataCompraRaw !== '') {
    $dtCompra = false;
    $formatosCompra = ['Y-m-d', 'Y-m-d H:i:s', 'd/m/Y', 'd-m-Y'];
    foreach ($formatosCompra as $formatoCompra) {
        $dtCompra = DateTime::createFromFormat($formatoCompra, $dataCompraRaw);
        if ($dtCompra instanceof DateTime) {
            break;
        }
    }
    if ($dtCompra instanceof DateTime) {
        $diaAtual = $dtCompra->format('d');
        $mesAtual = $dtCompra->format('m');
        $anoAtual = $dtCompra->format('Y');
    }
}

$nome = $ouPadrao($nome);
$cpf = $ouPadrao($cpf);
$rg = $ouPadrao($rg);
$dataNascimento = $ouPadrao($dataNascimento);
$idade = $ouPadrao($idade);
$telefone = $ouPadrao($telefone);
$cep = $ouPadrao($cep);
$endereco = $ouPadrao($endereco);
$numero = $ouPadrao($numero);
$bairro = $ouPadrao($bairro);
$uf = $ouPadrao($uf);
$naturalidade = $ouPadrao($naturalidade);
$complemento = $ouPadrao($complemento);

$esc = static function (string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
};

$fundoRequerimentoPath = realpath(__DIR__ . '/../../img/requerimento.jpg');
$fundoRequerimentoUrl = '';
if ($fundoRequerimentoPath) {
    $fundoMime = 'image/jpeg';
    $fundoBin = @file_get_contents($fundoRequerimentoPath);
    if ($fundoBin !== false) {
        $fundoRequerimentoUrl = 'data:' . $fundoMime . ';base64,' . base64_encode($fundoBin);
    }
}

$logoPath = realpath(__DIR__ . '/../../img/logo.png');
$logoUrl = '';
if ($logoPath) {
    $logoMime = 'image/png';
    $logoBin = @file_get_contents($logoPath);
    if ($logoBin !== false) {
        $logoUrl = 'data:' . $logoMime . ';base64,' . base64_encode($logoBin);
    }
}

$normalizar = static function (string $texto): string {
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($conv !== false) {
            $texto = mb_strtolower($conv, 'UTF-8');
        }
    }
    return preg_replace('/\s+/', ' ', $texto) ?? $texto;
};

$stmtCursosAluno = $pdo->prepare("
    SELECT c.nome, c.nome_url
    FROM matriculas m
    INNER JOIN cursos c ON c.id = m.id_curso
    WHERE m.aluno = :aluno
      AND m.status != 'Aguardando'
");
$stmtCursosAluno->execute([':aluno' => $idUsuario]);
$cursosAlunoBruto = $stmtCursosAluno->fetchAll(PDO::FETCH_ASSOC) ?: [];

$cursosAluno = [];
foreach ($cursosAlunoBruto as $cursoRow) {
    $nomeCursoBruto = trim((string) ($cursoRow['nome'] ?? ''));
    $nomeUrlBruto = trim((string) ($cursoRow['nome_url'] ?? ''));
    $indiceBusca = trim($nomeCursoBruto . ' ' . $nomeUrlBruto);
    $nomeCursoNorm = $normalizar($indiceBusca);
    if ($nomeCursoNorm === '') {
        continue;
    }
    $cursosAluno[] = [
        'nome' => $nomeCursoNorm,
        'fundamental' => strpos($nomeCursoNorm, 'fundamental') !== false,
        'medio' => (strpos($nomeCursoNorm, 'medio') !== false) || (strpos($nomeCursoNorm, 'ensino medio') !== false),
    ];
}

$disciplinaCursando = static function (array $cursos, array $palavrasChave, ?string $etapa = null): bool {
    foreach ($cursos as $curso) {
        $nomeCurso = (string) ($curso['nome'] ?? '');
        if ($nomeCurso === '') {
            continue;
        }

        $nomeCursoCompacto = preg_replace('/[^a-z0-9]+/u', '', $nomeCurso) ?? $nomeCurso;
        $achou = false;
        foreach ($palavrasChave as $palavra) {
            $palavraNorm = (string) $palavra;
            if ($palavraNorm === '') {
                continue;
            }

            // Permite regras mais robustas com "token1+token2" (todos os tokens devem existir).
            if (strpos($palavraNorm, '+') !== false) {
                $tokens = array_filter(array_map('trim', explode('+', $palavraNorm)));
                if (!$tokens) {
                    continue;
                }
                $todosPresentes = true;
                foreach ($tokens as $token) {
                    $tokenCompacto = preg_replace('/[^a-z0-9]+/u', '', $token) ?? $token;
                    if ($tokenCompacto === '' || strpos($nomeCursoCompacto, $tokenCompacto) === false) {
                        $todosPresentes = false;
                        break;
                    }
                }
                if ($todosPresentes) {
                    $achou = true;
                    break;
                }
                continue;
            }

            $palavraCompacta = preg_replace('/[^a-z0-9]+/u', '', $palavraNorm) ?? $palavraNorm;
            if (strpos($nomeCurso, $palavraNorm) !== false || ($palavraCompacta !== '' && strpos($nomeCursoCompacto, $palavraCompacta) !== false)) {
                $achou = true;
                break;
            }
        }
        if (!$achou) {
            continue;
        }

        if ($etapa === 'fundamental') {
            if (($curso['medio'] ?? false) && !($curso['fundamental'] ?? false)) {
                continue;
            }
            return true;
        }

        if ($etapa === 'medio') {
            if (($curso['fundamental'] ?? false) && !($curso['medio'] ?? false)) {
                continue;
            }
            return true;
        }

        return true;
    }

    return false;
};

$disciplinasFundamental = [
    ['nome' => 'Língua Portuguesa e Redação', 'x' => $disciplinaCursando($cursosAluno, ['portugues', 'redacao', 'lingua portuguesa'], 'fundamental') ? 'X' : ''],
    ['nome' => 'Língua Inglesa', 'x' => $disciplinaCursando($cursosAluno, ['ingles', 'lingua inglesa'], 'fundamental') ? 'X' : ''],
    ['nome' => 'História', 'x' => $disciplinaCursando($cursosAluno, ['historia'], 'fundamental') ? 'X' : ''],
    ['nome' => 'Matemática', 'x' => $disciplinaCursando($cursosAluno, ['matematica'], 'fundamental') ? 'X' : ''],
    ['nome' => 'Educação Física', 'x' => $disciplinaCursando($cursosAluno, ['educacao fisica', 'educacaofisica', 'ed fisica', 'educacao+fisica'], 'fundamental') ? 'X' : ''],
    ['nome' => 'Ciências', 'x' => $disciplinaCursando($cursosAluno, ['ciencias', 'biologia', 'fisica', 'quimica'], 'fundamental') ? 'X' : ''],
    ['nome' => 'Arte', 'x' => $disciplinaCursando($cursosAluno, ['arte', 'artes'], 'fundamental') ? 'X' : ''],
    ['nome' => 'Geografia', 'x' => $disciplinaCursando($cursosAluno, ['geografia'], 'fundamental') ? 'X' : ''],
];

$disciplinasMedio = [
    ['nome' => 'Língua Portuguesa/', 'x' => $disciplinaCursando($cursosAluno, ['portugues', 'redacao', 'lingua portuguesa'], 'medio') ? 'X' : ''],
    ['nome' => 'Língua Inglesa', 'x' => $disciplinaCursando($cursosAluno, ['ingles', 'lingua inglesa'], 'medio') ? 'X' : ''],
    ['nome' => 'História', 'x' => $disciplinaCursando($cursosAluno, ['historia'], 'medio') ? 'X' : ''],
    ['nome' => 'Matemática', 'x' => $disciplinaCursando($cursosAluno, ['matematica'], 'medio') ? 'X' : ''],
    ['nome' => 'Biologia', 'x' => $disciplinaCursando($cursosAluno, ['biologia'], 'medio') ? 'X' : ''],
    ['nome' => 'Física', 'x' => $disciplinaCursando($cursosAluno, ['fisica'], 'medio') ? 'X' : ''],
    ['nome' => 'Geografia', 'x' => $disciplinaCursando($cursosAluno, ['geografia'], 'medio') ? 'X' : ''],
    ['nome' => 'Química', 'x' => $disciplinaCursando($cursosAluno, ['quimica'], 'medio') ? 'X' : ''],
    ['nome' => 'Arte', 'x' => $disciplinaCursando($cursosAluno, ['arte', 'artes'], 'medio') ? 'X' : ''],
    ['nome' => 'Educação Física', 'x' => $disciplinaCursando($cursosAluno, ['educacao fisica', 'educacaofisica', 'ed fisica', 'educacao+fisica'], 'medio') ? 'X' : ''],
    ['nome' => 'Filosofia', 'x' => $disciplinaCursando($cursosAluno, ['filosofia'], 'medio') ? 'X' : ''],
    ['nome' => 'Sociologia', 'x' => $disciplinaCursando($cursosAluno, ['sociologia'], 'medio') ? 'X' : ''],
];

$html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
@page{size:A4 portrait;margin:8mm 7mm;}
*{box-sizing:border-box;}body{font-family:DejaVu Sans,sans-serif;margin:0;padding:0;color:#111;}
.pagina{width:100%;padding:0;}
.borda{border:none;padding:10mm 9mm 10mm 9mm;background-image:url("' . $esc($fundoRequerimentoUrl) . '");background-size:100% 100%;background-position:center;background-repeat:no-repeat;}
.topo{display:table;width:100%;margin-bottom:8px;}.topo-esq,.topo-dir{display:table-cell;vertical-align:top;}.topo-esq{width:160px;}
.logo{width:92px;height:92px;border:1px solid #ccc;text-align:center;padding:0;overflow:hidden;}.logo img{width:100%;height:100%;display:block;object-fit:cover;}
.topo-dir{text-align:right;font-size:12px;line-height:1.4;color:#0b83cc;}
.titulo{margin:16px 0 16px 0;text-align:center;font-size:13px;font-weight:bold;}
.linha{font-size:12px;margin:2px 0;}.lbl{font-weight:bold;}.campos{display:inline;}
.subtitulo{margin:18px 0 10px 0;text-align:center;font-size:13px;font-weight:bold;}
.tabelas{width:100%;display:table;}.col{display:table-cell;width:50%;vertical-align:top;padding:0 8px;}
table.grade{width:100%;border-collapse:collapse;font-size:11px;}table.grade th,table.grade td{border:1px solid #222;padding:2px 5px;text-align:center;}table.grade td.disc{text-align:left;padding-left:8px;}table.grade td.mk{width:16px;text-align:center;font-weight:bold;}
.rodape-ficha{margin-top:34mm;font-size:12px;}.data-direita{text-align:right;margin-right:18px;margin-bottom:20px;position:relative;top:-55px;}
.linha-ass-1{margin:0 auto 18px auto;width:440px;border-bottom:1px solid #111;}.txt-ass-1{text-align:center;margin-bottom:64px;}
.linha-ass-2{margin:0 auto 4px auto;width:430px;border-bottom:1px solid #111;}.txt-ass-2{text-align:center;font-size:12px;}
</style>
</head>
<body>
<div class="pagina"><div class="borda">
<div class="topo"><div class="topo-esq"><div class="logo"><img src="' . $esc($logoUrl) . '" alt="Logo"></div></div><div class="topo-dir">Educação de Jovens e Adultos<br>Exames de Conclusão do Ensino Fundamental 1º e<br>2º seguimento e Ensino Médio 3º seguimento</div></div>
<div class="titulo">REQUERIMENTO DE INSCRIÇÃO EXAMES DE CONCLUSÃO</div>
<div class="linha"><span class="lbl">Nome:</span> <span class="campos">' . $esc($nome) . '</span></div>
<div class="linha">Local de Nascimento <span class="campos">' . $esc($naturalidade) . '</span>, UF <span class="campos">' . $esc($uf) . '</span>, Data de Nascimento: <span class="campos">' . $esc($dataNascimento) . '</span>.</div>
<div class="linha"><span class="lbl">Nacionalidade:</span> ( x ) Brasileira ( ) Estrangeira, RG: <span class="campos">' . $esc($rg) . '</span>, CPF: <span class="campos">' . $esc($cpf) . '</span>.</div>
<div class="linha">End.: <span class="campos">' . $esc($endereco) . '</span>, nº <span class="campos">' . $esc($numero) . '</span>, CEP <span class="campos">' . $esc($cep) . '</span>, Bairro <span class="campos">' . $esc($bairro) . '</span>, Complemento <span class="campos">' . $esc($complemento) . '</span>.</div>
<div class="linha">Telefone: <span class="campos">' . $esc($telefone) . '</span>, Tel.p/ contato <span class="campos">' . $esc($telefone) . '</span>, Idade: <span class="campos">' . $esc($idade) . '</span>.</div>
<div class="subtitulo">DISCIPLINAS DE INSCRIÇÃO</div>
<div class="tabelas"><div class="col"><table class="grade"><tr><th colspan="2">ENSINO FUNDAMENTAL</th></tr><tr><th colspan="2">DISCIPLINA</th></tr>';

foreach ($disciplinasFundamental as $disc) {
    $html .= '<tr><td class="mk">' . $esc($disc['x']) . '</td><td class="disc">' . $esc($disc['nome']) . '</td></tr>';
}

$html .= '</table></div><div class="col"><table class="grade"><tr><th colspan="2">ENSINO MÉDIO</th></tr><tr><th colspan="2">DISCIPLINA</th></tr>';

foreach ($disciplinasMedio as $disc) {
    $html .= '<tr><td class="mk">' . $esc($disc['x']) . '</td><td class="disc">' . $esc($disc['nome']) . '</td></tr>';
}

$html .= '</table></div></div>
<div class="rodape-ficha"><div class="data-direita">Buritis, ' . $esc($diaAtual) . ' de ' . $esc($mesAtual) . ' de ' . $esc($anoAtual) . '.</div><div class="linha-ass-1"></div><div class="txt-ass-1">Assinatura do responsável pelo Candidato.</div><div class="linha-ass-2"></div><div class="txt-ass-2">Assinatura do responsável pela inscrição</div></div>
</div></div></body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$pdo->exec("CREATE TABLE IF NOT EXISTS documentos_emitidos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 aluno_id INT NOT NULL,
 tipo VARCHAR(30) NOT NULL,
 categoria VARCHAR(30) NULL,
 versao INT NULL,
 arquivo_relativo VARCHAR(255) NOT NULL,
 visivel_aluno TINYINT(1) NOT NULL DEFAULT 1,
 criado_em DATETIME NOT NULL,
 criado_por INT NULL,
 ip VARCHAR(45) NULL,
 INDEX idx_aluno_tipo (aluno_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $stmtColuna = $pdo->query("SHOW COLUMNS FROM documentos_emitidos LIKE 'visivel_aluno'");
    if (!$stmtColuna || !$stmtColuna->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE documentos_emitidos ADD COLUMN visivel_aluno TINYINT(1) NOT NULL DEFAULT 1");
    }
} catch (Throwable $e) {
    // Não interrompe a emissão.
}

// Mantém somente a última ficha: remove fichas anteriores do aluno.
$stmtFichasAntigas = $pdo->prepare("SELECT id, arquivo_relativo FROM documentos_emitidos WHERE aluno_id = :aluno_id AND tipo = 'ficha_inscricao'");
$stmtFichasAntigas->execute([':aluno_id' => $idPessoaAluno]);
$fichasAntigas = $stmtFichasAntigas->fetchAll(PDO::FETCH_ASSOC);

$raizProjeto = realpath(__DIR__ . '/../../..');
foreach ($fichasAntigas as $fichaAntiga) {
    $arquivoRelativoAntigo = ltrim((string) ($fichaAntiga['arquivo_relativo'] ?? ''), '/');
    $arquivoRelativoAntigo = str_replace('\\', '/', $arquivoRelativoAntigo);
    $arquivoRelativoAntigo = preg_replace('#\.\./#', '', $arquivoRelativoAntigo);

    if ($raizProjeto) {
        $arquivoCompletoAntigo = realpath($raizProjeto . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $arquivoRelativoAntigo));
        if ($arquivoCompletoAntigo && strpos($arquivoCompletoAntigo, $raizProjeto) === 0 && is_file($arquivoCompletoAntigo)) {
            @unlink($arquivoCompletoAntigo);
        }
    }
}

$stmtDeleteAntigas = $pdo->prepare("DELETE FROM documentos_emitidos WHERE aluno_id = :aluno_id AND tipo = 'ficha_inscricao'");
$stmtDeleteAntigas->execute([':aluno_id' => $idPessoaAluno]);

$dirDestino = __DIR__ . '/../../documentos/fichas_inscricao/' . $idPessoaAluno;
if (!is_dir($dirDestino) && !mkdir($dirDestino, 0777, true) && !is_dir($dirDestino)) {
    http_response_code(500);
    echo 'Não foi possível criar o diretório do documento.';
    exit();
}

$nomeArquivo = 'FICHA_INSCRICAO_' . $idPessoaAluno . '_' . date('YmdHis') . '.pdf';
$caminhoCompleto = $dirDestino . '/' . $nomeArquivo;
$pdfBinario = $dompdf->output();
file_put_contents($caminhoCompleto, $pdfBinario);

$arquivoRelativo = '/sistema/documentos/fichas_inscricao/' . $idPessoaAluno . '/' . $nomeArquivo;

$stmtInsert = $pdo->prepare("INSERT INTO documentos_emitidos (aluno_id, tipo, categoria, versao, arquivo_relativo, criado_em, criado_por, ip) VALUES (:aluno_id, :tipo, :categoria, :versao, :arquivo_relativo, :criado_em, :criado_por, :ip)");
$stmtInsert->execute([
    ':aluno_id' => $idPessoaAluno,
    ':tipo' => 'ficha_inscricao',
    ':categoria' => 'eja',
    ':versao' => 1,
    ':arquivo_relativo' => $arquivoRelativo,
    ':criado_em' => date('Y-m-d H:i:s'),
    ':criado_por' => $idUsuario,
    ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
]);

$idDocumento = (int) $pdo->lastInsertId();
header('Location: baixar_documento_emitido.php?id=' . $idDocumento . '&view=1');
exit();
