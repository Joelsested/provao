<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/config/webhook.php';
require_once __DIR__ . '/sistema/conexao.php';
require_once __DIR__ . '/efi/boleto.php';

webhook_require_token();

$webhookLogFile = __DIR__ . '/logs/efi_webhook_boleto.log';
if (!is_dir(dirname($webhookLogFile))) {
    @mkdir(dirname($webhookLogFile), 0775, true);
}

function fileWebhookLog(string $message): void
{
    global $webhookLogFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($webhookLogFile, $line, FILE_APPEND);
}

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array((int) $err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        fileWebhookLog('FATAL: ' . ($err['message'] ?? '') . ' em ' . ($err['file'] ?? '') . ':' . ($err['line'] ?? ''));
    }
});

/**
 * Registra log em tabela, sem quebrar o webhook se a tabela nao existir.
 */
function webhookLog(PDO $pdo, string $eventType, array $payload): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO webhook_logs (event_type, payload, received_at) VALUES (:event_type, :payload, NOW())");
        $stmt->execute([
            ':event_type' => $eventType,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $e) {
        error_log('[EFI BOLETO WEBHOOK] Falha ao gravar webhook_logs: ' . $e->getMessage());
        fileWebhookLog('Falha webhook_logs: ' . $e->getMessage());
    }
}

/**
 * Extrai token "notification" tanto de form-urlencoded quanto JSON.
 */
function extrairNotificationToken(string $rawBody): string
{
    $rawBody = trim($rawBody);

    if (isset($_POST['notification']) && trim((string) $_POST['notification']) !== '') {
        return trim((string) $_POST['notification']);
    }

    if ($rawBody === '') {
        return '';
    }

    $json = json_decode($rawBody, true);
    if (is_array($json)) {
        foreach (['notification', 'notification_token', 'token'] as $k) {
            if (!empty($json[$k])) {
                return trim((string) $json[$k]);
            }
        }
    }

    $parsed = [];
    parse_str($rawBody, $parsed);
    if (!empty($parsed['notification'])) {
        return trim((string) $parsed['notification']);
    }

    if (strpos($rawBody, '=') !== false) {
        [$key, $value] = explode('=', $rawBody, 2);
        if (trim((string) $key) === 'notification') {
            return trim((string) $value);
        }
    }

    return '';
}

/**
 * Atualiza status do pagamento e matricula com idempotencia.
 */
