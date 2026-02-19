<?php

require_once('../conexao.php');
if (!headers_sent()) {
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Expires: 0');
}

require_once('verificar.php');

$csrf_token = csrf_token();



$id_usuario = $_SESSION['id'];



$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : '';

if (@$_GET['pagina'] != "") {

	$menu = $_GET['pagina'];

} else {

	$menu = 'home';

}





//RECUPERAR DADOS DO USUÃRIO

$query = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$query->execute([':id' => $id_usuario]);

$res = $query->fetchAll(PDO::FETCH_ASSOC);

$nome_usuario = $res[0]['nome'];

$email_usuario = $res[0]['usuario'];

$nivel_usuario = $res[0]['nivel'];

$foto_usuario = $res[0]['foto'];

$cpf_usuario = $res[0]['cpf'];

$senha_usuario = $res[0]['senha'];

$id_pessoa = $res[0]['id_pessoa'];



$query = $pdo->prepare("SELECT * FROM alunos where id = :id");
$query->execute([':id' => $id_pessoa]);

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$alunoDados = $res[0] ?? [];

$rg_usu = $alunoDados['rg'] ?? '';
$expedidor_usu = $alunoDados['orgao_expedidor'] ?? '';
$expedicao_usu = $alunoDados['expedicao'] ?? '';
$telefone_usu = $alunoDados['telefone'] ?? '';
$cep_usu = $alunoDados['cep'] ?? '';
$endereco_usu = $alunoDados['endereco'] ?? '';
$numero_usu = $alunoDados['numero'] ?? '';
$bairro_usu = $alunoDados['bairro'] ?? '';
$cidade_usu = $alunoDados['cidade'] ?? '';
$estado_usu = $alunoDados['estado'] ?? '';
$sexo_usu = $alunoDados['sexo'] ?? '';
$nascimento_usu = $alunoDados['nascimento'] ?? '';
$mae_usu = $alunoDados['mae'] ?? '';
$pai_usu = $alunoDados['pai'] ?? '';
$naturalidade_usu = $alunoDados['naturalidade'] ?? '';
$cartao_aluno = $alunoDados['cartao'] ?? '';
$id_retorno_resgate = 0;
$cpfColSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '/', ''), '(', ''), ')', '')";
$cpfUsuarioLimpo = preg_replace('/\D/', '', (string) $cpf_usuario);
$stmtRetornoResgate = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Vendedor' AND ativo = 'Sim' AND id <> :id AND ({$cpfColSql} = :cpf OR LOWER(usuario) = :email) ORDER BY id DESC LIMIT 1");
$stmtRetornoResgate->execute([
	':id' => (int) $id_usuario,
	':cpf' => $cpfUsuarioLimpo,
	':email' => strtolower(trim((string) $email_usuario))
]);
$id_retorno_resgate = (int) ($stmtRetornoResgate->fetchColumn() ?: 0);
$conta_retorno_disponivel = (
	(isset($_SESSION['switch_back_id'], $_SESSION['switch_back_nivel']) && (int) $_SESSION['switch_back_id'] > 0)
	|| (isset($_SESSION['switch_vendedor_usuario_id']) && (int) $_SESSION['switch_vendedor_usuario_id'] > 0)
	|| ($id_retorno_resgate > 0)
);



$stmt = $pdo->query("SELECT * FROM cores_sistema ORDER BY nome_classe");

$cores = $stmt->fetchAll(PDO::FETCH_ASSOC);





$classeDesejada = 'topo_pagina';



$coress = [];

foreach ($cores as $item) {

  $coress[$item['nome_classe']] = $item['valor_cor'];

}



$bg_menu = $coress['menu_lateral'];

$topo_pagina = $coress['topo_pagina'];

$texto_menu = $coress['texto_menu'];

$texto_submenu = $coress['texto_submenu'];

$bg_menu_hover = $coress['bg_menu_hover'];



?>

<!DOCTYPE HTML>

<html>



