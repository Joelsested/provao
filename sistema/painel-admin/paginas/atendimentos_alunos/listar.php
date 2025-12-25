<?php

require_once("../../../conexao.php");

$tabela = 'alunos';



@session_start();



$id_user = $_SESSION['id'];









echo <<<HTML

<small>

HTML;



if (@$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Administrador') {

	$oculter = 'ocultar';

} else {

	$oculter = '';

}

if (
  
    @$_SESSION['nivel'] != 'Tutor'
) {
    $oculter2 = 'ocultar';
} else {
    $oculter2 = '';
}




if (@$_SESSION['nivel'] == 'Professor' || @$_SESSION['nivel'] == 'Tutor' || @$_SESSION['nivel'] == 'Parceiro' || @$_SESSION['nivel'] == 'Vendedor') {





	$query_vendedor_professor = $pdo->query("SELECT * FROM vendedores WHERE professor = 1");

	$res_query_vendedor_professor = $query_vendedor_professor->fetchAll(PDO::FETCH_ASSOC);



	$vendedor_ids = array_column($res_query_vendedor_professor, 'id');



	if (!empty($vendedor_ids)) {

		$placeholders = implode(',', array_fill(0, count($vendedor_ids), '?'));



		// Consulta usuários com o nível 'Vendedor'

		$query_usuarios = $pdo->prepare("SELECT * FROM usuarios WHERE id_pessoa IN ($placeholders) AND nivel = 'Vendedor' ORDER BY id desc");

		$query_usuarios->execute($vendedor_ids);



		$res_query_usuarios = $query_usuarios->fetchAll(PDO::FETCH_ASSOC);

	} else {

		$res_query_usuarios = []; // Se não houver vendedores, a lista de usuários fica vazia

	}



	if (!empty($res_query_usuarios)) {

		// Extrai os IDs dos usuários

		$usuario_ids = array_column($res_query_usuarios, 'id');



		// Se houver IDs de usuários, consulta os alunos

		if (!empty($usuario_ids)) {

			$placeholders_alunos = implode(',', array_fill(0, count($usuario_ids), '?'));



			// Consulta os alunos cujos usuários estão na lista

			$query_alunos = $pdo->prepare("SELECT * FROM alunos WHERE usuario IN ($placeholders_alunos) ORDER BY id desc");

			$query_alunos->execute($usuario_ids);



			$res_query_alunos = $query_alunos->fetchAll(PDO::FETCH_ASSOC);

		} else {

			$res_query_alunos = []; // Se não houver usuários, a lista de alunos fica vazia

		}

	} else {

		$res_query_alunos = []; // Se não houver usuários, a lista de alunos fica vazia

	}



	$res = $res_query_alunos;



	$total_reg = @count($res);

} else {

	$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
	$query->execute();
	$res = $query->fetchAll(PDO::FETCH_ASSOC);

	$total_reg = @count($res);

}

