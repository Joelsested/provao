<?php

require_once("../../../conexao.php");
require_once(__DIR__ . '/../../../../helpers.php');

$tabela = 'alunos';

@session_start();

$id_user = (int) ($_SESSION['id'] ?? 0);
$nivel_sessao = $_SESSION['nivel'] ?? '';
$usuarioRelacionados = buscarIdsUsuariosMesmaPessoa($pdo, $id_user);
if (empty($usuarioRelacionados)) {
	$usuarioRelacionados = [$id_user];
}

echo <<<HTML

<small>

HTML;

if (@$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Administrador') {
	$oculter = 'ocultar';
} else {
	$oculter = '';
}

if (@$_SESSION['nivel'] != 'Tutor') {
    $oculter2 = 'ocultar';
} else {
    $oculter2 = '';
}

$exprResponsavel = tableHasColumn($pdo, 'alunos', 'responsavel_id')
	? "COALESCE(NULLIF(responsavel_id, 0), usuario)"
	: "usuario";

if ($nivel_sessao == 'Administrador') {
	$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
	$query->execute();
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
	$placeholdersAtendente = implode(',', array_fill(0, count($usuarioRelacionados), '?'));
	$placeholdersResponsavel = implode(',', array_fill(0, count($usuarioRelacionados), '?'));
	$query = $pdo->prepare("SELECT * FROM alunos WHERE usuario IN ($placeholdersAtendente) AND {$exprResponsavel} NOT IN ($placeholdersResponsavel) ORDER BY id desc");
	$query->execute(array_merge($usuarioRelacionados, $usuarioRelacionados));
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
}

$total_reg = count($res);