<head>

	<title><?php echo $nome_sistema ?></title>

	<link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">



	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">





	<script type="application/x-javascript">

		addEventListener("load", function() {

			setTimeout(hideURLbar, 0);

		}, false);



		function hideURLbar() {

			window.scrollTo(0, 1);

		}

	</script>



	<!-- Bootstrap Core CSS -->

	<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />



	<!-- Custom CSS -->

	<link href="css/style.css" rel='stylesheet' type='text/css' />



	<!-- font-awesome icons CSS -->

	<link href="css/font-awesome.css" rel="stylesheet">

	<!-- //font-awesome icons CSS-->



	<!-- side nav css file -->

	<link href='css/SidebarNav.min.css' media='all' rel='stylesheet' type='text/css' />

	<!-- //side nav css file -->



	<!-- js-->

	<script src="js/jquery-1.11.1.min.js"></script>

	<script>
		window.CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>";
		(function () {
			function getToken() {
				return window.CSRF_TOKEN || '';
			}
			if (window.fetch) {
				var originalFetch = window.fetch;
				window.fetch = function (resource, init) {
					init = init || {};
					var headers = new Headers(init.headers || {});
					if (!headers.has('X-CSRF-Token')) {
						headers.set('X-CSRF-Token', getToken());
					}
					init.headers = headers;
					return originalFetch(resource, init);
				};
			}
			if (window.jQuery) {
				window.jQuery.ajaxSetup({
					beforeSend: function (xhr) {
						if (getToken()) {
							xhr.setRequestHeader('X-CSRF-Token', getToken());
						}
					}
				});
			}
			document.addEventListener('submit', function (e) {
				var form = e.target;
				if (!form || form.tagName !== 'FORM') {
					return;
				}
				if (form.querySelector('input[name=\"csrf_token\"]')) {
					return;
				}
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'csrf_token';
				input.value = getToken();
				form.appendChild(input);
			}, true);
		})();
	</script>
	<script>
		(function () {
			var sessionUserId = "<?php echo (int) $id_usuario; ?>";
			var key = 'active_user_id';
			try {
				var activeId = localStorage.getItem(key);
				if (!activeId) {
					localStorage.setItem(key, sessionUserId);
				} else if (activeId !== sessionUserId) {
					window.location.reload();
					return;
				}
				window.addEventListener('storage', function (e) {
					if (e.key === key && e.newValue && e.newValue !== sessionUserId) {
						window.location.reload();
					}
				});
			} catch (err) {
				// localStorage blocked or unavailable
			}
		})();
	</script>

	<script src="js/modernizr.custom.js"></script>



	<!--webfonts-->

	<link href="//fonts.googleapis.com/css?family=PT+Sans:400,400i,700,700i&amp;subset=cyrillic,cyrillic-ext,latin-ext" rel="stylesheet">

	<!--//webfonts-->



	<!-- chart -->

	<script src="js/Chart.js"></script>

	<!-- //chart -->



	<!-- Metis Menu -->

	<script src="js/metisMenu.min.js"></script>

	<script src="js/custom.js"></script>

	<script src="js/sweetalert2.js"></script>

	<link href="css/custom.css" rel="stylesheet">

	<!--//Metis Menu -->

	<style>

		#chartdiv {

			width: 100%;

			height: 295px;

		}

		.sidebar-left {

      background-color:

        <?= $bg_menu ?>

        !important;

    }



    .treeview a {

      color:

        <?= $texto_menu ?>

        !important;

    }

	</style>

	<!--pie-chart --><!-- index page sales reviews visitors pie chart -->

	<script src="js/pie-chart.js" type="text/javascript"></script>



		



	<script type="text/javascript">

		$(document).ready(function() {

			$('#demo-pie-1').pieChart({

				barColor: '#2dde98',

				trackColor: '#eee',

				lineCap: 'round',

				lineWidth: 8,

				onStep: function(from, to, percent) {

					$(this.element).find('.pie-value').text(Math.round(percent) + '%');

				}

			});



			$('#demo-pie-2').pieChart({

				barColor: '#8e43e7',

				trackColor: '#eee',

				lineCap: 'butt',

				lineWidth: 8,

				onStep: function(from, to, percent) {

					$(this.element).find('.pie-value').text(Math.round(percent) + '%');

				}

			});



			$('#demo-pie-3').pieChart({

				barColor: '#e30e27',

				trackColor: '#eee',

				lineCap: 'square',

				lineWidth: 8,

				onStep: function(from, to, percent) {

					$(this.element).find('.pie-value').text(Math.round(percent) + '%');

				}

			});





		});

	</script>

	<!-- //pie-chart --><!-- index page sales reviews visitors pie chart -->



	<!-- requried-jsfiles-for owl -->

	<link href="css/owl.carousel.css" rel="stylesheet">

	<script src="js/owl.carousel.js"></script>

	<script>

		$(document).ready(function() {

			$("#owl-demo").owlCarousel({

				items: 3,

				lazyLoad: true,

				autoPlay: true,

				pagination: true,

				nav: true,

			});

		});

	</script>

	<!-- //requried-jsfiles-for owl -->

