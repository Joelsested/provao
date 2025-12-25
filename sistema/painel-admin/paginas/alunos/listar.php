<?php
require_once("../../../conexao.php");
require_once "queries.php";
$tabela = 'alunos';

@session_start();

$id_user = $_SESSION['id'];



echo <<<HTML
<small>
HTML;

if (@$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Administrador') {
	$ocultar = 'ocultar';

} else {
	$ocultar = '';
}

if (@$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Vendedor' and @$_SESSION['nivel'] != 'Tutor') {
	$ocultar2 = 'ocultar';

} else {
	$ocultar2 = '';
}

if (@$_SESSION['nivel'] == 'Professor' || @$_SESSION['nivel'] == 'Tutor' || @$_SESSION['nivel'] == 'Parceiro' || @$_SESSION['nivel'] == 'Vendedor') {

	$query = $pdo->prepare("SELECT * FROM $tabela where usuario = :usuario ORDER BY id desc");
	$query->execute([':usuario' => $id_user]);
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);
} else {
	$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
	$query->execute();
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);


	foreach ($res as $i => $aluno) {
		$idAluno = $aluno["id"];

		// Buscar dados do usuarioAluno
		$usuarioAluno = buscarUsuarioAluno($idAluno);
		// $res[$i]["usuarioAluno"] = $usuarioAluno;

		// Buscar categorias, se o usuarioAluno for válido
		if ($usuarioAluno && isset($usuarioAluno["id"])) {
			$categorias = buscarCategoriasCursos($usuarioAluno["id"]);
		} else {
			$categorias = false;
		}
		$res[$i]["categoriasCursos"] = $categorias;
	}
	


}
if ($total_reg > 0) {
	echo <<<HTML
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Nome</th>
	<th class="esc">Telefone</th> 
	<th class="esc">Email</th> 	
	<th class="esc">Professor</th>	
	<th>Ações</th>
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
		$email = $res[$i]['email'];
		$telefone = $res[$i]['telefone'];
		$rg = $res[$i]['rg'];
		$orgao_expedidor = $res[$i]['orgao_expedidor'] ?? '';
		$expedicao = $res[$i]['expedicao'];
		$nascimento = $res[$i]['nascimento'];
		$cep = $res[$i]['cep'];
		$sexo = $res[$i]['sexo'];
		$endereco = $res[$i]['endereco'];
		$numero = $res[$i]['numero'];
		$bairro = $res[$i]['bairro'];
		$cidade = $res[$i]['cidade'];
		$estado = $res[$i]['estado'];
		$mae = $res[$i]['mae'];
		$pai = $res[$i]['pai'];
		$naturalidade = $res[$i]['naturalidade'];
		$professor4 = $res[$i]['usuario'];
		$foto = $res[$i]['foto'];
		$data = $res[$i]['data'];
		$categoriasCursos = $res[$i]['categoriasCursos'];

		$tem_fundamental = isset($categoriasCursos['tem_fundamental']) ? $categoriasCursos['tem_fundamental'] : 0;
		$tem_medio = isset($categoriasCursos['tem_medio']) ? $categoriasCursos['tem_medio'] : 0;

		$ativo = $res[$i]['ativo'];
		$arquivo = $res[$i]['arquivo'];



		$query7 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
		$query7->execute([':id' => $professor4]);
		$res7 = $query7->fetchAll(PDO::FETCH_ASSOC);
		$nome_professor = @$res7[0]['nome'];

		$dataF = implode('/', array_reverse(explode('-', $data)));

		$query2 = $pdo->prepare("SELECT * FROM usuarios where id_pessoa = :id_pessoa and nivel = 'Aluno'");
		$query2->execute([':id_pessoa' => $id]);
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$senha_usuario = $res2[0]['senha'];




		if ($ativo == 'Sim') {
			$icone = 'fa-check-square';
			$titulo_link = 'Desativar Item';
			$acao = 'Não';
			$classe_linha = '';
		} else {
			$icone = 'fa-square-o';
			$titulo_link = 'Ativar Item';
			$acao = 'Sim';
			$classe_linha = 'text-muted';
		}

		if ($telefone == "") {
			$icone_whatsapp = '';
		} else {
			$icone_whatsapp = 'fa-whatsapp';
		}

		if ($arquivo == "") {
			$esconder2 = 'ocultar';
		} else {
			$esconder2 = '';
		}




		echo <<<HTML
<tr class="{$classe_linha}"> 
		<td>
		<img src="../painel-aluno/img/perfil/{$foto}" width=30px" height="30px" class="mr-2">
		{$nome}	
		</td> 
		<td class="esc">
		{$telefone}
		<a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55{$telefone}" title="Chamar no Whatsapp"><i class="fa {$icone_whatsapp} verde"></i></a>
		</td>
		<td class="esc">{$email}</td>		
		<td class="esc">{$nome_professor}</td>
		


<!-- TD SWAL HERER -->

<!-- <td>
  <li class="dropdown head-dpdn2" style="display: inline-block;">
    <big>
      <a href="index.php?pagina=pagamentos_aluno&aluno={$id}" title="Ver Pagamentos">
        <i class="fa fa-money text-primary"></i>
      </a>
    </big>
    <a href="index.php?pagina=arquivos_alunos&usuario={$email}" title="Arquivos do aluno">
      <big>
        <i class="fa fa-file-pdf-o text-success"></i>
      </big>
    </a>
    <ul class="dropdown-menu" style="margin-left:-230px;">
      <li>
        <div id="listar-cursosfin_{$id}"></div>
      </li>
    </ul>
  </li>
  <big>
    <a href="#" onclick="editar ('{$id}', '{$nome}','{$cpf}','{$email}', '{$telefone}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$nascimento}','{$cep}','{$sexo}','{$endereco}','{$numero}','{$bairro}','{$cidade}','{$estado}','{$mae}','{$pai}','{$naturalidade}','{$foto}','{$arquivo}')" title="Editar Dados">
      <i class="fa fa-edit text-primary"></i>
    </a>
  </big>
  <big>
    <a href="#" onclick="mostrar( '{$nome}','{$cpf}','{$email}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$telefone}','{$cep}','{$endereco}','{$cidade}','{$estado}','{$sexo}','{$nascimento}','{$mae}','{$pai}','{$naturalidade}', '{$foto}', '{$dataF}', '{$ativo}', '{$senha_usuario}','{$arquivo}')" title="Ver Dados">
      <i class="fa fa-info-circle text-secondary"></i>
    </a>
  </big>
  <li class="dropdown head-dpdn2" style="display: inline-block;">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
      <big>
        <i class="fa fa-trash-o text-danger"></i>
      </big>
    </a>
    <ul class="dropdown-menu" style="margin-left:-230px;">
      <li>
        <div class="notification_desc2">
          <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')">
              <span class="text-danger">Sim</span>
            </a>
          </p>
        </div>
      </li>
    </ul>
  </li>
  <big>
    <a href="#" class="{$ocultar}" onclick="ativar('{$id}', '{$acao}')" title="{$titulo_link}">
      <i class="fa {$icone} text-success"></i>
    </a>
  </big>
  <big>
    <a class="{$ocultar}" href="$url_sistema/sistema/rel/avaliacoes_class.php?id={$id}" target="_blank" title="Avaliaçoes do aluno">
      <small>
        <span class="fa fa-file-pdf-o text-danger"></span>
      </small>
    </a>
  </big>
  <big>
    <a class="{$ocultar}" href="#" onclick="gerarCertAluno($id);" title="Certificado do aluno">
      <small>
        <span class="fa fa-file-pdf-o text-primary"></span>
      </small>
    </a>
  </big>
  <big>
    <a class="{$ocultar}" href="#" onclick="gerarDeclaracaoMedioAluno($id);" title="Declaração Médio">
      <small>
        <span class="fa fa-file-pdf-o text-danger"></span>
      </small>
    </a>
  </big>
  <big>
    <a class="{$ocultar}" href="#" onclick="gerarDeclaracaoFundamentalAluno($id);" title="Declaração Fundamental">
      <small>
        <span class="fa fa-file-pdf-o text-primary"></span>
      </small>
    </a>
  </big>
</td> -->

<td class="text-center">
  <!-- Single button to open actions modal -->
  <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#actionsModal{$id}">
    <i class="fa fa-cog"></i> Ver Ações
  </button>
  
  <!-- Modal with all actions -->
  <div class="modal fade" id="actionsModal{$id}" tabindex="-1" role="dialog" aria-labelledby="actionsModalLabel{$id}">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="actionsModalLabel{$id}">Ações para {$nome}</h4>
        </div>
        <div class="modal-body">
          <div class="row">

		   <!-- View Data -->
		   <div class="col-md-4 text-center mb-3">
              <a href="#" onclick="mostrar('{$nome}','{$cpf}','{$email}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$telefone}','{$cep}','{$endereco}','{$cidade}','{$estado}','{$sexo}','{$nascimento}','{$mae}','{$pai}','{$naturalidade}', '{$foto}', '{$dataF}', '{$ativo}', '{$senha_usuario}','{$arquivo}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-info-circle text-secondary"></i><br>
                Visualizar
              </a>
            </div>

		   <!-- Edit Data -->
		   <div class="col-md-4 text-center mb-3">
              <a href="#" onclick="editar('{$id}', '{$nome}','{$cpf}','{$email}', '{$telefone}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$nascimento}','{$cep}','{$sexo}','{$endereco}','{$numero}','{$bairro}','{$cidade}','{$estado}','{$mae}','{$pai}','{$naturalidade}','{$foto}','{$arquivo}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-edit text-primary"></i><br>
                Editar
              </a>
            </div>
            
           

            <!-- Payments -->
            <div class="col-md-4 text-center mb-3">
              <a href="index.php?pagina=pagamentos_aluno&aluno={$id}" class="btn btn-default btn-block">
                <i class="fa fa-money text-primary"></i><br>
                Pagamentos
              </a>
            </div>
            
             <!-- Financy -->
			 <div class="col-md-4 text-center mb-3">
              <a href="index.php?pagina=relatorio_aluno&aluno={$id}" class="btn btn-default btn-block">
                <i class="fa fa-money text-primary"></i><br>
                Relatório Financeiro
              </a>
            </div>
            
            <!-- Student Files -->
            <div class="col-md-4 text-center mb-3">
              <a href="index.php?pagina=arquivos_alunos&usuario={$email}" class="btn btn-default btn-block">
                <i class="fa fa-file-pdf-o text-success"></i><br>
                Arquivos do Aluno
              </a>
            </div>
            
           
            
            <!-- Delete -->
            <div class="{$ocultar} col-md-4 text-center mb-3">
              <a href="#" onclick="if(confirm('Confirm deletion?')) { excluir('{$id}'); }" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-trash-o text-danger"></i><br>
                Apagar
              </a>
            </div>
            
            <!-- Activate/Deactivate -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="ativar('{$id}', '{$acao}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa {$icone} text-success"></i><br>
                {$titulo_link}
              </a>
            </div>
            
            <!-- Student Evaluations -->
            <!-- <div class="col-md-4 text-center mb-3 {$ocultar2}">
              <a href="$url_sistema/sistema/rel/avaliacoes_class.php?id={$id}" target="_blank" class="btn btn-default btn-block">
                <i class="fa fa-file-pdf-o text-danger"></i><br>
                Avaliações
              </a>
            </div> -->

			<div class="col-md-4 text-center mb-3 {$ocultar2}">
				<a href="javascript:void(0);" onclick="modalAvaliacao('{$url_sistema}/sistema/rel/avaliacoes_class.php?id={$id}')" class="btn btn-default btn-block">
					<i class="fa fa-file-pdf-o text-danger"></i><br>
					Avaliações
				</a>
				</div>
            
            <!-- Student Certificate -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarCertAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-pdf-o text-primary"></i><br>
                Certificados
              </a>
            </div>
            
            <!-- Medium Declaration -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarDeclaracaoMedioAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-pdf-o text-danger"></i><br>
                Declaração Médio
              </a>
            </div>
            
            <!-- Fundamental Declaration -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarDeclaracaoFundamentalAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-pdf-o text-primary"></i><br>
                Declaração Fundamental
              </a>
            </div>

			<div class="col-md-4 text-center mb-3">
              <a href="#" onclick="gerarHistoricoAluno({$id} , {$tem_fundamental}, {$tem_medio});" class="btn btn-default btn-block">
                <i class="fa fa-clock-o text-primary"></i><br>
               Gerar Histórico
              </a>
            </div>

            <!-- Enrollment Declaration -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarDeclaracaoMatriculadoAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-text-o text-info"></i><br>
                Matriculado(a)
              </a>
            </div>

			<!-- Reset Quest -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="listarMatriculasAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-graduation-cap text-primary"></i><br>
               Listar Cursos
              </a>
            </div>

            <!-- Enrollment Declaration Fundamental -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarDeclaracaoMatriculadoFundamentalAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-text-o text-primary"></i><br>
                Matriculado(a) Fundamental
              </a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>
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

<script>
$(document).ready(function () {
    $('#tabela').DataTable({
        ordering: false,
        stateSave: true,
        search: {
            smart: false
        },
        columnDefs: [
            {
                targets: "_all",
                render: function (data, type, row) {
                    if (type === 'filter') {
                        if (!data) return '';
                        let original = data.toString();
                        let semAcento = original
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '');
                        // retorna o original + uma cópia sem acento
                        return original + ' ' + semAcento;
                    }
                    return data;
                }
            }
        ]
    });

    $('#tabela_filter label input').focus();
});
</script>

