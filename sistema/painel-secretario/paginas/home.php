<?php 

//verificar matriculas pendentes
$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = :status");
$query->execute([
	':aluno' => $id_usuario,
	':status' => 'Aguardando',
]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_m = @count($res);
if($total_m > 0){

	for($i=0; $i < $total_m; $i++){
		
		$id_mat = @$res[$i]['id'];
		$id_curso = @$res[$i]['id_curso'];
		$sub_total = @$res[$i]['subtotal'];
		$status = @$res[$i]['status'];
		$pacote = @$res[$i]['pacote'];
		$boleto = @$res[$i]['boleto'];
		$ref_api = @$res[$i]['ref_api'];
		$forma_pg = @$res[$i]['forma_pgto'];

		/*
		if($boleto != "" and $status == 'Aguardando'){
			require("../../pagamentos/boletos/notificacoes.php");


			if(@$status_boleto == 'paid'){
				$id_matricula = $id_mat;
				$forma_pgto = 'Boleto';
				$total_recebido = $sub_total - $taxa_boleto;
				require("../../pagamentos/aprovar_matricula.php");
			}

		}
		*/

		if($ref_api != "" and $status == 'Aguardando'){
			$pix_api = 'Sim';
			require("../../pgtos/cartao/consulta.php");

			if(@$status_api == 'approved'){
				$id_matricula = $id_mat;
				$forma_pgto = $forma_pg;
				if($forma_pgto == 'Boleto'){
					$total_recebido = $sub_total - $taxa_boleto;
				}else{
					$total_recebido = $sub_total;
				}
				
				require("../../pagamentos/aprovar_matricula.php");
			}

		}
	}
}




		$total_mat = 0;
		$total_mat_pendentes = 0;
		$total_mat_aprovadas = 0;
		$total_cursos_finalizados = 0;
		$total_cartoes = 0;

		$total_itens_preenchidos = 3;
		$total_itens_perfil = 10;
		$porcentagemPerfil = 0;
		$porcentagemCursos = 0;

		$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
		$query->execute([':id' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$id_aluno = $res[0]['id_pessoa'];

		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno");
		$query->execute([':aluno' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_mat = @count($res);

		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = :status");
		$query->execute([
			':aluno' => $id_usuario,
			':status' => 'Aguardando',
		]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_mat_pendentes = @count($res);


		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = :status");
		$query->execute([
			':aluno' => $id_usuario,
			':status' => 'Finalizado',
		]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_cursos_finalizados = @count($res);


		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = :status");
		$query->execute([
			':aluno' => $id_usuario,
			':status' => 'Matriculado',
		]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_mat_aprovadas = @count($res) + $total_cursos_finalizados;





		$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
		$query->execute([':id' => $id_aluno]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_cartoes = $res[0]['cartao'];


		$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
		$query->execute([':id' => $id_aluno]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);

		if($res[0]['cpf'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['email'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['rg'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['expedicao'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['telefone'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['cep'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['endereco'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['cidade'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['estado'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['sexo'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['nascimento'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['mae'] != ""){
			$total_itens_preenchidos += 1;
		}
        
         if($res[0]['pai'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['naturalidade'] != ""){
			$total_itens_preenchidos += 1;
		}

		if($res[0]['foto'] != "sem-perfil.jpg"){
			$total_itens_preenchidos += 1;
		}


		$porcentagemPerfil = ($total_itens_preenchidos / $total_itens_perfil) * 100;
		if($porcentagemPerfil < 100){
			$cor_porcent = 'demo-pie-3';
		}else{
			$cor_porcent = 'demo-pie-1';
		}

		if($total_cursos_finalizados > 0 && $total_mat_aprovadas > 0){
			$porcentagemCursos = ($total_cursos_finalizados / $total_mat_aprovadas) * 100;
		}

		$porcentagemPerfilF = round($porcentagemPerfil, 2);
		$porcentagemCursosF = round($porcentagemCursos, 2);

		?>

		<div class="col_3 margem-mobile">
			<div class="col-md-3 widget widget1">
				<div class="r3_counter_box">
					<i class="pull-left fa fa-book icon-rounded"></i>
					<div class="stats">
						<h5><strong><big><big><?php echo $total_mat ?></big></big></strong></h5>

					</div>
					<hr style="margin-top:10px">
					<div align="center"><span>Total de Matrículas</span></div>
				</div>
			</div>
			<div class="col-md-3 widget widget1">
				<div class="r3_counter_box">
					<i class="pull-left fa fa-laptop user1 icon-rounded"></i>
					<div class="stats">
						<h5><strong><big><big><?php echo $total_mat_pendentes ?></big></big></strong></h5>

					</div>
					<hr style="margin-top:10px">
					<div align="center"><span>Matrículas Pendentes</span></div>
				</div>
			</div>
			<div class="col-md-3 widget widget1">
				<div class="r3_counter_box">
					<i class="pull-left fa fa-laptop dollar2 icon-rounded"></i>
					<div class="stats">
						<h5><strong><big><big><?php echo $total_mat_aprovadas ?></big></big></strong></h5>

					</div>
					<hr style="margin-top:10px">
					<div align="center"><span>Matrículas Aprovadas</span></div>
				</div>
			</div>
			<div class="col-md-3 widget widget1">
				<div class="r3_counter_box">
					<i class="pull-left fa fa-book user2 icon-rounded"></i>
					<div class="stats">
						<h5><strong><big><big><?php echo $total_cursos_finalizados ?></big></big></strong></h5>

					</div>
					<hr style="margin-top:10px">
					<div align="center"><span>Cursos Finalizados</span></div>
				</div>
			</div>
			<div class="col-md-3 widget esc">
				<div class="r3_counter_box">
					<i class="pull-left fa fa-credit-card dollar1 icon-rounded"></i>
					<div class="stats">
						<h5><strong><big><big><?php echo $total_cartoes ?></big></big></strong></h5>

					</div>
					<hr style="margin-top:10px">
					<div align="center"><span>Cartões Fidelidade</span></div>
				</div>
			</div>
			<div class="clearfix"> </div>
		</div>




		
		<div class="row-one widgettable">
			<div class="col-md-9 content-top-2 card" style="padding-top: 5px">
				<h4 style="margin-top: 15px">Últimas Matrículas</h4>
				<hr>
				<div class="row">
					<?php 
					$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno order by id desc limit 8");
					$query->execute([':aluno' => $id_usuario]);
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					$total_m = @count($res);
					if($total_m > 0){

						for($i=0; $i < $total_m; $i++){
							foreach ($res[$i] as $key => $value){}
								$id_mat = $res[$i]['id'];
							$id_curso = $res[$i]['id_curso'];
							$sub_total = $res[$i]['subtotal'];
							$status = $res[$i]['status'];
							$pacote = $res[$i]['pacote'];
							$boleto = $res[$i]['boleto'];
							if($pacote == 'Sim'){
								$tab = 'pacotes';
								$link = 'cursos-do-';
							}else{
								$tab = 'cursos';
								$link = 'curso-de-';
							}

							

							$query2 = $pdo->prepare("SELECT * FROM $tab where id = :id");
							$query2->execute([':id' => $id_curso]);
							$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
							$nome_curso = $res2[0]['nome'];
							$foto_curso = $res2[0]['imagem'];
							$nome_curso = mb_strimwidth($nome_curso, 0, 20, "...");
							$nome_url = $res2[0]['nome_url'];
							$url_do_curso = $link.$nome_url;

							if($pacote != 'Sim'){
								$query2 = $pdo->prepare("SELECT * FROM aulas where curso = :curso");
								$query2->execute([':curso' => $id_curso]);
								$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
								$aulas_curso = @count($res2);	
							}
							



							?>

							<div class="col-md-3 col-sm-6 col-xs-6" style="margin-bottom: 15px">
								<img src="../painel-admin/img/<?php echo $tab ?>/<?php echo $foto_curso ?>" width="100%" height="150px">
								<p align="center"><small><?php echo mb_strtoupper($nome_curso) ?></small> <br>


									<?php if($status == 'Aguardando'){ ?>	
										<form method="post" action="../../<?php echo $url_do_curso ?>" target="_blank">
											<div align="center">
												<small><small><span class="text-danger">Pendente</span> 
													<span> - 


														<button  type="submit" style="background-color: transparent;  border:none!important;"><i class="fa fa-money verde" ></i><span class="verde" style="margin-left:2px">Pagar</span>
														</button>
														<input type="hidden" name="painel_aluno" value="sim">


													</span>
												</small></small>
											</div>
										</form>
									<?php }else{ ?>
										<?php 
										if($pacote != 'Sim'){
											?>
											<div align="center">
												<form method="post" action="index.php?pagina=cursos" >		
													<small><small><button  type="submit" style="background-color: transparent;  border:none!important;">
														Ir para o Curso
													</button></small></small>
													<input type="hidden" name="id_mat_post" value="<?php echo $id_mat ?>">
													<input type="hidden" name="id_curso_post" value="<?php echo $id_curso ?>">
													<input type="hidden" name="nome_curso_post" value="<?php echo $nome_curso ?>">
													<input type="hidden" name="aulas_curso_post" value="<?php echo $aulas_curso ?>">
												</form>
											</div>
										<?php }else{ ?>
											<div align="center">
												<form method="post" action="index.php?pagina=cursos" >	
													<small><small><button  type="submit" style="background-color: transparent;  border:none!important;">
														Ir para os Cursos
													</button></small></small>
													<input type="hidden" name="id_pacote" value="<?php echo $id_curso ?>">	
												</form>
											</div>
										<?php } ?>
									<?php } ?>


								</p>
							</div>

						<?php }

					}else{
						echo '<p style="margin-bottom: 15px">Você não possui ainda nenhuma matrícula!</p>';
					}
					?>
				</div>
			</div>



			<a href="" data-toggle="modal" data-target="#modalPerfil">
				<div class="col-md-3 stat">
					<div class="content-top-1">
						<div class="col-md-6 top-content">
							<h5>Perfil Aluno</h5>
							<label><?php echo $porcentagemPerfilF ?>%</label>
						</div>
						<div class="col-md-6 top-content1">	   
							<div id="<?php echo $cor_porcent ?>" class="pie-title-center" data-percent="<?php echo $porcentagemPerfil ?>"> <span class="pie-value"></span> </div>
						</div>
						<div class="clearfix"> </div>
					</div>
					<div class="content-top-1">
						<div class="col-md-6 top-content">
							<h5>Cursos Finalizados</h5>
							<label><?php echo $porcentagemCursosF ?>%</label>
						</div>
						<div class="col-md-6 top-content1">	   
							<div id="demo-pie-2" class="pie-title-center" data-percent="<?php echo $porcentagemCursos ?>"> <span class="pie-value"></span> </div>
						</div>
						<div class="clearfix"> </div>
					</div>

				</div>
			</a>


			
			<div class="clearfix"> </div>
		</div>




		<!-- for amcharts js -->
		<script src="js/amcharts.js"></script>
		<script src="js/serial.js"></script>
		<script src="js/export.min.js"></script>
		<link rel="stylesheet" href="css/export.css" type="text/css" media="all" />
		<script src="js/light.js"></script>
		<!-- for amcharts js -->

		<script  src="js/index1.js"></script>

		




