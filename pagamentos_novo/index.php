<?php
require_once __DIR__ . '/../config/env.php';
$mp_enabled = filter_var(env('MP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
if (!$mp_enabled) {
	http_response_code(403);
	echo 'Meio de pagamento desativado.';
	exit;
}
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
//error_reporting(E_ALL);

include("./config.php");
include("../sistema/conexao.php");

@session_start();
$id_do_aluno = @$_SESSION['id'];

$id_do_curso_pag = $_GET['id_do_curso'];
$nome_curso_titulo = $_GET['nome_do_curso'];

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
            $cpf_aluno = $res3[0]['cpf'];

        }
}


//buscar id da matricula
    $query = $pdo->prepare("SELECT * FROM matriculas where id_curso = :id_curso and aluno = :aluno");
    $query->execute([':id_curso' => $id_do_curso_pag, ':aluno' => $id_do_aluno]);
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    if(@count($res) > 0){
        $valor_curso = $res[0]['subtotal'];       
        $status_mat = $res[0]['status'];
        $id_venda = $res[0]['id'];
        $valorF = number_format($valor_curso, 2, ',', '.');
        $ref_pix = $res[0]['ref_api'];

        if($ref_pix != ""){
             require('consultar_pagamento.php');
             if($status_api == 'approved'){
                 echo 'Já está pago';
                 echo '<script>window.location="'.$URL_REDIRECIONAR.'";</script>';  
                 exit();  
                }
        }
        
    }


$valor = $valor_curso;
$token_valor = ($valor!="")? sha1($valor) : "";
$doc = ($_REQUEST["doc"]!="")? $_REQUEST["doc"] : $CPF_PADRAO;
$doc =  str_replace(array(",", ".", "-", "/", " "), "", $doc);
$ref = $_REQUEST["ref"];
$email = ($_REQUEST["email"]!="")? $_REQUEST["email"] : $EMAIL_NOTIFICACAO;
$gerarDireto = $_REQUEST["gerarDireto"];
$descricao = ($_REQUEST["descricao"]!="")? $_REQUEST["descricao"] : $nome_curso_titulo;
$nome = $nome_aluno;
$sobrenome = $_REQUEST["sobrenome"];



?>
<html lang="pt-br">
<head>
    <title>Pagamento</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <link href="./assets/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/signin.css" rel="stylesheet">
    <script src="./assets/jquery-3.6.4.min.js"></script>
</head>
<body  class="text-center">





<div style="max-width: 500px; max-height: 800px; margin: 0 auto;  text-align: center; margin-bottom: 20px; word-break: break-all;" >


<div id="info_pagamento" style="text-align: center; margin-top: 70px">
        
 <img class="mb-4" src="assets/img/logo.png" alt="" style="width:130px;height:auto; display:inline-block; margin-right: 20px; margin-top: 15px">       
       <h4 class="h3 mb-3 font-weight-normal" style=" font-size: 22px; border-radius: 4px; display:flex; display:inline-block;">R$ <?=$valorF;?></h4>  


</div>    

