<?php
session_start();

$tempo_limite = 30 * 60; // 30 minutos

$response = ["expired" => false];

if (isset($_SESSION['last_activity'])) {
    $tempo_inativo = time() - $_SESSION['last_activity'];

    if ($tempo_inativo > $tempo_limite) {
        // Sessão expirada
        session_unset();
        session_destroy();
        $response["expired"] = true;
    } else {
        // Atualiza last_activity se ainda válida
        $_SESSION['last_activity'] = time();
    }
} else {
    // Nenhuma sessão iniciada
    $response["expired"] = true;
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
