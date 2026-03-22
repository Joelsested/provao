<?php
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'cursos';

$id_pacote_post = @$_POST['id_pacote'];
$id_mat_post = @$_POST['id_mat_post'];
$id_curso_post = @$_POST['id_curso_post'];
$nome_curso_post = @$_POST['nome_curso_post'];
$aulas_curso_post = @$_POST['aulas_curso_post'];




if (@$_SESSION['nivel'] != 'Aluno') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>


<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">

</div>




<!-- Modal Aulas -->
<div class="modal fade" id="modalAulas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true" data-backdrop="static" style="overflow: scroll; height:100%; scrollbar-width: thin;">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_aula_titulo"> </span> - Aulas <span id="aulas_aula"> </span>
				</h4>
				<span id="link-drive" class="text-muted"><small><small><a title="Assistir pelo Google Drive"
								id="link_drive_curso" href="" target="_blank"><i class="fa fa-link"
									aria-hidden="true"></i>
								Assistir pelo Google Drive</a> (Ao Finalizar solicitar liberação do
							Certificado)</small></small></span>


				<button id="btn-fechar-aula" type="button" class="close" data-dismiss="modal" aria-label="Close"
					style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<div class="row">
					<div class="col-md-5" style="margin-bottom: 20px">
						<div id="listar-aulas">

						</div>
					</div>

					<div class="col-md-7" style="margin-top: -10px">
						<div id="perguntas">
							<a class="text-dark" href="" data-toggle="modal" data-target="#modalPergunta"><i
									class="fa fa-question-circle"></i> <span class="text-muted">Nova Pergunta</span></a>
							<hr>

						</div>

						<div id="listar-perguntas">

						</div>
					</div>
				</div>


			</div>


		</div>



	</div>
</div>
</div>





<!-- Modal Aula -->
<div class="modal fade" id="modalAula" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel"><span class="neutra ocultar-mobile" id="nome_da_sessao">
					</span> <span class="neutra ocultar-mobile" id="numero_da_aula"> </span> <span class="neutra"
						id="nome_da_aula"></span> </h4>


				<button onclick="location.reload()" type="button" class="close" data-dismiss="modal" aria-label="Close"
					style="margin-top: -25px">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">

				<iframe class="video-mobile" width="100%" height="450" src="" frameborder="0"
					allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen
					id="target-video-aula"></iframe>

				<div id="video-fallback" style="display:none; margin: 10px 0;">
					<a id="link-video-externo" href="#" target="_blank" class="btn btn-sm btn-primary">Abrir video em nova aba</a>
				</div>

				<span id="texto-finalizado"></span>

				<div align="center">

					<a href="#" onclick="anterior()" class="cinza_escuro" id="btn-anterior">
						<span style="margin-right:10px"><i class="fa fa-arrow-left" style="font-size:20px;"></i>
							Anterior
						</span>
					</a>

					<a href="#" onclick="proximo()" class="cinza_escuro" id="btn-proximo">
						<span style="margin-right:10px">Próximo<i class="fa fa-arrow-right"
								style="font-size:20px;margin-left:3px"></i>
						</span>
					</a>

				</div>

				<input type="hidden" id="id_da_aula">

			</div>

		</div>
	</div>
</div>





<!-- Modal Pergunta -->
<div class="modal fade" id="modalPergunta" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Nova Pergunta - <span class="neutra ocultar-mobile"
						id="nome_do_curso_pergunta"> </span> </h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px"
					id="btn-fechar-pergunta">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form method="post" id="form-perguntas">
					<div class="modal-body">

						<div class="row">

							<div class="col-md-12">
								<div class="form-group">
									<label>Pergunta <small>(Max 255 Caracteres)</small></label>
									<input type="text" class="form-control" name="pergunta" id="pergunta" required
										maxlength="255">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label>Número da Aula <small>(Se Necessário)</small></label>
									<input type="number" class="form-control" name="num_aula" id="num_aula">
								</div>
							</div>

							<div class="col-md-6" align="right" style="margin-top: 15px">
								<button type="submit" class="btn btn-primary">Salvar</button>
							</div>




						</div>


						<br>
						<input type="hidden" name="id_curso" id="id_curso_pergunta">
						<small>
							<div id="mensagem-pergunta" align="center" class="mt-3"></div>
						</small>


						<hr>
						<div align="center" class="text-muted">
							<small>Se preferir mande sua dúvida diretamente em nosso whatsapp <a
									href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>"
									title="Chamar no Whatsapp" target="_blank"><i
										class="fa fa-whatsapp"></i><?php echo $tel_sistema ?></a></small>
						</div>


					</div>




				</form>


			</div>

		</div>
	</div>
</div>






