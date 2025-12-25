<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'finalizados';

if(@$_SESSION['nivel'] != 'Aluno'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


<div class="bs-example widget-shadow margem-mobile" style="padding:15px; margin-top:-10px" id="listar">
	
</div>




<!-- Modal Aulas -->
<div class="modal fade" id="modalAulas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_aula_titulo"> </span> - <span id="aulas_aula"> </span> Aulas</h4>
				<button id="btn-fechar-aula" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				
				<div class="modal-body">
					<div class="row">
						<div class="col-md-4">
							<div id="listar-aulas">

							</div>
						</div>

						<div class="col-md-8">
							<div id="perguntas">
								Perguntas do Curso
							</div>
						</div>
					</div>
					

					</div>


				</div>	

						

		</div>
	</div>
</div>





<!-- Modal Aula -->
<div class="modal fade" id="modalAula" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel"><span class="neutra ocultar-mobile" id="nome_da_sessao"> </span>  <span class="neutra ocultar-mobile" id="numero_da_aula"> </span>  <span class="neutra" id="nome_da_aula"></span>	 </h4>


				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -25px">
					<span class="neutra" aria-hidden="true">&times;</span>
				</button>
			</div>
			
			<div class="modal-body">

				<iframe width="100%" height="450" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target-video-aula"></iframe>

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




<input type="hidden" id="id_da_matricula">
<input type="hidden" id="id_do_curso">

<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


<script type="text/javascript">
	$(document).ready(function() {
		
		$('.sel2').select2({
				dropdownParent: $('#modalForm')
		});
});
</script>


<script type="text/javascript">
	function abrirAulas(id, nome, aulas, id_curso){		
		$('#nome_aula_titulo').text(nome);
		$('#aulas_aula').text(aulas);		
		$('#modalAulas').modal('show');
		$('#id_da_matricula').val(id);
		$('#id_do_curso').val(id_curso);
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
		$.ajax({
        url: 'paginas/' + pag + "/listar-aulas.php",
        method: 'POST',
        data: {id, id_mat},
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
		$('#id_da_aula').val(id);
		$.ajax({
        url: 'paginas/' + pag + "/listar-video.php",
        method: 'POST',
        data: {id, aula},
        dataType: "html",

        success:function(result){

        	if(result.trim() === 'Curso Finalizado'){
        		$('#nome_da_aula').text('Parabéns, você concluiu o Curso');
        		document.getElementById('btn-anterior').style.display = 'none';
        		document.getElementById('btn-proximo').style.display = 'none';
        		document.getElementById('target-video-aula').style.display = 'none';
        		$('#numero_da_aula').text('');
        		$('#nome_da_sessao').text('');
        		$('#texto-finalizado').text('Agora você já pode emitir seu certificado e avaliar nosso curso!!');
        	}else{
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

		

	}

	function anterior(){
		var id = $('#id_da_aula').val();
		abrirAula(id, 'anterior');
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

