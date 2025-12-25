	<?php 

	$total_comissoes_receberF = 0;

	$total_comissoes_recebidasF = 0;

	$total_pacotes = 0;

	$total_cursos = 0;



	require_once('verificar.php');

		$pag = 'pagar';



	if(@$_SESSION['nivel'] != 'Professor' and @$_SESSION['nivel'] != 'Tutor' and @$_SESSION['nivel'] != 'Parceiro' and @$_SESSION['nivel'] != 'Vendedor'and @$_SESSION['nivel'] != 'Assessor'){

			echo "<script>window.location='../index.php'</script>";

			exit();

		}





		$data_hoje = date('Y-m-d');

		$data_ontem = date('Y-m-d', strtotime("-1 days",strtotime($data_hoje)));



		$mes_atual = Date('m');

		$ano_atual = Date('Y');

		$data_mes = $ano_atual."-".$mes_atual."-01";



		if($mes_atual == "4" || $mes_atual == "6" || $mes_atual == "9" || $mes_atual == "11"){

			$data_final_mes = $ano_atual."-".$mes_atual."-30";

		}else if($mes_atual == "2"){

			$data_final_mes = $ano_atual."-".$mes_atual."-28";

		}else{

			$data_final_mes = $ano_atual."-".$mes_atual."-31";

		}

		



		



		$total_pacotes = 0;

		$total_cursos = 0;

		$total_comissoes_receber = 0;

		$total_comissoes_recebidas = 0;

		$total_perguntas = 0;



		$total_alunos = 0;

			



		$query = $pdo->prepare("SELECT * FROM cursos where status = 'Aprovado' and professor = :professor");
		$query->execute([':professor' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);

		$total_cursos = @count($res);



		$query = $pdo->prepare("SELECT * FROM pacotes where professor = :professor");
		$query->execute([':professor' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);

		$total_pacotes = @count($res);



		$query = $pdo->prepare("SELECT * FROM alunos where usuario = :usuario");
		$query->execute([':usuario' => $id_usuario]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);

		$total_alunos = @count($res);





		$query = $pdo->prepare("SELECT * FROM pagar where professor = :professor and vencimento >= :data_inicio and vencimento <= :data_final");
		$query->execute([
			':professor' => $id_usuario,
			':data_inicio' => $data_mes,
			':data_final' => $data_final_mes,
		]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);

		$total_reg = @count($res);

		for($i=0; $i < $total_reg; $i++){

			foreach ($res[$i] as $key => $value){}

				$pago = $res[$i]['pago'];	

			$valor = $res[$i]['valor'];	



				if($pago == 'Sim'){

					$total_comissoes_recebidas += $valor;					

				}else{

					$total_comissoes_receber += $valor;

					

				}



				$total_comissoes_receberF = number_format($total_comissoes_receber, 2, ',', '.');	

				$total_comissoes_recebidasF = number_format($total_comissoes_recebidas, 2, ',', '.');	



		}









		$query = $pdo->query("SELECT * FROM perguntas where respondida != 'Sim'");

						$res = $query->fetchAll(PDO::FETCH_ASSOC);

						for($i=0; $i < @count($res); $i++){

						foreach ($res[$i] as $key => $value){}



						$id_curso = $res[$i]['curso'];

						$query2 = $pdo->prepare("SELECT * FROM cursos where id = :id and professor = :professor");
						$query2->execute([
							':id' => $id_curso,
							':professor' => $id_usuario,
						]);
						$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);

						if(@count($res2)> 0 ){



							$total_perguntas += 1;

						}



						}

















		$dados_meses =	'';

		//ALIMENTAR DADOS PARA O GRÁFICO

		for($i=1; $i <= 12; $i++){

			if($i < 10){

				$mes_atual = '0'.$i;

			}else{

				$mes_atual = $i;

			}



		if($mes_atual == '4' || $mes_atual == '6' || $mes_atual == '9' || $mes_atual == '11'){

			$dia_final_mes = '30';

		}else if($mes_atual == '2'){

			$dia_final_mes = '28';

		}else{

			$dia_final_mes = '31';

		}





		$data_mes_inicio_grafico = $ano_atual."-".$mes_atual."-01";

		$data_mes_final_grafico = $ano_atual."-".$mes_atual."-".$dia_final_mes;



		$total_mes = 0;

		$query = $pdo->prepare("SELECT * FROM pagar where pago = 'Sim' and descricao = 'Comiss?o' and data_pgto >= :data_inicio and data_pgto <= :data_final ORDER BY id asc");
				$query->execute([':data_inicio' => $data_mes_inicio_grafico, ':data_final' => $data_mes_final_grafico]);

		$res = $query->fetchAll(PDO::FETCH_ASSOC);

		$total_reg = @count($res);

		if($total_reg > 0){

			for($i2=0; $i2 < $total_reg; $i2++){

				foreach ($res[$i2] as $key => $value){}

			$total_mes +=  $res[$i2]['valor'];

		}

		}



		$dados_meses = $dados_meses. $total_mes. '-';



	}



	





	 ?>



	 <input type="hidden" id="dados_grafico">



	<div class="col_3 margem-mobile">



			

		



			<a href="index.php?pagina=alunos">

			<div class="col-md-3 widget widget1">

				<div class="r3_counter_box">

					<i class="pull-left fa fa-book icon-rounded"></i>

					<div class="stats">

						<h5><strong><big><big><?php echo $total_alunos ?></big></big></strong></h5>



					</div>

					<hr style="margin-top:10px">

					<div align="center"><span>Total Alunos</span></div>

				</div>

			</div>

			</a>



			<a href="index.php?pagina=minhas_comissoes">

			<div class="col-md-3 widget widget1">

				<div class="r3_counter_box">

					<i class="pull-left fa fa-laptop user1 icon-rounded"></i>

					<div class="stats">

						<h5><strong><big><?php echo $total_comissoes_receberF ?></big></strong></h5>



					</div>

					<hr style="margin-top:10px">

					<div align="center"><span>Comissões à Receber</span></div>

				</div>

			</div>	

			</a>



			<a href="index.php?pagina=minhas_comissoes">

			<div class="col-md-3 widget widget1">

				<div class="r3_counter_box">

					<i class="pull-left fa fa-laptop dollar2 icon-rounded"></i>

					<div class="stats">

						<h5><strong><big><?php echo $total_comissoes_recebidasF ?></big></strong></h5>



					</div>

					<hr style="margin-top:10px">

					<div align="center"><span>Comissões Recebidas</span></div>

				</div>

			</div>

			</a>



			<a href="index.php?pagina=pacotes">

			<div class="col-md-3 widget widget1">

				<div class="r3_counter_box">

					<i class="pull-left fa fa-book user2 icon-rounded"></i>

					<div class="stats">

						<h5><strong><big><big><?php echo $total_pacotes ?></big></big></strong></h5>



					</div>

					<hr style="margin-top:10px">

					<div align="center"><span>Total de Pacotes</span></div>

				</div>

			</div>

			</a>



			<a href="index.php?pagina=cursos">

			<div class="col-md-3 widget esc">

				<div class="r3_counter_box">

					<i class="pull-left fa fa-credit-card dollar1 icon-rounded"></i>

					<div class="stats">

						<h5><strong><big><big><?php echo $total_cursos ?></big></big></strong></h5>



					</div>

					<hr style="margin-top:10px">

					<div align="center"><span>Total de Cursos</span></div>

				</div>

			</div>

			</a>



			<div class="clearfix"> </div>

		</div>



		











		<div class="row-one widgettable">

			<div class="col-md-12 content-top-2 card">

				<div class="agileinfo-cdr">

					<div class="card-header">

                        <h3>Demonstrativo de Comissões</h3>

                    </div>

					

						<div id="Linegraph" style="width: 98%; height: 400px">

						</div>

						

				</div>

			</div>

		



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

	

		

	





		<!-- for index page weekly sales java script -->

	<script src="js/SimpleChart.js"></script>

    <script>

    	$('#dados_grafico').val('<?=$dados_meses?>');  

    	var dados = $('#dados_grafico').val();

    	saldo_mes = dados.split('-');



        var graphdata1 = {

            linecolor: "#035908",

            title: "Monday",

            values: [

            { X: "Janeiro", Y: parseFloat(saldo_mes[0]) },

            { X: "Fevereiro", Y: parseFloat(saldo_mes[1]) },

            { X: "Março", Y: parseFloat(saldo_mes[2]) },

            { X: "Abril", Y: parseFloat(saldo_mes[3]) },

            { X: "Maio", Y: parseFloat(saldo_mes[4]) },

            { X: "Junho", Y: parseFloat(saldo_mes[5]) },

            { X: "Julho", Y: parseFloat(saldo_mes[6]) },

            { X: "Agosto", Y: parseFloat(saldo_mes[7]) },

            { X: "Setembro", Y: parseFloat(saldo_mes[8]) },

            { X: "Outubro", Y: parseFloat(saldo_mes[9]) },

            { X: "Novembro", Y: parseFloat(saldo_mes[10]) },

            { X: "Dezembro", Y: parseFloat(saldo_mes[11]) },

            

            ]

        };

       

       

        $(function () {                   

            

            $("#Linegraph").SimpleChart({

                ChartType: "Line",

                toolwidth: "50",

                toolheight: "25",

                axiscolor: "#E6E6E6",

                textcolor: "#6E6E6E",

                showlegends: false,

                data: [graphdata1],

                legendsize: "140",

                legendposition: 'bottom',

                xaxislabel: '',

                title: 'Total R$ Comissões',

                yaxislabel: ''

            });

           

        });



    </script>

	<!-- //for index page weekly sales java script -->









	<!-- new added graphs chart js-->

	

    <script src="js/Chart.bundle.js"></script>

    <script src="js/utils.js"></script>

	
