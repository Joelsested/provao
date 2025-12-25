<?php 
require_once('conexao.php');
$senha_admin = '123456';
$senha_admin_hash = password_hash($senha_admin, PASSWORD_DEFAULT);


$senha_prof = '123456';
$senha_prof_hash = password_hash($senha_prof, PASSWORD_DEFAULT);

$email_prof = 'professorpadrao@gmail.com';

//VERIFICAR SE EXISTE UM USUÁRIO ADMINISTRADOR CRIADO NO BANCO
$query = $pdo->query("SELECT * FROM usuarios where nivel = 'Administrador'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	//CRIAR UM USUÁRIO ADMINISTRADOR CASO NÃO EXISTA NENHUM USUÁRIO
	$stmtAdminUser = $pdo->prepare("INSERT INTO usuarios SET nome = 'Administrador', cpf = '000.000.000-00', usuario = :email, senha='', senha_crip = :senha_crip, nivel = 'Administrador', foto = 'sem-perfil.jpg', id_pessoa = 1, ativo = 'Sim', data = curDate() ");
	$stmtAdminUser->execute([':email' => $email_sistema, ':senha_crip' => $senha_admin_hash]);

	//CRIAR UM ADMINISTRADOR NA TABELA ADMINISTRADORES
	$stmtAdmin = $pdo->prepare("INSERT INTO administradores SET nome = 'Administrador', cpf = '000.000.000-00', email = :email, telefone = :telefone, foto = 'sem-perfil.jpg', ativo = 'Sim', data = curDate() ");
	$stmtAdmin->execute([':email' => $email_sistema, ':telefone' => $tel_sistema]);
}



//VERIFICAR SE EXISTE UM PROFESSOR ADM CRIADO NO BANCO
$query = $pdo->query("SELECT * FROM usuarios where nome = 'Professor_padrao'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	//CRIAR UM USUÁRIO ADMINISTRADOR CASO NÃO EXISTA NENHUM USUÁRIO
	$stmtProf = $pdo->prepare("INSERT INTO usuarios SET nome = 'Professor_padrao', usuario = :email, senha='', senha_crip = :senha_crip, nivel = 'Professor', foto = 'sem-perfil.jpg', ativo = 'Sim', data = curDate() ");
	$stmtProf->execute([':email' => $email_prof, ':senha_crip' => $senha_prof_hash]);

	//$query = $pdo->prepare("INSERT INTO professores SET nome = 'Professor_padrao', email = '$email_prof', cpf = '0000000000000', telefone = '$tel_sistema', foto = 'sem-perfil.jpg', ativo = 'Sim', data = curDate()");
}


//VERIFICAR SE A TABELA DE ENVIOS ESTÁ VAZIA
$query = $pdo->query("SELECT * FROM envios");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	$query = $pdo->query("INSERT INTO envios SET data = curDate(), final = '0', assunto = '', mensagem = '', link = ''");
}