if ($total_reg > 0) {

	echo <<<HTML
<div class="row" style="margin-bottom:10px;">
	<div class="col-sm-12" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
		<div>
			<label for="mostrar_outros_alunos_unico" style="margin-right:8px;">Mostrar</label>
			<select id="mostrar_outros_alunos_unico" class="form-control" style="display:inline-block; width:90px;">
				<option value="10" selected>10</option>
				<option value="25">25</option>
				<option value="50">50</option>
				<option value="100">100</option>
				<option value="-1">Todos</option>
			</select>
			<span style="margin-left:8px;">registros</span>
		</div>
		<div style="margin-left:auto;">
			<label for="busca_outros_alunos_unica" style="margin-right:8px;">Buscar:</label>
			<input type="text" id="busca_outros_alunos_unica" class="form-control" style="display:inline-block; width:280px;" placeholder="Buscar aluno...">
		</div>
	</div>
</div>

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
		$responsavel_id = $res[$i]['responsavel_id'] ?? $professor4;

		$foto = $res[$i]['foto'];

		$data = $res[$i]['data'];



		$ativo = $res[$i]['ativo'];

		$arquivo = $res[$i]['arquivo'];







		$query7 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
		$query7->execute([':id' => $responsavel_id]);
		$res7 = $query7->fetchAll(PDO::FETCH_ASSOC);

		$nome_professor = @$res7[0]['nome'];



		$dataF = implode('/', array_reverse(explode('-', $data)));

		$alunoPayload = [
			'id' => (int) $id,
			'nome' => $nome,
			'cpf' => $cpf,
			'email' => $email,
			'telefone' => $telefone,
			'rg' => $rg,
			'orgao_expedidor' => $orgao_expedidor,
			'expedicao' => $expedicao,
			'nascimento' => $nascimento,
			'cep' => $cep,
			'sexo' => $sexo,
			'endereco' => $endereco,
			'cidade' => $cidade,
			'estado' => $estado,
			'mae' => $mae,
			'pai' => $pai,
			'naturalidade' => $naturalidade,
			'responsavel_id' => $responsavel_id,
			'foto' => $foto,
			'arquivo' => $arquivo,
			'dataF' => $dataF,
			'ativo' => $ativo,
		];
		$alunoJson = json_encode($alunoPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
		$alunoJsonAttr = htmlspecialchars($alunoJson, ENT_QUOTES, 'UTF-8');









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









		$fotoLinha = trim((string)$foto);
		$srcFoto = $fotoLinha !== '' ? "../painel-aluno/img/perfil/{$fotoLinha}" : "../painel-aluno/img/perfil/sem-perfil.jpg";

		echo <<<HTML

<tr class="{$classe_linha}"> 

		<td>

		<img src="{$srcFoto}" width="27px" class="mr-2" onerror="this.onerror=null;this.src='../painel-aluno/img/perfil/sem-perfil.jpg';">

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









		<big><a href="#"  onclick='editarAluno({$alunoJsonAttr})' title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>



		<big><a href="#" onclick='mostrarAluno({$alunoJsonAttr})' title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>









		







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
<div id="rodape_registros_outros_alunos" style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:nowrap; overflow-x:auto;">
	<div id="resumo_registros_outros_alunos" style="color:#666; white-space:nowrap;"></div>
	<div id="paginacao_registros_outros_alunos" style="white-space:nowrap;"></div>
</div>

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
		let dtApi = null;
		let termoBuscaAtual = '';
		let totalRegistrosTabela = $('#tabela tbody tr').length;
		let paginaLocalAtual = 1;
		let totalPaginasLocal = 1;

		function normalizarTexto(valor) {
			return (valor || '')
				.toString()
				.toLowerCase()
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, '');
		}

		function aplicarBuscaLimiteLocal() {
			const busca = normalizarTexto(termoBuscaAtual);
			const limite = parseInt($('#mostrar_outros_alunos_unico').val() || '10', 10);
			const linhasCorrespondentes = [];

			$('#tabela tbody tr').each(function () {
				const textoLinha = normalizarTexto($(this).text());
				const corresponde = textoLinha.indexOf(busca) !== -1;
				if (!corresponde) {
					$(this).hide();
					return;
				}
				linhasCorrespondentes.push($(this));
			});

			const totalFiltrado = linhasCorrespondentes.length;
			if (limite === -1) {
				paginaLocalAtual = 1;
				totalPaginasLocal = 1;
				linhasCorrespondentes.forEach(function ($linha) { $linha.show(); });
				return { totalFiltrado: totalFiltrado, inicio: totalFiltrado ? 1 : 0, fim: totalFiltrado };
			}

			totalPaginasLocal = Math.max(1, Math.ceil(totalFiltrado / limite));
			if (paginaLocalAtual > totalPaginasLocal) {
				paginaLocalAtual = totalPaginasLocal;
			}
			if (paginaLocalAtual < 1) {
				paginaLocalAtual = 1;
			}

			const inicioIndice = (paginaLocalAtual - 1) * limite;
			const fimIndice = inicioIndice + limite;

			linhasCorrespondentes.forEach(function ($linha, indice) {
				if (indice >= inicioIndice && indice < fimIndice) {
					$linha.show();
				} else {
					$linha.hide();
				}
			});

			const inicio = totalFiltrado ? (inicioIndice + 1) : 0;
			const fim = Math.min(fimIndice, totalFiltrado);
			return { totalFiltrado: totalFiltrado, inicio: inicio, fim: fim };
		}

		function renderizarPaginacaoLocal(totalFiltrado) {
			const $paginacao = $('#paginacao_registros_outros_alunos');
			if (!$paginacao.length) {
				return;
			}

			const limite = parseInt($('#mostrar_outros_alunos_unico').val() || '10', 10);
			if (limite === -1 || totalFiltrado < 1) {
				$paginacao.html('');
				return;
			}

			let paginas = Math.max(1, Math.ceil(totalFiltrado / limite));
			if (dtApi) {
				const info = dtApi.page.info();
				paginas = Math.max(1, (info && info.pages) ? info.pages : 1);
				paginaLocalAtual = (info ? info.page : 0) + 1;
			}

			const inicioJanela = Math.max(1, paginaLocalAtual - 2);
			const fimJanela = Math.min(paginas, inicioJanela + 4);
			let html = '<ul class="pagination" style="margin:0;">';

			if (paginaLocalAtual > 1) {
				html += '<li><a href="#" data-page="' + (paginaLocalAtual - 1) + '">Anterior</a></li>';
			} else {
				html += '<li class="disabled"><span>Anterior</span></li>';
			}

			for (let i = inicioJanela; i <= fimJanela; i++) {
				if (i === paginaLocalAtual) {
					html += '<li class="active"><span>' + i + '</span></li>';
				} else {
					html += '<li><a href="#" data-page="' + i + '">' + i + '</a></li>';
				}
			}

			if (paginaLocalAtual < paginas) {
				html += '<li><a href="#" data-page="' + (paginaLocalAtual + 1) + '">Próximo</a></li>';
			} else {
				html += '<li class="disabled"><span>Próximo</span></li>';
			}

			html += '</ul>';
			$paginacao.html(html);
		}

		function atualizarResumoRegistros() {
			if (!$('#resumo_registros_outros_alunos').length) {
				return;
			}
			if (dtApi) {
				const info = dtApi.page.info();
				const totalFiltrado = info ? info.recordsDisplay : 0;
				const totalGeral = info ? info.recordsTotal : totalRegistrosTabela;
				if (!totalFiltrado) {
					$('#resumo_registros_outros_alunos').text('Nenhum aluno encontrado.');
					renderizarPaginacaoLocal(0);
					return;
				}
				const inicio = (info.start || 0) + 1;
				const fim = info.end || totalFiltrado;
				$('#resumo_registros_outros_alunos').text('Mostrando ' + inicio + ' até ' + fim + ' de ' + totalFiltrado + ' alunos' + (totalFiltrado !== totalGeral ? ' (total: ' + totalGeral + ')' : '') + '.');
				renderizarPaginacaoLocal(totalFiltrado);
				return;
			}
			const resultadoLocal = aplicarBuscaLimiteLocal();
			const totalFiltradoLocal = resultadoLocal ? resultadoLocal.totalFiltrado : 0;
			if (!totalFiltradoLocal) {
				$('#resumo_registros_outros_alunos').text('Nenhum aluno encontrado.');
				renderizarPaginacaoLocal(0);
				return;
			}
			$('#resumo_registros_outros_alunos').text('Mostrando ' + resultadoLocal.inicio + ' até ' + resultadoLocal.fim + ' de ' + totalFiltradoLocal + ' alunos' + (totalFiltradoLocal !== totalRegistrosTabela ? ' (total: ' + totalRegistrosTabela + ')' : '') + '.');
			renderizarPaginacaoLocal(totalFiltradoLocal);
		}

		function sincronizarPaginacaoRodape() {
			$('#tabela_paginate').hide();
		}

		function iniciarTabelaAtendimentosAlunos(tentativas) {
			if (!$('#tabela').length) {
				return;
			}

			if (!$.fn.DataTable) {
				if (tentativas > 0) {
					setTimeout(function () {
						iniciarTabelaAtendimentosAlunos(tentativas - 1);
					}, 150);
				}
				return;
			}

			if ($.fn.DataTable.isDataTable('#tabela')) {
				$('#tabela').DataTable().destroy();
			}

			dtApi = $('#tabela').DataTable({
				"ordering": false,
				"stateSave": true,
			});
			$('#tabela_filter').hide();
			$('#tabela_length').hide();
			$('#tabela_info').hide();
			sincronizarPaginacaoRodape();
			dtApi.page.len(parseInt($('#mostrar_outros_alunos_unico').val() || '10', 10)).draw();
			termoBuscaAtual = (dtApi.search() || '').toString();
			$('#busca_outros_alunos_unica').val(termoBuscaAtual);
			atualizarResumoRegistros();
			$('#tabela').on('draw.dt', function () {
				$('#tabela_info').hide();
				sincronizarPaginacaoRodape();
				atualizarResumoRegistros();
			});
		}

		iniciarTabelaAtendimentosAlunos(20);
		aplicarBuscaLimiteLocal();
		sincronizarPaginacaoRodape();
		atualizarResumoRegistros();
		$('#busca_outros_alunos_unica').on('input', function () {
			termoBuscaAtual = $(this).val() || '';
			paginaLocalAtual = 1;
			if (dtApi) {
				dtApi.search(termoBuscaAtual).draw();
				atualizarResumoRegistros();
				return;
			}
			aplicarBuscaLimiteLocal();
			atualizarResumoRegistros();
		});
		$('#mostrar_outros_alunos_unico').on('change', function () {
			const limite = parseInt($(this).val() || '10', 10);
			paginaLocalAtual = 1;
			if (dtApi) {
				dtApi.page.len(limite).draw();
				atualizarResumoRegistros();
				return;
			}
			aplicarBuscaLimiteLocal();
			atualizarResumoRegistros();
		});
		$(document).on('click', '#paginacao_registros_outros_alunos a[data-page]', function (e) {
			e.preventDefault();
			const novaPagina = parseInt($(this).attr('data-page') || '1', 10);
			if (!Number.isNaN(novaPagina) && novaPagina >= 1) {
				paginaLocalAtual = novaPagina;
				atualizarResumoRegistros();
			}
		});
		$('#busca_outros_alunos_unica').focus();
	});

	function editarAluno(data) {
		if (!data) {
			return;
		}
		editar(
			data.id || '',
			data.nome || '',
			data.cpf || '',
			data.email || '',
			data.rg || '',
			data.orgao_expedidor || '',
			data.expedicao || '',
			data.telefone || '',
			data.cep || '',
			data.endereco || '',
			data.cidade || '',
			data.estado || '',
			data.sexo || '',
			data.nascimento || '',
			data.mae || '',
			data.pai || '',
			data.naturalidade || '',
			data.responsavel_id || '',
			data.foto || ''
		);
	}

	function mostrarAluno(data) {
		if (!data) {
			return;
		}
		mostrar(
			data.nome || '',
			data.cpf || '',
			data.email || '',
			data.rg || '',
			data.orgao_expedidor || '',
			data.expedicao || '',
			data.telefone || '',
			data.cep || '',
			data.endereco || '',
			data.cidade || '',
			data.estado || '',
			data.sexo || '',
			data.nascimento || '',
			data.mae || '',
			data.pai || '',
			data.naturalidade || '',
			data.foto || '',
			data.dataF || '',
			data.ativo || '',
			data.arquivo || ''
		);
	}



	function editar(id, nome, cpf, email, rg, orgao_expedidor, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, responsavel_id, foto, ) {



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
		$('#responsavel_id').val(responsavel_id);

		$('#foto').val('');



		$('#target').attr('src', '../painel-aluno/img/perfil/' + foto);



		$('#tituloModal').text('Editar Registro');

		$('#modalForm').modal('show');

		$('#mensagem').text('');

	}





	function mostrar(nome, cpf, email, rg, orgao_expedidor, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, foto, data, ativo, arquivo) {



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
		$('#responsavel_id').val('');

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

