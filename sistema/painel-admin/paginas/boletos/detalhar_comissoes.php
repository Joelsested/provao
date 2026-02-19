<?php
require_once dirname(__DIR__, 3) . '/conexao.php';
require_once dirname(__DIR__, 2) . '/verificar.php';
require_once dirname(__DIR__, 4) . '/efi/boleto.php';
require_once dirname(__DIR__, 4) . '/config/env.php';

@session_start();
header('Content-Type: application/json; charset=utf-8');

$niveisPermitidos = ['Administrador', 'Secretario', 'Tesoureiro', 'Tutor', 'Parceiro', 'Professor', 'Vendedor'];
$nivelSessao = $_SESSION['nivel'] ?? '';

if (!in_array($nivelSessao, $niveisPermitidos, true)) {
    echo json_encode(['ok' => false, 'msg' => 'Nao autorizado.']);
    exit();
}

$chargeId = trim((string) ($_GET['charge_id'] ?? ''));
if ($chargeId === '' || !preg_match('/^[0-9]+$/', $chargeId)) {
    echo json_encode(['ok' => false, 'msg' => 'Charge ID invalido.']);
    exit();
}

try {
    $options = require dirname(__DIR__, 4) . '/efi/options.php';

    $boletoPayment = new EFIBoletoPayment(
        $options['clientId'] ?? '',
        $options['clientSecret'] ?? '',
        !empty($options['sandbox'])
    );

    $consulta = $boletoPayment->consultarCobranca($chargeId);
    $data = $consulta['data'] ?? [];
    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
    $status = (string) ($data['status'] ?? '');
    $totalCentavos = (int) ($data['total'] ?? 0);

    $agregado = [];
    foreach ($items as $item) {
        $repasses = $item['marketplace']['repasses'] ?? [];
        if (!is_array($repasses)) {
            continue;
        }

        foreach ($repasses as $repasse) {
            $payeeCode = trim((string) ($repasse['payee_code'] ?? ''));
            $percentageRaw = (int) ($repasse['percentage'] ?? 0);
            if ($payeeCode === '' || $percentageRaw <= 0) {
                continue;
            }

            if (!isset($agregado[$payeeCode])) {
                $agregado[$payeeCode] = 0;
            }

            $agregado[$payeeCode] += $percentageRaw;
        }
    }

    $mapUsuarios = [];
    $codes = array_keys($agregado);
    if (!empty($codes)) {
        $walletColumn = null;
        $columnCandidates = ['wallet_id', 'identificador_banco'];

        foreach ($columnCandidates as $candidate) {
            $check = $pdo->prepare('SHOW COLUMNS FROM usuarios LIKE :col');
            $check->execute([':col' => $candidate]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $walletColumn = $candidate;
                break;
            }
        }

        if ($walletColumn !== null) {
            $placeholders = implode(',', array_fill(0, count($codes), '?'));
            $stmtUsuarios = $pdo->prepare("SELECT id, nome, nivel, {$walletColumn} AS wallet_code FROM usuarios WHERE {$walletColumn} IN ($placeholders)");
            $stmtUsuarios->execute($codes);
            foreach ($stmtUsuarios->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $walletId = trim((string) ($u['wallet_code'] ?? ''));
                if ($walletId !== '') {
                    $mapUsuarios[$walletId] = [
                        'id' => (int) ($u['id'] ?? 0),
                        'nome' => (string) ($u['nome'] ?? ''),
                        'nivel' => (string) ($u['nivel'] ?? ''),
                    ];
                }
            }
        }
    }

    $repassesOut = [];
    $somaRaw = 0;
    foreach ($agregado as $payeeCode => $percentageRaw) {
        $somaRaw += $percentageRaw;
        $percent = $percentageRaw / 100;
        $valorAproximado = ($totalCentavos * ($percentageRaw / 10000)) / 100;
        $usuario = $mapUsuarios[$payeeCode] ?? null;

        $repassesOut[] = [
            'payee_code' => $payeeCode,
            'percentage_raw' => $percentageRaw,
            'percent' => round($percent, 2),
            'valor_aprox' => round($valorAproximado, 2),
            'usuario_id' => $usuario['id'] ?? null,
            'usuario_nome' => $usuario['nome'] ?? '',
            'usuario_nivel' => $usuario['nivel'] ?? '',
        ];
    }

    usort($repassesOut, static function (array $a, array $b): int {
        return ($b['percentage_raw'] <=> $a['percentage_raw']);
    });

    echo json_encode([
        'ok' => true,
        'charge_id' => $chargeId,
        'sandbox' => !empty($options['sandbox']),
        'status' => $status,
        'total_centavos' => $totalCentavos,
        'total_reais' => round($totalCentavos / 100, 2),
        'soma_raw' => $somaRaw,
        'soma_percent' => round($somaRaw / 100, 2),
        'repasses' => $repassesOut,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage(),
        'charge_id' => $chargeId,
    ], JSON_UNESCAPED_UNICODE);
}