</head>



<body class="cbp-spmenu-push">

	<div class="main-content">

		<div class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">

			<!--left-fixed -navigation-->

			<aside class="sidebar-left">

				<nav class="navbar navbar-inverse">

					<div class="navbar-header">

						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".collapse" aria-expanded="false">

							<span class="sr-only">Menu</span>

							<span class="icon-bar"></span>

							<span class="icon-bar"></span>

							<span class="icon-bar"></span>

						</button>

						<h1><a class="navbar-brand" href="index.php"><span class="fa fa-book"></span> Sested EJA<span class="dashboard_text"></span></a></h1>

					</div>

					<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
						<ul class="sidebar-menu">

							<li class="treeview <?= empty($pagina) ? 'active' : '' ?>">
								<a href="index.php">
									<i class="fa fa-home"></i> <span>Home</span>
								</a>
							</li>

							<li class="treeview <?= $pagina === 'cursos' ? 'active' : '' ?>">
								<a href="index.php?pagina=cursos">
									<i class="fa fa-book"></i> <span>Meus Cursos</span>
								</a>
							</li>


							<li class="treeview <?= $pagina === 'pacotes' ? 'active' : '' ?>">
								<a href="index.php?pagina=pacotes">
									<i class="fa fa-th-large"></i> <span>Meus Pacotes</span>
								</a>
							</li>

							<li class="treeview <?= $pagina === 'andamento' ? 'active' : '' ?>">
								<a href="index.php?pagina=andamento">
									<i class="fa fa-check-square"></i> <span>Cursos em Andamento</span>
								</a>
							</li>


							<li class="treeview <?= $pagina === 'finalizados' ? 'active' : '' ?>">
								<a href="index.php?pagina=finalizados">
									<i class="fa fa-check-square"></i> <span>Cursos Finalizados</span>
								</a>
							</li>


							<li class="treeview <?= $pagina === 'parcelas' ? 'active' : '' ?>">
								<a href="index.php?pagina=parcelas">
									<i class="fa fa-money" aria-hidden="true"></i> <span>Parcelas Boleto</span>
								</a>
							</li>
							
								<li class="treeview <?= $pagina === 'parcelas_cartao' ? 'active' : '' ?>">
								<a href="index.php?pagina=parcelas_cartao">
									<i class="fa fa-credit-card" aria-hidden="true"></i> <span>Parcelas CartÃ£o</span>
								</a>
							</li>

							<li class="treeview <?= $pagina === 'arquivos' ? 'active' : '' ?>">
								<a href="index.php?pagina=arquivos">
									<i class="fa fa-file" aria-hidden="true"></i> <span>Meus Documentos</span>
								</a>
							</li>


							<li class="treeview">
								<a href="../../" target="_blank">
									<i class="fa fa-globe"></i> <span>Ir para o Site</span>
								</a>
							</li>




						</ul>
					</div>

					<!-- /.navbar-collapse -->

				</nav>

			</aside>

		</div>

		<!--left-fixed -navigation-->



		<!-- header-starts -->

		<div class="sticky-header header-section ">

			<div class="header-left">

				<!--toggle button start-->

				<button id="showLeftPush"><i class="fa fa-bars"></i></button>

				<!--toggle button end-->

				<div class="profile_details_left"><!--notifications of menu start -->



					<div class="clearfix"> </div>

				</div>

				<!--notification menu end -->

				<div class="clearfix"> </div>

			</div>

			<div class="header-right">





				<div class="profile_details">

					<ul>

						<li class="dropdown profile_details_drop">

							<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">

								<div class="profile_img">

									<span class="prfil-img"><img src="img/perfil/<?php echo $foto_usuario ?>" alt="" width="50px" height="50px"> </span>

									<div class="user-name">

										<p><?php echo $nome_usuario ?></p>

										<span><?php echo $nivel_usuario ?></span>

									</div>

									<i class="fa fa-angle-down lnr"></i>

									<i class="fa fa-angle-up lnr"></i>

									<div class="clearfix"></div>

								</div>

							</a>

							<ul class="dropdown-menu drp-mnu">

								<li> <a href="" data-toggle="modal" data-target="#modalPerfil"><i class="fa fa-user"></i> Editar Perfil</a> </li>
								<?php if ($conta_retorno_disponivel): ?>
									<li>
										<form action="voltar-para-conta.php" method="POST" style="margin:0;">
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
											<?php if ($id_retorno_resgate > 0): ?>
												<input type="hidden" name="vendedor_usuario_id_resgate" value="<?php echo (int) $id_retorno_resgate; ?>">
											<?php endif; ?>
											<button type="submit" style="border:0;background:transparent;width:100%;text-align:left;padding:6px 20px;color:#333;">
												<i class="fa fa-exchange"></i> Entra como Vendedor
											</button>
										</form>
									</li>
								<?php endif; ?>



								<li> <a href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a> </li>

							</ul>

						</li>

					</ul>

				</div>

				<div class="clearfix"> </div>

			</div>

			<div class="clearfix"> </div>

		</div>

		<!-- //header-ends -->









		<!-- main content start-->

		<div id="page-wrapper">

			<div class="main-page">

				<?php

				require_once('paginas/' . $menu . '.php');

				?>



			</div>











		</div>



		<div class="footer">

			<small>

				<p><?php echo $nome_sistema ?> - Desenvolvedor - Joel de Souza - <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" title="Chamar no Whatsapp" target="_blank"><i class="fa fa-whatsapp" style="margin-right: 3px"></i><?php echo $tel_sistema ?></a></p>

			</small>

		</div>







	</div>







	<!-- Classie --><!-- for toggle left push menu script -->

	<script src="js/classie.js"></script>

	<script>

		var menuLeft = document.getElementById('cbp-spmenu-s1'),

			showLeftPush = document.getElementById('showLeftPush'),

			body = document.body;



		showLeftPush.onclick = function() {

			classie.toggle(this, 'active');

			classie.toggle(body, 'cbp-spmenu-push-toright');

			classie.toggle(menuLeft, 'cbp-spmenu-open');

			disableOther('showLeftPush');

		};





		function disableOther(button) {

			if (button !== 'showLeftPush') {

				classie.toggle(showLeftPush, 'disabled');

			}

		}

	</script>

	<!-- //Classie --><!-- //for toggle left push menu script -->



	<!--scrolling js-->

	<script src="js/jquery.nicescroll.js"></script>

	<script src="js/scripts.js"></script>

	<!--//scrolling js-->



	<!-- side nav js -->

	<script src='js/SidebarNav.min.js' type='text/javascript'></script>

	<script>

		$('.sidebar-menu').SidebarNav()

	</script>

	<!-- //side nav js -->









	<!-- Bootstrap Core JavaScript -->

	<script src="js/bootstrap.js"> </script>

	<!-- //Bootstrap Core JavaScript -->



