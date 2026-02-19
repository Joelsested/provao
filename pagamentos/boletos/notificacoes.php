<?php
 
//CHAVES DO GERENCIANET gerencianet.com.br
require_once('config.php');

// AUTO LOAD PARA O COMPOSER
require_once('vendor/autoload.php');


use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;
 
$clientId = CONF_ID; // insira seu Client_Id, conforme o ambiente (Des ou Prod)
$clientSecret = CONF_SECRETO; // insira seu Client_Secret, conforme o ambiente (Des ou Prod)
 
$options = [
  'client_id' => $clientId,
  'client_secret' => $clientSecret,
  'sandbox' => CONF_SANDBOX,
];
 
/*
* Este token será recebido em sua variável que representa os parâmetros do POST
* Ex.: $_POST['notification']
*/
$params = [
  'id' => $boleto // $charge_id refere-se ao ID da transação ("charge_id")
];

try {
    $api = new Gerencianet($options);
    $charge = $api->detailCharge($params, []);
    //print_r($charge);   
    //print_r($charge['data']['status']);  

     $status_boleto = $charge['data']['status']; 
   
} catch (GerencianetException $e) {
    print_r($e->code);
    print_r($e->error);
    print_r($e->errorDescription);
} catch (Exception $e) {
    print_r($e->getMessage());
}