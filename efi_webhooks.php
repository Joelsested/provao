<?php

require_once __DIR__ . '/config/webhook.php';
webhook_require_token();

require_once("sistema/conexao.php");

// Lê o conteúdo bruto enviado
$input = file_get_contents('php://input');

// Tenta decodificar como JSON
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = ['raw' => $input];
}

// Extrai tipo de evento, se disponível
$eventType = $data['event'] ?? $data['type'] ?? 'unknown';

// Prepara os dados para inserção
$payload = json_encode($data, JSON_UNESCAPED_UNICODE);
$receivedAt = date('Y-m-d H:i:s');

// Insere no banco
$stmt = $pdo->prepare("INSERT INTO webhook_logs (event_type, payload, received_at) VALUES (?, ?, ?)");
$stmt->execute([$eventType, $payload, $receivedAt]);

// Retorna resposta para o gateway
http_response_code(200);
echo json_encode(['status' => 'success']);
