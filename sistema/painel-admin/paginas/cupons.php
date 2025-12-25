<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'cupons';

if(@$_SESSION['nivel'] != 'Administrador'  and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


  <button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Cupom</button>


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

					<div class="row">
						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Código</label> 
								<input maxlength="25" type="text" class="form-control" name="codigo" id="codigo" required> 
							</div>						
						</div>

						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Tipo</label> 
								<select class="form-control" name="tipo" id="tipo">
									<option value="Fixo">Valor Fixo R$</option>
									<option value="%">Porcentagem %</option>
								</select>
							</div>						
						</div>

						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Valor</label> 
								<input type="text" class="form-control" name="valor" id="valor" required> 
							</div>						
						</div>


					</div>

					<div class="row">
						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Validade</label> 
								<input type="date" class="form-control" name="data" id="data"> 
							</div>						
						</div>


						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Quantidade</label> 
								<input type="number" class="form-control" name="quantidade" id="quantidade" required="" value="1"> 
							</div>						
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