</body>



</html>











<!-- Modal -->



<div class="modal fade" id="modalPerfil" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">



	<div class="modal-dialog-lg" role="document">



		<div class="modal-content">



			<div class="modal-header">



				<h4 class="modal-title" id="exampleModalLabel">Editar Dados</h4>



				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">



					<span aria-hidden="true">&times;</span>



				</button>



			</div>



			<form method="post" id="form-usu">



				<div class="modal-body">



					<div class="row">

						<div class="col-md-4">

							<div class="form-group">

								<label>Nome do Aluno*</label>

					         <input type="text" class="form-control" id="nome_usu" name="nome_usu" value="<?php echo $nome_usuario ?>" placeholder="Nome do Aluno" required>





							</div>

						</div>



						<div class="col-md-2">

							<div class="form-group">

								<label>Cpf*</label>

							<input type="text" class="form-control" id="cpf_usu" name="cpf_usu" value="<?php echo $cpf_usuario ?>" placeholder="CPF do Aluno" required>



							</div>

						</div>



						<div class="col-md-3">

							<div class="form-group">

								<label>Email*</label>

								<input type="email" class="form-control" id="email_usu" name="email_usu" value="<?php echo $email_usuario ?>" required>

                               

                               </div>

						    </div>



								<div class="col-md-2">

							<div class="form-group">

								<label> Telefone:</label>

								<input type="text" class="form-control" id="telefone_usu" name="telefone_usu" value="<?php echo $telefone_usu ?>" placeholder="Telefone" required>



							



							</div>

						</div>



					</div>





					<div class="row">

						<div class="col-md-3">

							<div class="form-group">

								<label>Documento:<small><small>( RG, CTPS, etc)</small></small></label>

								<input type="text" class="form-control" name="rg_usu" value="<?php echo $rg_usu ?>" placeholder="Documento pra certificaÃ§Ã£o">

							</div>

						</div>

						<div class="col-md-2">

							<div class="form-group">

								<label>OrgÃ£o Expedidor:</label>

								<input type="text" class="form-control" id="expedidor_usu" name="expedidor_usu" value="<?php echo $expedidor_usu ?>" placeholder="OrgÃ£o Expedidor">

							</div>

						</div>



						<div class="col-md-2">

							<div class="form-group">

								<label>Data de Expedicao:</label>

								<input type="text" class="form-control" id="expedicao_usu" name="expedicao_usu" value="<?php echo $expedicao_usu ?>" placeholder="Data de ExpediÃ§Ã£o">

							</div>

						</div>





						<div class="col-md-2">

							<div class="form-group">

								<label>Data de Nascimento:</label>

								<input type="text" class="form-control" id="nascimento_usu" name="nascimento_usu" value="<?php echo $nascimento_usu ?>" placeholder="Data de Nascimento" required>


							</div>

						</div>

					

                      <div class="col-md-2">

							<div class="form-group">

								<label>Cep:</label>

								<input type="text" class="form-control" id="cep_usu" name="cep_usu" value="<?php echo $cep_usu ?>" placeholder="Cep">
							</div>

						</div>

					</div>



					<div class="row">

						<div class="col-md-3">

							<div class="form-group">

								<label>EndereÃ§o:<small><small>(Rua, NÃºmero e Bairro)</small></small></label>

								<input type="text" class="form-control" id="endereco_usu" name="endereco_usu" name="endereco_usu" value="<?php echo $endereco_usu ?>" placeholder="Rua X NÃºmero 50 Bairro X">

							</div>

						</div>

						

						<div class="col-md-2">

							<div class="form-group">

								<label>Numero:</label>

								<input type="text" class="form-control" id="numero_usu" name="numero_usu" value="<?php echo $numero_usu ?>" placeholder="Numero">



							</div>

						</div>



						<div class="col-md-2">

							<div class="form-group">

								<label>Bairro:</label>

								<input type="text" class="form-control" id="bairro_usu" name="bairro_usu" value="<?php echo $bairro_usu ?>" placeholder="Bairro">



							</div>

						</div>



						<div class="col-md-2">

							<div class="form-group">

								<label>Cidade:</label>

								<input type="text" class="form-control" id="cidade_usu" name="cidade_usu" value="<?php echo $cidade_usu ?>" placeholder="Cidade">

							</div>

						</div>

						

						 <div class="col-md-2">

							<div class="form-group">

								<label>Sexo:</label>

							<input type="text" class="form-control" id="sexo_usu" name="sexo_usu" value="<?php echo $sexo_usu ?>" placeholder="Sexo">



							</div>

					    	</div>

                           </div>

                                <div class="row">

					        	<div class="col-md-4">

							<div class="form-group">

								<label>Nome da MÃ£e:</label>

								<input type="text" class="form-control" id="mae_usu" name="mae_usu" value="<?php echo $mae_usu ?>" placeholder="Nome da Mae">

							</div>

					       </div>



						<div class="col-md-4">

							<div class="form-group">

								<label>Nome do Pai:</label>

								<input type="text" class="form-control" id="pai_usu" name="pai_usu" value="<?php echo $pai_usu ?>" placeholder="Nome do Pai">

							</div>

						</div>

						<div class="col-md-2">

							<div class="form-group">

								<label>Estado:</label>
                             <input type="text" class="form-control" id="estado_usu" name="estado_usu" value="<?php echo $estado_usu ?>" placeholder="Estado">

							</div>

						</div>

                      	</div>



					   <div class="row">

						<div class="col-md-3">

							<div class="form-group">

								<label>Naturalidade:</label>

								<input type="text" class="form-control" id="naturalidade_usu" name="naturalidade_usu" value="<?php echo $naturalidade_usu ?>" placeholder="Naturalidade">



							</div>

						</div>



				

						<div class="col-md-4">

							<div class="form-group">

								<label>Foto do aluno</label>

								<input class="form-control" type="file" name="foto" onChange="carregarImgPerfil();" id="foto-usu">

							</div>

						</div>

						<div class="col-md-4">

							<div id="divImg">

								<img src="img/perfil/<?php echo $foto_usuario ?>" width="100px" id="target-usu">

							</div>

						</div>



					</div>









					<input type="hidden" name="id_usu" value="<?php echo $id_usuario ?>">

					<input type="hidden" name="foto_usu" value="<?php echo $foto_usuario ?>">



					<small>

						<div id="mensagem-usu" align="center" class="mt-3"></div>

					</small>

					<div class="alert alert-info" style="margin-top: 10px;">
						Envio de documentos somente em <a href="index.php?pagina=arquivos">Meus Documentos</a>.
					</div>



				</div>



				<div class="modal-footer">

					<button type="submit" class="btn btn-primary">Editar Dados</button>

				</div>

			</form>



			



		</div>

	</div>

