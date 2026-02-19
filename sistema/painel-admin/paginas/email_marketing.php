<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'email_marketing';

//totalizar emails do banco
$query = $pdo->query("SELECT * FROM emails where enviar = 'sim'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_emails = @count($res);

?>


<div class="container bs-example widget-shadow pagina-mark" style="margin-top: -5px; padding:10px;">

	<h4 class="modal-title" id="exampleModalLabel">Email Marketing - 
						<small>Total de Emails Cadastrados : <?php echo $total_emails ?></small>

					</h4>

					<hr>
		<form method="post" id="form-marketing">
				

						<div class="row">
							<div class="col-md-6">						
								<div class="form-group"> 
									<label>Assunto</label> 
									<input type="text" class="form-control" name="assunto_email" id="assunto_email" value="" required> 
								</div>						
							</div>
							<div class="col-md-6">
								<div class="form-group"> 
									<label>Link</label> 
									<input type="text" class="form-control" name="link_email" id="link_email" value=""> 
								</div>
							</div>					

							
						</div>

						<div class="row">
								<div class="col-md-12">
								<div class="form-group"> 
									<label>Mensagem</label> 
									<textarea name="mensagem" id="area" class="textareag"> </textarea>
								</div>
							</div>
						</div>

			
					<hr>

					<small><div align="center" id="mensagem"></div></small>
					
					<div align="right">
						<button type="submit" class="btn btn-primary">Disparar Email</button>
					</div>
				</form>
</div>

<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>

<script src="//js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
<script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script>






<script type="text/javascript">
	
$("#form-marketing").submit(function () {	
	event.preventDefault();
	nicEditors.findEditor('area').saveContent();
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/email.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Enviado com Sucesso") { 
            $('#mensagem').addClass('verde')                   
                    $('#mensagem').text(mensagem)
                } else {
                	$('#mensagem').addClass('text-danger')
                    $('#mensagem').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>
				