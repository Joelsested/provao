<?php

require_once __DIR__ . '/config/webhook.php';
webhook_require_token();

require_once("sistema/conexao.php");

function buscarValorRecursivo(array $arr, array $chaves, $default = null)
{
    foreach ($chaves as $chave) {
        if (array_key_exists($chave, $arr) && $arr[$chave] !== null && $arr[$chave] !== '') {
            return $arr[$chave];
        }
    }

    foreach ($arr as $valor) {
        if (is_array($valor)) {
            $achado = buscarValorRecursivo($valor, $chaves, '__nao_encontrado__');
            if ($achado !== '__nao_encontrado__') {
                return $achado;
            }
        }
    }

    return $default;
}

function mapearStatusAssinaturaInterno(string $status): string
{
    $s = strtolower(trim($status));
    if ($s === '') {
        return 'PENDENTE';
    }

    if (in_array($s, ['paid', 'approved', 'active', 'settled'], true)) {
        return 'ATIVA';
    }
    if (in_array($s, ['canceled', 'cancelled'], true)) {
        return 'CANCELADA';
    }
    if (in_array($s, ['unpaid', 'refused', 'rejected', 'chargeback'], true)) {
        return 'FALHA';
    }

    return 'PENDENTE';
}

function atualizarRecorrenciaCartaoViaWebhook(PDO $pdo, array $data): array
{
    $subscriptionId = (int) (buscarValorRecursivo($data, ['subscription_id', 'subscriptionId', 'id_subscription'], 0) ?: 0);
    $chargeId = (string) (buscarValorRecursivo($data, ['charge_id', 'chargeId', 'payment_id'], '') ?: '');
    $statusOriginal = (string) (buscarValorRecursivo($data, ['current', 'status', 'status_current'], '') ?: '');
    $statusAssinatura = mapearStatusAssinaturaInterno($statusOriginal);
    $payloadJson = json_encode($data, JSON_UNESCAPED_UNICODE);

    if ($subscriptionId <= 0) {
        return [
            'ok' => false,
            'msg' => 'subscription_id nao encontrado no payload',
        ];
    }

    $stmtAssinatura = $pdo->prepare("SELECT id, id_matricula FROM efi_assinaturas_cartao WHERE subscription_id = :subscription_id ORDER BY id DESC LIMIT 1");
    $stmtAssinatura->execute([':subscription_id' => $subscriptionId]);
    $assinatura = $stmtAssinatura->fetch(PDO::FETCH_ASSOC);
    if (!$assinatura) {
        return [
            'ok' => false,
            'msg' => 'assinatura nao encontrada no banco',
            'subscription_id' => $subscriptionId,
        ];
    }

    $idAssinatura = (int) ($assinatura['id'] ?? 0);
    $idMatricula = (int) ($assinatura['id_matricula'] ?? 0);

    $stmtUpdateAss = $pdo->prepare("
        UPDATE efi_assinaturas_cartao
        SET status = :status, updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
        LIMIT 1
    ");
    $stmtUpdateAss->execute([
        ':status' => $statusAssinatura,
        ':id' => $idAssinatura,
    ]);

    $parcelaAtualizada = null;
    if ($statusAssinatura === 'ATIVA') {
        $stmtParcela = $pdo->prepare("
            SELECT id, numero_parcela
            FROM efi_assinaturas_cartao_parcelas
            WHERE id_assinatura = :id_assinatura
              AND status IN ('PENDENTE', 'ATRASADA')
            ORDER BY numero_parcela ASC
            LIMIT 1
        ");
        $stmtParcela->execute([':id_assinatura' => $idAssinatura]);
        $parcela = $stmtParcela->fetch(PDO::FETCH_ASSOC);
        if ($parcela) {
            $parcelaId = (int) ($parcela['id'] ?? 0);
            $stmtUpdateParcela = $pdo->prepare("
                UPDATE efi_assinaturas_cartao_parcelas
                SET status = 'PAGA',
                    charge_id = COALESCE(:charge_id, charge_id),
                    data_pagamento = NOW(),
                    payload = :payload,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                LIMIT 1
            ");
            $stmtUpdateParcela->execute([
                ':charge_id' => $chargeId !== '' ? $chargeId : null,
                ':payload' => $payloadJson,
                ':id' => $parcelaId,
            ]);

            $parcelaAtualizada = [
                'id' => $parcelaId,
                'numero_parcela' => (int) ($parcela['numero_parcela'] ?? 0),
            ];
        }
    }

    if (in_array($statusAssinatura, ['CANCELADA', 'FALHA'], true)) {
        $stmtPendentes = $pdo->prepare("
            UPDATE efi_assinaturas_cartao_parcelas
            SET status = 'CANCELADA',
                payload = COALESCE(payload, :payload),
                updated_at = CURRENT_TIMESTAMP
            WHERE id_assinatura = :id_assinatura
              AND status IN ('PENDENTE', 'ATRASADA')
        ");
        $stmtPendentes->execute([
            ':payload' => $payloadJson,
            ':id_assinatura' => $idAssinatura,
        ]);
    }

    return [
        'ok' => true,
        'subscription_id' => $subscriptionId,
        'status' => $statusAssinatura,
        'charge_id' => $chargeId,
        'id_matricula' => $idMatricula,
        'parcela_atualizada' => $parcelaAtualizada,
    ];
}

try {
    $input = file_get_contents('php://input');

    try {
        $pdo->exec("
            UPDATE efi_assinaturas_cartao_parcelas
            SET status = 'ATRASADA',
                updated_at = CURRENT_TIMESTAMP
            WHERE status = 'PENDENTE'
              AND vencimento IS NOT NULL
              AND vencimento < CURDATE()
        ");
    } catch (Throwable $e) {
        // Mantem processamento do webhook mesmo se a atualizacao preventiva falhar.
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = ['raw' => $input];
    }

    $eventType = $data['event'] ?? $data['type'] ?? 'efi_webhook';
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
    $receivedAt = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO webhook_logs (event_type, payload, received_at) VALUES (?, ?, ?)");
    $stmt->execute([$eventType, $payload, $receivedAt]);

    $resultadoRecorrencia = ['ok' => false, 'msg' => 'nao processado'];
    $temEstruturaRec = $pdo->query("SHOW TABLES LIKE 'efi_assinaturas_cartao'")->fetch(PDO::FETCH_NUM);
    if ($temEstruturaRec) {
        $resultadoRecorrencia = atualizarRecorrenciaCartaoViaWebhook($pdo, $data);
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'event_type' => $eventType,
        'recorrencia' => $resultadoRecorrencia,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha ao processar webhook EFY',
        'detail' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