<!-- Modal Resposta -->
<div class="modal fade" id="modalResposta" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel"><span id="pergunta_resposta"></span> <span
						class="neutra ocultar-mobile"> </span> </h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px"
					id="btn-fechar-resposta">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form method="post" id="form-respostas">

					<div id="listar-respostas">fsdfdsfsdfsd</div>

					<hr>

					<div class="col-md-12">
						<div class="form-group">
							<label>Resposta<small>(Max 500 Caracteres)</small></label>
							<textarea maxlength="500" class="form-control" name="resposta" id="resposta"></textarea>
						</div>
					</div>



					<div class="col-md-12" align="right" style="margin-top: 15px">
						<button type="submit" class="btn btn-primary">Salvar</button>
					</div>



					<br>
					<input type="hidden" name="id_pergunta" id="id_pergunta_resposta">
					<input type="hidden" name="id_curso" id="id_curso_resposta">
					<small>
						<div id="mensagem-pergunta" align="center" class="mt-3"></div>
					</small>


					<hr>
					<div class="modal-footer">

					</div>





				</form>


			</div>

		</div>
	</div>
</div>






<!-- Modal Avaliar -->
<div class="modal fade" id="modalAvaliar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Avaliar Curso - <span id="nome_curso_avaliar"></span>
					<span class="neutra ocultar-mobile"> </span>
				</h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px"
					id="btn-fechar-resposta">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form method="post" id="form-avaliar">

					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Nota<small>(de 1 a 5)</small></label>
								<select name="nota" class="form-control">
									<option value="5">5</option>
									<option value="4">4</option>
									<option value="3">3</option>
									<option value="2">2</option>
									<option value="1">1</option>
								</select>
							</div>
						</div>


						<div class="col-md-12">
							<div class="form-group">
								<label>Mensagem da Avaliação<small>(Max 500 Caracteres)</small></label>
								<textarea maxlength="500" class="form-control" name="avaliacao"
									id="avaliacao"></textarea>
							</div>
						</div>

					</div>



					<div class="col-md-12" align="right" style="margin-top: 15px">
						<button type="submit" class="btn btn-primary">Salvar</button>
					</div>



					<br>

					<input type="hidden" name="id_curso" id="id_curso_avaliar">

					<small>
						<div id="mensagem-avaliar" align="center" class="mt-3"></div>
					</small>


					<hr>
					<div class="modal-footer">

					</div>





				</form>


			</div>

		</div>
	</div>
</div>



<!-- Modal Questionario -->
<div class="modal fade" id="modalQuest" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document" style="  max-height: 60vh;">
		<div class="modal-content" style=" height: 90%; border-radius: 15px;">


			<div class="modal-body" style="padding: 0px !important;">
				<div style="display: none;" type="hidden" name="id_mat" id="id_mat_quest"></div>
				<div id="quest"></div>
			</div>

		</div>
	</div>
</div>






<!-- Modal Ações do Curso -->
<div class="modal fade" id="modalAcoesCurso" tabindex="-1" role="dialog" aria-labelledby="modalAcoesCursoLabel"
	aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header" style="background:#1f5f95; color:#fff;">
				<h4 class="modal-title" id="modalAcoesCursoLabel">Ações do Curso</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px">
					<span aria-hidden="true" style="color:#fff;">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<p class="text-muted" style="margin-bottom: 12px;">Curso: <strong id="acoes-curso-nome"></strong></p>
				<div class="acoes-curso-horizontal">
					<button id="btn-acao-videos" type="button" class="btn btn-success btn-acao-horizontal" onclick="acaoVerVideosCurso()">
						<i class="fa fa-video-camera"></i> Vídeos
					</button>
					<button id="btn-acao-apostilas" type="button" class="btn btn-primary btn-acao-horizontal" onclick="acaoAbrirApostilaCurso()">
						<i class="fa fa-book"></i> Apostilas
					</button>
					<button id="btn-acao-gabarito" type="button" class="btn btn-warning btn-acao-horizontal" onclick="acaoAbrirGabaritoCurso()">
						<i class="fa fa-download"></i> Gabarito
					</button>
					<button id="btn-acao-avaliacoes" type="button" class="btn btn-info btn-acao-horizontal" onclick="acaoAbrirAvaliacoesCurso()">
						<i class="fa fa-bar-chart"></i> Avaliações
					</button>
					<button id="btn-acao-prova" type="button" class="btn btn-vinho btn-acao-horizontal" onclick="acaoFazerProvaCurso()">
						<i class="fa fa-question-circle-o"></i> Fazer Prova
					</button>
				</div>
				<small class="text-muted" id="acoes-curso-alerta" style="display:block; margin-top: 12px;"></small>
			</div>
		</div>
	</div>
</div>

