<?php

if (!function_exists('efi_only_block_legacy_gateway')) {
    function efi_only_block_legacy_gateway(string $gatewayLabel = 'Gateway legado'): void
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $wantsJson = stripos($accept, 'application/json') !== false
            || (isset($_SERVER['CONTENT_TYPE']) && stripos((string) $_SERVER['CONTENT_TYPE'], 'application/json') !== false)
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        http_response_code(410);
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'disabled',
                'message' => $gatewayLabel . ' desativado. Este sistema processa pagamentos somente via EFY.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo $gatewayLabel . ' desativado. Este sistema processa pagamentos somente via EFY.';
        exit;
    }
}

