<?php

class EFIPixPayment
{
    private $clientId;
    private $clientSecret;
    private $certificate;
    private $sandbox;
    private $baseUrl;

    public function __construct($clientId, $clientSecret, $certificate, $sandbox = true)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->certificate = $certificate;
        $this->sandbox = $sandbox;
        $this->baseUrl = $sandbox ? 'https://pix-h.api.efipay.com.br' : 'https://pix.api.efipay.com.br';
    }

    private function getAccessToken()
    {
        $url = $this->baseUrl . '/oauth/token';

        $postData = http_build_query([
            'grant_type' => 'client_credentials'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, ''); // Senha do certificado se houver
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Erro na autenticação PIX: ' . $response);
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    public function createPixCharge($dados)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            throw new Exception('Não foi possível obter o token de acesso');
        }

        // Gerar txid único se não fornecido
        $txid = $dados['txid'] ?? $this->generateTxid();

        $url = $this->baseUrl . '/v2/cob/' . $txid;

        $body = [
            'calendario' => [
                'expiracao' => $dados['expiracao'] ?? 3600 // 1 hora em segundos
            ],
            'devedor' => [
                'cpf' => preg_replace('/\D/', '', $dados['cpf']),
                'nome' => $dados['nome']
            ],
            'valor' => [
                'original' => number_format($dados['valor'], 2, '.', '')
            ],
            'chave' => $dados['chave_pix'], // Sua chave PIX
            'solicitacaoPagador' => $dados['descricao'] ?? 'Cobrança PIX'
        ];

        // Adicionar informações adicionais se fornecidas
        if (isset($dados['infoAdicionais']) && is_array($dados['infoAdicionais'])) {
            $body['infoAdicionais'] = $dados['infoAdicionais'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, ''); // Senha do certificado se houver
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new Exception('Erro ao criar cobrança PIX: ' . $response);
        }

        $cobranca = json_decode($response, true);

        // Gerar QR Code
        $qrCode = $this->generateQRCode($cobranca['loc']['id']);

        return [
            'txid' => $cobranca['txid'],
            'status' => $cobranca['status'],
            'valor' => $cobranca['valor']['original'],
            'qr_code' => $qrCode['qrcode'],
            'qr_code_image' => $qrCode['imagemQrcode'],
            'link_pagamento' => $cobranca['loc']['location'],
            'vencimento' => date('Y-m-d H:i:s', time() + ($dados['expiracao'] ?? 3600))
        ];
    }

    private function generateQRCode($locId)
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/v2/loc/' . $locId . '/qrcode';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, ''); // Senha do certificado se houver
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        throw new Exception('Erro ao gerar QR Code: ' . $response);
    }

    public function consultarCobranca($txid)
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/v2/cob/' . $txid;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, ''); // Senha do certificado se houver
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

    private function generateTxid()
    {
        return substr(md5(uniqid(rand(), true)), 0, 35);
    }
}

// Exemplo de uso:
/*
try {
    $pixPayment = new EFIPixPayment(
        'YOUR_CLIENT_ID',
        'YOUR_CLIENT_SECRET',
        '/path/to/certificate.p12',
        true // sandbox
    );
    
    $dados = [
        'cpf' => '12345678901',
        'nome' => 'João Silva',
        'valor' => 100.50,
        'chave_pix' => 'sua_chave_pix@email.com',
        'descricao' => 'Pagamento de produto',
        'expiracao' => 3600, // 1 hora
        'infoAdicionais' => [
            [
                'nome' => 'Produto',
                'valor' => 'Camiseta Azul'
            ]
        ]
    ];
    
    $resultado = $pixPayment->createPixCharge($dados);
    
    echo "PIX criado com sucesso!\n";
    echo "TXID: " . $resultado['txid'] . "\n";
    echo "QR Code: " . $resultado['qr_code'] . "\n";
    echo "Status: " . $resultado['status'] . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
*/

?>