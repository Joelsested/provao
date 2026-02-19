<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'parceiros';

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}

$consulta_tutores = $pdo->query("SELECT id, nome FROM tutores WHERE ativo = 'Sim' ORDER BY nome");
$tutores = $consulta_tutores ? $consulta_tutores->fetchAll(PDO::FETCH_ASSOC) : [];
$consulta_secretarios = $pdo->query("SELECT id, nome FROM secretarios WHERE ativo = 'Sim' ORDER BY nome");
$secretarios = $consulta_secretarios ? $consulta_secretarios->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Parceiro</button>


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
						<div class="col-md-6">
							<div class="form-group">
								<label>Nome</label>
								<input type="text" class="form-control" name="nome" id="nome" required>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label>Telefone</label>
								<input type="text" class="form-control" name="telefone" id="telefone">
							</div>
						</div>




					</div>


					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>CPF</label>
								<input type="text" class="form-control" name="cpf" id="cpf">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label>Email</label>
								<input type="email" class="form-control" name="email" id="email" required>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Data de Nascimento</label>
								<input type="text" class="form-control" name="nascimento" id="nascimento" required>
							</div>
						</div>
					</div>

					<div class="col-md-12">
						<div class="form-group">
							<label>Indentificador Banco</label>
							<input type="text" class="form-control" name="wallet_id" id="wallet_id" required>
						</div>
					</div>

					<div class="col-md-6">
						<div class="form-group">
							<label>Comissão</label>
							<input type="number" class="form-control" name="comissao" id="comissao">
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 d-flex align-items-center" style="margin-top: 30px;">
							<div class="form-group">
								<input type="checkbox" name="professor" id="professor" class="mr-2">
								<label for="professor">Professor</label>
							</div>
						</div>
						<div class="col-md-6" id="atendente-wrapper" style="display:none;">
							<div class="form-group">
								<label>Atendente padrão</label>
								<select class="form-control" name="atendente" id="atendente">
									<option value="">Selecione</option>
									<?php if (!empty($tutores)) : ?>
										<optgroup label="Tutores">
											<?php foreach ($tutores as $tutor) : ?>
												<option value="tutor:<?= htmlspecialchars($tutor['id']) ?>"><?= htmlspecialchars($tutor['nome']) ?></option>
											<?php endforeach; ?>
										</optgroup>
									<?php endif; ?>
									<?php if (!empty($secretarios)) : ?>
										<optgroup label="Secretários">
											<?php foreach ($secretarios as $secretario) : ?>
												<option value="secretario:<?= htmlspecialchars($secretario['id']) ?>"><?= htmlspecialchars($secretario['nome']) ?></option>
											<?php endforeach; ?>
										</optgroup>
									<?php endif; ?>
									<?php if (empty($tutores) && empty($secretarios)) : ?>
										<option value="" disabled>Nenhum atendente cadastrado</option>
									<?php endif; ?>
								</select>
							</div>
						</div>
					</div>


					<div class="row">


						<div class="col-md-8">
							<div class="form-group">
								<label>Foto</label>
								<input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
							</div>
						</div>
						<div class="col-md-4">
							<div id="divImg">
								<img src="img/perfil/sem-perfil.jpg" width="100px" id="target">
							</div>
						</div>

					</div>


					<br>
					<input type="hidden" name="id" id="id">
					<small>
						<div id="mensagem" align="center" class="mt-3"></div>
					</small>

				</div>


				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Salvar</button>
				</div>



			</form>

		</div>
	</div>
</div>






<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span></h4>
				<button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">



				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-6">
						<span><b>CPF: </b></span>
						<span id="cpf_mostrar"></span>
					</div>
					<div class="col-md-6">
						<span><b>Telefone: </b></span>
						<span id="telefone_mostrar"></span>
					</div>
				</div>

				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-6">
						<span><b>Data de Nascimento: </b></span>
						<span id="nascimento_mostrar"></span>
					</div>
				</div>


				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-7">
						<span><b>Email: </b></span>
						<span id="email_mostrar"></span>
					</div>

					<div class="col-md-5">
						<span><b>Data Cadastro: </b></span>
						<span id="data_mostrar"></span>
					</div>

				</div>

				<div class="col-md-5" style="margin-top: 15px;">
					<span><b>Comissão: </b></span>
					<span id="comissao_mostrar"></span>%

				</div>

				<div class="col-md-5" style="margin-top: 15px;">
					<span><b>Professor: </b></span>
					<span id="professor_mostrar"></span>

				</div>

				<!-- <div class="col-md-8">
					<div class="form-group">
						<label>Indentificador EFI</label>
						<input type="text" class="form-control" name="walletId" id="walletId" required>
					</div>
				</div> -->
				<div class="col-md-12" style="margin-top: 15px;">
					<div class="form-group">
						<label>Indentificador EFI</label>
						<br>
						<!-- <input type="text" class="form-control" name="wallet_id" id="wallet_id" required value="wallet_id"> -->
						<div style="border: 1px solid #dedede; border-radius: 5px; padding: 5px; width: 100%;">
							<span style="margin-top: 5px;" id="walletId"></span>
						</div>
					</div>
				</div>


				<div class="row">
					<div class="col-md-12" align="center">
						<img width="200px" id="target_mostrar">
					</div>
				</div>



			</div>


		</div>
	</div>
</div>




<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$('#modalForm').on('shown.bs.modal', function() {
			toggleAtendente();
		});
	});
</script>

<script type="text/javascript">
	function toggleAtendente() {
		const wrapper = document.getElementById('atendente-wrapper');
		const professorField = document.getElementById('professor');
		const professorMarcado = professorField ? professorField.checked : false;
		if (wrapper) {
			wrapper.style.display = professorMarcado ? 'block' : 'none';
		}
	if (!professorMarcado) {
		const atendenteSelect = document.getElementById('atendente');
		if (atendenteSelect) {
			atendenteSelect.value = '';
		}
	}
	}

	const checkboxProfessor = document.getElementById('professor');
	if (checkboxProfessor) {
		checkboxProfessor.addEventListener('change', toggleAtendente);
	}
</script>


<script type="text/javascript">
	function carregarImg() {
		var target = document.getElementById('target');
		var file = document.querySelector("#foto").files[0];

		var reader = new FileReader();

		reader.onloadend = function() {
			target.src = reader.result;
		};

		if (file) {
			reader.readAsDataURL(file);

		} else {
			target.src = "";
		}
	}
</script>