</div>









<div class="modal fade" id="modalArquivos" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">



	<div class="modal-dialog modal-lg" role="document">

		<div class="modal-content">

			<div class="modal-header">

				<h4 class="modal-title" id="exampleModalLabel">Arquivos</h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">

					<span aria-hidden="true">&times;</span>

				</button>

			</div>





			<form method="post" id="form-arq">

				<div class="modal-body">







					<div class="row">

						<div class="col">

							<div class="form-group">

								<label>DescriÃ§Ã£o</label>

								<input value="" type="text" class="form-control" id="descricao" name="descricao" placeholder="DescriÃ§Ã£o" required>

							</div>

							<div class="row">

								<div class="col">

									<div class="form-group">

										<label>arquivo</label>

										<input class="form-control" type="file" name="arquivo_2" onChange="carregarImg2();" id="arquivo_2">

									</div>



									<div id="divImg">

										<img src="img/arquivos/sem-arquivo.png" width="130px" id="target_2">

									</div>

								</div>

							</div>





						</div>



					



					</div>









					<div align="center" id="mensagem_arquivo" class="">



					</div>



				</div>

				<div class="modal-footer">

					<button type="button" class="btn btn-secondary" data-dismiss="modal" id="btn-cancelar-excluir">Cancelar</button>





					<input type="hidden" id="id" name="id" value="<?php echo @$id_pessoa ?>" required>



					<button type="submit" id="btn-arquivo" name="btn-arquivo" class="btn btn-primary">Inserir</button>



				</div>

			</form>



		</div>

	</div>