<!-- Modal Gabarito -->
<div class="modal fade" id="modalGabaritoCurso" tabindex="-1" role="dialog" aria-labelledby="modalGabaritoCursoLabel"
	aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="modalGabaritoCursoLabel">Gabaritos e Arquivos do Curso - <span id="nome-curso-gabarito"></span></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="listar-doc-modal"></div>
			</div>
		</div>
	</div>
</div>

<!-- Modal Apostilas -->
<div class="modal fade" id="modalApostilasCurso" tabindex="-1" role="dialog" aria-labelledby="modalApostilasCursoLabel"
	aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="modalApostilasCursoLabel">Apostilas do Curso - <span id="nome-curso-apostilas"></span></h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="listar-apostilas-modal"></div>
			</div>
		</div>
	</div>
</div>

<input type="hidden" id="id_da_matricula">
<input type="hidden" id="id_do_curso">

<script type="text/javascript">var pag = "<?= $pag ?>"</script>
<style>
	#modalAcoesCurso .modal-dialog {
		width: 760px;
		max-width: 95%;
	}
	.acoes-curso-horizontal {
		display: flex;
		gap: 8px;
		flex-wrap: nowrap;
		align-items: stretch;
	}
	.btn-acao-horizontal {
		flex: 1 1 0;
		min-width: 0;
		font-weight: 600;
		font-size: 14px;
		white-space: nowrap;
		padding: 8px 10px;
	}
	.btn-vinho {
		background-color: #7b1e3a;
		border-color: #7b1e3a;
		color: #fff;
	}
	.btn-vinho:hover,
	.btn-vinho:focus,
	.btn-vinho:active {
		background-color: #65172f;
		border-color: #65172f;
		color: #fff;
	}
</style>
<?php $ajax_js_ver = @filemtime(__DIR__ . '/../js/ajax.js'); ?>
<script src="js/ajax.js?v=<?php echo $ajax_js_ver; ?>"></script>


<script type="text/javascript">
	$(document).ready(function () {
		var id = "<?= $id_pacote_post ?>";
		listarCursos(id)

		var mat = "<?= $id_mat_post ?>";
		var curso = "<?= $id_curso_post ?>";
		var nome = "<?= $nome_curso_post ?>";
		var aulas = "<?= $aulas_curso_post ?>";


		if (mat != "") {
			abrirAulas(mat, nome, aulas, curso)
		}

		$('.sel2').select2({
			dropdownParent: $('#modalForm')
		});
	});
</script>


