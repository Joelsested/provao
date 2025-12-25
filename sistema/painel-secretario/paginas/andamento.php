<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'andamento';

$id_pacote_post = @$_POST['id_pacote'];
$id_mat_post = @$_POST['id_mat_post'];
$id_curso_post = @$_POST['id_curso_post'];
$nome_curso_post = @$_POST['nome_curso_post'];
$aulas_curso_post = @$_POST['aulas_curso_post'];




if(@$_SESSION['nivel'] != 'Aluno'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">
	
</div>




<!-- Modal Aulas -->
<div class="modal fade" id="modalAulas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" style="overflow: scroll; height:100%; scrollbar-width: thin;">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_aula_titulo"> </span> - Aulas <span id="aulas_aula"> </span>  </h4>
				<span id="link-drive" class="text-muted"><small><small><a title="Assistir pelo Google Drive" id="link_drive_curso" href="" target="_blank"><i class="fa fa-link" aria-hidden="true"></i>
				Assistir pelo Google Drive</a> (Ao Finalizar solicitar liberação do Certificado)</small></small></span>


				<button id="btn-fechar-aula" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
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
								<a class="text-dark" href="" data-toggle="modal" data-target="#modalPergunta"><i class="fa fa-question-circle"></i> <span class="text-muted">Nova Pergunta</span></a>
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
<div class="modal fade" id="modalAula" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel"><span class="neutra ocultar-mobile" id="nome_da_sessao"> </span>  <span class="neutra ocultar-mobile" id="numero_da_aula"> </span>  <span class="neutra" id="nome_da_aula"></span>	 </h4>


				<button onclick="location.reload()" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>
			
			<div class="modal-body">

				<iframe class="video-mobile" width="100%" height="450" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target-video-aula"></iframe>

				<span id="texto-finalizado"></span>

				<div align="center">

				<a href="#" onclick="anterior()" class="cinza_escuro" id="btn-anterior">	
				<span style="margin-right:10px"><i class="fa fa-arrow-left" style="font-size:20px;"></i> Anterior
				</span>
				</a>

				<a href="#" onclick="proximo()" class="cinza_escuro" id="btn-proximo">
				<span style="margin-right:10px">Próximo<i class="fa fa-arrow-right" style="font-size:20px;margin-left:3px"></i>
				</span>
				</a>

				</div>

				<input type="hidden" id="id_da_aula">
				
			</div>
			
		</div>
	</div>
</div>





<!-- Modal Pergunta -->
<div class="modal fade" id="modalPergunta" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Nova Pergunta - <span class="neutra ocultar-mobile" id="nome_do_curso_pergunta"> </span>  </h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px" id="btn-fechar-pergunta">
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
								<input type="text" class="form-control" name="pergunta" id="pergunta" required maxlength="255"> 
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
					<small><div id="mensagem-pergunta" align="center" class="mt-3"></div></small>


					<hr>
				<div align="center" class="text-muted">
					<small>Se preferir mande sua dúvida diretamente em nosso whatsapp <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" title="Chamar no Whatsapp" target="_blank"><i class="fa fa-whatsapp"></i><?php echo $tel_sistema ?></a></small>
				</div>
					

				</div>

				


			</form>
				
				
			</div>
			
		</div>
	</div>
</div>






<!-- Modal Resposta -->
<div class="modal fade" id="modalResposta" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel"><span id="pergunta_resposta"></span> <span class="neutra ocultar-mobile"> </span>  </h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px" id="btn-fechar-resposta">
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
					<small><div id="mensagem-pergunta" align="center" class="mt-3"></div></small>


					<hr>
				<div  class="modal-footer">
					
				</div>
					

				


			</form>
				
				
			</div>
			
		</div>
	</div>
</div>






<!-- Modal Avaliar -->
<div class="modal fade" id="modalAvaliar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Avaliar Curso - <span id="nome_curso_avaliar"></span> <span class="neutra ocultar-mobile"> </span>  </h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px" id="btn-fechar-resposta">
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
								<textarea maxlength="500" class="form-control" name="avaliacao" id="avaliacao"></textarea> 
							</div>						
						</div>

					</div>

						

						<div class="col-md-12" align="right" style="margin-top: 15px">						
							<button type="submit" class="btn btn-primary">Salvar</button>					
						</div>

							

					<br>
					
					<input type="hidden" name="id_curso" id="id_curso_avaliar"> 

					<small><div id="mensagem-avaliar" align="center" class="mt-3"></div></small>


					<hr>
				<div  class="modal-footer">
					
				</div>
					

				


			</form>
				
				
			</div>
			
		</div>
	</div>
</div>






<!-- Modal Questionario -->
<div class="modal fade" id="modalQuest" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel"><span id="curso_quest"></span> <span class="neutra ocultar-mobile"> </span>  </h4>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px" id="btn-fechar-quest">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>
			
			<div class="modal-body">
				<form method="post" id="form-quest">

					<div id="quest">

					</div>
					
					
					<input type="hidden" name="id_curso" id="id_curso_quest"> 
					<input type="hidden" name="id_mat" id="id_mat_quest"> 
					<small><div id="mensagem-quest" align="center" class="mt-3"></div></small>


					<hr>
				<div  class="modal-footer">
					<button type="submit" class="btn btn-primary">Finalizar</button>
				</div>
					

				


			</form>
				
				
			</div>
			
		</div>
	</div>
</div>





<input type="hidden" id="id_da_matricula">
<input type="hidden" id="id_do_curso">

<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


<script type="text/javascript">
	$(document).ready(function() {
		var id = "<?=$id_pacote_post?>";		
		listarCursos(id)

		var mat = "<?=$id_mat_post?>";
		var curso = "<?=$id_curso_post?>";
		var nome = "<?=$nome_curso_post?>";
		var aulas = "<?=$aulas_curso_post?>";
		

		if(mat != ""){			
			abrirAulas(mat, nome, aulas, curso)
		}

		$('.sel2').select2({
				dropdownParent: $('#modalForm')
		});
});
</script>


<script type="text/javascript">
	function abrirAulas(id, nome, aulas, id_curso, link){

		if(link == ""){
			document.getElementById('link-drive').style.display = 'none';
		}else{
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
	function listarAulas(id, id_mat){
		var id_usu = localStorage.id_usu;

		$.ajax({
        url: 'paginas/' + pag + "/listar-aulas.php",
        method: 'POST',
        data: {id, id_mat, id_usu},
        dataType: "html",

        success:function(result){
            $("#listar-aulas").html(result);
            $('#mensagem-aulas').text('');
        }
    });
	}
</script>


<script type="text/javascript">
	function abrirAula(id, aula){
		var id_usu = localStorage.id_usu;
		var questionario = "<?=$questionario_config?>";

		$('#id_da_aula').val(id);
		$.ajax({
        url: 'paginas/' + pag + "/listar-video.php",
        method: 'POST',
        data: {id, aula, id_usu},
        dataType: "html",

        success:function(result){
        	//alert(result)
        	if(result.trim() === 'Curso Finalizado'){
        	
        			$('#nome_da_aula').text('Parabéns, você concluiu o Curso');
        			$('#texto-finalizado').text('Agora você já pode emitir seu certificado e avaliar nosso curso!!');        		
        			
        		
        		document.getElementById('btn-anterior').style.display = 'none';
        		document.getElementById('btn-proximo').style.display = 'none';
        		document.getElementById('target-video-aula').style.display = 'none';
        		$('#numero_da_aula').text('');
        		$('#nome_da_sessao').text('');
        		
        	}else if(result.trim() === 'Aulas Concluídas'){

        		$('#nome_da_aula').text('Parabéns, você concluiu as aulas, agora vá para a avaliação final!');
        			$('#texto-finalizado').text('Responda o questionário final para ser aprovado no curso e solicitar seu certificado!');

        				document.getElementById('btn-anterior').style.display = 'none';
        		document.getElementById('btn-proximo').style.display = 'none';
        		document.getElementById('target-video-aula').style.display = 'none';
        		$('#numero_da_aula').text('');
        		$('#nome_da_sessao').text('');
        		

        	}else{

        		document.getElementById('btn-anterior').style.display = 'inline-block';
        		document.getElementById('btn-proximo').style.display = 'inline-block';
        		document.getElementById('target-video-aula').style.display = 'inline-block';
        		$('#texto-finalizado').text('');

        		var res = result.split('***');
        	
            $('#target-video-aula').attr('src', res[2]);
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
	function proximo(){
		var id = $('#id_da_aula').val();
		abrirAula(id, 'proximo');

		var id_curso = $('#id_do_curso').val();
		var id_mat = $('#id_da_matricula').val();
		listarAulas(id_curso, id_mat);

		var id_post = "<?=$id_pacote_post?>";		
		listarCursos(id_post)


	}

	function anterior(){
		var id = $('#id_da_aula').val();
		abrirAula(id, 'anterior');
	}
</script>


<script type="text/javascript">	 
	function listarCursos(id){
    $.ajax({
        url: 'paginas/' + pag + "/listar-cursos.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
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
	function listarPerguntas(id){
    $.ajax({
        url: 'paginas/' + pag + "/listar-perguntas.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
            $("#listar-perguntas").html(result);

           
        }
    });
}

</script>


<script type="text/javascript">
function excluirPergunta(id){
	var id_curso = $('#id_curso_pergunta').val();
    $.ajax({
        url: 'paginas/' + pag + "/excluir-pergunta.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
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
	function modalResposta(id_pergunta, pergunta){
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
	function listarRespostas(id){
    $.ajax({
        url: 'paginas/' + pag + "/listar-respostas.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
            $("#listar-respostas").html(result);
           
        }
    });
}

</script>




<script type="text/javascript">
	

function excluirResposta(id){
	var id_pergunta = $('#id_pergunta_resposta').val();
    $.ajax({
        url: 'paginas/' + pag + "/excluir-resposta.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
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
	function avaliar(id_curso, nome){
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
	
function excluir(id){
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Excluído com Sucesso") {                
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
	function questionario(curso, nome, id){
		$('#curso_quest').text(nome);
		$('#id_curso_quest').val(curso);
		$('#id_mat_quest').val(id);		
		$('#modalQuest').modal('show');
		listarQuest(curso);
	}

		function listarQuest(curso){
    $.ajax({
        url: 'paginas/' + pag + "/listar-quest.php",
        method: 'POST',
        data: {curso},
        dataType: "html",

        success:function(result){
            $("#quest").html(result);
           
        }
    });
}


	
$("#form-quest").submit(function () {	
	event.preventDefault();
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/resultado.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
			var msg = mensagem.split('***');			
			var nota = msg[1];
            $('#mensagem-quest').text('');
            $('#mensagem-quest').removeClass()
            if (msg[0].trim() == "Aprovado") { 
            		 alert('Parabéns, você foi aprovado com '+nota+'%, agora pode retirar seu certificado!');		                 
                     $('#btn-fechar-quest').click();                    
                     $('#mensagem-quest').text('');
                     listarCursos()
                 }else if(msg[0].trim() == "Reprovado"){
                 	 alert('Você foi reprovado. sua nota foi de '+nota+'%, você pode refazer o questionário com atenção para tentar novamente!');		                 
                     $('#btn-fechar-quest').click();                    
                     $('#mensagem-quest').text('');
                     listarCursos()

                } else {
                	$('#mensagem-quest').addClass('text-danger')
                    $('#mensagem-quest').text(msg[0])
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});
</script>
