<?php
include_once('../../sistema/conexao.php');
?>

<!DOCTYPE html>
<html>
<head>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">

    <script src="https://www.paypalobjects.com/api/checkout.js"></script>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

    <title>Pagamento Paypal</title>
</head>
<body>

	<!-- Modal Contatos -->
      <div id="modalPaypal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
         <!-- Modal content-->
          <div class="modal-content">
            <form method="POST" action="">
              <div class="modal-header">
              
                <h5 class="modal-title"><small>Ir para o Painél do Aluno</small></h5>
               

                <button type="submit" class="close" name="fecharModal">&times;</button>
              </form>
            </div>
            
            <div class="modal-body">

              <p class="text-muted">Seu pagamento foi processado com sucesso, em breve estará aprovado e será liberado no painél do aluno, vá até seu painél de cursos para poder iniciar os estudos.</p>

            </div>


            <a class="text-danger ml-2" href="<?php echo $url_sistema; ?>sistema/painel-aluno/" target="_blank">Ir para o Painél do Aluno</a>


            <div class="modal-footer">
                 <i class="fa fa-whatsapp neutra-escura" style="color:#FFF; margin-right:5px"></i><a href="http://api.whatsapp.com/send?1=pt_BR&phone=55<?php echo $tel_sistema ?>" target="_blank"><?php echo $tel_sistema ?></a>
            </div>
                   
            
          </div>
        </div>
      </div>  


</body>
</html> 



 <script> $("#modalPaypal").modal("show"); </script> 