<script type="text/javascript">

	

	function editar(id, nome, cpf, email, telefone, rg, orgao_expedidor, expedicao, nascimento, cep, sexo, endereco, numero, bairro, cidade, estado, mae, pai, naturalidade, foto,) {

		$('#id').val(id);
		$('#nome').val(nome);
		$('#cpf').val(cpf);
		$('#email').val(email);
		$('#telefone').val(telefone);
		$('#rg').val(rg);
		$('#orgao_expedidor').val(orgao_expedidor);
		$('#expedicao').val(expedicao);
		$('#nascimento').val(nascimento);
		$('#cep').val(cep);
		$('#sexo').val(sexo);
		$('#endereco').val(endereco);
		$('#numero').val(numero);
		$('#bairro').val(bairro);
		$('#cidade').val(cidade);
		$('#estado').val(estado);
		$('#mae').val(mae);
		$('#pai').val(pai);
		$('#naturalidade').val(naturalidade);
		$('#foto').val('');

		$('#target').attr('src', '../painel-aluno/img/perfil/' + foto);

		$('#tituloModal').text('Editar Registro');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	// 	function mostrar(nome, cpf, email, rg, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, foto, data, ativo, senha, arquivo) {

	// 		$('#nome_mostrar').text(nome);
	// 		$('#cpf_mostrar').text(cpf);
	// 		$('#email_mostrar').text(email);
	// 		$('#rg_mostrar').text(rg);
	// 		$('#expedicao_mostrar').text(expedicao);
	// 		$('#telefone_mostrar').text(telefone);
	// 		$('#cep_mostrar').text(cep);
	// 		$('#endereco_mostrar').text(endereco);
	// 		$('#cidade_mostrar').text(cidade);
	// 		$('#estado_mostrar').text(estado);
	// 		$('#sexo_mostrar').text(sexo);
	// 		$('#nascimento_mostrar').text(nascimento);
	// 		$('#mae_mostrar').text(mae);
	// 		$('#pai_mostrar').text(pai);
	// 		$('#naturalidade_mostrar').text(naturalidade);
	// 		$('#data_mostrar').text(data);

	// 		$('#ativo_mostrar').text(ativo);
	// 		$('#senha_mostrar').text(senha);
	// 		$('#target_mostrar').attr('src', '../painel-aluno/img/perfil/' + foto);

	// 		$('#modalMostrar').modal('show');

	// 	}


	function mostrar(nome, cpf, email, rg, orgao_expedidor, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, foto, data, ativo, senha, arquivo) {
		// Definindo cores para gradientes
		const primaryGradient = 'linear-gradient(135deg, #337ab7, #337ab7)';
		const secondaryGradient = 'linear-gradient(135deg, #337ab7, #337ab7)';

		// Status com cor apropriada
		const statusColor = ativo === 'Sim' ? '#42e695' : '#ff6b6b';
		const statusIcon = ativo === 'Sim' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';

		// Formatando dados
		const formatarData = (dataString) => {
			try {
				if (!dataString) return 'Não informado';
				const data = new Date(dataString);
				return data.toLocaleDateString('pt-BR');
			} catch (e) {
				return dataString;
			}
		};

		// Adiciona animação CSS
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
				background: ${primaryGradient};
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
				position: relative;
				margin-top: -5px;
				text-align: center;
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
				font-size: 14px;
			}
			
			.status-active {
				color: ${statusColor};
				font-weight: bold;
				display: flex;
				align-items: center;
				gap: 5px;
			}
			
			.location-chip {
				background: ${secondaryGradient};
				color: white;
				padding: 5px 12px;
				border-radius: 20px;
				display: inline-flex;
				align-items: center;
				gap: 5px;
				font-size: 13px;
				margin-right: 8px;
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
		</style>
	`;

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
					<img src="/sistema/painel-aluno/img/perfil/${foto}" class="profile-img" alt="Foto de Perfil">
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
						<div class="info-item">
							<span class="info-label">Telefone</span>
							<span class="info-value">${telefone}</span>
						</div>
						 <div class="info-item">
							<span class="info-label">Cidade</span>
							<span class="info-value">${cidade}</span>
						</div>
						 <div class="info-item">
							<span class="info-label">Estado</span>
							<span class="info-value">${estado}</span>
						</div>
					</div>
					
				   
					
					<div class="info-card">
						<h5>Status da Conta</h5>
						<div class="row">
							<div class="col-md-6">
								<div class="info-item">
									<span class="info-label">Data de Cadastro</span>
									<span class="info-value">${data}</span>
								</div>
							</div>
							<div class="col-md-6">
								<div class="info-item">
									<span class="info-label">Ativo</span>
									<span class="status-active">${ativo}</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`,
			showCloseButton: true,
			showConfirmButton: false,
			customClass: {
				popup: 'swal-profile-popup',
				closeButton: 'swal-profile-close'
			}
		});
	}



	function limparCampos() {
		$('#id').val('');
		$('#nome').val('');
		$('#cpf').val('');
		$('#email').val('');
		$('#telefone').val('');
		$('#rg').val('');
		$('#orgao_expedidor').val('');
		$('#expedicao').val('');
		$('#nascimento').val('');
		$('#cep').val('');
		$('#sexo').val('');
		$('#endereco').val('');
		$('#numero').val('');
		$('#bairro').val('');
		$('#cidade').val('');
		$('#estado').val('');
		$('#mae').val('');
		$('#pai').val('');
		$('#naturalidade').val('');
		$('#foto').val('');
		$('#target').attr('src', 'img/perfil/sem-perfil.jpg');
	}



	function editarCartoes(id) {
		var cartoes = $('#cartao-' + id).val();
		$.ajax({
			url: 'paginas/' + pag + "/editar-cartoes.php",
			method: 'POST',
			data: { id, cartoes },
			dataType: "text",

			success: function (mensagem) {
				if (mensagem.trim() == "Alterado com Sucesso") {
					$('#mensagem-excluir').addClass('verde')
					$('#mensagem-excluir').text(mensagem)
				} else {
					$('#mensagem-excluir').addClass('text-danger')
					$('#mensagem-excluir').text(mensagem)
				}
			},

		});
	}


	function listarCur(id) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-cur.php",
			method: 'POST',
			data: { id },
			dataType: "html",

			success: function (result) {

				$("#listar-cursosfin_" + id).html(result);

			}
		});
	}



	const baseUrl = "<?php echo rtrim($url_sistema, '/'); ?>/";

	function gerarCertAluno(id) {
		Swal.fire({
			title: "Gerar Certificado",
			html: `
			<label for="ano_certificado">Insira o ano da conclusão:</label>
			<br>
			<input type="number" id="ano_certificado" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
			<br>
			<br>
			<label for="data_certificado">Selecione a data do certificado:</label>
			<input type="date" id="data_certificado" class="swal2-input">
		`,
			showCancelButton: true,
			confirmButtonText: "Gerar Certificado",
			cancelButtonText: "Cancelar",
			preConfirm: () => {
				const anoCertificado = document.getElementById("ano_certificado").value;
				const dataCertificado = document.getElementById("data_certificado").value;

				if (!anoCertificado || anoCertificado.length !== 4) {
					Swal.showValidationMessage("Por favor, insira um ano válido (ex: 2025).");
					return false;
				}
				if (!dataCertificado) {
					Swal.showValidationMessage("Por favor, selecione uma data.");
					return false;
				}

				return { ano: anoCertificado, data: dataCertificado };
			}
		}).then((result) => {
			if (result.isConfirmed) {
				const { ano, data } = result.value;
				const url = `${baseUrl}sistema/rel/rel_certificado.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
				window.open(url, "_blank"); // Abre em uma nova guia
			}
		});
	}

	function gerarDeclaracaoFundamentalAluno(id) {
		Swal.fire({
			title: "Declaração Ensino Fundamental",
			// icon: "info",
			html: `
			<label for="ano_declaracao_fundamental">Insira o ano da conclusão:</label>
			<br>
			<input type="number" id="ano_declaracao_fundamental" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
			<br>
			<br>
			<label for="data_declaracao_fundamental">Selecione a data da declaração:</label>
			<input type="date" id="data_declaracao_fundamental" class="swal2-input">
		`,
			showCancelButton: true,
			confirmButtonText: "Gerar Declaração",
			cancelButtonText: "Cancelar",
			preConfirm: () => {
				const anoDeclaracaoFundamental = document.getElementById("ano_declaracao_fundamental").value;
				const dataDeclaracaoFundamental = document.getElementById("data_declaracao_fundamental").value;

				if (!anoDeclaracaoFundamental || anoDeclaracaoFundamental.length !== 4) {
					Swal.showValidationMessage("Por favor, insira um ano válido (ex: 2025).");
					return false;
				}
				if (!dataDeclaracaoFundamental) {
					Swal.showValidationMessage("Por favor, selecione uma data.");
					return false;
				}

				return { ano: anoDeclaracaoFundamental, data: dataDeclaracaoFundamental };
			}
		}).then((result) => {
			if (result.isConfirmed) {
				const { ano, data } = result.value;
				const url = `${baseUrl}sistema/rel/declaracao_fundamental_class.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
				window.open(url, "_blank"); // Abre em uma nova guia
			}
		});
	}


	

	function gerarDeclaracaoMatriculadoAluno(id) {
		Swal.fire({
			title: "Declara??o Matriculado(a)",
			html: `
			<label for="ano_declaracao_matriculado">Insira o ano:</label>
			<br>
			<input type="number" id="ano_declaracao_matriculado" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
			<br>
			<br>
			<label for="data_declaracao_matriculado">Selecione a data:</label>
			<input type="date" id="data_declaracao_matriculado" class="swal2-input">
		`,
			showCancelButton: true,
			confirmButtonText: "Gerar Declara??o",
			cancelButtonText: "Cancelar",
			preConfirm: () => {
				const anoDeclaracao = document.getElementById("ano_declaracao_matriculado").value;
				const dataDeclaracao = document.getElementById("data_declaracao_matriculado").value;

				if (!anoDeclaracao || anoDeclaracao.length !== 4) {
					Swal.showValidationMessage("Por favor, insira um ano v?lido (ex: 2025).");
					return false;
				}
				if (!dataDeclaracao) {
					Swal.showValidationMessage("Por favor, selecione uma data.");
					return false;
				}

				return { ano: anoDeclaracao, data: dataDeclaracao };
			}
		}).then((result) => {
			if (result.isConfirmed) {
				const { ano, data } = result.value;
				const url = `${baseUrl}sistema/rel/declaracao_matriculado_class.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
				window.open(url, "_blank");
			}
		});
	}



	function gerarDeclaracaoMatriculadoFundamentalAluno(id) {
		Swal.fire({
			title: "Declaracao Matriculado(a) Fundamental",
			html: `
			<label for="ano_declaracao_matriculado_fundamental">Insira o ano:</label>
			<br>
			<input type="number" id="ano_declaracao_matriculado_fundamental" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
			<br>
			<br>
			<label for="data_declaracao_matriculado_fundamental">Selecione a data:</label>
			<input type="date" id="data_declaracao_matriculado_fundamental" class="swal2-input">
		`,
			showCancelButton: true,
			confirmButtonText: "Gerar Declaracao",
			cancelButtonText: "Cancelar",
			preConfirm: () => {
				const anoDeclaracao = document.getElementById("ano_declaracao_matriculado_fundamental").value;
				const dataDeclaracao = document.getElementById("data_declaracao_matriculado_fundamental").value;

				if (!anoDeclaracao || anoDeclaracao.length !== 4) {
					Swal.showValidationMessage("Por favor, insira um ano valido (ex: 2025).");
					return false;
				}
				if (!dataDeclaracao) {
					Swal.showValidationMessage("Por favor, selecione uma data.");
					return false;
				}

				return { ano: anoDeclaracao, data: dataDeclaracao };
			}
		}).then((result) => {
			if (result.isConfirmed) {
				const { ano, data } = result.value;
				const url = `${baseUrl}sistema/rel/declaracao_matriculado_fundamental_class.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
				window.open(url, "_blank");
			}
		});
	}

