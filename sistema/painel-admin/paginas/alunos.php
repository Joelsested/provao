<?php
require_once('../conexao.php');
require_once('verificar.php');
require_once(__DIR__ . '/../../../helpers.php');
$pag = 'alunos';

@session_start();

$id_user = @$_SESSION['id'];
$nivel_session = $_SESSION['nivel'] ?? '';
$responsavelAutoLevels = ['Vendedor', 'Tutor', 'Tesoureiro', 'Parceiro'];
$responsavelSelectableLevels = ['Vendedor', 'Tesoureiro', 'Parceiro'];
$precisaEscolherResponsavel = in_array($nivel_session, ['Administrador', 'Secretario'], true) || !in_array($nivel_session, $responsavelAutoLevels, true);
$responsaveis = [];
function flag_professor_responsavel(PDO $pdo, array $usuario): int
{
	$nivel = $usuario['nivel'] ?? '';
	$idPessoa = (int) ($usuario['id_pessoa'] ?? 0);
	if ($idPessoa <= 0) {
		return 0;
	}
	$tabela = $nivel === 'Vendedor' ? 'vendedores' : ($nivel === 'Parceiro' ? 'parceiros' : '');
	if ($tabela === '') {
		return 0;
	}
	try {
		$stmt = $pdo->prepare("SELECT professor FROM {$tabela} WHERE id = :id LIMIT 1");
		$stmt->execute([':id' => $idPessoa]);
		return (int) ($stmt->fetchColumn() ?: 0);
	} catch (Exception $e) {
		return 0;
	}
}
if ($precisaEscolherResponsavel) {
	$placeholders = implode(',', array_fill(0, count($responsavelSelectableLevels), '?'));
	$stmtResponsaveis = $pdo->prepare("SELECT id, nome, nivel, id_pessoa FROM usuarios WHERE nivel IN ($placeholders) AND ativo = 'Sim' ORDER BY nome");
	$stmtResponsaveis->execute($responsavelSelectableLevels);
	$responsaveis = $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC);
	if (in_array($nivel_session, ['Tutor', 'Secretario'], true) && $id_user > 0) {
		$stmtAtendenteAtual = $pdo->prepare("SELECT id, nome, nivel, id_pessoa FROM usuarios WHERE id = :id AND nivel IN ('Tutor', 'Secretario') AND ativo = 'Sim' LIMIT 1");
		$stmtAtendenteAtual->execute([':id' => (int) $id_user]);
		$atendenteAtual = $stmtAtendenteAtual->fetch(PDO::FETCH_ASSOC);
		if ($atendenteAtual) {
			$responsaveis[] = $atendenteAtual;
		}
	}
	foreach ($responsaveis as &$resp) {
		$resp['professor'] = flag_professor_responsavel($pdo, $resp);
	}
	unset($resp);
	$responsaveis = array_values(array_filter($responsaveis, function ($resp) use ($id_user) {
		$nivelResp = (string) ($resp['nivel'] ?? '');
		if ($nivelResp !== 'Secretario') {
			return true;
		}
		return (int) ($resp['id'] ?? 0) === (int) $id_user;
	}));
	$idsResponsaveis = [];
	$responsaveis = array_values(array_filter($responsaveis, function ($resp) use (&$idsResponsaveis) {
		$idResp = (int) ($resp['id'] ?? 0);
		if ($idResp <= 0 || isset($idsResponsaveis[$idResp])) {
			return false;
		}
		$idsResponsaveis[$idResp] = true;
		return true;
	}));
}
$transferLevels = ['Tutor', 'Secretario'];
$placeholdersTransfer = implode(',', array_fill(0, count($transferLevels), '?'));
$stmtTransfer = $pdo->prepare("SELECT id, nome, nivel FROM usuarios WHERE nivel IN ($placeholdersTransfer) AND ativo = 'Sim' ORDER BY nome");
$stmtTransfer->execute($transferLevels);
$responsaveisTransfer = $stmtTransfer->fetchAll(PDO::FETCH_ASSOC);
$dataCorteAtendente = getConfigDateCorteAtendente($pdo);
$destravaTrocaAdmin = getConfigAdminOverrideTrocaAtendente($pdo);

