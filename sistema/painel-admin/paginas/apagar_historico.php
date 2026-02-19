<?php
require_once('../conexao.php');
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
    $arquivo = preg_replace('#^historicos/#', '', $arquivo);
    $filePath = $baseDir ? realpath($baseDir . '/' . $arquivo) : false;

    $arquivoRel = '/' . ltrim($arquivo, '/');

    if ($filePath !== false && strpos($filePath, $baseDir) === 0 && file_exists($filePath)) {
        if (unlink($filePath)) {
            // Remover registro no banco (se existir)
            try {
                $stmt = $pdo->prepare("DELETE FROM documentos_emitidos WHERE arquivo_relativo = :arquivo OR arquivo_relativo = :arquivo_sem_barra");
                $stmt->execute([
                    ':arquivo' => $arquivoRel,
                    ':arquivo_sem_barra' => ltrim($arquivoRel, '/')
                ]);
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
            $stmt = $pdo->prepare("DELETE FROM documentos_emitidos WHERE arquivo_relativo = :arquivo OR arquivo_relativo = :arquivo_sem_barra");
            $stmt->execute([
                ':arquivo' => $arquivoRel,
                ':arquivo_sem_barra' => ltrim($arquivoRel, '/')
            ]);
            echo "Registro removido com sucesso!";
        } catch (Throwable $e) {
            http_response_code(400);
            echo "Arquivo invalido.";
        }
    }
}
?>