<script type="text/javascript">
	var contextoAcoesCurso = {
		idMatricula: 0,
		idCurso: 0,
		nomeCurso: '',
		totalAulas: 0,
		aulasConcluidas: 0,
		idPrimeiraAula: 0,
		idAulaPendente: 0,
		linkCurso: '',
		urlApostila: '',
		urlAvaliacoes: '',
		podeAcessar: false,
		temVideo: false,
		temApostila: false,
		temGabarito: false,
		podeProva: false,
		provaAprovada: false,
		notaPercentual: 0,
		notaEscala10: 0,
		mediaAprovacao: 60
	};

	function mostrarAvisoAcoesCurso(mensagem) {
		if (window.Swal) {
			Swal.fire({
				icon: 'info',
				title: 'Atenção',
				text: mensagem
			});
			return;
		}
		alert(mensagem);
	}

	function ajustarBotaoAcaoCurso(seletor, habilitado) {
		var $btn = $(seletor);
		$btn.prop('disabled', !habilitado);
		$btn.css('opacity', habilitado ? '1' : '0.6');
	}

	function normalizarUrlArquivo(url) {
		return String(url || '').replace(/([^:]\/)\/+/g, '$1');
	}

	function baixarArquivoNoApp(url) {
		var destino = normalizarUrlArquivo(url);
		if (!destino) {
			mostrarAvisoAcoesCurso('Arquivo invalido para download.');
			return false;
		}

		var link = document.createElement('a');
		link.href = destino;
		link.setAttribute('download', '');
		link.rel = 'noopener';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		return false;
	}

	function abrirArquivoNoApp(url, titulo) {
		var destino = normalizarUrlArquivo(url);
		if (!destino) {
			mostrarAvisoAcoesCurso('Arquivo invalido.');
			return false;
		}

		var nome = String(titulo || 'Visualizar Arquivo');
		var semQuery = destino.split('?')[0];
		var partes = semQuery.split('.');
		var ext = partes.length > 1 ? String(partes.pop()).toLowerCase() : '';
		var ehImagem = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1;

		var conteudo;
		if (ehImagem) {
			conteudo = '<div style="height:78vh;min-height:520px;display:flex;align-items:center;justify-content:center;">'
				+ '<img src="' + destino + '" alt="' + nome + '" style="max-width:100%;max-height:74vh;object-fit:contain;">'
				+ '</div>';
		} else {
			conteudo = '<iframe src="' + destino + '" style="width:100%;height:78vh;border:none;min-height:520px;"></iframe>';
		}

		if (window.Swal) {
			Swal.fire({
				title: nome,
				html: conteudo,
				width: '92%',
				showCloseButton: true,
				showConfirmButton: false
			});
		} else {
			window.location.href = destino;
		}

		return false;
	}

	function abrirModalAcoesCursoPorBotao(botao) {
		var $btn = $(botao);
		var lerBooleano = function (valor) {
			return String(valor || '').toLowerCase() === 'true';
		};
		abrirModalAcoesCurso(
			parseInt($btn.data('id-mat') || 0, 10),
			parseInt($btn.data('id-curso') || 0, 10),
			String($btn.data('nome-curso') || ''),
			parseInt($btn.data('total-aulas') || 0, 10),
			parseInt($btn.data('aulas-concluidas') || 0, 10),
			parseInt($btn.data('id-primeira-aula') || 0, 10),
			parseInt($btn.data('id-aula-pendente') || 0, 10),
			String($btn.data('link-curso') || ''),
			String($btn.data('url-apostila') || ''),
			String($btn.data('url-avaliacoes') || ''),
			lerBooleano($btn.data('pode-acessar')),
			lerBooleano($btn.data('tem-video')),
			lerBooleano($btn.data('tem-apostila')),
			lerBooleano($btn.data('tem-gabarito')),
			lerBooleano($btn.data('pode-prova')),
			lerBooleano($btn.data('prova-aprovada')),
			parseFloat($btn.data('nota-percentual') || 0),
			parseFloat($btn.data('nota-escala10') || 0),
			parseFloat($btn.data('media-aprovacao') || 60)
		);
	}

	function abrirModalAcoesCurso(idMatricula, idCurso, nomeCurso, totalAulas, aulasConcluidas, idPrimeiraAula, idAulaPendente, linkCurso, urlApostila, urlAvaliacoes, podeAcessar, temVideo, temApostila, temGabarito, podeProva, provaAprovada, notaPercentual, notaEscala10, mediaAprovacao) {
		contextoAcoesCurso.idMatricula = parseInt(idMatricula || 0, 10);
		contextoAcoesCurso.idCurso = parseInt(idCurso || 0, 10);
		contextoAcoesCurso.nomeCurso = nomeCurso || '';
		contextoAcoesCurso.totalAulas = parseInt(totalAulas || 0, 10);
		contextoAcoesCurso.aulasConcluidas = parseInt(aulasConcluidas || 0, 10);
		contextoAcoesCurso.idPrimeiraAula = parseInt(idPrimeiraAula || 0, 10);
		contextoAcoesCurso.idAulaPendente = parseInt(idAulaPendente || 0, 10);
		contextoAcoesCurso.linkCurso = linkCurso || '';
		contextoAcoesCurso.urlApostila = urlApostila || '';
		contextoAcoesCurso.urlAvaliacoes = urlAvaliacoes || '';
		contextoAcoesCurso.podeAcessar = !!podeAcessar;
		contextoAcoesCurso.temVideo = !!temVideo;
		contextoAcoesCurso.temApostila = !!temApostila;
		contextoAcoesCurso.temGabarito = !!temGabarito;
		contextoAcoesCurso.podeProva = !!podeProva;
		contextoAcoesCurso.provaAprovada = !!provaAprovada;
		contextoAcoesCurso.notaPercentual = Number(notaPercentual || 0);
		contextoAcoesCurso.notaEscala10 = Number(notaEscala10 || 0);
		contextoAcoesCurso.mediaAprovacao = Number(mediaAprovacao || 60);

		$('#acoes-curso-nome').text(contextoAcoesCurso.nomeCurso);
		$('#acoes-curso-alerta').text(contextoAcoesCurso.podeAcessar ? '' : 'Curso com status aguardando pagamento.');

		ajustarBotaoAcaoCurso('#btn-acao-videos', contextoAcoesCurso.podeAcessar && contextoAcoesCurso.idPrimeiraAula > 0);
		ajustarBotaoAcaoCurso('#btn-acao-apostilas', contextoAcoesCurso.podeAcessar && contextoAcoesCurso.temApostila);
		ajustarBotaoAcaoCurso('#btn-acao-gabarito', contextoAcoesCurso.podeAcessar && contextoAcoesCurso.temGabarito);
		ajustarBotaoAcaoCurso('#btn-acao-avaliacoes', true);
		// Mantém clicável para exibir aviso quando a prova ainda não estiver liberada.
		ajustarBotaoAcaoCurso('#btn-acao-prova', true);

		$('#modalAcoesCurso').modal('show');
	}

	function acaoVerVideosCurso() {
		if (!contextoAcoesCurso.podeAcessar) {
			mostrarAvisoAcoesCurso('Este curso ainda não está liberado.');
			return;
		}
		if (contextoAcoesCurso.idPrimeiraAula <= 0) {
			mostrarAvisoAcoesCurso('Este curso não possui aulas cadastradas.');
			return;
		}
		$('#id_da_matricula').val(contextoAcoesCurso.idMatricula);
		$('#id_do_curso').val(contextoAcoesCurso.idCurso);
		$('#modalAcoesCurso').modal('hide');
		abrirAula(contextoAcoesCurso.idPrimeiraAula, 'aula');
	}

	function listarApostilasModal(id) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-apostilas.php",
			method: 'POST',
			data: { id },
			dataType: "html",
			success: function (result) {
				$("#listar-apostilas-modal").html(result);
			}
		});
	}

	function acaoAbrirApostilaCurso() {
		if (!contextoAcoesCurso.podeAcessar) {
			mostrarAvisoAcoesCurso('Este curso ainda não está liberado.');
			return;
		}
		if (!contextoAcoesCurso.temApostila || !contextoAcoesCurso.urlApostila) {
			mostrarAvisoAcoesCurso('Este curso não possui apostila cadastrada.');
			return;
		}
		$('#nome-curso-apostilas').text(contextoAcoesCurso.nomeCurso);
		listarApostilasModal(contextoAcoesCurso.idCurso);
		$('#modalApostilasCurso').modal('show');
	}

	function listarDocModal(id) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-doc.php",
			method: 'POST',
			data: { id },
			dataType: "html",
			success: function (result) {
				$("#listar-doc-modal").html(result);
			}
		});
	}

	function acaoAbrirGabaritoCurso() {
		if (!contextoAcoesCurso.podeAcessar) {
			mostrarAvisoAcoesCurso('Este curso ainda não está liberado.');
			return;
		}
		if (!contextoAcoesCurso.temGabarito) {
			mostrarAvisoAcoesCurso('Este curso não possui gabarito/arquivo cadastrado.');
			return;
		}
		$('#nome-curso-gabarito').text(contextoAcoesCurso.nomeCurso);
		listarDocModal(contextoAcoesCurso.idCurso);
		$('#modalGabaritoCurso').modal('show');
	}

	function acaoAbrirAvaliacoesCurso() {
		if (!contextoAcoesCurso.urlAvaliacoes) {
			mostrarAvisoAcoesCurso('Não foi possível carregar as avaliações deste curso.');
			return;
		}
		modalAvaliacao(contextoAcoesCurso.urlAvaliacoes);
	}

	function acaoFazerProvaCurso() {
		if (!contextoAcoesCurso.podeAcessar) {
			mostrarAvisoAcoesCurso('Este curso ainda não está liberado.');
			return;
		}
		if (contextoAcoesCurso.provaAprovada) {
			var nota10 = contextoAcoesCurso.notaEscala10.toFixed(1).replace('.', ',');
			var notaPerc = contextoAcoesCurso.notaPercentual.toFixed(2).replace('.', ',');
			var msgAprovado = 'Prova já feita com aprovação. Nota: ' + nota10 + ' (' + notaPerc + '%).';
			if (window.Swal) {
				Swal.fire({
					icon: 'success',
					title: 'Prova já realizada',
					text: msgAprovado,
					showCancelButton: true,
					confirmButtonText: 'Ver Avaliações',
					cancelButtonText: 'Fechar'
				}).then(function (result) {
					if (result.isConfirmed && contextoAcoesCurso.urlAvaliacoes) {
						modalAvaliacao(contextoAcoesCurso.urlAvaliacoes);
					}
				});
			} else {
				alert(msgAprovado);
			}
			return;
		}
		if (!contextoAcoesCurso.podeProva) {
			var aulasConcluidas = parseInt(contextoAcoesCurso.aulasConcluidas || 0, 10);
			var totalAulas = parseInt(contextoAcoesCurso.totalAulas || 0, 10);
			var mensagem = 'Prova ainda não liberada. Você concluiu ' + aulasConcluidas + ' de ' + totalAulas + ' aulas. Assista todas as aulas em Vídeos para liberar a prova.';
			if (window.Swal) {
				Swal.fire({
					icon: 'info',
					title: 'Prova não liberada',
					text: mensagem,
					showCancelButton: true,
					confirmButtonText: 'Ir para Vídeos',
					cancelButtonText: 'Fechar'
				}).then(function (result) {
					if (result.isConfirmed) {
						var aulaDestino = parseInt(contextoAcoesCurso.idAulaPendente || 0, 10);
						if (aulaDestino <= 0) {
							aulaDestino = parseInt(contextoAcoesCurso.idPrimeiraAula || 0, 10);
						}
						if (aulaDestino > 0) {
							$('#id_da_matricula').val(contextoAcoesCurso.idMatricula);
							$('#id_do_curso').val(contextoAcoesCurso.idCurso);
							$('#modalAcoesCurso').modal('hide');
							abrirAula(aulaDestino, 'aula');
						}
					}
				});
			} else {
				alert(mensagem);
			}
			return;
		}
		$('#modalAcoesCurso').modal('hide');
		questionario(contextoAcoesCurso.idCurso, contextoAcoesCurso.nomeCurso, contextoAcoesCurso.idMatricula);
	}

	function abrirAulas(id, nome, aulas, id_curso, link) {

		if (link == "") {
			document.getElementById('link-drive').style.display = 'none';
		} else {
			document.getElementById('link-drive').style.display = 'block';
		}

		$('#nome_aula_titulo').text(nome);
		$('#aulas_aula').text(aulas);
		$('#modalAulas').modal('show');
		$('#id_da_matricula').val(id);
		$('#id_do_curso').val(id_curso);
		$('#link_drive_curso').attr('href', link);
		listarAulas(id_curso, id);
		//listarPerguntas(id);		

		$('#nome_do_curso_pergunta').text(nome);
		$('#id_curso_pergunta').val(id_curso);
		$('#id_curso_resposta').val(id_curso);

		listarPerguntas(id_curso);


	}
