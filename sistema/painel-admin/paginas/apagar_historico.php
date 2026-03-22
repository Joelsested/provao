<?php
require_once('../../conexao.php');
@session_start();

if (!isset($_SESSION['nivel']) || ($_SESSION['nivel'] !== 'Administrador' && $_SESSION['nivel'] !== 'Secretario')) {
    http_response_code(403);
    echo "Acesso negado.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['arquivo'])) {
    $arquivo = $_POST['arquivo'];

    // Seguranca: evita apagar arquivos fora da pasta "historicos"
    $baseDir = realpath(__DIR__ . "/../../../historicos/");
    $arquivo = str_replace('\\', '/', $arquivo);
    $arquivo = ltrim($arquivo, '/');
    $arquivo = preg_replace('#^(\.{2}/)+#', '', $arquivo);
    $arquivoSemHistoricos = preg_replace('#^historicos/#', '', $arquivo);
    $arquivoComHistoricos = 'historicos/' . ltrim($arquivoSemHistoricos, '/');
    $filePath = $baseDir ? realpath($baseDir . '/' . $arquivoSemHistoricos) : false;

    $removerRegistro = static function () use ($pdo, $arquivoComHistoricos, $arquivoSemHistoricos) {
        $caminhos = [
            '/' . ltrim($arquivoComHistoricos, '/'),
            ltrim($arquivoComHistoricos, '/'),
            '/' . ltrim($arquivoSemHistoricos, '/'),
            ltrim($arquivoSemHistoricos, '/')
        ];
        $stmt = $pdo->prepare("
            DELETE FROM documentos_emitidos
            WHERE arquivo_relativo = :c1
               OR arquivo_relativo = :c2
               OR arquivo_relativo = :c3
               OR arquivo_relativo = :c4
        ");
        $stmt->execute([
            ':c1' => $caminhos[0],
            ':c2' => $caminhos[1],
            ':c3' => $caminhos[2],
            ':c4' => $caminhos[3],
        ]);
    };

    if ($filePath !== false && strpos($filePath, $baseDir) === 0 && file_exists($filePath)) {
        if (unlink($filePath)) {
            // Remover registro no banco (se existir)
            try {
                $removerRegistro();
            } catch (Throwable $e) {
                // Nao bloqueia a resposta
            }
            echo "Arquivo removido com sucesso!";
        } else {
            http_response_code(500);
            echo "Erro ao remover o arquivo.";
        }
    } else {
        // Mesmo sem arquivo fisico, tenta remover registro do banco
        try {
            $removerRegistro();
            echo "Registro removido com sucesso!";
        } catch (Throwable $e) {
            http_response_code(400);
            echo "Arquivo invalido.";
        }
    }
}
?>
