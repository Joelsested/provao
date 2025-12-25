<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['arquivo'])) {
    $arquivo = $_POST['arquivo'];

    // Segurança: evita apagar arquivos fora da pasta "historicos"
    $baseDir = realpath(__DIR__ . "/../../../historicos/");
    $arquivo = str_replace('\\', '/', $arquivo);
    $arquivo = ltrim($arquivo, '/');
    $arquivo = preg_replace('#^(\.{2}/)+#', '', $arquivo);
    $arquivo = preg_replace('#^historicos/#', '', $arquivo);
    $filePath = $baseDir ? realpath($baseDir . '/' . $arquivo) : false;

    if ($filePath !== false && strpos($filePath, $baseDir) === 0 && file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "Arquivo removido com sucesso!";
        } else {
            http_response_code(500);
            echo "Erro ao remover o arquivo.";
        }
    } else {
        http_response_code(400);
        echo "Arquivo inválido.";
    }
}
?>
