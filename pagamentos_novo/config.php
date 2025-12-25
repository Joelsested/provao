<?php
require("../sistema/conexao.php");
require("tokens.php");

$modoProducao = true; // Defina isso como true para usar credenciais de produção e false para usar credenciais de teste

if ($modoProducao) {
    $NOME_SITE = $nome_sistema; // Nome do seu site em produção
    $TOKEN_MERCADO_PAGO = $access_token; // Token do Mercado Pago em produção
    $TOKEN_MERCADO_PAGO_PUBLICO = $public_key; // Token público do Mercado Pago em produção (PUBLIC KEY)
    
} else {
    $NOME_SITE = "Meu pix (Modo Teste)"; // Nome do seu site em modo de teste
    $TOKEN_MERCADO_PAGO = "TEST-7908075532782170-121002-ca36424f2b560fc177d7cf32b6ef5ab5-1014420370"; // Token do Mercado Pago em teste
    $TOKEN_MERCADO_PAGO_PUBLICO = "TEST-da159e2a-4731-4fe4-abf0-5d890ab6c0e0"; // Token público do Mercado Pago em teste
    
}


$DESCRICAO_PAGAMENTO = "Compra Loja ".$nome_sistema; // OBRIGATÓRIO: DESCRIÇÃO PAGAMENTO O PAGAMENTO

$MSG_APOS_PAGAMENTO = "Recebemos seu pagamento.";

$URL_REDIRECIONAR = "../sistema/painel-aluno"; // LINK PARA DIRECIONAR 5 SEGUNDOS APÓS RECEBER O PAGAMENTO (https://seusite.com). Deixar vazio para não redirecionar

$PAGAMENTO_MINIMO = "0"; // NÃO OBRIGATORIO: VALOR PARA PAGAMENTO MINIMO. EXEMPLO: 10,00 / 20,40

$EMAIL_NOTIFICACAO = ""; // OBRIGATÓRIO. SE NÃO FOR CONFIGURADO O CLIENTE DEVERÁ INFORMAR.

$CPF_PADRAO = ""; // É OBRIGATÓRIO O CPF. SE NÃO FOI CONFIGURADO AQUI O CLIENTE DEVERÁ INFORMAR. 

$URL_NOTIFICACAO = "https://sestedcursosvirtual.com/pagamentos_novo/webhook.php";  // URL LOCAL  // OBRIGATORIO

$VALOR_PADRAO = "5,00"; // EX: 20,00

$ATIVAR_PIX = "1";

$ATIVAR_BOLETO = "1";

$ATIVAR_CARTAO_CREDITO = "1";

$ATIVAR_CARTAO_DEBIDO = "1";

?>