if (@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Tutor' and @$_SESSION['nivel'] != 'Parceiro' and @$_SESSION['nivel'] != 'Professor' and @$_SESSION['nivel'] != 'Vendedor') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}


?>

<style>
    .invalid-feedback {
    color: red !important;
    
}
</style>

<button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Aluno</button>

<?php if (@$_SESSION['nivel'] == 'Administrador') { ?>
	<div class="row" style="margin-top:10px;">
		<div class="col-md-6">
			<div class="form-group">
				<label>Data de corte do atendente</label>
				<div style="display:flex; gap:8px; align-items:center;">
					<input type="date" class="form-control" id="config_data_corte_atendente" value="<?php echo htmlspecialchars($dataCorteAtendente); ?>">
					<button type="button" class="btn btn-primary" id="btn-salvar-data-corte">Salvar</button>
				</div>
				<small id="mensagem_data_corte"></small>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label>Chave destravar troca (somente Admin)</label>
				<div style="display:flex; gap:8px; align-items:center;">
					<select class="form-control" id="config_destrava_troca_admin">
						<option value="0" <?php echo !$destravaTrocaAdmin ? 'selected' : ''; ?>>Travado (regra normal)</option>
						<option value="1" <?php echo $destravaTrocaAdmin ? 'selected' : ''; ?>>Destravado (manual)</option>
					</select>
					<button type="button" class="btn btn-warning" id="btn-salvar-destrava-troca">Salvar Chave</button>
				</div>
				<small id="mensagem_destrava_troca"></small>
			</div>
		</div>
	</div>
<?php } elseif (@$_SESSION['nivel'] == 'Secretario') { ?>
	<div class="row" style="margin-top:10px;">
		<div class="col-md-4">
			<div class="form-group">
				<label>Data de corte do atendente</label>
				<input type="date" class="form-control" id="config_data_corte_atendente" value="<?php echo htmlspecialchars($dataCorteAtendente); ?>">
			</div>
		</div>
		<div class="col-md-2" style="margin-top:25px;">
			<button type="button" class="btn btn-primary" id="btn-salvar-data-corte">Salvar</button>
		</div>
		<div class="col-md-6" style="margin-top:32px;">
			<small id="mensagem_data_corte"></small>
		</div>
	</div>
<?php } ?>

<div class="bs-example widget-shadow" style="padding:15px" id="listar">

</div>





<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog-lg" role="document">
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
								<label>Nome do Aluno*</label>
					          <input type="text" class="form-control" name="nome" id="nome" required>

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Cpf*</label>
							<input type="text" class="form-control" name="cpf" id="cpf"required>

							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								<label>Email*</label>
								<input type="email" class="form-control" name="email" id="email" required>
                               
                               </div>
						    </div>

								<div class="col-md-2">
							<div class="form-group">
								<label> Telefone:</label>
								<input type="text" class="form-control" name="telefone" id="telefone" required>

							

							</div>
						</div>

					</div>

					<?php if ($precisaEscolherResponsavel) { ?>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Responsável*</label>
									<select class="form-control" name="responsavel_id" id="responsavel_id" required>
										<option value="">Selecione</option>
										<?php foreach ($responsaveis as $responsavel) : ?>
											<option value="<?php echo (int) $responsavel['id']; ?>" data-nivel="<?php echo htmlspecialchars($responsavel['nivel']); ?>" data-professor="<?php echo (int) ($responsavel['professor'] ?? 0); ?>" <?php echo ((int) $responsavel['id'] === (int) $id_user) ? 'selected' : ''; ?>>
													<?php echo htmlspecialchars($responsavel['nome']); ?> (<?php echo htmlspecialchars($responsavel['nivel']); ?>)
												</option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php if (@$_SESSION['nivel'] == 'Administrador' || @$_SESSION['nivel'] == 'Secretario') { ?>
								<div class="col-md-3">
									<div class="form-group">
										<label>Transferir Atendente</label>
										<select class="form-control" name="transferir_responsavel_id" id="transferir_responsavel_id">
											<option value="">Selecione</option>
											<?php foreach ($responsaveisTransfer as $responsavel) : ?>
												<?php if (!in_array($responsavel['nivel'], $transferLevels, true)) { continue; } ?>
												<option value="<?php echo (int) $responsavel['id']; ?>">
													<?php echo htmlspecialchars($responsavel['nome']); ?> (<?php echo htmlspecialchars($responsavel['nivel']); ?>)
												</option>
											<?php endforeach; ?>
										</select>
											<small id="transferir_alerta" class="text-muted"></small>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Data da Transferência</label>
										<input type="date" class="form-control" name="data_transferencia_atendente" id="data_transferencia_atendente">
									</div>
								</div>
							<?php } ?>
						</div>
					<?php } ?>

					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Documento:<small><small>( RG, CTPS, etc)</small></small></label>
								<input type="text" class="form-control" name="rg" id="rg">

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Órgão Expedidor:</label>
								<input type="text" class="form-control" name="orgao_expedidor" id="orgao_expedidor">

							</div>
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label>Data de Expedição:</label>
								<input type="text" class="form-control" name="expedicao" id="expedicao">

							</div>
						</div>

					
							<div class="col-md-2">
								<div class="form-group">
									<label>Data de Nascimento:</label>
									<input type="text" class="form-control" name="nascimento" id="nascimento" placeholder="dd-mm-aaaa" required>

								</div>
							</div>
                            
                            
                            <div class="col-md-2">
							<div class="form-group">
								<label>Cep:</label>
								<input type="text" class="form-control" name="cep" id="cep">

							</div>
					     </div>
                        </div>
							
								<div class="row">				
								<div class="col-md-2">
							<div class="form-group">
								<label>Sexo:</label>
								<input type="text" class="form-control" name="sexo" id="sexo">
							</div>
						</div>
					
		
						<div class="col-md-3">
							<div class="form-group">
								<label>Endereço:</label>
								<input type="text" class="form-control" name="endereco" id="endereco">

							</div>
						</div>

						
						<div class="col-md-2">
							<div class="form-group">
								<label>Numero:</label>
								<input type="text" class="form-control" name="numero" id="numero">

							</div>
						</div>

						
						<div class="col-md-2">
							<div class="form-group">
								<label>Bairro:</label>
								<input type="text" class="form-control" name="bairro" id="bairro">

							</div>
						</div>

						

						<div class="col-md-2">
							<div class="form-group">
								<label>Cidade:</label>
								<input type="text" class="form-control" name="cidade" id="cidade">

							</div>
						</div>
                       </div>

                        <div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Estado:</label>
							<input type="text" class="form-control" name="estado" id="estado">

							</div>
						   </div>
					    

						<div class="col-md-4">
							<div class="form-group">
								<label>Nome da Mãe:</label>
								<input type="text" class="form-control" name="mae" id="mae">
							</div>
						   </div>

				

					
						<div class="col-md-4">
							<div class="form-group">
								<label>Nome do Pai:</label>
								<input type="text" class="form-control" name="pai" id="pai">

								</div>
						       </div>
							   </div>

                            <div class="row">
                            <div class="col-md-3">
							<div class="form-group">
								<label>Naturalidade:</label>
								<input type="text" class="form-control" name="naturalidade" id="naturalidade">

							</div>
						   </div>
                          

					    <div class="col-md-4">
							<div class="form-group">
								<label>Foto do Aluno:</label>
								<input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
							</div>
						</div>
						<div class="col-md-2">
							<div id="divImg">
								<img src="img/perfil/sem-perfil.jpg" width="100px" id="target">
							</div>
						</div>

				

			
					<input type="hidden" name="id" id="id">
					<small>
						<div id="mensagem" align="center" class="mt-3"></div>
					</small>

				</div>


				<div class="modal-footer">
					<button id="saveAluno" type="submit" disabled class="btn btn-primary">Salvar</button>
				</div>



			</form>

		</div>
	</div>
</div>



<!-- Modal Arquivos -->
<div class="modal fade" id="modalArquivos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal">Gestão de Arquivos - <span id="nome_arquivo"> </span></h4>
				<button id="btn-fechar-arquivos" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="form-arquivos" method="post">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-8">
							<div class="form-group">
								<label>Arquivo</label>
								<input class="form-control" type="file" name="arquivo_conta" onChange="carregarImgArquivos();" id="arquivo_conta">
							</div>
						</div>
						<div class="col-md-4" style="margin-top:-10px">
							<div id="divImgArquivos">
								<img src="img/arquivos/sem-foto.png" width="60px" id="target-arquivos">
							</div>
						</div>




					</div>

					<div class="row" style="margin-top:-40px">
						<div class="col-md-8">
							<input type="text" class="form-control" name="nome_arq" id="nome_arq" placeholder="Nome do Arquivo * " required>
						</div>

						<div class="col-md-4">
							<button type="submit" class="btn btn-primary">Inserir</button>
						</div>
					</div>

					<hr>

					<small>
						<div id="listar_arquivos"></div>
					</small>

					<br>
					<small>
						<div align="center" id="mensagem_arquivo"></div>
					</small>

					<input type="hidden" class="form-control" name="id_arquivo" id="id_arquivo">


				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Transferir Atendente -->
<div class="modal fade" id="modalTransferir" tabindex="-1" role="dialog" aria-labelledby="modalTransferirLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="modalTransferirLabel">Transferir Atendente - <span id="transferir_nome"></span></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" id="form-transferir">
				<div class="modal-body">
					<input type="hidden" name="aluno_id" id="transferir_aluno_id">
					<div class="form-group">
						<label>Novo Atendente*</label>
						<select class="form-control" name="responsavel_id" id="transferir_responsavel" required>
							<option value="">Selecione</option>
							<?php foreach ($responsaveisTransfer as $responsavel) : ?>
								<?php if (!in_array($responsavel['nivel'], $transferLevels, true)) { continue; } ?>
								<option value="<?php echo (int) $responsavel['id']; ?>">
									<?php echo htmlspecialchars($responsavel['nome']); ?> (<?php echo htmlspecialchars($responsavel['nivel']); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group">
						<label>Motivo (opcional)</label>
						<input type="text" class="form-control" name="motivo" id="transferir_motivo" maxlength="255">
					</div>
					<small>
						<div id="mensagem_transferir" align="center" class="mt-3"></div>
					</small>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Transferir</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php if (@$_SESSION['nivel'] == 'Administrador' || @$_SESSION['nivel'] == 'Secretario') { ?>
<!-- Modal Historico de Atendentes -->
<div class="modal fade" id="modalHistoricoAtendente" tabindex="-1" role="dialog" aria-labelledby="modalHistoricoAtendenteLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="modalHistoricoAtendenteLabel">Historico de Atendentes - <span id="historico_nome"></span></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="historico_atendente_conteudo">Carregando...</div>
			</div>
		</div>
	</div>
</div>
<?php } ?>



<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span></h4>
				<button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">



				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-3">
						<span><b>CPF: </b></span>
						<span id="cpf_mostrar"></span>
					</div>
					<div class="col-md-5">
						<span><b>Email: </b></span>
						<span id="email_mostrar"></span>
					</div>
					<div class="col-md-4">
						<span><b>RG </b></span>
						<span id="rg_mostrar"></span>

					</div>
				</div>
				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-3">
						<span><b>Expedicao: </b></span>
						<span id="expedicao_mostrar"></span>
					</div>


					<div class="col-md-4">
						<span><b>Telefone: </b></span>
						<span id="telefone_mostrar"></span>
					</div>


					<div class="col-md-3">
						<span><b>Cep: </b></span>
						<span id="cep_mostrar"></span>
					</div>
				</div>


				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-12">
						<span><b>Endereço: </b></span>
						<span id="endereco_mostrar"></span>
					</div>

				</div>


				<div class="row" style="border-bottom: 1px solid #cac7c7;">


					<div class="col-md-5">
						<span><b>Cidade: </b></span>
						<span id="cidade_mostrar"></span>
					</div>
					<div class="col-md-2">
						<span><b>Estado: </b></span>
						<span id="estado_mostrar"></span>
					</div>


					<div class="col-md-2">
						<span><b>sexo: </b></span>
						<span id="sexo_mostrar"></span>
					</div>
				</div>

				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-3">
						<span><b>Nascimento: </b></span>
						<span id="nascimento_mostrar"></span>
					</div>
					<div class="col-md-5">
						<span><b>Mae: </b></span>
						<span id="mae_mostrar"></span>
					</div>
				</div>
				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-6">
						<span><b>Pai: </b></span>
						<span id="pai_mostrar"></span>
					</div>
					<div class="col-md-4">
						<span><b>Naturalidade: </b></span>
						<span id="naturalidade_mostrar"></span>
					</div>
				</div>

				<div class="row" style="border-bottom: 1px solid #cac7c7;">
					<div class="col-md-4">
						<span><b>Data Cadastro: </b></span>
						<span id="data_mostrar"></span>
					</div>



					<div class="col-md-3">
						<span><b>Ativo: </b></span>
						<span id="ativo_mostrar"></span>
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
<script src="js/ajax.js?v=<?php echo @filemtime(__DIR__ . '/../js/ajax.js'); ?>"></script>

<?php if (@$_SESSION['nivel'] == 'Administrador' || @$_SESSION['nivel'] == 'Secretario') { ?>
<script type="text/javascript">
	$('#btn-salvar-data-corte').on('click', function() {
		var dataCorte = $('#config_data_corte_atendente').val();
		$.ajax({
			url: 'paginas/' + pag + '/salvar_data_corte.php',
			method: 'POST',
			data: { data_corte: dataCorte },
			success: function(mensagem) {
				$('#mensagem_data_corte').removeClass().text('');
				if (mensagem.trim().toLowerCase().indexOf('salvo') !== -1) {
					$('#mensagem_data_corte').addClass('text-success').text(mensagem);
				} else {
					$('#mensagem_data_corte').addClass('text-danger').text(mensagem);
				}
			},
			error: function() {
				$('#mensagem_data_corte').addClass('text-danger').text('Erro ao salvar data de corte.');
			}
		});
	});

<?php if (@$_SESSION['nivel'] == 'Administrador') { ?>
	$('#btn-salvar-destrava-troca').on('click', function() {
		var destrava = $('#config_destrava_troca_admin').val();
		$.ajax({
			url: 'paginas/' + pag + '/salvar_destrava_troca.php',
			method: 'POST',
			data: { destrava: destrava },
			success: function(mensagem) {
				$('#mensagem_destrava_troca').removeClass().text('');
				if ((mensagem || '').trim().toLowerCase().indexOf('sucesso') !== -1 || (mensagem || '').trim().toLowerCase().indexOf('ativad') !== -1 || (mensagem || '').trim().toLowerCase().indexOf('desativad') !== -1) {
					$('#mensagem_destrava_troca').addClass('text-success').text(mensagem);
				} else {
					$('#mensagem_destrava_troca').addClass('text-danger').text(mensagem);
				}
			},
			error: function() {
				$('#mensagem_destrava_troca').removeClass().addClass('text-danger').text('Erro ao salvar chave de destrava.');
			}
		});
	});
<?php } ?>

	function abrirHistoricoAtendente(id, nome) {
		$('#historico_nome').text(nome || '');
		$('#historico_atendente_conteudo').html('Carregando...');
		$('#modalHistoricoAtendente').modal('show');
		$.ajax({
			url: 'paginas/' + pag + '/listar_historico_atendentes.php',
			method: 'POST',
			data: { aluno_id: id },
			dataType: 'html',
			success: function(result) {
				$('#historico_atendente_conteudo').html(result);
			},
			error: function() {
				$('#historico_atendente_conteudo').html('Erro ao carregar historico.');
			}
		});
	}
</script>
<?php } ?>

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


<script type="text/javascript">
	function listarArquivos() {
		var id = $('#id_arquivo').val();

		$.ajax({
			url: 'paginas/' + pag + "/listar_arquivos.php",
			method: 'POST',
			data: {
				id
			},
			dataType: "html",

			success: function(result) {
				$("#listar_arquivos").html(result);

			}
		});

	}
</script>

<script type="text/javascript">
	function abrirTransferencia(data) {
		if (!data) {
			return;
		}
		$('#transferir_aluno_id').val(data.id || '');
		$('#transferir_nome').text(data.nome || '');
		$('#transferir_responsavel').val(data.responsavel_id || '');
		$('#transferir_motivo').val('');
		$('#mensagem_transferir').text('');
		$('#modalTransferir').modal('show');
	}

	$('#form-transferir').on('submit', function(e) {
		e.preventDefault();
		var formData = new FormData(this);
		if (typeof getCsrfToken === 'function') {
			var token = getCsrfToken();
			if (token && (!formData.has || !formData.has('csrf_token'))) {
				formData.append('csrf_token', token);
			}
		}
		$.ajax({
			url: 'paginas/' + pag + '/transferir_responsavel.php',
			type: 'POST',
			data: formData,
			success: function(mensagem) {
				$('#mensagem_transferir').removeClass().text('');
				if ((mensagem || '').toLowerCase().indexOf('sucesso') !== -1) {
					$('#modalTransferir').modal('hide');
					listar();
				} else {
					$('#mensagem_transferir').addClass('text-danger').text(mensagem);
				}
			},
			error: function(xhr) {
				var msg = (xhr && xhr.responseText) ? xhr.responseText : 'Erro na requisicao.';
				$('#mensagem_transferir').addClass('text-danger').text(msg);
			},
			cache: false,
			contentType: false,
			processData: false
		});
	});
</script>


<script>
    const permitirTransferenciaManualAdmin = <?php echo ((@$_SESSION['nivel'] == 'Administrador' && $destravaTrocaAdmin) ? 'true' : 'false'); ?>;

    function atualizarTransferenciaUI(responsavelProfessor) {
        const $sel = $('#transferir_responsavel_id');
        const $data = $('#data_transferencia_atendente');
        const $hint = $('#transferir_alerta');
        if (!$sel.length) {
            return;
        }

        if (responsavelProfessor || permitirTransferenciaManualAdmin) {
            $sel.prop('disabled', false);
            if ($data.length) $data.prop('disabled', false);
            if ($hint.length) {
                $hint.text(responsavelProfessor ? '' : 'Transferencia manual habilitada pela chave de Admin.');
            }
        } else {
            $sel.val('').prop('disabled', true);
            if ($data.length) {
                $data.val('').prop('disabled', true);
            }
            if ($hint.length) $hint.text('Atendente fixo: responsavel sem Professor marcado.');
        }
    }

    function toggleTransferirPorResponsavel() {
        const $resp = $('#responsavel_id');
        if (!$resp.length) return;
        const selecionada = $resp.find('option:selected').first();
        const professor = selecionada.length ? parseInt(selecionada.data('professor') || 0, 10) : 0;
        atualizarTransferenciaUI(professor === 1);
    }

    $(document).on('change', '#responsavel_id', toggleTransferirPorResponsavel);
    $(document).ready(toggleTransferirPorResponsavel);
</script>
<script>
// --- Função para validar CPF ---
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
    let resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(9))) return false;

    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    return resto === parseInt(cpf.charAt(10));
}