</div>







<link rel="stylesheet" type="text/css" href="../DataTables/datatables.min.css" />

<script type="text/javascript" src="../DataTables/datatables.min.js"></script>





<script type="text/javascript">

	function obterCampoObrigatorioPerfil() {
		var campos = [
			{ id: 'nome_usu', mensagem: 'Informe o nome.' },
			{ id: 'cpf_usu', mensagem: 'Informe o CPF.' },
			{ id: 'email_usu', mensagem: 'Informe o email.' },
			{ id: 'telefone_usu', mensagem: 'Informe o telefone.' },
			{ id: 'nascimento_usu', mensagem: 'Informe a data de nascimento.' }
		];

		for (var i = 0; i < campos.length; i++) {
			var campo = document.getElementById(campos[i].id);
			if (campo && campo.value.trim() === '') {
				return campos[i].mensagem;
			}
		}

		return '';
	}

	$("#form-usu").submit(function() {

		event.preventDefault();

		var mensagemCampo = obterCampoObrigatorioPerfil();
		if (mensagemCampo) {
			$('#mensagem-usu').removeClass();
			$('#mensagem-usu').addClass('text-danger');
			$('#mensagem-usu').text(mensagemCampo);
			return;
		}

		var formData = new FormData(this);



		$.ajax({

			url: "editar-perfil.php",

			type: 'POST',

			data: formData,



			success: function(mensagem) {

				$('#mensagem-usu').text('');

				$('#mensagem-usu').removeClass()

				if (mensagem.trim() == "Editado com Sucesso") {

					location.reload();

					//$('#btn-fechar-usu').click();						



				} else {



					$('#mensagem-usu').addClass('text-danger')

					$('#mensagem-usu').text(mensagem)

				}





			},



			cache: false,

			contentType: false,

			processData: false,



		});



	});

