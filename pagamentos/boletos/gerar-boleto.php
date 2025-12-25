<?php

//CHAVES DO GERENCIANET gerencianet.com.br
require_once('config.php');

// AUTO LOAD PARA O COMPOSER
require_once('vendor/autoload.php');
  
 use Gerencianet\Exception\GerencianetException;
 use Gerencianet\Gerencianet;
  
 $clientId = CONF_ID; // insira seu Client_Id, conforme o ambiente (Des ou Prod)
 $clientSecret = CONF_SECRETO; // insira seu Client_Secret, conforme o ambiente (Des ou Prod)

 $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRIPPED);
 $nome = filter_input(INPUT_GET, 'nome', FILTER_SANITIZE_STRIPPED);
 $fone = filter_input(INPUT_GET, 'fone', FILTER_SANITIZE_STRIPPED);
 $cpf = filter_input(INPUT_GET, 'cpf', FILTER_SANITIZE_STRIPPED);
 $vencimento = filter_input(INPUT_GET, 'vencimento', FILTER_SANITIZE_STRIPPED);



 $options = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'sandbox' => CONF_SANDBOX,
  ];
   
  // $charge_id refere-se ao ID da transação gerada anteriormente
  $params = [
    'id' => $id
  ];
   
  $customer = [
    'name' => $nome, // nome do cliente
    'cpf' => $cpf , // cpf válido do cliente
    'phone_number' => $fone // telefone do cliente
  ];
   
  $bankingBillet = [
    'expire_at' => $vencimento, // data de vencimento do boleto (formato: YYYY-MM-DD)
    'customer' => $customer
  ];
   
  $payment = [
    'banking_billet' => $bankingBillet // forma de pagamento (banking_billet = boleto)
  ];
   
  $body = [
    'payment' => $payment
  ];
   
  try {
      $api = new Gerencianet($options);
      $charge = $api->payCharge($params, $body);
     // print_r($charge);
      header("Location: ".$charge['data']['link']);
  } catch (GerencianetException $e) {
      print_r($e->code);
      print_r($e->error);
      print_r($e->errorDescription);
  } catch (Exception $e) {
      print_r($e->getMessage());
  }
 

?>