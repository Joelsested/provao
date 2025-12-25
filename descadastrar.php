<?php
    require_once("cabecalho.php");
 
?>



<body>
    <div class="container" style="margin-top: 20px">
        <div class="row">
			<div class="col-md-5 mx-auto">
			<div id="first">
				<div class="myform form ">
					 <div class="logo mb-3">
						 <div class="col-md-12 text-center">
							<h5>Descadastrar da Nossa Lista de Email</h5>
						 </div>
					</div>
          <hr>
          <br>
                   <form method="post">
                    <div class="col-md-12 text-center mt-4">
                           <div class="form-group">                              
                              <input type="text" name="email"  class="form-control" id="email" aria-describedby="emailHelp" placeholder="Insira seu Email">
                           </div>

                            <small><div align="center" id="div-mensagem-rec"></div></small>
                         </div>

                          
                         
                          

                           <div class="col-md-12 text-center mt-4">
                              <button name="btn-descadastrar" id="btn-descadastrar" class=" btn btn-block mybtn btn-primary tx-tfm">Descadastrar</button>
                           </div>
                          
                         
                        </form>
                 
				</div>
			</div>
			  
			</div>
		</div>
      </div>   
         
</body>


<br><br><br><br>
<hr>


<?php
    require_once("rodape.php");
 
?>


</html>




<script type="text/javascript">
    $('#btn-descadastrar').click(function(event){
        event.preventDefault();
        
        $.ajax({
            url:"ajax/cursos/ajax-descadastrar.php",
            method:"post",
            data: $('form').serialize(),
            dataType: "text",
            success: function(msg){
                if(msg.trim() === 'Descadastrado da Lista com Sucesso!'){
                    
                    $('#div-mensagem-rec').addClass('text-success')
                    $('#div-mensagem-rec').text(msg);
                                        
                    }else if(msg.trim() === 'Preencha o Campo Email!'){
                      $('#div-mensagem-rec').addClass('text-danger')
                      $('#div-mensagem-rec').text(msg);

                    }else if(msg.trim() === 'Este email não está cadastrado!'){
                      $('#div-mensagem-rec').addClass('text-danger')
                      $('#div-mensagem-rec').text(msg);
                    }



                 else{
                    $('#div-mensagem-rec').addClass('text-danger')
                    $('#div-mensagem-rec').text('Deu erro ao Enviar o Formulário! Provavelmente seu servidor de hospedagem não está com permissão de envio habilitada ou você está em um servidor local');
                   

                 }
            }
        })
    })
</script>



<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>

  <script src="../js/mascara.js"></script>

