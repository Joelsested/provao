<?php
function upload_sanitize_base(string $name): string
{
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
    $base = trim($base, '_');
    return $base !== '' ? $base : 'arquivo';
}

function upload_handle(array $file, string $destDir, array $allowedExt, array $allowedMime, int $maxBytes, string $prefix = '', bool $allowEmpty = true): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $allowEmpty ? ['ok' => true, 'skipped' => true, 'filename' => null] : ['ok' => false, 'error' => 'Arquivo nao enviado.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Falha no upload.'];
    }

    $size = $file['size'] ?? 0;
    if ($size <= 0 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'Tamanho de arquivo invalido.'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'Extensao nao permitida.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime === false || (!empty($allowedMime) && !in_array($mime, $allowedMime, true))) {
        return ['ok' => false, 'error' => 'Tipo de arquivo invalido.'];
    }

    if (strpos($mime, 'image/') === 0 && @getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'Arquivo de imagem invalido.'];
    }

    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            return ['ok' => false, 'error' => 'Diretorio de upload indisponivel.'];
        }
    }

    $base = upload_sanitize_base($file['name'] ?? '');
    $rand = bin2hex(random_bytes(6));
    $filename = $prefix . $base . '_' . $rand . '.' . $ext;
    $destino = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        return ['ok' => false, 'error' => 'Nao foi possivel salvar o arquivo.'];
    }

    @chmod($destino, 0644);

    return ['ok' => true, 'filename' => $filename, 'path' => $destino];
}

function upload_handle_fixed(array $file, string $destPath, array $allowedExt, array $allowedMime, int $maxBytes, bool $allowEmpty = true): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $allowEmpty ? ['ok' => true, 'skipped' => true, 'filename' => null] : ['ok' => false, 'error' => 'Arquivo nao enviado.'];
    }

    $destDir = dirname($destPath);
    $result = upload_handle($file, $destDir, $allowedExt, $allowedMime, $maxBytes, '', false);
    if (!$result['ok']) {
        return $result;
    }

    $tempPath = $result['path'] ?? '';
    if ($tempPath === '' || !file_exists($tempPath)) {
        return ['ok' => false, 'error' => 'Falha ao salvar o arquivo.'];
    }

    if (!rename($tempPath, $destPath)) {
        @unlink($tempPath);
        return ['ok' => false, 'error' => 'Nao foi possivel salvar o arquivo.'];
    }

    @chmod($destPath, 0644);

    return ['ok' => true, 'filename' => basename($destPath), 'path' => $destPath];
}
