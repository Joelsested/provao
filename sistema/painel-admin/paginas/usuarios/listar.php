<?php
require_once("../../../conexao.php");
$tabela = 'usuarios';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0) {
	echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Nome</th>	
	<th class="">Email</th> 
	<th class="">Senha</th>	
	<th class="esc">CPF</th>
	<th class="esc">Nível</th>
	<th class="esc">Data</th>	
	<th class="esc">Ações</th>	
	
	
	</tr> 
	</thead> 
	<tbody>
HTML;

	for ($i = 0; $i < $total_reg; $i++) {
		foreach ($res[$i] as $key => $value) {
		}
		$id = $res[$i]['id'];
		$nome = $res[$i]['nome'];
		$cpf = $res[$i]['cpf'];
		$email = $res[$i]['usuario'];
		$senha = $res[$i]['senha'];
		$nivel = $res[$i]['nivel'];
		$data = $res[$i]['data'];
		$foto = $res[$i]['foto'];
		$ativo = $res[$i]['ativo'];

		if ($nivel == 'Administrador') {
			$senha = '*********';
		}

		$dataF = implode('/', array_reverse(explode('-', $data)));


		if ($ativo == 'Sim') {
			$classe_linha = '';
		} else {
			$classe_linha = 'text-muted';
		}

		if ($nivel == 'Aluno') {
			$caminho = '../painel-aluno/img/perfil/' . $foto;
		} else {
			$caminho = 'img/perfil/' . $foto;
		}

		echo <<<HTML
<tr class="{$classe_linha}"> 
		<td>

		<img src="{$caminho}" width="30px" height="30px" class="mr-2">
		
		{$nome}	
		</td> 		
		<td class="">{$email}</td>
		<td class="">{$senha}</td>
		<td class="esc">{$cpf}</td>	
		<td class="esc">{$nivel}</td>		
		<td class="esc">{$dataF}</td>
		<td>
		<button type="button" class="btn btn-sm btn-primary" 
		onclick="mostrar('{$nome}', '{$email}','{$cpf}','{$nivel}', '{$dataF}', '{$caminho}')"
		>
           Visualizar
        </button>


	</td>
		
		</tr>
HTML;

	}

	echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>	
HTML;

} else {
	echo 'Não possui nenhum registro cadastrado!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready(function () {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
		});
		$('#tabela_filter label input').focus();
	});



	function mostrar(nome, email, cpf, nivel, dataF, foto) {
		// Animações e estilos herdados do design original
		const animationStyles = `
	<style>
		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(20px); }
			to { opacity: 1; transform: translateY(0); }
		}
		
		@keyframes pulse {
			0% { transform: scale(1); }
			50% { transform: scale(1.05); }
			100% { transform: scale(1); }
		}

		.profile-card {
			border-radius: 16px;
			overflow: hidden;
			box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
			animation: fadeIn 0.6s ease-out forwards;
			background-color: #FFF;
			max-height: 100%;
		}

		.profile-header {
			background: linear-gradient(135deg, #337ab7, #337ab7);
			padding: 20px;
			color: white;
			text-align: center;
		}

		.profile-header h3 {
			margin: 0;
			font-weight: 600;
			letter-spacing: 1px;
		}

		.profile-img-container {
			text-align: center;
			margin-top: -20px;
		}

		.profile-img {
			width: 100px;
			height: 100px;
			object-fit: cover;
			border-radius: 50%;
			border: 5px solid white;
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
			animation: pulse 2s infinite;
		}

		.profile-body {
			padding: 30px;
			margin-top: -20px;
		}

		.info-card {
			background: rgba(255, 255, 255, 0.9);
			backdrop-filter: blur(10px);
			border-radius: 12px;
			padding: 15px;
			box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);
			transition: transform 0.3s ease, box-shadow 0.3s ease;
			margin-bottom: -15px;
		}

		.info-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 7px 14px rgba(50, 50, 93, 0.15);
		}

		.info-card h5 {
			margin-top: 0;
			font-size: 14px;
			font-weight: 600;
			color: #8898aa;
			text-transform: uppercase;
			letter-spacing: 1px;
			border-bottom: 1px solid #f0f0f0;
			padding-bottom: 8px;
		}

		.info-item {
			display: flex;
			justify-content: space-between;
			margin-bottom: 10px;
			align-items: center;
		}

		.info-label {
			font-weight: 600;
			color: #525f7f;
		}

		.info-value {
			color: #32325d;
			background: #f6f9fc;
			padding: 5px 10px;
			border-radius: 6px;
			font-family: 'Roboto Mono', monospace;
			font-size: 16px;
		}

		.swal2-close {
			position: absolute !important;
			top: 35px !important;
			right: 35px !important;
			background: rgba(255, 255, 255, 0.2) !important;
			backdrop-filter: blur(5px) !important;
			border-radius: 50% !important;
			width: 36px !important;
			height: 36px !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			color: white !important;
			font-size: 24px !important;
			transition: background 0.3s !important;
		}

		.swal2-close:hover {
			background: rgba(255, 255, 255, 0.3) !important;
			color: white !important;
		}
	</style>`;

		Swal.fire({
			title: '',
			width: '700px',
			padding: 0,
			background: 'transparent',
			html: `
		${animationStyles}
		<div class="profile-card">
			<div class="profile-header">
				<h3>${nome.toUpperCase()}</h3>
			</div>
			<div class="profile-img-container">
				<img src="${foto}" class="profile-img" alt="Foto de Perfil">
			</div>
			<div style="margin-top:10px;">
			<h4 style="color: blue; font-weight: bold;">${nivel}</h4>
			</div>
			<div class="profile-body">
				<div class="info-card">
					<h5>Informações Pessoais</h5>
					<div class="info-item">
						<span class="info-label">CPF</span>
						<span class="info-value">${cpf}</span>
					</div>
				
					<div class="info-item">
						<span class="info-label">Email</span>
						<span class="info-value">${email}</span>
					</div>
				</div>
				<div class="info-card">
					<div class="info-item">
						<span class="info-label">Data de Cadastro</span>
						<span class="info-value">${dataF}</span>
					</div>
					
				</div>
				
			</div>
		</div>`,
			showCloseButton: true,
			showConfirmButton: false,
			customClass: {
				popup: 'swal-profile-popup',
				closeButton: 'swal-profile-close'
			}
		});
	}


</script>