function gerarDeclaracaoMedioAluno(id) {
		Swal.fire({
			title: "Declaração Ensino Médio",
			// icon: "info",
			html: `
			<label for="ano_declaracao_medio">Insira o ano da conclusão:</label>
			<br>
			<input type="number" id="ano_declaracao_medio" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
			<br>
			<br>
			<label for="data_declaracao_medio">Selecione a data da declaração:</label>
			<input type="date" id="data_declaracao_medio" class="swal2-input">
		`,
			showCancelButton: true,
			confirmButtonText: "Gerar Declaração",
			cancelButtonText: "Cancelar",
			preConfirm: () => {
				const anoDeclaracaoMedio = document.getElementById("ano_declaracao_medio").value;
				const dataDeclaracaoMedio = document.getElementById("data_declaracao_medio").value;

				if (!anoDeclaracaoMedio || anoDeclaracaoMedio.length !== 4) {
					Swal.showValidationMessage("Por favor, insira um ano válido (ex: 2025).");
					return false;
				}
				if (!dataDeclaracaoMedio) {
					Swal.showValidationMessage("Por favor, selecione uma data.");
					return false;
				}

				return { ano: anoDeclaracaoMedio, data: dataDeclaracaoMedio };
			}
		}).then((result) => {
			if (result.isConfirmed) {
				const { ano, data } = result.value;
				const url = `${baseUrl}sistema/rel/declaracao_medio_class.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
				window.open(url, "_blank"); // Abre em uma nova guia
			}
		});
	}

	function gerarHistoricoAluno(id, tem_fundamental, tem_medio) {
    Swal.fire({
        title: "Selecione o tipo de histórico",
        html: `
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
            <button id="btnFundamental" class="swal2-confirm swal2-styled" 
                style="background-color:${tem_fundamental == 1 ? '#3085d6' : '#6c757d'}; 
                       display:flex; flex-direction:column; align-items:center; justify-content:center; 
                       width:120px; height:120px; font-size:14px; 
                       ${tem_fundamental == 1 ? '' : 'cursor:not-allowed;'}"
                ${tem_fundamental == 1 ? '' : 'disabled title="O aluno não possui matrícula no Fundamental"'}>
                <i class="fa fa-book" style="font-size:28px; margin-bottom:10px;"></i>
                Fundamental
            </button>
            <button id="btnMedio" class="swal2-confirm swal2-styled" 
                style="background-color:${tem_medio == 1 ? '#28a745' : '#6c757d'}; 
                       display:flex; flex-direction:column; align-items:center; justify-content:center; 
                       width:120px; height:120px; font-size:14px; 
                       ${tem_medio == 1 ? '' : 'cursor:not-allowed;'}"
                ${tem_medio == 1 ? '' : 'disabled title="O aluno não possui matrícula no Ensino Médio"'}>
                <i class="fa fa-graduation-cap" style="font-size:28px; margin-bottom:10px;"></i>
                Médio
            </button>
        </div>
    `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: "Cancelar",
        didOpen: () => {
            // Botão Fundamental
            if (tem_fundamental == 1) {
                document.getElementById("btnFundamental").addEventListener("click", () => {
                    const url = `index.php?pagina=gerar_historico_fundamental&id=${id}`;
                    window.open(url, "_self");
                    Swal.close();
                });
            }

            // Botão Médio
            if (tem_medio == 1) {
                document.getElementById("btnMedio").addEventListener("click", () => {
                    const url = `index.php?pagina=gerar_historico_medio&id=${id}`;
                    window.open(url, "_self");
                    Swal.close();
                });
            }
        }
    });
}




	// index.php?pagina=gerar_historico_medio&id={$id}

</script>

<script>
	function modalAvaliacao(href) {
		const normalizedHref = href.replace(/([^:]\/)\/+/g, '$1');
		Swal.fire({
			title: 'Avaliações do Aluno',
			html: `
			<style>
			.spinner {
  width: 100px;
  height: 100px;
  border: 6px solid #f3f3f3; /* Cor da borda "fundo" */
  border-top: 6px solid #3498db; /* Cor da borda "frente" (girando) */
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: auto; /* Centraliza */
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
			
			</style>
			<div id="loading-spinner" class="text-center">
				<div class="spinner-border" role="status">
					
				</div>
				
				<div class="spinner"></div>
			</div>
			<iframe id="pdf-iframe" src="${normalizedHref}" width="100%" height="500px" style="border: none; display: none;" onload="hideLoading()"></iframe>
		`,
			width: '80%',
			showCloseButton: true,
			showConfirmButton: false,
			didOpen: () => {
				// Função que será executada quando o modal abrir
				window.hideLoading = function () {
					document.getElementById('loading-spinner').style.display = 'none';
					document.getElementById('pdf-iframe').style.display = 'block';
				}
			}
		});
	}
</script>


<script>
	function listarMatriculasAluno(id) {
		Swal.fire({
			title: 'Buscando informações...',
			didOpen: () => Swal.showLoading(),
			allowOutsideClick: false
		});

		fetch('/api/usuarios/buscar_aluno.php?id=' + id)
			.then(response => response.json())
			.then(data => {
				if (!data.success) {
					Swal.fire('Erro', data.message || 'Erro ao buscar informações.', 'error');
					return;
				}

				// Monta o <select>
				let selectOptions = '';
				let buttonsHTML = '';

				data.matriculas.forEach((mat, i) => {
					selectOptions += `<option value="${mat.id}">
		  ${mat.nome_curso} - ${mat.status} - R$ ${parseFloat(mat.valor).toFixed(2)}
		</option>`;

					if (parseInt(mat.has_perguntas) === 1) {
						buttonsHTML += `
			<button 
			  class="swal2-confirm swal2-styled"
			  onclick="apagarRespostas(${mat.id_curso}, '${mat.nome_curso}')"
			  style="margin: 5px 0; background-color: #d33"
			>
			  Apagar Respostas de "${mat.nome_curso}"
			</button>
		  `;
					}
				});

				const htmlContent = `
		<strong>Email:</strong> ${data.email}<br>
		<strong>Telefone:</strong> ${data.telefone}<br><br>

		<label>Selecione uma matrícula:</label>
		<select class="swal2-select" style="width: 80%">
		  ${selectOptions}
		</select>

		<br><br>
		${buttonsHTML}
	  `;

				Swal.fire({
					title: `Aluno: ${data.nome}`,
					html: htmlContent,
					width: '700px',
					showConfirmButton: false
				});
			})
			.catch(error => {
				console.error(error);
				Swal.fire('Erro', 'Erro na comunicação com o servidor.', 'error');
			});
	}


	function apagarRespostas(id_curso, nome_curso) {
		Swal.fire({
			title: `Deseja apagar todas as respostas de "${nome_curso}"?`,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Sim, apagar',
			cancelButtonText: 'Cancelar'
		}).then((result) => {
			if (result.isConfirmed) {
				fetch('/api/usuarios/apagar_perguntas.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'id_curso=' + encodeURIComponent(id_curso)
				})
					.then(res => res.json())
					.then(res => {
						Swal.fire(res.success ? 'Sucesso' : 'Erro', res.message, res.success ? 'success' : 'error');
					})
					.catch(() => {
						Swal.fire('Erro', 'Erro ao tentar apagar respostas.', 'error');
					});
			}
		});
	}


</script>