// --- Função para formatar CPF enquanto digita ---
function formatarCPF(input) {
    let valor = input.value.replace(/[^\d]/g, '');
    if (valor.length <= 11) {
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    input.value = valor;
}

// --- Lista dos campos obrigatórios ---
const camposObrigatorios = [
    'nome', 'cpf', 'email', 'telefone', 'nascimento'
];
if (document.getElementById('responsavel_id')) {
    camposObrigatorios.push('responsavel_id');
}

// --- Função para verificar se todos os campos estão preenchidos e válidos ---
function verificarCampos() {
    const botao = document.getElementById('saveAluno');
    const mensagem = document.getElementById('mensagem');
    let todosPreenchidos = true;
    let mensagemCampo = '';

    for (let id of camposObrigatorios) {
        const campo = document.getElementById(id);
        if (!campo.value.trim()) {
            todosPreenchidos = false;
            if (!mensagemCampo) {
                switch (id) {
                    case 'nome':
                        mensagemCampo = 'Informe o nome.';
                        break;
                    case 'cpf':
                        mensagemCampo = 'Informe o CPF.';
                        break;
                    case 'email':
                        mensagemCampo = 'Informe o email.';
                        break;
                    case 'telefone':
                        mensagemCampo = 'Informe o telefone.';
                        break;
                    case 'nascimento':
                        mensagemCampo = 'Informe a data de nascimento.';
                        break;
                    case 'responsavel_id':
                        mensagemCampo = 'Informe o responsável.';
                        break;
                    default:
                        mensagemCampo = 'Preencha os campos obrigatórios.';
                        break;
                }
            }
            break;
        }
    }

    // Verifica se CPF e e-mail são válidos
    const cpf = document.getElementById('cpf').value.trim();
    const email = document.getElementById('email').value.trim();
    const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    const cpfValido = validarCPF(cpf);

    // Se tudo estiver preenchido e válido, habilita o botão
    if (todosPreenchidos && cpfValido && emailValido) {
        botao.removeAttribute('disabled');
        mensagem.innerHTML = '';
        mensagem.classList.remove('text-danger');
    } else {
        botao.setAttribute('disabled', true);
        if (mensagemCampo) {
            mensagem.innerHTML = mensagemCampo;
            mensagem.classList.add('text-danger');
        } else {
            mensagem.innerHTML = '';
            mensagem.classList.remove('text-danger');
        }
    }
}

// --- Adiciona eventos para atualizar o estado do botão em tempo real ---
camposObrigatorios.forEach(id => {
    const campo = document.getElementById(id);
    if (campo) {
        campo.addEventListener('input', verificarCampos);
        campo.addEventListener('change', verificarCampos);
        campo.addEventListener('blur', verificarCampos);
    }
});

// --- M?scara e validação de CPF em tempo real ---
const inputCPF = document.getElementById('cpf');
inputCPF.addEventListener('input', function() {
    formatarCPF(this);
    verificarCampos();
});

inputCPF.addEventListener('blur', function() {
    const cpf = this.value;
    if (cpf && !validarCPF(cpf)) {
        this.classList.add('is-invalid');
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
            const erro = document.createElement('div');
            erro.className = 'invalid-feedback';
            erro.textContent = 'CPF inválido!';
            this.parentNode.appendChild(erro);
        }
    } else {
        this.classList.remove('is-invalid');
        const erro = this.parentNode.querySelector('.invalid-feedback');
        if (erro) erro.remove();
    }
    verificarCampos();
});

// --- Também chama a verificação inicial ---
verificarCampos();
</script>



<script>
	document.getElementById('cep').addEventListener('input', function() {
		let cep = this.value.replace(/\D/g, ''); // Remove caracteres não numéricos

	if (cep.length === 8) { // Verifica se o CEP tem 8 dígitos
		fetch(`https://viacep.com.br/ws/${cep}/json/`)
			.then(response => response.json())
			.then(data => {
				if (!data.erro) {
					document.getElementById('endereco').value = `${data.logradouro}, ${data.bairro}`;
					document.getElementById('cidade').value = data.localidade;
					document.getElementById('estado').value = data.uf;
				} else {
					alert("CEP não encontrado!");
				}
			})
			.catch(error => console.error('Erro ao buscar o CEP:', error));
    }
});
</script>









