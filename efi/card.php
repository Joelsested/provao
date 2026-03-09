<?php

class EFICreditCardPayment
{
    private $clientId;
    private $clientSecret;
    private $sandbox;
    private $baseUrl;

    public function __construct($clientId, $clientSecret, $sandbox = false)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->sandbox = $sandbox;
        $this->baseUrl = $sandbox ? 'https://sandbox.gerencianet.com.br' : 'https://api.gerencianet.com.br';
    }

    private function registrarDebugGateway(array $dados): void
    {
        $paths = [
            __DIR__ . '/../logs/efi_card_gateway.log',
            __DIR__ . '/efi_card_gateway.log',
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'efi_card_gateway.log',
        ];

        $linha = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($dados, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        foreach ($paths as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $ok = @file_put_contents($path, $linha, FILE_APPEND);
            if ($ok !== false) {
                break;
            }
        }
    }

    private function getAccessToken()
    {
        $url = $this->baseUrl . '/v1/authorize';

        $postData = json_encode([
            'grant_type' => 'client_credentials'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Erro na autenticação Cartão: ' . $response);
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    public function createCreditCardCharge($dados)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            throw new Exception('Não foi possível obter o token de acesso');
        }

        // Criar a cobrança
        $chargeId = $this->createCharge($dados, $token);

        // Definir pagamento via cartão
        $paymentData = $this->payWithCreditCard($chargeId, $dados, $token);

        return $paymentData;
    }

    public function createRecurringSubscription(array $dados): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            throw new Exception('Não foi possível obter o token de acesso');
        }

        $repeats = (int) ($dados['installments'] ?? 2);
        if ($repeats < 2) {
            $repeats = 2;
        }
        if ($repeats > 24) {
            $repeats = 24;
        }

        $valorTotal = round((float) ($dados['valor'] ?? 0), 2);
        if ($valorTotal <= 0) {
            throw new Exception('Valor inválido para assinatura recorrente.');
        }

        $valorParcela = round($valorTotal / $repeats, 2);
        if ($valorParcela <= 0) {
            throw new Exception('Valor por recorrência inválido.');
        }

        $planId = $this->createPlan($dados, $token, $repeats);
        $subscriptionId = $this->createSubscription($planId, $dados, $token, $valorParcela);
        $paymentData = $this->paySubscriptionWithCreditCard($subscriptionId, $dados, $token);

        return [
            'subscription_id' => $subscriptionId,
            'charge_id' => $paymentData['charge_id'] ?? null,
            'status' => $paymentData['status'] ?? 'new',
            'total' => $valorTotal,
            'payment_data' => $paymentData['raw'] ?? $paymentData
        ];
    }

    public function payExistingRecurringSubscription(int $subscriptionId, array $dados): array
    {
        if ($subscriptionId <= 0) {
            throw new Exception('subscription_id inválido para reprocessamento.');
        }

        $token = $this->getAccessToken();
        if (!$token) {
            throw new Exception('Não foi possível obter o token de acesso');
        }

        $paymentData = $this->paySubscriptionWithCreditCard($subscriptionId, $dados, $token);
        $total = round((float) ($dados['valor'] ?? 0), 2);

        return [
            'subscription_id' => $subscriptionId,
            'charge_id' => $paymentData['charge_id'] ?? null,
            'status' => $paymentData['status'] ?? 'new',
            'total' => $total,
            'payment_data' => $paymentData['raw'] ?? $paymentData
        ];
    }

    private function createPlan(array $dados, string $token, int $repeats): int
    {
        $url = $this->baseUrl . '/v1/plan';
        $nomeBase = trim((string) ($dados['item_nome'] ?? 'Plano Recorrente'));
        if ($nomeBase === '') {
            $nomeBase = 'Plano Recorrente';
        }

        $body = [
            'name' => substr($nomeBase . ' - Assinatura', 0, 255),
            'interval' => 1,
            'repeats' => $repeats,
        ];

        $this->registrarDebugGateway([
            'flow' => 'recurring_plan_create',
            'sandbox' => $this->sandbox ? 'sim' : 'nao',
            'base_url' => $this->baseUrl,
            'repeats' => $repeats,
            'body' => $body,
        ]);

        [$httpCode, $response] = $this->requestJson('POST', $url, $token, $body);

        $this->registrarDebugGateway([
            'flow' => 'recurring_plan_create',
            'http_code' => $httpCode,
            'response' => $response,
        ]);

        if (!in_array($httpCode, [200, 201], true)) {
            throw new Exception('Erro ao criar plano recorrente: ' . $response);
        }

        $data = json_decode((string) $response, true);
        $planId = (int) ($data['data']['plan_id'] ?? 0);
        if ($planId <= 0) {
            throw new Exception('Não foi possível obter plan_id na resposta da EFY.');
        }

        return $planId;
    }

    private function createSubscription(int $planId, array $dados, string $token, float $valorParcela): int
    {
        $url = $this->baseUrl . '/v1/plan/' . $planId . '/subscription';
        $item = [
            'name' => $dados['item_nome'] ?? 'Assinatura',
            'value' => (int) round($valorParcela * 100),
            'amount' => 1,
        ];

        $body = [
            'items' => [$item],
            'metadata' => [
                'notification_url' => $dados['notification_url'] ?? null
            ]
        ];

        $this->registrarDebugGateway([
            'flow' => 'recurring_subscription_create',
            'plan_id' => $planId,
            'valor_parcela' => $valorParcela,
            'body' => $body,
        ]);

        [$httpCode, $response] = $this->requestJson('POST', $url, $token, $body);

        $this->registrarDebugGateway([
            'flow' => 'recurring_subscription_create',
            'plan_id' => $planId,
            'http_code' => $httpCode,
            'response' => $response,
        ]);

        if (!in_array($httpCode, [200, 201], true)) {
            throw new Exception('Erro ao criar assinatura recorrente: ' . $response);
        }

        $data = json_decode((string) $response, true);
        $subscriptionId = (int) ($data['data']['subscription_id'] ?? 0);
        if ($subscriptionId <= 0) {
            throw new Exception('Não foi possível obter subscription_id na resposta da EFY.');
        }

        return $subscriptionId;
    }

    private function paySubscriptionWithCreditCard(int $subscriptionId, array $dados, string $token): array
    {
        $url = $this->baseUrl . '/v1/subscription/' . $subscriptionId . '/pay';
        $body = [
            'payment' => [
                'credit_card' => [
                    'billing_address' => [
                        'street' => $dados['street'],
                        'number' => $dados['number'],
                        'neighborhood' => $dados['neighborhood'],
                        'zipcode' => $dados['zipcode'],
                        'city' => $dados['city'],
                        'state' => $dados['state']
                    ],
                    'customer' => [
                        'name' => $dados['nome'],
                        'email' => $dados['email'],
                        'cpf' => preg_replace('/\D/', '', $dados['cpf']),
                        'birth' => '1995-10-27',
                        'phone_number' => '11961722303'
                    ],
                    'payment_token' => $dados['credit_card_token']
                ]
            ]
        ];

        $this->registrarDebugGateway([
            'flow' => 'recurring_subscription_pay',
            'subscription_id' => $subscriptionId,
            'token_len' => strlen((string) ($dados['credit_card_token'] ?? '')),
            'body' => [
                'payment' => [
                    'credit_card' => [
                        'payment_token' => '***',
                        'billing_address' => $body['payment']['credit_card']['billing_address'],
                        'customer' => [
                            'name' => $body['payment']['credit_card']['customer']['name'] ?? '',
                            'email' => $body['payment']['credit_card']['customer']['email'] ?? '',
                            'cpf_len' => strlen((string) ($body['payment']['credit_card']['customer']['cpf'] ?? '')),
                        ],
                    ],
                ],
            ],
        ]);

        [$httpCode, $response] = $this->requestJson('POST', $url, $token, $body);

        $this->registrarDebugGateway([
            'flow' => 'recurring_subscription_pay',
            'subscription_id' => $subscriptionId,
            'http_code' => $httpCode,
            'response' => $response,
        ]);

        if (!in_array($httpCode, [200, 201], true)) {
            if (
                strpos((string) $response, '3500010') !== false
                && strpos((string) $response, 'payment_token') !== false
            ) {
                throw new Exception('Token de pagamento inválido para a conta/ambiente atual. Gere o token com o identificador de conta correto (EFI_CARD_ACCOUNT_HOMOLOG/PROD) e mesmas credenciais do backend.');
            }
            throw new Exception('Erro ao processar pagamento recorrente com cartão: ' . $response);
        }

        $data = json_decode((string) $response, true);
        $raw = $data['data'] ?? [];

        return [
            'status' => $raw['status'] ?? ($raw['subscription']['status'] ?? 'new'),
            'charge_id' => $raw['charge_id'] ?? null,
            'raw' => $raw,
        ];
    }

    private function requestJson(string $method, string $url, string $token, ?array $body = null): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            $erro = curl_error($ch);
            curl_close($ch);
            throw new Exception('Erro cURL: ' . $erro);
        }

        curl_close($ch);
        return [$httpCode, (string) $response];
    }

    private function createCharge($dados, $token)
    {
        $url = $this->baseUrl . '/v1/charge';

        $items = [];
        if (isset($dados['items']) && is_array($dados['items'])) {
            $items = $dados['items'];
        } else {
            $item = [
                'name' => $dados['item_nome'] ?? 'Produto/Serviço',
                'value' => (int) ($dados['valor'] * 100),
                'amount' => $dados['quantidade'] ?? 1,
            ];

            if (!empty($dados['repasses']) && is_array($dados['repasses'])) {
                $item['marketplace'] = [
                    'repasses' => $dados['repasses']
                ];
            }

            $items[] = $item;
        }

        $metadata = ['notification_url' => $dados['notification_url'] ?? null];
        $body = [
            'items' => $items,
            'metadata' => $metadata
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Erro ao criar cobrança: ' . $response);
        }

        $data = json_decode($response, true);
        return $data['data']['charge_id'] ?? null;
    }

    private function payWithCreditCard($chargeId, $dados, $token)
    {
        $url = $this->baseUrl . '/v1/charge/' . $chargeId . '/pay';

        $body = [
            'payment' => [
                'credit_card' => [
                    'installments' => (int) ($dados['installments'] ?? 1),
                    'billing_address' => [
                        'street' => $dados['street'],
                        'number' => $dados['number'],
                        'neighborhood' => $dados['neighborhood'],
                        'zipcode' => $dados['zipcode'],
                        'city' => $dados['city'],
                        'state' => $dados['state']
                    ],
                    'customer' => [
                        'name' => $dados['nome'],
                        'email' => $dados['email'],
                        'cpf' => preg_replace('/\D/', '', $dados['cpf']),
                        'birth' => '1995-10-27',
                        'phone_number' => '11961722303'
                    ],
                    'payment_token' => $dados['credit_card_token'] // gerado pelo SDK JS da Efí
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $this->registrarDebugGateway([
            'flow' => 'credit_card_pay',
            'attempt' => 1,
            'sandbox' => $this->sandbox ? 'sim' : 'nao',
            'base_url' => $this->baseUrl,
            'charge_id' => $chargeId,
            'token_field' => 'payment_token',
            'token_len' => strlen((string) ($dados['credit_card_token'] ?? '')),
            'installments' => (int) ($dados['installments'] ?? 1),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        $this->registrarDebugGateway([
            'flow' => 'credit_card_pay',
            'attempt' => 1,
            'http_code' => $httpCode,
            'response' => $response,
        ]);

        if ($httpCode !== 200) {
            if (
                strpos((string) $response, '3500010') !== false
                && strpos((string) $response, 'payment_token') !== false
            ) {
                throw new Exception('Token de pagamento inválido para a conta/ambiente atual. Gere o token com o identificador de conta correto (EFI_CARD_ACCOUNT_HOMOLOG/PROD) e mesmas credenciais do backend.');
            }
            throw new Exception('Erro ao processar pagamento com cartão: ' . $response);
        }

        $data = json_decode($response, true);

        return [
            'charge_id' => $chargeId,
            'status' => $data['data']['status'] ?? 'waiting',
            'total' => $data['data']['total'] / 100,
            'payment_data' => $data['data']
        ];
    }

    public function consultarCobranca($chargeId)
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/v1/charge/' . $chargeId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization' => 'Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        throw new Exception('Erro ao consultar cobrança: ' . $response);
    }
}

?>

