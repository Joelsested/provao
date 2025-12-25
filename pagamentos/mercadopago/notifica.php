<?php 
require_once("../../sistema/conexao.php");

 if($_GET['collection_id'] || $_GET['id']){   
                   // Conexão
   require_once 'lib/mercadopago.php';  // Biblioteca Mercado Pago
   require_once 'PagamentoMP.php';            // Classe Pagamento
   
   $pagar = new PagamentoMP;
   
   if(isset($_GET['collection_id'])):
    $id =  $_GET['collection_id'];
   elseif(isset($_GET['id'])):
    $id =  $_GET['id'];
   endif; 

    
    $id_matricula =  @$_GET['external_reference'];
   
   
   //por numero da operacao na matricula
      $stmtUpdate = $pdo->prepare("UPDATE matriculas SET ref_api = :ref_api where id = :id");
      $stmtUpdate->execute([':ref_api' => $id, ':id' => $id_matricula]);

   $retorno = $pagar->Retorno($id , $pdo);
   
   if($retorno){
      // Redirecionar usuario
      echo '<script>location.href="../../sistema/painel-aluno/"</script>';
   }else{
     // Redirecionar usuario e informar erro ao admin
      echo '<script>location.href="../../sistema/painel-aluno/"</script>';
      
      /*
       
       ENVIAR EMAIL AO ADMIN
      
      */
   }
   
 }
 
 
?>
