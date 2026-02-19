<?php



class EFIBoletoPayment

{

    private $clientId;

    private $clientSecret;

    private $sandbox;

    private $baseUrl;



    public function __construct($clientId, $clientSecret, $sandbox = true)

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

            throw new Exception('Erro na autenticação Boleto: ' . $response);

        }



        $data = json_decode($response, true);

        return $data['access_token'] ?? null;

    }



    public function createBoletoCharge($dados)

    {







        $token = $this->getAccessToken();



        if (!$token) {

            throw new Exception('Não foi possível obter o token de acesso');

        }



        // Primeira etapa: Criar a cobrança

        $chargeId = $this->createCharge($dados, $token);



        // Segunda etapa: Definir forma de pagamento (boleto)

        $boletoData = $this->setPaymentMethod($chargeId, $dados, $token);



        return $boletoData;

    }

    

    private function converterParaCentavos($valor)

{

    // Remove ponto de milhar e converte vírgula decimal para ponto

    $valorNumerico = floatval(str_replace(',', '.', str_replace('.', '', $valor)));



    return intval(round($valorNumerico * 100));

}

    private function normalizarRepasses(array $repasses): array
    {
        $agregado = [];
        foreach ($repasses as $repasse) {
            if (!is_array($repasse)) {
                continue;
            }
            $payee = trim((string)($repasse['payee_code'] ?? ''));
            $percentage = (int)($repasse['percentage'] ?? 0);
            if ($payee === '' || $percentage <= 0) {
                continue;
            }
            if (!isset($agregado[$payee])) {
                $agregado[$payee] = 0;
            }
            $agregado[$payee] += $percentage;
        }

        $saida = [];
        foreach ($agregado as $payee => $percentage) {
            $saida[] = [
                'payee_code' => $payee,
                'percentage' => $percentage,
            ];
        }

        return $saida;
    }



    private function createCharge($dados, $token)

    {



        $url = $this->baseUrl . '/v1/charge';



        $items = [];

        if (isset($dados['items']) && is_array($dados['items'])) {
            $items = $dados['items'];
        } else {
            // Item padrao se nao fornecido
            $items[] = [
                'name' => $dados['item_nome'] ?? 'Produto/Servico',
                'value' => (int) ($dados['valor']), // Valor em centavos
                'amount' => $dados['quantidade'] ?? 1,
                'marketplace' => [
                    'repasses' => $dados['repasses'] ?? []
                ]
            ];
        }

        foreach ($items as $idx => $item) {
            $repassesItem = $item['marketplace']['repasses'] ?? [];
            if (is_array($repassesItem)) {
                $items[$idx]['marketplace']['repasses'] = $this->normalizarRepasses($repassesItem);
            }
        }

        $body = [
            'items' => $items,
        ];

        // Metadados para webhook (quando enviados pelo chamador).
        if (isset($dados['metadata']) && is_array($dados['metadata'])) {
            $body['metadata'] = $dados['metadata'];
        } elseif (!empty($dados['notification_url'])) {
            $body['metadata'] = [
                'notification_url' => $dados['notification_url'],
            ];
        }







        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));

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



    private function setPaymentMethod($chargeId, $dados, $token)

    {



        $url = $this->baseUrl . '/v1/charge/' . $chargeId . '/pay';



        // Calcular data de vencimento

        $vencimento = isset($dados['vencimento']) ?

            date('Y-m-d', strtotime($dados['vencimento'])) :

            date('Y-m-d', strtotime('+7 days'));



        $telefone = $this->normalizarTelefone($dados['telefone'] ?? '');
        if ($telefone === '') {
            $telefone = '69999694538';
        }

        $body = [

            'payment' => [

                'banking_billet' => [

                    'expire_at' => $vencimento,

                    'customer' => [

                        'name' => $dados['nome'],

                        'email' => $dados['email'],

                        'cpf' => preg_replace('/\D/', '', $dados['cpf']),

                        'birth' => $dados['nascimento'] ?? null,

                        // 'phone_number' => preg_replace('/\D/', '', $dados['phone_number'] ?? '')

                        'phone_number' => $telefone

                    ]

                ]

            ]

        ];



        // Adicionar endereço se fornecido

        if (isset($dados['endereco'])) {

            $body['payment']['banking_billet']['customer']['address'] = [

                'street' => $dados['endereco']['rua'],

                'number' => $dados['endereco']['numero'],

                'neighborhood' => $dados['endereco']['bairro'],

                'zipcode' => preg_replace('/\D/', '', $dados['endereco']['cep']),

                'city' => $dados['endereco']['cidade'],

                'state' => $dados['endereco']['estado']

            ];



            if (isset($dados['endereco']['complemento'])) {

                $body['payment']['banking_billet']['customer']['address']['complement'] = $dados['endereco']['complemento'];

            }

        }



        // Configurações adicionais do boleto

        if (isset($dados['instrucoes'])) {

            $body['payment']['banking_billet']['instructions'] = $dados['instrucoes'];

        }



        if (isset($dados['multa'])) {

            $body['payment']['banking_billet']['fine'] = (int) ($dados['multa'] * 100); // Em centavos

        }



        if (isset($dados['juros'])) {

            $body['payment']['banking_billet']['interest'] = (int) ($dados['juros'] * 100); // Em centavos

        }



        if (isset($dados['desconto'])) {

            $body['payment']['banking_billet']['discount'] = [

                'type' => $dados['desconto']['tipo'] ?? 'currency', // currency ou percentage

                'value' => (int) ($dados['desconto']['valor'] * 100)

            ];

        }



        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));

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

            throw new Exception('Erro ao definir forma de pagamento: ' . $response);

        }



        $data = json_decode($response, true);



        $consulta_bobranca = $this->consultarCobranca($chargeId);



        return [

            'charge_id' => $chargeId,

            'status' => $data['data']['status'] ?? 'waiting',

            'total' => $data['data']['total'] / 100, // Converter de centavos

            'vencimento' => $vencimento,

            'linha_digitavel' => $data['data']['payment']['banking_billet']['line'] ?? null,

            'codigo_barras' => $data['data']['payment']['banking_billet']['barcode'] ?? null,

            'link_boleto' => $data['data']['payment']['banking_billet']['link'] ?? null,

            'pdf_boleto' => $data['data']['payment']['banking_billet']['pdf']['charge'] ?? null,

            'payment_data' => $consulta_bobranca

        ];

    }

    private function normalizarTelefone($telefone): string
    {
        $digits = preg_replace('/\D/', '', (string) $telefone);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '55') === 0 && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) > 10 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        if (!preg_match('/^[1-9]{2}9?[0-9]{8}$/', $digits)) {
            return '';
        }

        return $digits;
    }



    public function consultarCobranca($chargeId)

    {

        $token = $this->getAccessToken();

        $url = $this->baseUrl . '/v1/charge/' . $chargeId;



        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [

            'Authorization: Bearer ' . $token

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





    public function consultarWebhook($notification)

    {

        $token = $this->getAccessToken();

        $url = $this->baseUrl . '/v1/notification/' . $notification;



        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [

            'Authorization: Bearer ' . $token

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



    public function atualizarVencimentoBoleto($chargeId, $vencimento)
    {
        $token = $this->getAccessToken();
        $payload = [
            'payment' => [
                'banking_billet' => [
                    'expire_at' => $vencimento
                ]
            ]
        ];

        $endpoints = [
            $this->baseUrl . '/v1/charge/' . $chargeId . '/billet',
            $this->baseUrl . '/v1/charge/' . $chargeId,
            $this->baseUrl . '/v1/charge/' . $chargeId . '/pay'
        ];

        $erros = [];

        foreach ($endpoints as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_error($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception('Erro cURL: ' . $error);
            }

            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 201 || $httpCode === 204) {
                return json_decode((string)$response, true);
            }

            $erros[] = [
                'url' => $url,
                'http' => $httpCode,
                'response' => $response,
            ];
        }

        throw new Exception('Erro ao atualizar vencimento: ' . json_encode($erros, JSON_UNESCAPED_UNICODE));
    }

    public function cancelarCobranca($chargeId)

    {

        $token = $this->getAccessToken();

        $url = $this->baseUrl . '/v1/charge/' . $chargeId . '/cancel';



        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        curl_setopt($ch, CURLOPT_HTTPHEADER, [

            'Authorization: Bearer ' . $token

        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);



        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);



        if ($httpCode === 200) {

            return json_decode($response, true);

        }



        throw new Exception('Erro ao cancelar cobrança: ' . $response);

    }

    public function getCharges($query)
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/v1/charges?' . $query;

       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        throw new Exception('Erro ao obter saldo: ' . $response);
    }
    
    
    public function getChargesSummary($query)
{
    $token = $this->getAccessToken();
    $url = $this->baseUrl . '/v1/charges?' . $query;
   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Erro ao obter cobranças: ' . $response);
    }
    
    $data = json_decode($response, true);
    
    // Inicializa os totalizadores
    $summary = [
        'total_geral' => 0,
        'total_pago' => 0,
        'total_pendente' => 0,
        'total_expirado' => 0,
        'quantidade_total' => 0,
        'quantidade_pago' => 0,
        'quantidade_pendente' => 0,
        'quantidade_expirado' => 0
    ];
    
    // Processa cada cobrança
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $charge) {
            $valor = $charge['total'] ?? 0;
            $status = $charge['status'] ?? '';
            
            // Soma no total geral
            $summary['total_geral'] += $valor;
            $summary['quantidade_total']++;
            
            // Soma por status
            switch ($status) {
                case 'paid':
                    $summary['total_pago'] += $valor;
                    $summary['quantidade_pago']++;
                    break;
                case 'waiting':
                    $summary['total_pendente'] += $valor;
                    $summary['quantidade_pendente']++;
                    break;
                case 'expired':
                    $summary['total_expirado'] += $valor;
                    $summary['quantidade_expirado']++;
                    break;
            }
        }
    }
    
    // Converte os valores de centavos para reais (se necessário)
    // A API da EFI trabalha com valores em centavos
    $summary['total_geral'] = $summary['total_geral'] / 100;
    $summary['total_pago'] = $summary['total_pago'] / 100;
    $summary['total_pendente'] = $summary['total_pendente'] / 100;
    $summary['total_expirado'] = $summary['total_expirado'] / 100;
    
    return $summary;
}

}





?>
