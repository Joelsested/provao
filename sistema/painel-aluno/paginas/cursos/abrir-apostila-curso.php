<?php

declare(strict_types=1);

require_once("../../../conexao.php");

$idCurso = (int) ($_GET['curso'] ?? 0);
if ($idCurso <= 0) {
    http_response_code(400);
    echo 'Curso inválido.';
    exit;
}

$query = $pdo->prepare("SELECT apostila FROM aulas WHERE curso = :curso AND TRIM(IFNULL(apostila, '')) <> '' ORDER BY COALESCE(NULLIF(sequencia_aula, 0), 999999), COALESCE(NULLIF(num_aula, 0), 999999), id ASC LIMIT 1");
$query->execute([':curso' => $idCurso]);
$arquivo = (string) ($query->fetchColumn() ?: '');

if ($arquivo === '') {
    http_response_code(404);
    echo 'Este curso não possui apostila cadastrada.';
    exit;
}

$download = ((string) ($_GET['download'] ?? '0')) === '1' ? '1' : '0';
$destino = rtrim((string) $url_sistema, '/') . '/sistema/painel-aluno/paginas/cursos/abrir-apostila.php?arquivo=' . rawurlencode($arquivo) . '&download=' . $download;
header('Location: ' . $destino, true, 302);
exit;
