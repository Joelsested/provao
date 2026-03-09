<?php

declare(strict_types=1);

$arquivoParam = trim((string) ($_GET['arquivo'] ?? ''));
$forcarDownload = ((string) ($_GET['download'] ?? '0')) === '1';
if ($arquivoParam === '') {
    http_response_code(400);
    echo 'Arquivo da apostila não informado.';
    exit;
}

if (preg_match('#^https?://#i', $arquivoParam)) {
    header('Location: ' . $arquivoParam, true, 302);
    exit;
}

$arquivoParam = str_replace('\\', '/', $arquivoParam);
$arquivoParam = ltrim($arquivoParam, '/');
$arquivoNome = basename($arquivoParam);

$raizSistema = dirname(__DIR__, 3);
$diretorioArquivos = $raizSistema . '/painel-admin/img/arquivos';
$baseReal = realpath($diretorioArquivos) ?: '';

function caminhoValido(string $caminho, string $baseReal): bool
{
    if ($baseReal === '') {
        return false;
    }
    $real = realpath($caminho);
    return $real !== false
        && is_file($real)
        && strncmp($real, $baseReal, strlen($baseReal)) === 0;
}

$caminhoArquivo = '';
$candidatos = [
    $diretorioArquivos . '/' . $arquivoParam,
    $diretorioArquivos . '/' . $arquivoNome,
];

foreach ($candidatos as $candidato) {
    if (caminhoValido($candidato, $baseReal)) {
        $caminhoArquivo = (string) realpath($candidato);
        break;
    }
}

if ($caminhoArquivo === '' && $arquivoNome !== '') {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($diretorioArquivos, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }
        if (strcasecmp($item->getFilename(), $arquivoNome) === 0) {
            $possivel = $item->getPathname();
            if (caminhoValido($possivel, $baseReal)) {
                $caminhoArquivo = (string) realpath($possivel);
                break;
            }
        }
    }
}

if ($caminhoArquivo === '') {
    http_response_code(404);
    echo 'Apostila não encontrada.';
    exit;
}

$mime = function_exists('mime_content_type') ? (string) mime_content_type($caminhoArquivo) : '';
if ($mime === '') {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($caminhoArquivo));
if ($forcarDownload) {
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode(basename($caminhoArquivo)));
} else {
    header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode(basename($caminhoArquivo)));
}
readfile($caminhoArquivo);
exit;