</script>

<script type="text/javascript">
	function obterIdAlunoAtivo() {
		var sessaoId = "<?= (int) ($_SESSION['id'] ?? 0) ?>";
		try {
			return localStorage.getItem('active_user_id') || localStorage.id_usu || sessaoId;
		} catch (e) {
			return sessaoId;
		}
	}

	function listarAulas(id, id_mat) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-aulas.php",
			method: 'POST',
			data: { id, id_mat, _ts: Date.now() },
			cache: false,
			dataType: "html",

			success: function (result) {
				$("#listar-aulas").html(result);
				$('#mensagem-aulas').text('');
			}
		});
	}

</script>


<script type="text/javascript">
	function normalizarLinkAula(link) {
		var valor = (link || '').trim();
		if (!valor || valor === 'https://SEU_LINK_PADRAO' || valor === 'SEU_LINK_PADRAO') {
			return '';
		}
		if (valor.indexOf('youtu.be/') !== -1) {
			var idCurto = valor.split('youtu.be/')[1].split(/[?&]/)[0];
			return idCurto ? ('https://www.youtube.com/embed/' + idCurto) : '';
		}
		if (valor.indexOf('youtube.com/watch') !== -1) {
			var matchWatch = valor.match(/[?&]v=([^&#]+)/);
			return matchWatch && matchWatch[1] ? ('https://www.youtube.com/embed/' + matchWatch[1]) : '';
		}
		if (valor.indexOf('youtube.com/shorts/') !== -1) {
			var idShort = valor.split('youtube.com/shorts/')[1].split(/[?&]/)[0];
			return idShort ? ('https://www.youtube.com/embed/' + idShort) : '';
		}
		return valor;
	}

	function exibirFallbackVideo(link) {
		if (!link) {
			$('#video-fallback').hide();
			$('#link-video-externo').attr('href', '#');
			return;
		}
		$('#link-video-externo').attr('href', link);
		$('#video-fallback').show();
	}

	function abrirAula(id, aula) {
		var questionario = "<?= $questionario_config ?>";
		var idMatriculaAtual = $('#id_da_matricula').val();

		$('#id_da_aula').val(id);
		$.ajax({
			url: 'paginas/' + pag + "/listar-video.php",
			method: 'POST',
			data: { id, aula, id_mat: idMatriculaAtual, _ts: Date.now() },
			cache: false,
			dataType: "html",

			success: function (result) {
				//alert(result)
				if (result.trim() === 'Curso Finalizado') {

					$('#nome_da_aula').text('Parabéns, você concluiu a Disciplina');



					document.getElementById('btn-anterior').style.display = 'none';
					document.getElementById('btn-proximo').style.display = 'none';
					document.getElementById('target-video-aula').style.display = 'none';
					$('#numero_da_aula').text('');
					$('#nome_da_sessao').text('');

				} else if (result.trim() === 'Aulas Concluídas') {

					$('#nome_da_aula').text('Parabéns, você concluiu as aulas, agora vá para a avaliação final!');
					$('#texto-finalizado').text('Responda o questionário final para ser aprovado na Disciplina.');

					document.getElementById('btn-anterior').style.display = 'none';
					document.getElementById('btn-proximo').style.display = 'none';
					document.getElementById('target-video-aula').style.display = 'none';
					$('#numero_da_aula').text('');
					$('#nome_da_sessao').text('');


				} else {

					document.getElementById('btn-anterior').style.display = 'inline-block';
					document.getElementById('btn-proximo').style.display = 'inline-block';
					$('#texto-finalizado').text('');

					var res = result.split('***');
					if (res.length < 4) {
						$('#texto-finalizado').text('Não foi possível abrir esta aula agora.');
						document.getElementById('target-video-aula').style.display = 'none';
						$('#target-video-aula').attr('src', '');
						exibirFallbackVideo('');
						$('#modalAula').modal('show');
						return;
					}
					var linkAula = normalizarLinkAula(res[2]);

					if (linkAula === '') {
						document.getElementById('target-video-aula').style.display = 'none';
						$('#target-video-aula').attr('src', '');
						$('#texto-finalizado').text('Esta aula não possui vídeo cadastrado. Clique em Próxima para continuar.');
						exibirFallbackVideo('');
					} else {
						document.getElementById('target-video-aula').style.display = 'inline-block';
						$('#target-video-aula').attr('src', 'about:blank');
						$('#target-video-aula').attr('src', linkAula);
						$('#texto-finalizado').text('Se o vídeo não carregar no painel, use o botão para abrir em nova aba.');
						exibirFallbackVideo(linkAula);
					}
					$('#numero_da_aula').text('Aula - ' + res[0]);
					$('#nome_da_aula').text(res[1]);
					$('#modalAula').modal('show');
					$('#id_da_aula').val(res[3]);
					$('#nome_da_sessao').text(res[4]);

					/*
					if(res[0] == 1){
						document.getElementById('btn-anterior').style.display = 'none';
					}else{
						document.getElementById('btn-anterior').style.display = 'inline';
					}
					*/

				}


			}
		});


	}
</script>


<script type="text/javascript">
	function proximo() {
		var id = $('#id_da_aula').val();
		abrirAula(id, 'proximo');

		var id_curso = $('#id_do_curso').val();
		var id_mat = $('#id_da_matricula').val();
		listarAulas(id_curso, id_mat);

		var id_post = "<?= $id_pacote_post ?>";
		listarCursos(id_post)


	}

	function anterior() {
		var id = $('#id_da_aula').val();
		abrirAula(id, 'anterior');
	}
</script>


<script type="text/javascript">
	function listarCursos(id) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-cursos.php",
			method: 'POST',
			data: { id },
			dataType: "html",

			success: function (result) {
				$("#listar").html(result);
				$('#mensagem-excluir').text('');
			}
		});
	}

