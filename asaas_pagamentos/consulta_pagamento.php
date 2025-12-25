<?php
require('../vendor/autoload.php');
require("../config/env.php");

$asaas_enabled = filter_var(env('ASAAS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
if (!$asaas_enabled) {
	http_response_code(403);
	echo 'Meio de pagamento desativado.';
	exit;
}

require("../sistema/conexao.php");

$clienteGuzzle = new \GuzzleHttp\Client();
// Token Asaas via .env
$chaveAsaas = env('ASAAS_TOKEN', '');
if ($chaveAsaas === '') {
	http_response_code(500);
	echo 'Token Asaas nao configurado.';
	exit;
}

//Recebe a situação da cobrança gerada anteriormente
function obterSituacaoCobranca($clienteGuzzle, $asaasCobranca, $chaveAsaas) {
    $resposta = $clienteGuzzle->request('GET', 'https://asaas.com/api/v3/payments/' . $asaasCobranca . '/status', [
        'headers' => [
            'accept' => 'application/json',
            'access_token' => $chaveAsaas,
        ],
    ]);
    return json_decode($resposta->getBody()->getContents(), true);
}

$asaasCobranca = $_GET['id_pagamento'];

$novo_status = 'Matriculado';
$id_do_aluno = $_GET['id_do_aluno'];
$id_do_curso_pag = $_GET['id_do_curso_pag'];

$mensagem_sobre_cobranca = 'Aguardando pagamento';

try {
    $situacaoCobranca = obterSituacaoCobranca($clienteGuzzle, $asaasCobranca, $chaveAsaas);

    if ($situacaoCobranca['status'] !== 'PENDING') {
        $novo_status = 'Matriculado';

        //Atualiza a situação da matrícula no banco
        $pdo = new PDO("mysql:host=$servidor;dbname=$banco", $usuario, $senha);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "UPDATE matriculas
                SET status = :novo_status
                WHERE id_curso = :id_do_curso_pag
                  AND aluno = :id_do_aluno";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':novo_status' => $novo_status,
            ':id_do_curso_pag' => $id_do_curso_pag,
            ':id_do_aluno' => $id_do_aluno
        ]);

        if ($stmt->rowCount()) {
            $mensagem_sobre_cobranca = 'Pagamento realizado com sucesso!';
        } else {
            $mensagem_sobre_cobranca = 'Erro ao realizar matrícula. Por favor, entre em contato com o suporte e informe o seu protocolo de pagamento: ' . $asaasCobranca;
        }
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situação de Pagamento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
<div class="bg-white p-6 rounded-lg shadow-lg text-center">
    <?php
        if ($mensagem_sobre_cobranca == 'Pagamento realizado com sucesso!') {
            echo '<div class="h-12 w-12 relative">
            <div class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></div>
            <div class="relative inline-flex rounded-full h-12 w-12 bg-green-500 justify-center items-center">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>';
        } else{
            echo '<div class="flex items-center justify-center mb-4"><div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div></div>';
        }

            ?>

    <h2 class="text-2xl font-semibold mb-2"><?php echo $mensagem_sobre_cobranca; ?></h2>
    <p class="text-gray-600">Por favor, aguarde enquanto verificamos a situação do seu pagamento. Para uma resposta mais rápida, atualize a página.</p>
    
</div>
</body>
<script>
    const mensagem = <?php echo json_encode($mensagem_sobre_cobranca); ?>;

    if(mensagem === 'Pagamento realizado com sucesso!') {
        setTimeout(function() {
            window.location.href = '../sistema/painel-aluno/';
        }, 3000);
    }

</script>
</html>
