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

function aprovarMatriculaRecorrenciaWebhook(PDO $pdo, int $idMatricula): void
{
    if ($idMatricula <= 0) {
        return;
    }

    $stmtMat = $pdo->prepare("SELECT id, id_curso, aluno, professor, pacote, status FROM matriculas WHERE id = :id LIMIT 1");
    $stmtMat->execute([':id' => $idMatricula]);
    $matricula = $stmtMat->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$matricula) {
        return;
    }

    // Libera a matricula principal.
    if ((string) ($matricula['status'] ?? '') !== 'Matriculado') {
        $stmtUpdate = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado' WHERE id = :id LIMIT 1");
        $stmtUpdate->execute([':id' => $idMatricula]);
    }

    // Se nao for pacote, fim do fluxo.
    if (strtolower((string) ($matricula['pacote'] ?? '')) !== 'sim') {
        return;
    }

    $idPacote = (int) ($matricula['id_curso'] ?? 0);
    $idAluno = (int) ($matricula['aluno'] ?? 0);
    if ($idPacote <= 0 || $idAluno <= 0) {
        return;
    }

    $stmtCursosPacote = $pdo->prepare("SELECT cp.id_curso, c.professor FROM cursos_pacotes cp JOIN cursos c ON c.id = cp.id_curso WHERE cp.id_pacote = :id_pacote");
    $stmtCursosPacote->execute([':id_pacote' => $idPacote]);
    $cursosPacote = $stmtCursosPacote->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cursosPacote)) {
        return;
    }

    $stmtExiste = $pdo->prepare("SELECT id FROM matriculas WHERE aluno = :aluno AND id_curso = :id_curso AND (pacote <> 'Sim' OR pacote IS NULL OR pacote = '') LIMIT 1");
    $stmtInsert = $pdo->prepare("INSERT INTO matriculas (id_curso, aluno, professor, aulas_concluidas, data, status, pacote, id_pacote, obs) VALUES (:id_curso, :aluno, :professor, 1, CURDATE(), 'Matriculado', 'Nao', :id_pacote, 'Pacote')");
    $stmtUpdateCurso = $pdo->prepare("UPDATE cursos SET matriculas = COALESCE(matriculas, 0) + 1 WHERE id = :id LIMIT 1");

    foreach ($cursosPacote as $cursoPacote) {
        $idCurso = (int) ($cursoPacote['id_curso'] ?? 0);
        $idProfessor = (int) ($cursoPacote['professor'] ?? 0);
        if ($idCurso <= 0) {
            continue;
        }

        $stmtExiste->execute([
            ':aluno' => $idAluno,
            ':id_curso' => $idCurso,
        ]);
        $jaExiste = (int) ($stmtExiste->fetchColumn() ?: 0) > 0;
        if ($jaExiste) {
            continue;
        }

        $stmtInsert->execute([
            ':id_curso' => $idCurso,
            ':aluno' => $idAluno,
            ':professor' => $idProfessor,
            ':id_pacote' => $idPacote,
        ]);

        $stmtUpdateCurso->execute([':id' => $idCurso]);
    }
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

        if ($idMatricula > 0) {
            aprovarMatriculaRecorrenciaWebhook($pdo, $idMatricula);
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

function statusPagamentoConfirmadoWebhook(string $status): bool
{
    $s = strtolower(trim($status));
    return in_array($s, ['paid', 'approved', 'active', 'settled'], true);
}

function localizarMatriculaPorChargeIdEmLogs(PDO $pdo, string $chargeId): int
{
    $chargeId = trim($chargeId);
    if ($chargeId === '') {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT id_matricula, json_response
        FROM logs_pagamentos
        WHERE json_response LIKE :needle
        ORDER BY id DESC
        LIMIT 50
    ");
    $stmt->execute([':needle' => '%' . $chargeId . '%']);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($registros as $registro) {
        $payload = json_decode((string) ($registro['json_response'] ?? ''), true);
        if (!is_array($payload)) {
            continue;
        }
        $chargePayload = (string) (buscarValorRecursivo($payload, ['charge_id', 'chargeId', 'payment_id'], '') ?: '');
        if ($chargePayload !== '' && $chargePayload === $chargeId) {
            return (int) ($registro['id_matricula'] ?? 0);
        }
    }

    return 0;
}

function atualizarCartaoAvistaViaWebhook(PDO $pdo, array $data): array
{
    $chargeId = (string) (buscarValorRecursivo($data, ['charge_id', 'chargeId', 'payment_id'], '') ?: '');
    $statusOriginal = (string) (buscarValorRecursivo($data, ['current', 'status', 'status_current'], '') ?: '');

    if ($chargeId === '' || $statusOriginal === '') {
        return [
            'ok' => false,
            'msg' => 'payload sem charge_id/status para baixa de cartao a vista',
        ];
    }

    if (!statusPagamentoConfirmadoWebhook($statusOriginal)) {
        return [
            'ok' => true,
            'charge_id' => $chargeId,
            'status' => strtolower(trim($statusOriginal)),
            'matricula_atualizada' => false,
            'msg' => 'status ainda nao confirmado',
        ];
    }

    $idMatricula = localizarMatriculaPorChargeIdEmLogs($pdo, $chargeId);
    if ($idMatricula <= 0) {
        return [
            'ok' => false,
            'charge_id' => $chargeId,
            'msg' => 'matricula nao localizada por charge_id nos logs',
        ];
    }

    $stmt = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado' WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $idMatricula]);

    return [
        'ok' => true,
        'charge_id' => $chargeId,
        'status' => strtolower(trim($statusOriginal)),
        'id_matricula' => $idMatricula,
        'matricula_atualizada' => true,
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
    $resultadoCartaoAvista = atualizarCartaoAvistaViaWebhook($pdo, $data);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'event_type' => $eventType,
        'recorrencia' => $resultadoRecorrencia,
        'cartao_avista' => $resultadoCartaoAvista,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha ao processar webhook EFY',
        'detail' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
