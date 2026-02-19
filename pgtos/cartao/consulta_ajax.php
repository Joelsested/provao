<?php
include('ApiConfig.php');
require_once("../../sistema/conexao.php");

$consulta = @$_POST['consulta_ajax'];
$id_mat = @$_POST['id_mat'];
//$ref_api = '58970903187';

$query = $pdo->prepare("SELECT * FROM matriculas where id = :id");
$query->execute([':id' => $id_mat]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$ref_api = $res[0]['ref_api'];

$curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/'.$ref_api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'content-type: application/json',
        'Authorization: Bearer '.$access_token
    ),
    ));
    $response = curl_exec($curl);
    $resultado = json_decode($response);
curl_close($curl);

$status_api = $resultado->status;
echo $status_api;

//var_dump($resultado);
?>
