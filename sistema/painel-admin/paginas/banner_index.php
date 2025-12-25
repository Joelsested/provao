<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'banner_index';

if(@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


  <button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Banner</button>


 <div class="bs-example widget-shadow" style="padding:15px" id="listar">
	
</div>





<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"></h4>
				<button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" id="form">
				<div class="modal-body">

					
						<div class="col-md-6">						
							<div class="form-group"> 
								<label>Título <small>(Max 35 Caracteres)</small></label> 
								<input maxlength="35" type="text" class="form-control" name="titulo" id="titulo" required> 
							</div>						
						</div>					


					<div class="col-md-6">						
							<div class="form-group"> 
								<label>Link</label> 
								<input type="text" class="form-control" name="link" id="link"> 
							</div>						
						</div>	


						<div class="col-md-12">						
							<div class="form-group"> 
								<label>Descrição <small>(Max 160 Caracteres)</small></label> 
								<input maxlength="160" type="text" class="form-control" name="descricao" id="descricao" required> 
							</div>						
						</div>					
		

										

						<div class="col-md-8">						
							<div class="form-group"> 
								<label>Foto <small>(Aconselhável 940 x 627 px)</small></label> 
								<input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
							</div>						
						</div>
						<div class="col-md-4">
							<div id="divImg">
								<img src="img/login/sem-foto.png"  width="100%" id="target">									
							</div>
						</div>

						
					

					<br>
					<input type="hidden" name="id" id="id"> 
					<small><div id="mensagem" align="center" class="mt-3"></div></small>					

				</div>

				
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Salvar</button>
				</div>



			</form>

		</div>
	</div>
</div>






<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


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
