<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../config/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo nao permitido.']);
    exit();
}

$destDir = __DIR__ . '/../../sistema/painel-admin/img/contas';
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$upload = upload_handle($_FILES['photo'] ?? [], $destDir, $allowedExt, $allowedMime, 5 * 1024 * 1024, date('Y-m-d-H-i-s') . '-', false);

if (!$upload['ok']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $upload['error'] ?? 'Falha no upload.']);
    exit();
}

echo json_encode(['success' => true, 'arquivo' => $upload['filename']]);
?>
