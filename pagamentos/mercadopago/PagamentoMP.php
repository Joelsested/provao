<?php 

 class PagamentoMP{
      // vamos dar alguns atributos a esta class
      // como :
     // O botão que irá retornar da função PagarMP (string)
     public $btn_mp;
     // Definiremos o botão que irá retornar, se será uma lightbox // do mercado pago ou não, como padrão será false. o user será 
     // redirecionado para o site do mercado pago
     private $lightbox = false;
     // Esta variável recebe uma array com os dados da transação
     public $info = array();
     // Se for em modo de teste, esta variável recebe true, caso 
     // contrário o sistema estará em modo de produção
     private $sandbox = false;
     // Suas credenciais do mercado pago
     private $client_id = "6748425745253616";
     private $client_secret = "Xpx0Xyi3nZgAGQMAYSOK0wrQjCLxzRZP";
     
     public function PagarMP($ref , $nome ,  $valor , $url){    

     // iniciando as credenciais do MP
     // Os valores de client_id e client_secret são informados  aqui
     $mp = new MP($this->client_id, $this->client_secret);
     
    $preference_data = array(   
            // dados do produto para pagamento 
            "items" => array(
                array(
                    "id"          => 0001,
                    "title"       => $nome,
                    "currency_id" => "BRL",
                    "picture_url" => "https://www.mercadopago.com/org-img/MP3/home/logomp3.gif",
                    "description" => $nome,
                    "quantity"    => 1,
                    "unit_price"  => $valor
                )
            ),
            "back_urls" => array(
               "success" => $url."pagamentos/mercadopago/notifica.php?success",
               "failure" => $url."pagamentos/mercadopago/notifica.php?failure",
               "pending" => $url."pagamentos/mercadopago/notifica.php?pending"
             ),
            "notification_url" => $url."pagamentos/mercadopago/notifica.php",
            "external_reference" => $ref
        );
      $preference = $mp->create_preference($preference_data);

      //var_dump($preference);

     
      // Criar link para o botão de pagamento normal ou sandbox
        if($this->sandbox):
         //sandbox
         $mp->sandbox_mode(TRUE);
         $link = $preference["response"]["sandbox_init_point"];
            else:
            // normal em produção
            $mp->sandbox_mode(FALSE);
            $link = $preference["response"]["init_point"];
           endif;
        $this->btn_mp = '<a title="Mercado Pago - Acesso Imediato ao Curso" class="" id="btnMP" target="_blank" href="'.$link.'" '; 
        $this->btn_mp .= 'name="MP-Checkout" ><img src="img/pagamentos/mercadopago.jpg" width="100%"></a>';
         if($this->lightbox):
            $this->btn_mp .= '<script type="text/javascript" src="//secure.mlstatic.com/mptools/render.js"></script>';
          endif;
     
        return $this->btn_mp;
     
     
     }
   public function Retorno($id , $conexao){


      // iniciando as credenciais do MP
      $mp = new MP($this->client_id, $this->client_secret);
      
     
      // Dentro da função Retorno
      $params = ["access_token" => $mp->get_access_token()];
      // params recebe o token
      $topic = 'payment';


      
      if ($topic == 'payment'){

      $payment_info = $mp->get("/collections/notifications/" . $id, $params, false);

      
      $merchant_order_info = $mp->get("/merchant_orders/" . $payment_info["response"]["collection"]["merchant_order_id"], $params, false);


      }
      
      switch ($payment_info["response"]["collection"]["status"]):
         case "approved"     : $status = "Aprovado";           break;
         case "pending"      : $status = "Pendente";           break;
         case "in_process"   : $status = "Análise";            break;
         case "rejected"     : $status = "Rejeitado";          break;
         case "refunded"     : $status = "Devolvido";          break;
         case "cancelled"    : $status = "Cancelado";          break;
         case "in_mediation" : $status = "Mediação";           break;
   
     endswitch;
      
      
      switch ($payment_info["response"]["collection"]["payment_type"]):
      
         case "ticket"        : $forma = "Boleto";
        break;
         case "account_money" : $forma = "Saldo MP";
        break;
         case "credit_card"   : $forma = "Cartão de Crédito";
        break;
         default : $forma =   $payment_info["response"]["collection"]["payment_type"];
         
      endswitch;
        
        
     $ref = $payment_info["response"]["collection"]["external_reference"];
     $id_matricula = $ref;


      

      if ($status == "Aprovado"){
        $forma_pgto = 'MP';  
        
        //neste arquivo temos a aprovação da matricula, o envio por email e o lançamento na tabela de vendas
         include_once('../aprovar_matricula.php');          
         
      }
        
      
      
      
      
      
      
     }
   }
?>