</script>


<script type="text/javascript">

	$("#form-perguntas").submit(function () {
		var id_curso = $('#id_curso_pergunta').val();
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/inserir-pergunta.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-pergunta').text('');
				$('#mensagem-pergunta').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {
					$('#pergunta').val('')
					$('#num_aula').val('')
					$('#btn-fechar-pergunta').click();
					listarPerguntas(id_curso);

				} else {
					$('#mensagem-pergunta').addClass('text-danger')
					$('#mensagem-pergunta').text(mensagem)
				}

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>



<script type="text/javascript">
	function listarPerguntas(id) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-perguntas.php",
			method: 'POST',
			data: { id },
			dataType: "html",

			success: function (result) {
				$("#listar-perguntas").html(result);


			}
		});
	}

</script>


<script type="text/javascript">
	function excluirPergunta(id) {
		var id_curso = $('#id_curso_pergunta').val();
		$.ajax({
			url: 'paginas/' + pag + "/excluir-pergunta.php",
			method: 'POST',
			data: { id },
			dataType: "text",

			success: function (mensagem) {
				if (mensagem.trim() == "Excluído com Sucesso" || mensagem.trim() == "ExcluÃ­do com Sucesso") {
					listarPerguntas(id_curso);
				} else {
					$('#mensagem-excluir').addClass('text-danger')
					$('#mensagem-excluir').text(mensagem)
				}

			},

		});
	}

