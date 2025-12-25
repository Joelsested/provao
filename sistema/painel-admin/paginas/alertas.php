<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'alertas';

if(@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}

if($_SESSION['nivel'] == 'Administrador'){	
	$id_usuario = '%%';
}else{	
	$id_usuario = '%'.$_SESSION['id'].'%';
}
?>


<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Alerta</button>


<div class="bs-example widget-shadow" style="padding:15px" id="listar">
	
</div>





<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"></h4>
				<button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" id="form-niceEdit">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Título</label> 
								<input type="text" class="form-control" name="titulo" id="titulo" required> 
							</div>						
						</div>

						<div class="col-md-5">						
							<div class="form-group"> 
								<label>Link</label> 
								<input type="text" class="form-control" name="link" id="link"> 
							</div>						
						</div>	


						<div class="col-md-3">						
							<div class="form-group"> 
								<label>Data Expirar</label> 
								<input type="date" class="form-control" name="data" id="data" value="<?php echo date('Y-m-d') ?>"> 
							</div>						
						</div>	
				

					</div>



					<div class="row">	

						<div class="col-md-8 col-sm-12">						
							<div class="form-group"> 
								<label>Descrição do Alerta</label> 
								<textarea name="descricao" id="area" class="textarea"> </textarea>
							</div>						
						</div>	

						

						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Imagem</label> 
								<input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
							</div>

							<div id="divImg">
								<img src="img/alertas/sem-foto.png"  width="130px" id="target">									
							</div>						
						</div>


					</div>	


					<div class="row">
						<div class="col-md-8">
							<div class="form-group"> 
								<label>Vídeo <small>(Url Incorporada)</small></label> 
								<input onkeyup="carregarVideo();" type="text" name="video" id="video" class="form-control">
								
							</div>
						</div>

						<div class="col-md-2" style="margin-top:15px">
							<iframe width="100%" height="80" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target-video"></iframe>
						</div>


						<div class="col-md-2" style="margin-top:17px">
							<button type="submit" class="btn btn-primary">Salvar</button>
						</div>
					</div>


					<br>
					<input type="hidden" name="id" id="id"> 
					<small><div id="mensagem" align="center" class="mt-3"></div></small>					

				</div>


				

				



			</form>

		</div>
	</div>
</div>




<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal">Alerta <span id="nome_mostrar"> </span>  </h4>
				<button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			
			<div class="modal-body">			



				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-12">							
						<span><b>descricao: </b></span>
						<span id="descricao_mostrar"></span>							
					</div>					

				</div>


				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-8">							
						<span><b>Link: </b></span>
						<span id="link_mostrar"></span>
					</div>
					<div class="col-md-4">							
						<span><b>Data Expiração: </b></span>
						<span id="data_mostrar"></span>
					</div>
				
				</div>


				
				<div class="row">
					<div class="col-md-6" align="center">		
						<img  width="100%" id="target_mostrar">	
					</div>

					<div class="col-md-6" align="center">		
						<iframe width="100%" height="250" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target_video_mostrar"></iframe>
					</div>
				</div>



			</div>


		</div>
	</div>
</div>





	<script type="text/javascript">var pag = "<?=$pag?>"</script>
	<script src="js/ajax.js"></script>


	<script type="text/javascript">
		$(document).ready( function () {
			$('#tabela2').DataTable({
				"ordering": false,
				"stateSave": true,
			});
			$('#tabela_filter label input').focus();
		} );
	</script>


	<script type="text/javascript">
		$(document).ready(function() {
			$('.sel2').select2({
				dropdownParent: $('#modalForm')
			});
		});
	</script>


	<script type="text/javascript">
		function carregarImg() {
			var target = document.getElementById('target');
			var file = document.querySelector("#foto").files[0];

			var reader = new FileReader();

			reader.onloadend = function () {
				target.src = reader.result;
			};

			if (file) {
				reader.readAsDataURL(file);

			} else {
				target.src = "";
			}
		}
	</script>




	<script type="text/javascript">

		$("#form-niceEdit").submit(function () {	
			event.preventDefault();
			nicEditors.findEditor('area').saveContent();
			var formData = new FormData(this);

			$.ajax({
				url: 'paginas/' + pag + "/inserir.php",
				type: 'POST',
				data: formData,

				success: function (mensagem) {
					$('#mensagem').text('');
					$('#mensagem').removeClass()
					if (mensagem.trim() == "Salvo com Sucesso") {                    
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




	<script type="text/javascript">
		function carregarVideo() {		
			$('#target-video').attr('src', $('#video').val());
		}
	</script>


	<script src="//js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
	<script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script>
