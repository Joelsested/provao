<?php
require_once("../../sistema/conexao.php");
@session_start();
$id_do_aluno = @$_SESSION['id'];
$id_do_curso_pag = $_POST['id'];
$nome_curso_titulo = $_POST['nome'];

$nome_aluno = "Teste";
$email_aluno = "hugovasconcelosf@hotmail.com";
$cpf_aluno = "34152426764";

$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query2->execute([':id' => $id_do_aluno]);
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
    $id_pessoa = $res2[0]['id_pessoa'];
        $query3 = $pdo->prepare("SELECT * FROM alunos where id = :id");
        $query3->execute([':id' => $id_pessoa]);
        $res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
        if(@count($res3) > 0){
            $nome_aluno = $res3[0]['nome'];
            $email_aluno = $res3[0]['email'];
            //$cpf_aluno = $res3[0]['cpf'];

        }
}


//buscar id da matricula
    $query = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno");
    $query->execute([':id_curso' => $id_do_curso_pag, ':aluno' => $id_do_aluno]);
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    if(@count($res) > 0){
        $id_mat = $res[0]['id'];
        $valor_curso = $res[0]['subtotal'];
        $valor_pix = $valor_curso;
        $status_mat = $res[0]['status'];
        $id_mat = $res[0]['id'];
        if($desconto_pix > 0){
            $valor_pix = $valor_curso - ($valor_curso * ($desconto_pix / 100));
            $valor_pix = number_format($valor_pix, 2);
        }
    }

echo <<<HTML
 
  <form id="form-checkout" style="margin-top: 10px;">
    <div id="form-checkout__cardNumber"  style="width:180px; height:30px; border:1px solid #969696; margin-bottom: 5px; display:inline-block; font-size: 11px"></div>
     <div id="form-checkout__expirationDate" style="width:70px; height:30px; border:1px solid #969696; margin-bottom: 5px; display:inline-block;"> </div>
    <div id="form-checkout__securityCode" placeholder="Código" style="width:60px; height:30px; border:1px solid #969696; margin-bottom: 5px; display:inline-block;"></div>
    <input type="text" id="form-checkout__cardholderName" style="width:180px; margin-bottom: 5px" />
    <select id="form-checkout__issuer"></select>
    <select style="height:30px; width:135px; background: #FFF; border:1px solid #969696" id="form-checkout__installments"></select>
    <select id="form-checkout__identificationType"></select>
    <input style="width:180px;" type="text" id="form-checkout__identificationNumber" />
    <input type="email" id="form-checkout__cardholderEmail" value="{$email_aluno}"/>

    <button style="background: #5397fc; color:#FFF; width:135px" type="submit" id="form-checkout__submit"><i class="fa fa-credit-card" style="color:#FFF; margin-right:5px"></i>Pagar</button>
    <img id="img_load" src="img/loading.gif" width="40px">
 
  </form>


<script>
$('#form-checkout__issuer').hide();
$('#form-checkout__identificationType').hide();
$('#form-checkout__cardholderEmail').hide();
$('#img_load').hide();
    
    const cardForm = mp.cardForm({
      amount: "$valor_curso",
      iframe: true,
      form: {
        id: "form-checkout",
        cardNumber: {
          id: "form-checkout__cardNumber",
          placeholder: "Número do Cartão",
        },
        expirationDate: {
          id: "form-checkout__expirationDate",
          placeholder: "Mês/Ano",
        },
        securityCode: {
          id: "form-checkout__securityCode",
          placeholder: "Código",
        },
        cardholderName: {
          id: "form-checkout__cardholderName",
          placeholder: "Titular do cartão",
        },
        issuer: {
          id: "form-checkout__issuer",
          placeholder: "Banco emissor",
        },
        installments: {
          id: "form-checkout__installments",
          placeholder: "Parcelas",
        },        
        identificationType: {
          id: "form-checkout__identificationType",
          placeholder: "Tipo de documento",
        },
        identificationNumber: {
          id: "form-checkout__identificationNumber",
          placeholder: "Número do CPF",
        },
        cardholderEmail: {
          id: "form-checkout__cardholderEmail",
          placeholder: "E-mail",
        },
      },
      callbacks: {
        onFormMounted: error => {
          if (error) return console.warn("Form Mounted handling error: ", error);
          console.log("Form mounted");
        },
        onSubmit: event => {
          event.preventDefault();

          const {
            paymentMethodId: payment_method_id,
            issuerId: issuer_id,
            cardholderEmail: email,
            amount,
            token,
            installments,
            identificationNumber,
            identificationType,
          } = cardForm.getCardFormData();

          fetch("pgtos/cartao/processa.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              token,
              issuer_id,
              payment_method_id,
              transaction_amount: Number(amount),
              installments: Number(installments),
              description: "$nome_curso_titulo",
              id_reg: "$id_mat",
              payer: {
                email,
                identification: {
                  type: identificationType,
                  number: identificationNumber,
                },
              },
            }),
          });
          //alert('retorno')
          verificarPgto();
        },
        onFetching: (resource) => {

          console.log("Fetching resource: ", resource);

          // Animate progress bar
          //const progressBar = document.querySelector(".progress-bar");
          //progressBar.removeAttribute("value");

          return () => {
            //progressBar.setAttribute("value", "0");

          };
        }
      },
    });


    function verificarPgto(){
      $('#form-checkout__submit').hide();
      $('#img_load').show();

      var consulta_ajax = 'Sim';
      var id_mat = "$id_mat";      

      setTimeout(function() {

       $.ajax({
        url: 'pgtos/cartao/consulta_ajax.php',
        method: 'POST',
        data: {consulta_ajax, id_mat},
        dataType: "html",

        success:function(result){
            if(result.trim() === "approved"){
              alert('Seu Pagamento foi aprovado, clique em Ok para ir para seu painel!');
                window.location='sistema/painel-aluno/';
            }else{
              alert('Seu Pagamento foi recusado, tente novamente ou use outro meio de pagamento!');
            }

            $('#form-checkout__submit').show();
            $('#img_load').hide();
        }
    });

       }, 3000)

      
    }
</script>

HTML;

?>