function atualizarStatusBoleto(PDO $pdo, int $chargeId, string $statusGateway): array
{
    $statusGateway = strtolower(trim($statusGateway));

    $stmtPg = $pdo->prepare("SELECT id, id_matricula, status FROM pagamentos_boleto WHERE charge_id = :charge_id LIMIT 1");
    $stmtPg->execute([':charge_id' => $chargeId]);
    $pagamento = $stmtPg->fetch(PDO::FETCH_ASSOC);

    if (!$pagamento) {
        return [
            'atualizado' => false,
            'motivo' => 'pagamento_boleto_nao_encontrado',
            'id_matricula' => null,
        ];
    }

    $idMatricula = (int) ($pagamento['id_matricula'] ?? 0);

    $stmtUpdPg = $pdo->prepare("UPDATE pagamentos_boleto SET status = :status WHERE id = :id");
    $stmtUpdPg->execute([
        ':status' => $statusGateway,
        ':id' => (int) $pagamento['id'],
    ]);

    $matriculaAtualizada = false;
    if ($idMatricula > 0 && in_array($statusGateway, ['paid', 'settled'], true)) {
        $stmtMat = $pdo->prepare("UPDATE matriculas SET status = 'Matriculado' WHERE id = :id");
        $stmtMat->execute([':id' => $idMatricula]);
        $matriculaAtualizada = true;

        // Se for pacote, libera cursos individuais nao matriculados ainda.
        $stmtInfo = $pdo->prepare("SELECT id_curso, aluno, pacote FROM matriculas WHERE id = :id LIMIT 1");
        $stmtInfo->execute([':id' => $idMatricula]);
        $matInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if ($matInfo && strtolower((string) ($matInfo['pacote'] ?? '')) === 'sim') {
            $idPacote = (int) ($matInfo['id_curso'] ?? 0);
            $idAluno = (int) ($matInfo['aluno'] ?? 0);

            if ($idPacote > 0 && $idAluno > 0) {
                $sql = "
                    INSERT INTO matriculas (id_curso, aluno, professor, aulas_concluidas, data, status, pacote, id_pacote, obs)
                    SELECT
                        cp.id_curso,
                        :aluno,
                        c.professor,
                        1,
                        CURDATE(),
                        'Matriculado',
                        'Não',
                        :id_pacote,
                        'Pacote'
                    FROM cursos_pacotes cp
                    JOIN cursos c ON c.id = cp.id_curso
                    WHERE cp.id_pacote = :id_pacote
                      AND NOT EXISTS (
                        SELECT 1
                        FROM matriculas m
                        WHERE m.aluno = :aluno
                          AND m.id_pacote = :id_pacote
                          AND m.id_curso = cp.id_curso
                      )
                ";
                $stmtIns = $pdo->prepare($sql);
                $stmtIns->execute([
                    ':aluno' => $idAluno,
                    ':id_pacote' => $idPacote,
                ]);
            }
        }
    }

    return [
        'atualizado' => true,
        'motivo' => 'ok',
        'id_matricula' => $idMatricula > 0 ? $idMatricula : null,
        'matricula_atualizada' => $matriculaAtualizada,
    ];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
        exit;
    }

    $rawBody = file_get_contents('php://input') ?: '';
    fileWebhookLog('REQ method=' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '') . ' body=' . $rawBody);
    $notificationToken = extrairNotificationToken($rawBody);

    if ($notificationToken === '') {
        webhookLog($pdo, 'boleto_webhook_invalido', [
            'erro' => 'notification_token_ausente',
            'raw' => $rawBody,
        ]);
        http_response_code(200);
        echo json_encode(['success' => true, 'ignored' => true, 'message' => 'Notification token ausente.']);
        exit;
    }

    $options = require __DIR__ . '/efi/options.php';
    $boleto = new EFIBoletoPayment(
        (string) ($options['clientId'] ?? ''),
        (string) ($options['clientSecret'] ?? ''),
        (bool) ($options['sandbox'] ?? false)
    );

    $consulta = $boleto->consultarWebhook($notificationToken);
    fileWebhookLog('CONSULTA notification=' . $notificationToken . ' retorno=' . json_encode($consulta, JSON_UNESCAPED_UNICODE));
    $eventos = (array) ($consulta['data'] ?? []);

    if (empty($eventos)) {
        webhookLog($pdo, 'boleto_webhook_sem_eventos', [
            'notification' => $notificationToken,
            'consulta' => $consulta,
        ]);
        http_response_code(200);
        echo json_encode(['success' => true, 'ignored' => true, 'message' => 'Sem eventos para notificação.']);
        exit;
    }

    $ultimo = end($eventos);
    $chargeId = (int) (($ultimo['identifiers']['charge_id'] ?? 0));
    $status = (string) ($ultimo['status']['current'] ?? '');

    if ($chargeId <= 0 || $status === '') {
        webhookLog($pdo, 'boleto_webhook_evento_invalido', [
            'notification' => $notificationToken,
            'ultimo_evento' => $ultimo,
        ]);
        http_response_code(200);
        echo json_encode(['success' => true, 'ignored' => true, 'message' => 'Evento sem charge/status.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $resultado = atualizarStatusBoleto($pdo, $chargeId, $status);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    webhookLog($pdo, 'boleto_webhook_processado', [
        'notification' => $notificationToken,
        'charge_id' => $chargeId,
        'status' => $status,
        'resultado' => $resultado,
        'ultimo_evento' => $ultimo,
    ]);

    http_response_code(200);
    fileWebhookLog('OK charge_id=' . $chargeId . ' status=' . $status);
    echo json_encode([
        'success' => true,
        'charge_id' => $chargeId,
        'status' => $status,
        'resultado' => $resultado,
    ]);
} catch (Throwable $e) {
    error_log('[EFI BOLETO WEBHOOK] Erro: ' . $e->getMessage());
    fileWebhookLog('EXCEPTION: ' . $e->getMessage());
    webhookLog($pdo, 'boleto_webhook_erro', [
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    // Para evitar loop de reenvio com 500 no painel da EFI:
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'ignored' => true,
        'message' => 'Webhook recebido, mas com erro interno registrado.',
    ]);
}
