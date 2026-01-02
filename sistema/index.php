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

$consulta_responsaveis = $pdo->query("SELECT u.id, u.nome, u.nivel,
	COALESCE(t.telefone, v.telefone, s.telefone, te.telefone, '') AS telefone
	FROM usuarios u
	LEFT JOIN tutores t ON (u.nivel = 'Tutor' AND t.id = u.id_pessoa)
	LEFT JOIN vendedores v ON (u.nivel = 'Vendedor' AND v.id = u.id_pessoa)
	LEFT JOIN secretarios s ON (u.nivel = 'Secretario' AND s.id = u.id_pessoa)
	LEFT JOIN tesoureiros te ON (u.nivel = 'Tesoureiro' AND te.id = u.id_pessoa)
	WHERE u.nivel IN ('Tutor', 'Vendedor', 'Secretario', 'Tesoureiro') AND u.ativo = 'Sim'
	AND (
		(u.nivel = 'Tutor' AND (t.ativo = 'Sim' OR t.ativo IS NULL))
		OR (u.nivel = 'Vendedor' AND (v.ativo = 'Sim' OR v.ativo IS NULL))
		OR (u.nivel = 'Secretario' AND (s.ativo = 'Sim' OR s.ativo IS NULL))
		OR (u.nivel = 'Tesoureiro' AND (te.ativo = 'Sim' OR te.ativo IS NULL))
	)
	ORDER BY u.nome");
$responsaveis = $consulta_responsaveis ? $consulta_responsaveis->fetchAll(PDO::FETCH_ASSOC) : [];

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
								<input type="text" name="usuario" id="usuario" class="input100" placeholder="CPF (somente numeros)" pattern="[0-9A-Za-z@._\\-]{5,80}" title="Informe o CPF do aluno (apenas numeros) ou o e-mail do administrador" required>
								<small class="text-muted">Digite o CPF apenas com numeros (ex: 12345678909).</small>
								<span class="focus-input100"></span>
								</div>

							<div class="wrap-input100 validate-input">
								<span class="label-input100">Senha</span>
								<div class="input-group">
									<input type="password" name="senha" id="senha" class="input100" placeholder="Data de nascimento (DDMMAAAA)" required>
									<div class="input-group-append">
										<button type="button" class="btn btn-outline-secondary btn-sm" id="toggleSenha" aria-label="Mostrar senha" title="Mostrar senha"><i class="fa fa-eye"></i></button>
									</div>
								</div>
								<small class="text-muted">Informe somente os numeros da data de nascimento (DDMMAAAA).</small>
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

					<div class="form-group" id="grupo-professor-tutor">
						<label for="professor_tutor_id"><small>Responsavel</small></label>
						<select class="form-control" id="professor_tutor_id" name="professor_tutor_id" required>
							<option value="">Selecione</option>
							<?php foreach ($responsaveis as $responsavel) : ?>
								<?php
								$telefone_responsavel = $responsavel['telefone'] ?? '';
								$telefone_digits = preg_replace('/\D/', '', $telefone_responsavel);
								$nome_responsavel = $responsavel['nome'] ?? '';
								$nivel_responsavel = $responsavel['nivel'] ?? '';
								?>
								<option value="<?php echo (int) $responsavel['id'] ?>"
									data-nome="<?php echo htmlspecialchars($nome_responsavel, ENT_QUOTES) ?>"
									data-telefone="<?php echo htmlspecialchars($telefone_responsavel, ENT_QUOTES) ?>"
									data-phone="<?php echo htmlspecialchars($telefone_digits, ENT_QUOTES) ?>">
									<?php echo htmlspecialchars($nome_responsavel) ?> (<?php echo htmlspecialchars($nivel_responsavel) ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="termos" name="termos" value="Sim" required>
						<label class="form-check-label" for="exampleCheck1"><small>Aceitar <a href="../termos" target="_blank">Termos e Condições</a> e <a href="../politica" target="_blank">Politíca de Privacidade</a></small></label>
					</div>					
				
				<br><small><div align="center" id="mensagem-cadastro"></div></small>
				<div align="center" id="contato-professor" style="display:none;"></div>
			</div>
			<div class="modal-footer">       
				<button type="submit" class="btn btn-primary">Cadastrar</button>
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
				$('#mensagem-cadastro').removeClass();
				$('#contato-professor').hide().html('');
				if (mensagem.trim() == "Cadastrado com Sucesso") {
					$('#mensagem-cadastro').addClass('text-success')
					$('#mensagem-cadastro').text(mensagem)	
					const nascimentoDigits = $('#nascimento_cadastro').val().replace(/\D/g, '');
					$('#usuario').val($('#cpf_cadastro').val())
					$('#senha').val(nascimentoDigits)				

					var contatoHtml = montarContatoProfessor();
					if (contatoHtml) {
						$('#contato-professor').html(contatoHtml).show();
					}
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
	function escapeHtml(value) {
		return $('<div>').text(value || '').html();
	}

	function montarContatoProfessor() {
		var select = $('#professor_tutor_id');
		if (!select.length) {
			return '';
		}
		var option = select.find('option:selected');
		if (!option.val()) {
			return '';
		}
		var nome = option.data('nome') || '';
		var telefone = option.data('telefone') || '';
		var phoneDigits = (option.data('phone') || '').toString();

		var nomeHtml = escapeHtml(nome);
		var telefoneHtml = escapeHtml(telefone || phoneDigits);

		var html = '<div class="text-muted">Entre em contato com seu responsavel para receber atendimento.</div>';
		if (nomeHtml) {
			html += '<div><strong>' + nomeHtml + '</strong></div>';
		}
		if (phoneDigits) {
			html += '<div><a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55' + phoneDigits + '"><i class="fa fa-whatsapp"></i> ' + telefoneHtml + '</a></div>';
		} else if (telefoneHtml) {
			html += '<div>' + telefoneHtml + '</div>';
		}
		return html;
	}

	function ajustarResponsavel() {
		var tipo = $('#tipo').length ? $('#tipo').val() : 'Aluno';
		var grupo = $('#grupo-professor-tutor');
		var campo = $('#professor_tutor_id');
		if (tipo === 'Professor') {
			grupo.hide();
			campo.prop('required', false).val('');
		} else {
			grupo.show();
			campo.prop('required', true);
		}
	}

	$(function () {
		ajustarResponsavel();
		$('#tipo').on('change', ajustarResponsavel);
	});
</script>







 