</script>



<script type="text/javascript">
	function modalResposta(id_pergunta, pergunta) {
		$('#pergunta_resposta').text(pergunta);
		$('#id_pergunta_resposta').val(id_pergunta);
		$('#modalResposta').modal('show');
		listarRespostas(id_pergunta);
	}
</script>





<script type="text/javascript">

	$("#form-respostas").submit(function () {
		var id_pergunta = $('#id_pergunta_resposta').val();
		var id_curso = $('#id_curso_pergunta').val();
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/inserir-resposta.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-resposta').text('');
				$('#mensagem-resposta').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {
					$('#resposta').val('')
					//$('#btn-fechar-resposta').click();
					listarRespostas(id_pergunta)
					listarPerguntas(id_curso)

				} else {
					$('#mensagem-resposta').addClass('text-danger')
					$('#mensagem-resposta').text(mensagem)
				}

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>




<script type="text/javascript">
	function listarRespostas(id) {
		$.ajax({
			url: 'paginas/' + pag + "/listar-respostas.php",
			method: 'POST',
			data: { id },
			dataType: "html",

			success: function (result) {
				$("#listar-respostas").html(result);

			}
		});
	}


	function listardoc(id) {



		$.ajax({
			url: 'paginas/' + pag + "/listar-doc.php",
			method: 'POST',
			data: { id },
			dataType: "html",

			success: function (result) {


				$("#listar-docfin_" + id).html(result);

			}
		});
	}

</script>




<script type="text/javascript">


	function excluirResposta(id) {
		var id_pergunta = $('#id_pergunta_resposta').val();
		$.ajax({
			url: 'paginas/' + pag + "/excluir-resposta.php",
			method: 'POST',
			data: { id },
			dataType: "text",

			success: function (mensagem) {
				if (mensagem.trim() == "Excluído com Sucesso" || mensagem.trim() == "ExcluÃ­do com Sucesso") {
					listarRespostas(id_pergunta);
				} else {
					$('#mensagem-resposta').addClass('text-danger')
					$('#mensagem-resposta').text(mensagem)
				}

			},

		});
	}

</script>



<script type="text/javascript">
	function avaliar(id_curso, nome) {
		$('#nome_curso_avaliar').text(nome);
		$('#id_curso_avaliar').val(id_curso);
		$('#modalAvaliar').modal('show');

	}
</script>




<script type="text/javascript">

	$("#form-avaliar").submit(function () {
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/inserir-avaliar.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-avaliar').text('');
				$('#mensagem-avaliar').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {

					//$('#btn-fechar-resposta').click();
					$('#mensagem-avaliar').addClass('verde')
					$('#mensagem-avaliar').text(mensagem)
					listarCursos()

				} else {
					$('#mensagem-avaliar').addClass('text-danger')
					$('#mensagem-avaliar').text(mensagem)
				}

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>



<script type="text/javascript">

	function excluir(id) {
		var idMat = '';
		if (id && typeof id === 'object') {
			idMat = (id.dataset && id.dataset.id) ? id.dataset.id : '';
			if (!idMat && window.jQuery) {
				idMat = $(id).data('id') || '';
			}
		}
		if (!idMat) {
			idMat = String(id || '').trim();
		}
		if (!idMat || idMat.toLowerCase() === 'undefined' || idMat.toLowerCase() === 'null') {
			$('#mensagem-excluir').addClass('text-danger')
			$('#mensagem-excluir').text('Matrícula inválida.')
			return;
		}
		idMat = idMat.replace(/[^0-9]/g, '');
		if (!idMat) {
			$('#mensagem-excluir').addClass('text-danger')
			$('#mensagem-excluir').text('Matrícula inválida.')
			return;
		}
		$.ajax({
			url: 'paginas/' + pag + "/excluir.php",
			method: 'POST',
			data: { id: idMat, id_matricula: idMat },
			dataType: "text",

			success: function (mensagem) {
				var texto = (mensagem || '').toLowerCase();
				if (texto.indexOf('sucesso') !== -1) {
					listarCursos();
				} else {
					$('#mensagem-excluir').addClass('text-danger')
					$('#mensagem-excluir').text(mensagem)
				}

			},

		});
	}
</script>



<script type="text/javascript">
	function questionario(curso, nome, id) {
		$('#curso_quest').text(nome);
		$('#id_curso_quest').val(curso);
		$('#id_mat_quest').val(id);
		$('#modalQuest').modal('show');
		listarQuest(curso, id);

	}

	function listarQuest(curso, id_mat) {
		var id_usu = obterIdAlunoAtivo();
		$.ajax({
			url: 'paginas/' + pag + "/listar-quest.php",
			method: 'POST',
			data: { curso, id_mat, id_usu },
			dataType: "html",
			cache: false,

			success: function (result) {
				const html = (result || '').trim();
				if (!html) {
					$("#quest").html('<div class="alert alert-danger" style="margin: 15px;">Não foi possível carregar o questionário.</div>');
					return;
				}
				$("#quest").html(html);
				$("#quest").find("script").each(function () {
					if (this.src) {
						var s = document.createElement('script');
						s.src = this.src;
						document.head.appendChild(s);
					} else {
						$.globalEval(this.text || this.textContent || this.innerHTML || '');
					}
				});

			},
			error: function (xhr) {
				const msg = xhr && xhr.responseText ? xhr.responseText : 'Erro inesperado ao carregar o questionario.';
				$("#quest").html('<div class="alert alert-danger" style="margin: 15px;">' + msg + '</div>');
			}
		});
	}
</script>