//trazer dados do banner login
$query = $pdo->query("SELECT * FROM banner_login where ativo = 'Sim'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$foto_banner = $res[0]['foto'];
	$link_banner = $res[0]['link'];
	$nome_banner = $res[0]['nome'];
}else{
	$foto_banner = 'banner.jpg';
	$link_banner = '';
	$nome_banner = '';
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="SESTED Cursos, Profissionalizantes, Participe das nossas formações e seja um profissional reconhecido!!">
	<meta name="author" content="Sested Cursos">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<title><?php echo $nome_sistema ?></title>
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<link rel="stylesheet" type="text/css" href="css/login.css">
	<link rel="stylesheet" type="text/css" href="css/fonts.css">
	<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">


	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</head>
<body>

	<div class="container-fluid">

		<section class="login-block mt-4">
			<div class="container-fluid">
				<div class="row">
					<div class="col-md-4 login-sec">
						<h5 class="text-center mb-4"><a href="../" title="Voltar para o Site"><img class="mr-1" src="img/logo.png" width="50px"></a>Faça seu Login</h5>


						<form class="login100-form validate-form" action="autenticar.php" method="POST">
							<div class="wrap-input100 validate-input">
								<span class="label-input100">CPF</span><br>
								<input type="text" name="usuario" id="usuario" class="input100" placeholder="CPF (somente numeros) ou e-mail do admin" pattern="[0-9A-Za-z@._\\-]{5,80}" title="Informe o CPF do aluno (apenas numeros) ou o e-mail do administrador" required>
								<small class="text-muted">Digite o CPF apenas com n?meros (ex: 12345678909).</small>
								<span class="focus-input100"></span>
								</div>

							<div class="wrap-input100 validate-input">
								<span class="label-input100">Senha</span>
								<div class="input-group">
									<input type="password" name="senha" id="senha" class="input100" placeholder="Data de nascimento (DDMMAAAA) ou senha do administrador" required>
									<div class="input-group-append">
										<button type="button" class="btn btn-outline-secondary btn-sm" id="toggleSenha" aria-label="Mostrar senha" title="Mostrar senha"><i class="fa fa-eye"></i></button>
									</div>
								</div>
								<small class="text-muted">Informe somente os n?meros da data de nascimento (DDMMAAAA).</small>
								<span class="focus-input100 password"></span>
							</div>



							<div class="container-login100-form-btn">
								<div class="wrap-login100-form-btn">
									<button type="submit" class="btn btn-primary">
										Logar
									</button>
								</div>
							</div>


						</form>

						<div class="copy-text">
							<a href="" class="text-danger" data-toggle="modal" data-target="#modalRecuperar">
								Recuperar Senha? 
							</a>
						</div>

						<div class="text-center p-t-8 p-b-31">
							Não tem Cadastro?

							<a href="" class="text-primary" data-toggle="modal" data-target="#modalCadastro">Cadastre-se</a>

						</div>


					</div>
					<div class="col-md-8 banner-sec">   
						<div class="signup__overlay"></div>          
						<div class="banner">
							<div id="demo" class="carousel slide carousel-fade" data-ride="carousel">


								<div class="carousel-inner">
									<div class="carousel-item active">
										<a href="<?php echo $link_banner ?>" target="_blank" title="Ir para <?php echo $nome_banner ?>">
											<img src="painel-admin/img/login/<?php echo $foto_banner ?>" height="" width="100%">
										</a>

									</div>

								</div>
							</div>
						</div>

					</div>
				</div>

			</div>

		</section>

		<!-- login end -->

	</div>

<script>
$(function(){
    const senha = $('#senha');
    const botao = $('#toggleSenha');
    const icone = botao.find('i');
    botao.on('click', function(event){
        event.preventDefault();
        const isPassword = senha.attr('type') === 'password';
        senha.attr('type', isPassword ? 'text' : 'password');
        if (isPassword) {
            icone.removeClass('fa-eye').addClass('fa-eye-slash');
            botao.attr('aria-label', 'Ocultar senha').attr('title', 'Ocultar senha');
        } else {
            icone.removeClass('fa-eye-slash').addClass('fa-eye');
            botao.attr('aria-label', 'Mostrar senha').attr('title', 'Mostrar senha');
        }
    });
});
</script>
</body>
</html>





<!-- Modal Cadastro -->
<div class="modal fade" id="modalCadastro" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Faça seu Cadastro</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="form-cadastro">
			<div class="modal-body">

					<?php if($professor_cad == 'Sim'){ ?>
					<div class="form-group">
						<label for="exampleFormControlInput1"><small>Aluno / Professor</small></label>
							<select class="form-control" name="tipo" id="tipo"> 
										<option value="Aluno">Aluno</option>
										<option value="Professor">Professor</option>

									</select> 
					</div>
					<?php } ?>
				
					<div class="form-group">
						<label for="exampleFormControlInput1"><small>Nome</small></label>
						<input type="text" class="form-control" id="nome" name="nome" placeholder="Nome e Sobrenome" required>
					</div>

					<div class="form-group">
						<label for="exampleFormControlInput1"><small>E-mail</small></label>
						<input type="email" class="form-control" id="email_cadastro" name="email" placeholder="Seu E-mail" required>
					</div>

					<div class="form-group">
						<label for="exampleFormControlInput1"><small>CPF</small></label>
						<input type="text" class="form-control" id="cpf_cadastro" name="cpf" placeholder="000.000.000-00" maxlength="14" required>
					</div>

					<div class="form-group">
						<label for="exampleFormControlInput1"><small>Data de Nascimento</small></label>
						<input type="date" class="form-control" id="nascimento_cadastro" name="nascimento" required>
					</div>

					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="termos" name="termos" value="Sim" required>
						<label class="form-check-label" for="exampleCheck1"><small>Aceitar <a href="../termos" target="_blank">Termos e Condições</a> e <a href="../politica" target="_blank">Politíca de Privacidade</a></small></label>
					</div>					
				
				<br><small><div align="center" id="mensagem-cadastro"></div></small>	
			</div>
			<div class="modal-footer">       
				<button type="submit" class="btn btn-primary">Cadastrar</button>
			</div>
			</form>
		</div>
	</div>
</div>







<!-- Modal Recuperar Senha -->
<div class="modal fade" id="modalRecuperar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Recuperar Senha</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="form-recuperar">
			<div class="modal-body">
									

					<div class="form-group">
						<label for="exampleFormControlInput1"><small>E-mail ou CPF</small></label>
						<input type="text" class="form-control" name="recuperar" placeholder="Seu E-mail Cadastrado ou CPF (Se tiver Inserido)" required>
					</div>			

				<br><small><div align="center" id="mensagem-recuperar"></div></small>	
				
			</div>
			<div class="modal-footer">       
				<button type="submit" class="btn btn-primary">Recuperar</button>
			</div>
			</form>
		</div>
	</div>
</div>



<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>


 <script type="text/javascript">
	$("#form-cadastro").submit(function () {
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: "cadastro.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-cadastro').text('');
				$('#mensagem-cadastro').removeClass()
				if (mensagem.trim() == "Cadastrado com Sucesso") {
					$('#mensagem-cadastro').addClass('text-success')
					$('#mensagem-cadastro').text(mensagem)	
					const nascimentoDigits = $('#nascimento_cadastro').val().replace(/\D/g, '');
					$('#usuario').val($('#cpf_cadastro').val())
					$('#senha').val(nascimentoDigits)				

				} else {

					$('#mensagem-cadastro').addClass('text-danger')
					$('#mensagem-cadastro').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>







 <script type="text/javascript">
	$("#form-recuperar").submit(function () {
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: "recuperar.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-recuperar').text('');
				$('#mensagem-recuperar').removeClass()
				if (mensagem.trim() == "") {
					//$('#btn-fechar-usu').click();
					$('#mensagem-recuperar').addClass('text-success')
					$('#mensagem-recuperar').text('Senha Enviada para o Email!')						

				} else {

					if(mensagem.trim() == "Não possui cadastro com este email ou cpf digitado!"){
						$('#mensagem-recuperar').addClass('text-danger')
						$('#mensagem-recuperar').text(mensagem)
					}else{
						$('#mensagem-recuperar').addClass('text-danger')
						$('#mensagem-recuperar').text('Você não está conectado a um servidor SMTP, pode ser que esteja em um servidor local (não é possível disparar e-mail no servidor local) ou o seu servidor de hospedagem está com este serviço desativado, precisa ativá-lo!')
					}

					
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>