</script>





<script type="text/javascript">

	$("#form-arq").submit(function() {

		$("#form-arq").val()

		event.preventDefault();

		var formData = new FormData(this);



		$.ajax({

			url: "inserir-arquivo.php",

			type: 'POST',

			data: formData,



			success: function(mensagem) {

				$('#mensagem-usu').text('');

				$('#mensagem-usu').removeClass()

				if (mensagem.trim() == "Salvo com Sucesso") {



					location.reload();

					//$('#btn-fechar-usu').click();						



				} else {



					$('#mensagem-usu').addClass('text-danger')

					$('#mensagem-usu').text(mensagem)

				}





			},



			cache: false,

			contentType: false,

			processData: false,



		});



	});







	function excluir_arq(id_arq) {



		$.ajax({

			url: 'excluir-arquivo.php',

			method: 'POST',

			data: {

				id_arq

			},

			dataType: "text",



			success: function(mensagem) {



				if (mensagem.trim() == "ExcluÃ­do com Sucesso") {

					location.reload();

				} else {

					$('#mensagem-excluir').addClass('text-danger')

					$('#mensagem-excluir').text(mensagem)

				}



			},



		});

	}

</script>













<script type="text/javascript">

	function carregarImgPerfil() {

		var target = document.getElementById('target-usu');

		var file = document.querySelector("#foto-usu").files[0];



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









<!-- Ajax para funcionar Mascaras JS -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>

<!-- Mascaras JS -->

<script type="text/javascript" src="../js/mascaras.js"></script>
<script type="text/javascript" src="../js/cep-autocomplete.js"></script>





<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>







<style type="text/css">

	.select2-selection__rendered {

		line-height: 36px !important;

		font-size: 16px !important;

		color: #666666 !important;



	}



	.select2-selection {

		height: 36px !important;

		font-size: 16px !important;

		color: #666666 !important;



	}

</style>











<?php

$query = $pdo->query("SELECT * FROM alertas where data > curDate() ORDER BY id desc");

$res = $query->fetchAll(PDO::FETCH_ASSOC);



if (@count($res) > 0) {

	$classe_link = '';



	$titulo = $res[0]['titulo'];

	$tituloF = mb_strimwidth($titulo, 0, 25, "...");

	$descricao = $res[0]['descricao'];

	$link = $res[0]['link'];

	$video = $res[0]['video'];

	$foto = $res[0]['imagem'];

	$data = $res[0]['data'];

} else {

	$classe_link = 'hide';

	$titulo = '';

	$descricao = '';

	$link = '';

	$video = '';

	$foto = '';

	$data = '';

}



?>





<!-- ModalMostrar -->

<div class="modal fade" id="modalMostrar_rod" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

	<div class="modal-dialog modal-lg" role="document">

		<div class="modal-content">

			<div class="modal-header">

				<h4 class="modal-title" id="tituloModal_rod"><span class="neutra" id="nome_mostrar_rod"> </span> </h4>

				<button id="btn-fechar-excluir_rod" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">

					<span class="neutra" aria-hidden="true">&times;</span>

				</button>

			</div>



			<div class="modal-body">







				<div class="row" style="border-bottom: 1px solid #cac7c7; margin-bottom:5px">

					<div class="col-md-12">

						<span class="neutra" id="descricao_mostrar_rod"></span>

					</div>



				</div>



				<?php if ($link != "") { ?>

					<div class="row" style="border-bottom: 1px solid #cac7c7; margin-bottom:5px">

						<div class="col-md-12">

							<span class="neutra"><a id="link_mostrar_rod" target="_blank"><i>Clique aqui</i></a> para comprar ou ver mais detalhes sobre nossa promoÃ§Ã£o!!</span>



						</div>



					</div>

				<?php } ?>







				<div class="row" style="margin-top:10px">

					<?php if ($foto != "sem-foto.png" and $foto != "") { ?>

						<div class="col-md-6" align="center" style="margin-top:5px">

							<img width="100%" id="target_mostrar_rod">

						</div>

					<?php } ?>



					<?php if ($video != "") { ?>

						<div class="col-md-6" align="center" style="margin-top:10px">

							<iframe width="100%" height="250" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target_video_mostrar_rod"></iframe>

						</div>

					<?php } ?>

				</div>







			</div>





		</div>

	</div>

</div>

















<style type="text/css">

	.alerta {

		background-color: #fa8e14;

		color: #FFF;

		padding: 10px;

		font-family: Arial;

		text-align: center;

		position: fixed;

		bottom: 0;

		width: 250px;

		opacity: 80%;

		z-index: 1000;

		font-size: 12px;

	}



	.alerta.hide {

		display: none !important;

	}



	.link-alerta {

		color: #f2f2f2;

	}



	.link-alerta:hover {

		text-decoration: underline;

		color: #FFF;

	}



	.botao-aceitar {

		background-color: #e3e3e3;

		padding: 7px;

		margin-left: 15px;

		border-radius: 5px;

		border: none;

		margin-top: 3px;

	}



	.botao-aceitar:hover {

		background-color: #f7f7f7;

		text-decoration: none;



	}

</style>



<div class="alerta <?php echo $classe_link ?>">

	<?php echo $tituloF ?>

	<a class="botao-aceitar text-dark" href="#" onclick="mostrarAlerta('<?php echo $titulo ?>', '<?php echo $descricao ?>','<?php echo $link ?>','<?php echo $foto ?>','<?php echo $video ?>')" title="Clique para ver mais detalhes">Veja Mais</a>

</div>





<script type="text/javascript">

	function mostrarAlerta(titulo, descricao, link, foto, video) {





		$('#nome_mostrar_rod').text(titulo);

		$('#descricao_mostrar_rod').html(descricao);

		$('#link_mostrar_rod').attr('href', '../../' + link);





		$('#target_mostrar_rod').attr('src', '../painel-admin/img/alertas/' + foto);

		$('#target_video_mostrar_rod').attr('src', video);



		$('#modalMostrar_rod').modal('show');



	}

</script>



<script type="text/javascript">

	function carregarImg2() {



		var target = document.getElementById('target_2');

		var file = document.querySelector("#arquivo_2").files[0];



		var arquivo = file['name'];

		resultado = arquivo.split(".", 2);





		if (resultado[1] === 'pdf') {

			$('#target_2').attr('src', "img/arquivos/pdf.PNG");



		}

	}

</script>