if ($total_reg > 0) {

	echo <<<HTML

	<table class="table table-hover" id="tabela">

	<thead> 

	<tr> 

	<th>Nome</th>

	<th class="esc">Telefone</th> 

	<th class="esc">Email</th> 	

		

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

		$rg = $res[$i]['rg'];
		$orgao_expedidor = $res[$i]['orgao_expedidor'] ?? '';

		$expedicao = $res[$i]['expedicao'];

		$telefone = $res[$i]['telefone'];

		$cep = $res[$i]['cep'];

		$endereco = $res[$i]['endereco'];

		$cidade = $res[$i]['cidade'];

		$estado = $res[$i]['estado'];

		$sexo = $res[$i]['sexo'];

		$nascimento = $res[$i]['nascimento'];

		$mae = $res[$i]['mae'];

		$pai = $res[$i]['pai'];

		$naturalidade = $res[$i]['naturalidade'];

		$professor4 = $res[$i]['usuario'];

		$foto = $res[$i]['foto'];

		$data = $res[$i]['data'];



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

		<img src="../painel-aluno/img/perfil/{$foto}" width="27px" class="mr-2">

		{$nome}	

		</td> 

		<td class="esc">

		{$telefone}

		<a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55{$telefone}" title="Chamar no Whatsapp"><i class="fa {$icone_whatsapp} verde"></i></a>

		</td>

		<td class="esc">{$email}</td>		

	

		

		<td>





		<li class="dropdown head-dpdn2" style="display: inline-block;">

		

<a href="index.php?pagina=arquivos_alunos&usuario={$email}"   title="Arquivos do aluno" ><big><i class="fa fa-file-pdf-o text-success"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">

		<li>

		<div  id="listar-cursosfin_{$id}">

		

		</div>

		</li>										

		</ul>

		</li>









		<big><a href="#"  onclick="editar ('{$id}', '{$nome}','{$cpf}','{$email}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$telefone}','{$cep}','{$endereco}','{$cidade}','{$estado}','{$sexo}','{$nascimento}','{$mae}','{$pai}','{$naturalidade}','{$foto}','{$arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>



		<big><a href="#" onclick="mostrar( '{$nome}','{$cpf}','{$email}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$telefone}','{$cep}','{$endereco}','{$cidade}','{$estado}','{$sexo}','{$nascimento}','{$mae}','{$pai}','{$naturalidade}', '{$foto}', '{$dataF}', '{$ativo}', '{$senha_usuario}','{$arquivo}')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>









		







		<big><a href="#" class="{$oculter}" onclick="ativar('{$id}', '{$acao}')" title="{$titulo_link}"><i class="fa {$icone} text-success"></i></a></big>





	



		<big><a class="{$oculter2}" href="$url_sistema/sistema/rel/avaliacoes_class.php?id={$id}" target="_blank" title="Avaliaçoes do aluno">

		<small><span class="fa fa-file-pdf-o text-danger" ></span></small>

		</a></big>



		<big><a class="{$oculter}" href="$url_sistema/sistema/rel/rel_certificado.php?id={$id}" target="_blank" title="Certificado do aluno">

		<small><span class="fa fa-file-pdf-o text-primary" ></span></small>

		</a></big>

          



            <big><a class="{$oculter}" href="$url_sistema/sistema/rel/declaracao_medio_class.php?id={$id}" target="_blank" title="Declaração Médio">

		<small><span class="fa fa-file-pdf-o text-danger" ></span></small>

		</a></big>

          

          <big><a class="{$oculter}" href="$url_sistema/sistema/rel/declaracao_fundamental_class.php?id={$id}" target="_blank" title="Declaração Fundamental">

		<small><span class="fa fa-file-pdf-o text-primary" ></span></small>

		</a></big>

	        



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

	$(document).ready(function() {

		$('#tabela').DataTable({

			"ordering": false,

			"stateSave": true,

		});

		$('#tabela_filter label input').focus();

	});



	function editar(id, nome, cpf, email, rg, orgao_expedidor, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, foto, ) {



		$('#id').val(id);

		$('#nome').val(nome);

		$('#cpf').val(cpf);

		$('#email').val(email);

		$('#rg').val(rg);
		$('#orgao_expedidor').val(orgao_expedidor);
		$('#expedicao').val(expedicao);

		$('#telefone').val(telefone);

		$('#cep').val(cep);

		$('#endereco').val(endereco);

		$('#cidade').val(cidade);

		$('#estado').val(estado);

		$('#sexo').val(sexo);

		$('#nascimento').val(nascimento);

		$('#mae').val(mae);

		$('#pai').val(pai);

		$('#naturalidade').val(naturalidade);

		$('#foto').val('');



		$('#target').attr('src', '../painel-aluno/img/perfil/' + foto);



		$('#tituloModal').text('Editar Registro');

		$('#modalForm').modal('show');

		$('#mensagem').text('');

	}





	function mostrar(nome, cpf, email, rg, orgao_expedidor, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, foto, data, ativo, senha, arquivo) {



		$('#nome_mostrar').text(nome);

		$('#cpf_mostrar').text(cpf);

		$('#email_mostrar').text(email);

		$('#rg_mostrar').text(rg);
		$('#orgao_expedidor_mostrar').text(orgao_expedidor);
		$('#expedicao_mostrar').text(expedicao);

		$('#telefone_mostrar').text(telefone);

		$('#cep_mostrar').text(cep);

		$('#endereco_mostrar').text(endereco);

		$('#cidade_mostrar').text(cidade);

		$('#estado_mostrar').text(estado);

		$('#sexo_mostrar').text(sexo);

		$('#nascimento_mostrar').text(nascimento);

		$('#mae_mostrar').text(mae);

		$('#pai_mostrar').text(pai);

		$('#naturalidade_mostrar').text(naturalidade);

		$('#data_mostrar').text(data);



		$('#ativo_mostrar').text(ativo);

		$('#senha_mostrar').text(senha);

		$('#target_mostrar').attr('src', '../painel-aluno/img/perfil/' + foto);



		$('#modalMostrar').modal('show');



	}





	function limparCampos() {

		$('#id').val('');

		$('#nome').val('');

		$('#cpf').val('');

		$('#email').val('');

		$('#rg').val('');
		$('#orgao_expedidor').val('');

		$('#expedicao').val('');

		$('#telefone').val('');

		$('#cep').val('');

		$('#endereco').val('');

		$('#cidade').val('');

		$('#estado').val('');

		$('#sexo').val('');

		$('#nascimento').val('');

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

			data: {

				id,

				cartoes

			},

			dataType: "text",



			success: function(mensagem) {

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

			data: {

				id

			},

			dataType: "html",



			success: function(result) {



				$("#listar-cursosfin_" + id).html(result);



			}

		});

	}

</script>
