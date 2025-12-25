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

    private function createCharge($dados, $token)
    {
        $url = $this->baseUrl . '/v1/charge';

        $items = [];
        if (isset($dados['items']) && is_array($dados['items'])) {
            $items = $dados['items'];
        } else {
            $items[] = [
                'name' => $dados['item_nome'] ?? 'Produto/Serviço',
                'value' => (int) ($dados['valor'] * 100),
                'amount' => $dados['quantidade'] ?? 1,
                // 'marketplace' => [
                //     'repasses' => $dados['repasses'] ?? []
                // ]
            ];
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
                    'payment_token' => $dados['credit_card_token'] // gerado pelo JS SDK da Gerencianet
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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
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
