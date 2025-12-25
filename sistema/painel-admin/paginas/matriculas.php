<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'matriculas';


if(@$_SESSION['nivel'] != 'Administrador'  and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">
	
</div>




<!-- Modal Aulas -->
<div class="modal fade" id="modalAprovar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Aprovar Matrícula - <span id="nome_curso"> </span></h4>
				<button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				<form method="post" id="form-aprovar">
				<div class="modal-body">
					
					<div class="row">
						<div class="col-md-6">						
							<div class="form-group"> 
								<label>Total Pago</label> 
								<input type="text" class="form-control" name="valor" id="valor" required> 
							</div>						
						</div>

						<div class="col-md-6">						
							<div class="form-group"> 
								<label>Forma PGTO</label> 
									<select class="form-control sel13" name="forma_pgto" style="width:100%;">	
										<option value="Pix">Pix</option>
										<option value="MP">MP</option>
										<option value="Boleto">Boleto</option>
										<option value="Paypal">Paypal</option>
										<option value="">Outra</option>
									</select> 
							</div>						
						</div>	
					</div>


					<div class="row">
						<div class="col-md-9">	
							<div class="form-group"> 
								<label>OBS</label> 
								<input type="text" class="form-control" name="obs" id="obs" > 
							</div>	
						</div>

						<div class="col-md-3">	
							<div class="form-group"> 
								<label>Cartão Fid</label> 
								<select class="form-control sel13" name="cartao" style="width:100%;">
										<option value="Sim">Sim</option>
										<option value="Não">Não</option>
									</select> 
							</div>	
						</div>
					</div>

					
					<input type="hidden" name="id_mat" id="id_mat">
					<small><div id="mensagem" align="center" class="mt-3"></div></small>		
				
					</div>

					<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Aprovar</button>
				</div>

				</form>


				</div>	

						

		</div>
	</div>
</div>



<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>



<script type="text/javascript">
	function modalAprovar(id_pergunta, pergunta){
		$('#pergunta_resposta').text(pergunta);
		$('#id_pergunta_resposta').val(id_pergunta);		
		$('#modalResposta').modal('show');
		listarRespostas(id_pergunta);
	}
</script>



<script type="text/javascript">
	
$("#form-aprovar").submit(function () {	
	event.preventDefault();	
	var formData = new FormData(this);

	$('#mensagem').addClass('text-primary')
    $('#mensagem').text('...Carregando Aprovação')

	$.ajax({
		url: 'paginas/' + pag + "/aprovar.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
			alert(mensagem)
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Matriculado com Sucesso") {                    
                    $('#btn-fechar').click();
                    listar();
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

