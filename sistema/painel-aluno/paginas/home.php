<?php 

//verificar matriculas pendentes
$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = 'Aguardando'");
$query->execute([':aluno' => $id_usuario]);
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

		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = 'Aguardando'");
		$query->execute([':aluno' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_mat_pendentes = @count($res);


		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = 'Finalizado'");
		$query->execute([':aluno' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		$total_cursos_finalizados = @count($res);


		$query = $pdo->prepare("SELECT * FROM matriculas where aluno = :aluno and status = 'Matriculado'");
		$query->execute([':aluno' => $id_usuario]);
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
                    $matriculas = [];

                    foreach ($res as $matricula) {
                        $id_matricula = $matricula['id'];

                        // Agora busca os pagamentos referentes a essa matrícula
                        $stmt = $pdo->prepare("SELECT * FROM pagamentos_pix WHERE id_matricula = :id_matricula");
                        $stmt->bindParam(':id_matricula', $id_matricula);
                        $stmt->execute();
                        $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Exemplo: incluir os pagamentos dentro do array da matrícula
                        $matricula['pagamentos'] = $pagamentos;

                        // Faça o que precisar com os dados aqui
                        array_push($matriculas, $matricula);


                    }
					$total_m = @count($res);
					if($total_m > 0){

						for($i=0; $i < $total_m; $i++){
							foreach ($matriculas[$i] as $key => $value){}
								$id_mat = $res[$i]['id'];
							$id_curso = $res[$i]['id_curso'];
							$sub_total = $res[$i]['subtotal'];
							$status = $res[$i]['status'];
							$pacote = $res[$i]['pacote'];
							$boleto = $res[$i]['boleto'];
                            $qrcode = isset($matriculas[$i]['pagamentos'][0]['qrcode_url']);
                            $texto_copia_cola = isset($matriculas[$i]['pagamentos'][0]['texto_copia_cola']);
                            $valorC = isset($matriculas[$i]['pagamentos'][0]['valor']);
                            $data_criacao = isset($matriculas[$i]['pagamentos'][0]['data_criacao']);
                            $statusC = isset($matriculas[$i]['pagamentos'][0]['status']);

							if($pacote == 'Sim'){
								$tab = 'pacotes';
								$link = 'cursos-do-';
							}else{
								$tab = 'cursos';
								$link = 'curso-de-';
							}

							

							$query2 = $pdo->prepare("SELECT * FROM {$tab} where id = :id");
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
										<form method="post" action="../../<?php echo $url_do_curso ?>" target="_blank" class="hidden">
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
                                <div align="center">
                                    <small><small><span class="text-danger">Pendente</span>
                                            <span> -

<?php
$qrcode_esc = htmlspecialchars($qrcode, ENT_QUOTES, 'UTF-8');
$texto_esc = htmlspecialchars($texto_copia_cola, ENT_QUOTES, 'UTF-8');
$data_esc = htmlspecialchars($data_criacao, ENT_QUOTES, 'UTF-8');
$status_esc = htmlspecialchars($statusC, ENT_QUOTES, 'UTF-8');
?>
<button onclick="pagarCurso('<?php echo $qrcode_esc ?>', '<?php echo $texto_esc ?>', '<?php echo $valorC ?>', '<?php echo $data_esc ?>', '<?php echo $status_esc ?>');" style="background-color: transparent;  border:none!important;">
    <i class="fa fa-money verde" ></i><span class="verde" style="margin-left:2px">Pagar</span>
</button>
<!--														<button  onclick="pagarCurso('{$qrcode}', '{$texto_copia_cola}', '{$valorC}', '{$data_criacao}', '{$statusC}' );" style="background-color: transparent;  border:none!important;"><i class="fa fa-money verde" ></i><span class="verde" style="margin-left:2px">Pagar</span>-->
<!--														</button>-->
														<input type="hidden" name="painel_aluno" value="sim">


													</span>
                                        </small></small>
                                </div>
                                <div>



                                </div>
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





<style>
    /* Customização do SweetAlert2 */
    .financial-modal .swal2-popup {
        background: linear-gradient(135deg, #1a2035 0%, #121625 100%);
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(83, 92, 136, 0.3);
        padding: 0;
    }

    .financial-modal .swal2-title {
        color: #000;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        /*padding: 1.5rem 1.5rem 0.5rem;*/
        /*font-size: 1.5rem;*/
        border-bottom: 1px solid rgba(83, 92, 136, 0.2);
        margin: 0;
    }

    .financial-modal .swal2-html-container {
        padding: 0;
        margin: 0;
    }

    .financial-modal .swal2-actions {
        margin: 0;
        padding: 1rem;
        border-top: 1px solid rgba(83, 92, 136, 0.2);
    }

    .financial-modal .swal2-styled.swal2-confirm {
        background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
        border-radius: 8px;
        font-weight: 600;
        padding: 0.75rem 2rem;
        box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
        border: none;
    }

    .financial-modal .swal2-styled.swal2-confirm:hover {
        background: linear-gradient(135deg, #2a6ac4 0%, #00b3ee 100%);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .financial-modal .swal2-icon {
        margin: 1.5rem auto 0.5rem;
    }

    /* Conteúdo do Modal */
    .matricula-card {
        background-color: transparent;
        color: #fff;
        font-family: 'Poppins', sans-serif;
    }

    .header-info {
        background: linear-gradient(90deg, rgba(88, 103, 221, 0.1) 0%, rgba(0, 210, 255, 0.1) 100%);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-info::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #586BDD 0%, #00D2FF 100%);
    }

    .status-indicator {
        height: 60px;
        width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-pago {
        background: linear-gradient(135deg, rgba(56, 196, 138, 0.2) 0%, rgba(56, 196, 138, 0.05) 100%);
        border: 1px solid rgba(56, 196, 138, 0.3);
        color: #38C48A;
    }

    .bg-pendente {
        background: linear-gradient(135deg, rgba(255, 184, 34, 0.2) 0%, rgba(255, 184, 34, 0.05) 100%);
        border: 1px solid rgba(255, 184, 34, 0.3);
        color: #FFB822;
    }

    .bg-vencido {
        background: linear-gradient(135deg, rgba(244, 81, 108, 0.2) 0%, rgba(244, 81, 108, 0.05) 100%);
        border: 1px solid rgba(244, 81, 108, 0.3);
        color: #F4516C;
    }

    .bg-concluido {
        background: linear-gradient(135deg, rgba(85, 120, 235, 0.2) 0%, rgba(85, 120, 235, 0.05) 100%);
        border: 1px solid rgba(85, 120, 235, 0.3);
        color: #5578EB;
    }

    .status-indicator i {
        font-size: 1.5rem;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-pago {
        background: linear-gradient(135deg, #38C48A 0%, #28A745 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(56, 196, 138, 0.3);
    }

    .badge-pendente {
        background: blue;
        color: white;
        box-shadow: 0 2px 8px rgba(255, 184, 34, 0.3);
    }

    .badge-vencido {
        background: linear-gradient(135deg, #F4516C 0%, #E53935 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(244, 81, 108, 0.3);
    }

    .badge-concluido {
        background: linear-gradient(135deg, #5578EB 0%, #4E73DF 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(85, 120, 235, 0.3);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        padding: 0 1.25rem;
    }

    .info-card {
        background: rgba(83, 92, 136, 0.1);
        border-radius: 8px;
        padding: 1rem;
        position: relative;
        border: 1px solid rgba(83, 92, 136, 0.2);
    }

    .info-card .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8a94a6;
        margin-bottom: 0.3rem;
    }

    .info-card .value {
        font-size: 1rem;
        font-weight: 600;
        color: #000;
    }

    .highlight-box {
        background: linear-gradient(135deg, rgba(88, 103, 221, 0.15) 0%, rgba(0, 210, 255, 0.05) 100%);
        border: 1px solid rgba(83, 92, 136, 0.2);
        border-radius: 10px;
        padding: 1.5rem;
        margin: 2rem 1.25rem 1.5rem;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .highlight-box .icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        font-size: 2rem;
        color: rgba(0, 210, 255, 0.2);
    }

    .highlight-box .label {
        font-size: 0.85rem;
        color: #8a94a6;
        margin-bottom: 0.5rem;
    }

    .highlight-box .value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(90deg, #586BDD 0%, #00D2FF 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .matricula-id {
        font-size: 0.85rem;
        opacity: 0.7;
        margin-top: 0.5rem;
    }

    .curso-nome {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Animações */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.5s ease forwards;
    }

    .delay-1 {
        animation-delay: 0.1s;
    }

    .delay-2 {
        animation-delay: 0.2s;
    }

    .delay-3 {
        animation-delay: 0.3s;
    }

    .delay-4 {
        animation-delay: 0.4s;
    }
</style>

<script>
    function pagarCurso(qrcode, texto_copia_cola, valor, data_criacao, status) {

        // Formatar data e valor para exibição
        const dataFormatada = new Date(data_criacao).toLocaleDateString('pt-BR');
        const valorFormatado = parseFloat(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Definir classes e ícones de acordo com o status
        let statusClass = '';
        let iconClass = '';
        let badgeClass = '';

        switch(status) {
            case 'pago':
                statusClass = 'bg-pago';
                iconClass = 'fa-check-circle';
                badgeClass = 'badge-pago';
                break;
            case 'pendente':
                statusClass = 'bg-pendente';
                iconClass = 'fa-clock';
                badgeClass = 'badge-pendente';
                break;
            case 'vencido':
                statusClass = 'bg-vencido';
                iconClass = 'fa-exclamation-circle';
                badgeClass = 'badge-vencido';
                break;
            default:
                statusClass = 'bg-secondary';
                iconClass = 'fa-question-circle';
                badgeClass = 'badge-secondary';
        }

        // Montar o HTML para o conteúdo do SweetAlert
        const conteudoHtml = `
    <div class="matricula-card">


      <div class="highlight-box animate-fadeInUp delay-1">
        <img src="${qrcode}" width="250px" style="align-self: center;"/>
        <i class="fas fa-money-bill-wave icon"></i>
        <div class="label">COPIA E COLA</div>
    <input type="text" id="pix-code" value="${texto_copia_cola}" readonly style="border: none; color: #000; font-size: 10pt;" />
        <button onclick="copiarCodigo()"  class="status-badge ${badgeClass} mt-3">Copiar</button>
<br>

<div class="info-card animate-fadeInUp delay-3">
          <div class="label">Valor</div>
          <div class="value">${valorFormatado}</div>
        </div>

        <div class="info-card animate-fadeInUp delay-2">
          <div class="label">Data</div>
          <div class="value">${dataFormatada}</div>
        </div>


      </div>

      <div class="info-grid">

      </div>
    </div>
  `;

        // Configurar e exibir o SweetAlert com design personalizado
        Swal.fire({
            title: 'Realizar Pagamento',
            html: conteudoHtml,
            showConfirmButton: true,
            confirmButtonText: 'Fechar',
            customClass: {
                popup: 'swal2-popup',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container',
                actions: 'swal2-actions',
                confirmButton: 'swal2-styled swal2-confirm',
                container: 'financial-modal'
            },
            showClass: {
                popup: 'animate__animated animate__fadeIn'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOut'
            },
            width: '550px',
            padding: 0,
            background: '#fff',
            backdrop: `rgba(0, 0, 0, 0.6)`,
            allowOutsideClick: true
        });
    }
</script>

