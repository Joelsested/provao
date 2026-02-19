<?php
require_once("../../sistema/conexao.php");
@session_start();
$id_aluno = $_SESSION['id'];
$data_hoje = date('Y-m-d');
//ALIMENTAR O BOLETO
$id = $_POST['id'];
$cpf_aluno = $_POST['cpf'];
if($id == ""){
  echo 'Você não selecionou nenhum curso!';
  exit();
}

$query = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno");
$query->execute([':id_curso' => $id, ':aluno' => $id_aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
$id_matricula = $res[0]['id'];
$valor = $res[0]['subtotal'] * 100;
$data_venc = date('Y-m-d', strtotime("+7 days",strtotime($data_hoje)));
$pacote = $res[0]['pacote'];

if($pacote == 'Sim'){
  $tabela = 'pacotes';
}else{
  $tabela = 'cursos';
}

$query = $pdo->prepare("SELECT * FROM {$tabela} where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$titulo = $res[0]['nome'];

$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query->execute([':id' => $id_aluno]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$aluno = $res[0]['nome'];
$email = $res[0]['email'];
     
$nome = $aluno;
$telefone = $tel_sistema;
$email = $email_sistema;
$cpf = $cpf_aluno;



$cpf = str_replace('.', '', $cpf);   
$cpf = str_replace('-', '', $cpf);   

$telefone = str_replace('-', '', $telefone);   
$telefone = str_replace('(', '', $telefone); 
$telefone = str_replace(')', '', $telefone); 
$telefone = str_replace(' ', '', $telefone); 


}else{
 echo 'Você não selecionou uma matrícula válida!';
  exit();
}


//CHAVES DO GERENCIANET gerencianet.com.br
require_once('config.php');

// AUTO LOAD PARA O COMPOSER
require_once('vendor/autoload.php');


use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;
 
$clientId = CONF_ID; // insira seu Client_Id, conforme o ambiente (Des ou Prod)
$clientSecret = CONF_SECRETO; // insira seu Client_Secret, conforme o ambiente (Des ou Prod)

$boleto = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRIPPED);    

    $options = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'sandbox' => CONF_SANDBOX,
      ];
       
      $item_1 = [
          'name' => $titulo, // nome do item, produto ou serviço
          'amount' => 1, // quantidade
          'value' => intval($valor) // valor (1000 = R$ 10,00) (Obs: É possível a criação de itens com valores negativos. Porém, o valor total da fatura deve ser superior ao valor mínimo para geração de transações.)
      ];
       

      $items =  [
          $item_1
      ];

      $body  =  [
        'items' => $items
    ];
    
    try {
        $api = new Gerencianet($options);
        $charge = $api->createCharge([], $body);

         //SALVAR O ID DO BOLETO NA TABELA DAS CONTAS A RECEBER
        $id_boleto = $charge['data']['charge_id'];
        $query = $pdo->prepare("UPDATE matriculas SET boleto = :boleto WHERE id = :id");
        $query->execute([':boleto' => $id_boleto, ':id' => $id_matricula]);
     
        //print_r($charge);
        header("Location: gerar-boleto.php?id=".$charge['data']['charge_id']."&nome=".$nome."&cpf=".$cpf."&fone=".$telefone."&vencimento=".$data_venc);
    } catch (GerencianetException $e) {
        print_r($e->code);
        print_r($e->error);
        print_r($e->errorDescription);
    } catch (Exception $e) {
        print_r($e->getMessage());
    }

    


?>