<div  id="paymentBrick_container">
        </div>
        <div id="statusScreenBrick_container">
        </div>
        <div class="form-signin" id="form-pago" style="display:none;text-align: center;">
            <h1 class="h3 mb-3 font-weight-normal">Obrigado!</h1>
            <img class="mb-4"  src="./assets/check_ok.png" alt="" width="120" height="120">
            <br>
            <h3><?=$MSG_APOS_PAGAMENTO;?></h3>
            <br>
            Código do pagamento: <?php echo $_GET["id"]; ?>
        </div>
    </div>
    <style>
        body{font-family:arial}
    </style>
    <script>
        var payment_check;
        const mp = new MercadoPago('<?=$TOKEN_MERCADO_PAGO_PUBLICO;?>', {
            locale: 'pt-BR'
        });
        const bricksBuilder = mp.bricks();
        const renderPaymentBrick = async (bricksBuilder) => {
            const settings = {
                initialization: {
                    amount: '<?=$valor;?>',
                    payer: {
                        firstName: "<?=$nome;?>",
                        lastName: "<?=$sobrenome;?>",
                        email: "<?=$email;?>",
                        identification: {
                            type: '<?=(strlen($doc)>11? "CNPJ" : "CPF");?>',
                            number: '<?=$doc;?>',
                        },
                        address: {
                            zipCode: '',
                            federalUnit: '',
                            city: '',
                            neighborhood: '',
                            streetName: '',
                            streetNumber: '',
                            complement: '',
                        }
                    },
                },
                customization: {
                    visual: {
                        style: {
                            theme: "dark",
                        },
                    },
                    paymentMethods: {
                        <?php if($ATIVAR_CARTAO_CREDITO=="1"){?>creditCard: "all",<?php } ?>
                        <?php if($ATIVAR_CARTAO_DEBIDO=="0"){?>debitCard: "all",<?php } ?>
                        <?php if($ATIVAR_BOLETO=="0"){?>ticket: "all",<?php } ?>
                        <?php if($ATIVAR_PIX=="0"){?>bankTransfer: "all",<?php } ?>
                        maxInstallments: 12
                    },
                },
                callbacks: {
                    onReady: () => {
                    },
                    onSubmit: ({ selectedPaymentMethod, formData }) => {

                        formData.external_reference = '<?=$ref;?>';
                        formData.description = '<?=$descricao;?>';
                        var id_venda = '<?=$id_venda;?>';

                        return new Promise((resolve, reject) => {
                            fetch("./process_payment.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                },
                                body: JSON.stringify(formData),
                            })
                            .then((response) => response.json())
                            .then((response) => {
                // receber o resultado do pagamento
                                if(response.status==true){
                                    window.location.href = "./index.php?id="+response.id+"&id_venda="+id_venda;
                                }
                                if(response.status!=true){
                                    alert(response.message);
                                }
                                resolve();
                            })
                            .catch((error) => {
                                reject();
                            });
                        });
                    },
                    onError: (error) => {
                        console.error(error);
                    },
                },
            };
            window.paymentBrickController = await bricksBuilder.create(
                "payment",
                "paymentBrick_container",
                settings
                );
        };

        const renderStatusScreenBrick = async (bricksBuilder) => {
            const settings = {
                initialization: {
                    paymentId: '<?=$_GET["id"];?>',
                },
                customization: {
                    visual: {
                        hideStatusDetails: false,
                        hideTransactionDate: false,
                        style: {
            theme: 'dark', // 'default' | 'dark' | 'bootstrap' | 'flat'
        }
    },
    backUrls: {
        //'error': '<http://<your domain>/error>',
        //'return': '<http://<your domain>/homepage>'
    }
},
callbacks: {
    onReady: () => {
        check("<?=$_GET["id"];?>", "<?=$_GET["id_venda"];?>");
    },
    onError: (error) => {
    },
},
};
window.statusScreenBrickController = await bricksBuilder.create('statusScreen', 'statusScreenBrick_container', settings);
};

<?php if($_GET["id"]!=""){ ?>
    renderStatusScreenBrick(bricksBuilder);
<?php } else { ?>
    <?php if($valor==""){?>
        alert("O valor do pagamento está vazio.");
    <?php } ?>
    renderPaymentBrick(bricksBuilder);
<?php } ?>
var redi = "<?=$URL_REDIRECIONAR;?>";
function check(id, id_venda) {
    var settings = {
        "url": "./process_payment.php?acc=check&id=" + id + "&id_venda="+id_venda,
        "method": "GET",
        "timeout": 0
    };
    $.ajax(settings).done(function(response) {
        try {
            if (response.status == "pago") {
                $("#statusScreenBrick_container").slideUp("fast");
                $("#form-pago").slideDown("fast");
                if (redi != "") {
                    setTimeout(() => {
                        window.location = redi;
                    }, 5000);
                }
            } else {
                setTimeout(() => {
                    check(id)
                }, 3000);
            }
        } catch (error) {
            alert("Erro ao localizar o pagamento, contacte com o suporte");
        }
    });
}
</script>
</body>
</html>
