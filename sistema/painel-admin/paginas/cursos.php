<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'cursos';
@session_start();
$id_usuario = $_SESSION['id'];



if(@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Professor' and @$_SESSION['nivel'] != 'Tesoureiro' and @$_SESSION['nivel'] != 'Tutor' and @$_SESSION['nivel'] != 'Parceiro' and @$_SESSION['nivel'] != 'Assessor'and @$_SESSION['nivel'] != 'Vendedor'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}
 ?>


  <button onclick="inserir()" type="button" class="btn btn-primary btn-flat btn-pri"><i class="fa fa-plus" aria-hidden="true"></i> Novo Curso</button>


 <div class="bs-example widget-shadow" style="padding:15px" id="listar">
	
</div>





<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"></h4>
				<button id="btn-fechar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form method="post" id="form-niceEdit">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-5">						
							<div class="form-group"> 
								<label>Nome</label> 
								<input type="text" class="form-control" name="nome" id="nome" required> 
							</div>						
						</div>

						<div class="col-md-2">						
							<div class="form-group"> 
								<label>Subtítulo</label> 
								<input type="text" class="form-control" name="desc_rapida" id="desc_rapida"> 
							</div>						
						</div>	

						<div class="col-md-3">						
							<div class="form-group"> 
								<label>Categoria</label> 
								<select class="form-control sel2" name="categoria" id="categoria" required style="width:100%;"> 
									<?php 
									$query = $pdo->query("SELECT * FROM categorias order by nome asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){}

											?>	
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>

									<?php } ?>

								</select>
							</div>						
						</div>		


						<div class="col-md-2">						
							<div class="form-group"> 
								<label>Grupo/Turma</label> 
								<select class="form-control sel2" name="grupo" id="grupo" required style="width:100%;"> 
									<?php 
									$query = $pdo->query("SELECT * FROM grupos order by nome asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){}

											?>	
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>

									<?php } ?>

								</select>
							</div>						
						</div>		



					</div>
					
					




					<div class="row">

						<div class="col-md-2">						
							<div class="form-group"> 
								<label>Valor</label> 
								<input type="text" class="form-control" name="valor" id="valor"> 
							</div>						
						</div>	

						<div class="col-md-2">						
							<div class="form-group"> 
								<label>Valor Promoção</label> 
								<input type="text" class="form-control" name="promocao" id="promocao"> 
							</div>						
						</div>		

						<div class="col-md-2">						
							<div class="form-group"> 
								<label>Carga</label> 
								<input type="text" class="form-control" name="carga" id="carga" placeholder="Horas"> 
							</div>						
						</div>

						<div class="col-md-6">						
							<div class="form-group"> 
								<label>Palavras Chaves</label> 
								<input type="text" class="form-control" name="palavras" id="palavras" placeholder="Ex: Fundamenta,  Médio, Profissionalizantes etc..."> 
							</div>						
						</div>	

					</div>



					<div class="row">	

					<div class="col-md-8 col-sm-12">						
							<div class="form-group"> 
								<label>Descrição do Curso</label> 
								<textarea name="desc_longa" id="area" class="textarea"> </textarea>
							</div>						
						</div>	
		
						

						<div class="col-md-4">						
							<div class="form-group"> 
								<label>Imagem</label> 
								<input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
							</div>

							<div id="divImg">
								<img src="img/cursos/sem-foto.png"  width="130px" id="target">									
							</div>						
						</div>
					

					</div>	




					



					

					<br>
					<input type="hidden" name="id" id="id"> 
					<small><div id="mensagem" align="center" class="mt-3"></div></small>					

				</div>


				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Salvar</button>
				</div>



			</form>

			<form method="post" id="form-arq">
                <div class="modal-body">

                   

                    <div class="row">
                        <div class="col-md-6">
                              <div class="form-group">
                                    <label >Descrição</label>
                                    <input value="" type="text" class="form-control" id="descricao" name="descricao" placeholder="Descrição" required>
                                </div>
                                <div class="row">
						<div class="col-md-12">						
							<div class="form-group"> 
								<label>arquivo</label> 
								<input class="form-control" type="file" name="arquivo_4" onChange="carregarImg2();" id="arquivo_4">
							</div>

							<div id="divImg">
								<img src="img/arquivos/sem-arquivo.png"  width="130px" id="target_2">									
							</div>						
						</div>
					</div>
                             

                        </div>

                        

                         <div class="col-md-6" id="listar-id-edit">
                            
                        </div>

                    </div>
                  



                 <div align="center" id="mensagem_arquivo" class="">

                 </div>

             </div>
             <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btn-cancelar-excluir">Cancelar</button>


                

                <input type="hidden" id="id_do_arq"  name="id_do_arq" >



                <button type="submit" id="btn-arquivo" name="btn-arquivo" class="btn btn-primary">Inserir</button>

            </div>
        </form>

		</div>
	</div>
</div>







<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="tituloModal"><span id="nome_mostrar"> </span> - Status : <span id="status_mostrar"> </span> - Comissão: <span id="comissao_mostrar"> </span>%</h4>
				<button id="btn-fechar-excluir" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			
				<div class="modal-body">			
					


					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						<div class="col-md-5">							
							<span><b>Subtítulo: </b></span>
							<span id="desc_rapida_mostrar"></span>							
						</div>
						<div class="col-md-2">							
							<span><b>Valor: </b></span>
							<span id="valor_mostrar"></span>
						</div>
						<div class="col-md-5">							
							<span><b>Professor: </b></span>
							<span id="professor_mostrar"></span>
						</div>
						
					</div>


					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						<div class="col-md-3">							
							<span><b>Categoria: </b></span>
							<span id="categoria_mostrar"></span>
						</div>
						<div class="col-md-4">							
							<span><b>Grupo/Turma: </b></span>
							<span id="grupo_mostrar"></span>
						</div>
						<div class="col-md-3">							
							<span><b>Carga: </b></span>
							<span id="carga_mostrar"></span> Horas
						</div>
						<div class="col-md-2">							
							<span><b>Ano: </b></span>
							<span id="ano_mostrar"></span>
						</div>
					</div>

					
					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						<div class="col-md-12">			
							<span><b>Palavras Chaves: </b></span>
							<span id="palavras_mostrar"></span>
						</div>
					</div>
					
					


					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						<div class="col-md-9">							
							<span><b>Pacote: </b></span>
							<a target="_blank" href="" id="linkpacote"> <span id="pacote_mostrar"></span> </a>							
						</div>
						<div class="col-md-3">							
							<span><b>Sistema: </b></span>
							<span id="sistema_mostrar"></span>
						</div>
						
						
					</div>


					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						
						
						<div class="col-md-9">							
							<span><b>Técnologias: </b></span>
							<span id="tecnologias_mostrar"></span>
						</div>


						<div class="col-md-3">							
							<span><b>Valor Promoção: </b></span>
							<span id="promocao_mostrar"></span>
						</div>
						
					</div>


					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						<div class="col-md-12">							
							<span><b>Link Arquivos (Material Apoio Drive): </b></span>
							<a target="_blank" href="" id="linkarquivo"><small> <span id="arquivo_mostrar"></span></small> </a>							
						</div>			
						
						
					</div>


					<div class="row" style="border-bottom: 1px solid #cac7c7;">
						
						<div class="col-md-12">							
							<span><b>Link do Curso (Drive): </b></span>
							<a target="_blank" href="" id="linkcurso"> <small><span id="link_mostrar"></span></small> </a>							
						</div>
						
						
					</div>
					
					

					<div class="row">
						<div class="col-md-8">							
							<span><b>Descrição do Curso: </b></span>
							<small><span id="desc_longa_mostrar"></span></small> </a>							
						</div>

						<div class="col-md-4" align="center">		
							<img  width="200px" id="target_mostrar">	
						</div>
					</div>
					
								

				</div>


		</div>
	</div>
</div>






<!-- ModalMensagem -->
<div class="modal fade" id="modalMensagem" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_mensagem"> </span></h4>
				<button id="btn-fechar-mensagem" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				<form id="form-mensagem">
				<div class="modal-body">

					<div class="form-group"> 
								<label>Mensagem do Curso</label> 
								<textarea class="textarea" name="mensagem" id="mensagem_mensagem" ></textarea>
								
							</div>			
								
					<input type="hidden" name="id" id="id_mensagem"> 
					<small><div id="mensagem_msg" align="center" class="mt-3"></div></small>




				</div>

				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Salvar</button>
				</div>

				</form>


		</div>
	</div>
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
					<div class="col-md-6">
						<form id="form-aulas">

						<div class="row">
						<div class="col-md-3">
						<div class="form-group"> 
								<label>Num Aula</label> 
								<input type="number" name="num_aula" id="num_aula" class="form-control" required>
								
							</div>
						</div>

						<div class="col-md-9">
						<div class="form-group"> 
								<label>Nome Aula</label> 
								<input type="text" name="nome_aula" id="nome_aula" class="form-control" required>
								
							</div>
						</div>

						
						<div class="col-md-12">
						<div class="form-group"> 
								<label>Link <small>(Url Incorporada)</small></label> 
								<input onkeyup="carregarVideo();" type="text" name="link_aula" id="link_aula" class="form-control">
								
							</div>
						</div>



					
						<div class="col-md-9">
						<label>Módulo ou Sessão</label> 
						<div id="listar-sessao-aula">
						
							</div>
						</div>

						<div class="col-md-12">						
							<div class="form-group"> 
								<label>arquivo</label> 
								<input class="form-control" type="file" name="arquivo_2" onChange="carregarImg2();" id="arquivo_2">
							</div>

							<div id="divImg">
								<img src="img/arquivos/sem-arquivo.png"  width="130px" id="target_2">									
							</div>						
						</div>

						

						<div class="col-md-3">
							<button type="submit" class="btn btn-primary" style="margin-top:21px">Salvar</button>
						</div>


						<div class="col-md-12" style="margin-top:15px">
							 <iframe width="100%" height="200" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target-video"></iframe>
						</div>

					</div>


					<input type="hidden" name="id" id="id-aulas"> 
						<input type="hidden" name="id_aula" id="id-da-aula">

						
						<small><small><div id="mensagem_aulas" align="center" class="mt-3 col-md-12"></div></small></small> 

						</form>

						

					</div>

					

					<div class="col-md-6">
						<div id="listar-aulas"></div>
					</div>

				</div>

					
					</div>	



							
				

		</div>
	</div>
</div>





<!-- Modal Sessao -->
<div class="modal fade" id="modalSessao" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_curso_sessao"> </span> </h4>
				<button id="btn-fechar-sessao" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				
				<div class="modal-body">
					

					
					<form id="form-sessao">
					<div class="row">
						

						<div class="col-md-9"> 
								<label>Nome Sessão</label> 
								<input type="text" name="nome_sessao" id="nome_sessao" class="form-control" required>
								
							</div>
						

						<div class="col-md-3">
							<button type="submit" class="btn btn-primary" style="margin-top: 20px">Salvar</button>
						</div>
						<input type="hidden" name="id" id="id-curso-sessao"> 
					
					</div>
					</form>	

							
						<br>
						<small><small><div id="mensagem_sessao" align="center" class="mt-3 col-md-12"></div></small></small> 

				
						<br>


				
						<div id="listar-sessao"></div>
					</div>

					</div>
							
				

		</div>
	</div>
</div>


<!-- Modal Arquivos -->
	<div class="modal fade" id="modalArquivos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="tituloModal">Gestão de Arquivos - <span id="nome_arquivo"> </span></h4>
					<button id="btn-fechar-arquivos" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form id="form-arquivos" method="post">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-8">						
								<div class="form-group"> 
									<label>Arquivo</label> 
									<input class="form-control" type="file" name="arquivo_conta" onChange="carregarImgArquivos();" id="arquivo_conta">
								</div>	
							</div>
							<div class="col-md-4" style="margin-top:-10px">	
								<div id="divImgArquivos">
									<img src="images/arquivos/sem-foto.png"  width="60px" id="target-arquivos">									
								</div>					
							</div>




						</div>

						<div class="row" style="margin-top:-40px">
							<div class="col-md-8">
								<input type="text" class="form-control" name="nome_arq"  id="nome_arq" placeholder="Nome do Arquivo * " required>
							</div>

							<div class="col-md-4">										 
								<button type="submit" class="btn btn-primary">Inserir</button>
							</div>
						</div>

						<hr>

						<small><div id="listar_arquivos"></div></small>

						<br>
						<small><div align="center" id="mensagem_arquivo"></div></small>

						<input type="hidden" class="form-control" name="id_arquivo"  id="id_arquivo">


					</div>
				</form>
			</div>
		</div>
</div>




<!-- ModalEmail -->
<div class="modal fade" id="modalEmail" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_email"> </span></h4>
				<button id="btn-fechar-email" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				
				<div class="modal-body" id="listar-emails">

					
				</div>			


		</div>
	</div>
</div>





<!-- Modal Quest -->
<div class="modal fade" id="modalQuest" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" style="overflow: scroll; height:100%; scrollbar-width: thin;">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_curso_quest"> </span> </h4>
				<button id="btn-fechar-quest" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				
				<div class="modal-body">
					

					<div class="row">
					<form id="form-quest">
					<div class="col-md-9">					

						<div class="form-group"> 
								 
								<input maxlength="600" type="text" name="pergunta" id="pergunta_quest" class="form-control" placeholder="Nova Pergunta" required>
								
							</div>
						
						</div>	
						<div class="col-md-3">
							<button type="submit" class="btn btn-primary" style="">Salvar</button>
						</div>
					

						<input type="hidden" name="id" id="id-curso-quest"> 
					</form>		
					

					</div>

						<br>
						<small><small><div id="mensagem_quest" align="center" class="mt-3 col-md-12"></div></small></small> 

					</div>	

					<hr>

					<div class="row">
					<div class="col-md-12">
						<div id="listar-quest"></div>
					</div>
				</div>

				

					
					
				

		</div>
	</div>
</div>







<!-- Modal Alt -->
<div class="modal fade" id="modalAlt" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" style="overflow: scroll; height:100%; scrollbar-width: thin;">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><span id="nome_curso_alt"> </span> </h4>
				<button id="btn-fechar-alt" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
				
				<div class="modal-body">
					

					<div class="row">
					<form id="form-alt">
					<div class="col-md-8">					

						<div class="form-group"> 
								 
								<input maxlength="500" type="text" name="alternativa" id="alternativa" class="form-control" placeholder="Alternativa" required>
								
							</div>
						
						</div>	

						<div class="col-md-1">	
						<div class="form-group"> 								 
							 <input type="checkbox" class="form-check-input" id="correta" name="correta" value="Sim">
						</div>						
						</div>	

						<div class="col-md-3">
							<button type="submit" class="btn btn-primary" style="">Salvar</button>
						</div>
					

						<input type="hidden" name="id" id="id-alt"> 
						<input type="hidden" name="id_curso" id="id-curso-alt"> 
					</form>		
						

					</div>

					<br>
						<small><small><div id="mensagem_alt" align="center" class="mt-3 col-md-12"></div></small></small> 

					</div>	

					<hr>

					<div class="row">
					<div class="col-md-12">
						<div id="listar-alt"></div>
					</div>
				</div>

				

					
					
				

		</div>
	</div>
</div>




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
	function carregarImg() {

		var target = document.getElementById('target');
		var file = document.querySelector("#foto").files[0];

		var reader = new FileReader();

		reader.onloadend = function () {
			target.src = reader.result;
		};

		if (file) {
			reader.readAsDataURL(file);

		} else {
			target.src = "";
		}
	}
</script>

<script type="text/javascript">
		function carregarImg2() {
		
			var target = document.getElementById('target_2');
			var file = document.querySelector("#arquivo_4").files[0];

			var arquivo = file['name'];
			resultado = arquivo.split(".", 2);


			if(resultado[1] === 'pdf'){
				$('#target_2').attr('src', "img/arquivos/pdf.PNG");
				
			}
		}
		</script>




<script type="text/javascript">
	
$("#form-niceEdit").submit(function () {	
	event.preventDefault();
	nicEditors.findEditor('area').saveContent();
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/inserir.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                    $('#btn-fechar').click();
                    listar();
                } else {
                	$('#mensagem').addClass('text-danger')
                    $('#mensagem').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>



<script type="text/javascript">
	$("#form-arq").submit(function () {

		$("#form-arq").val()
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/inserir-arquivo.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				
				
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



function excluir_arq(id_arq){

    $.ajax({
        url: 'paginas/' + pag + "/excluir-arquivo.php",
        method: 'POST',
        data: {id_arq},
        dataType: "text",

        success: function (mensagem) {
                 
            if (mensagem.trim() == "Excluído com Sucesso") {                
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
	
$("#form-mensagem").submit(function () {	
	event.preventDefault();
	nicEditors.findEditor('mensagem_mensagem').saveContent();
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/mensagem.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem_msg').text('');
            $('#mensagem_msg').removeClass()
            if (mensagem.trim() == "Alterado com Sucesso") {                    
                    $('#btn-fechar-mensagem').click();
                    listar();
                } else {
                	$('#mensagem_msg').addClass('text-danger')
                    $('#mensagem_msg').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>



<script type="text/javascript">
	function listarAulas(){
	var id_curso = $('#id-aulas').val();
	var sessao_sel = $('#sessao_aula').val();
	
    $.ajax({
        url: 'paginas/' + pag + "/listar-aulas.php",
        method: 'POST',
        data: {id_curso, sessao_sel},
        dataType: "html",

        success:function(result){
            $("#listar-aulas").html(result);
            $('#mensagem-excluir-aulas').text('');
            $('#mensagem_aulas').text('')
            limparCamposAulas();
        }
    });
}
</script>





<script type="text/javascript">
	
$("#form-aulas").submit(function () {	
	event.preventDefault();	
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/inserir-aulas.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem_aulas').text('');
            $('#mensagem_aulas').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                   // $('#btn-fechar').click();
                    listarAulas();
                } else {
                	$('#mensagem_aulas').addClass('text-danger')
                    $('#mensagem_aulas').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>





<script type="text/javascript">
	function listarSessao(){

	var id_curso = $('#id-curso-sessao').val();
    $.ajax({
        url: 'paginas/' + pag + "/listar-sessao.php",
        method: 'POST',
        data: {id_curso},
        dataType: "html",

        success:function(result){
            $("#listar-sessao").html(result);
            $('#mensagem-excluir-sessao').text('');
            $('#mensagem_sessao').text('')            
        }
    });
}
</script>







<script type="text/javascript">
	
$("#form-sessao").submit(function () {	
	event.preventDefault();	
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/inserir-sessao.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem_sessao').text('');
            $('#mensagem_sessao').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                   // $('#btn-fechar').click();
                    $('#nome_sessao').val('')
                    listarSessao();
                } else {
                	$('#mensagem_sessao').addClass('text-danger')
                    $('#mensagem_sessao').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>


<script type="text/javascript">
	function listarEmails(id){	
	
    $.ajax({
        url: 'paginas/' + pag + "/listar-emails.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
            $("#listar-emails").html(result);           
            
        }
    });
}
</script>




<script type="text/javascript">
	
$("#form-quest").submit(function () {	
	var curso = $('#id-curso-quest').val();
	event.preventDefault();	
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/inserir-quest.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem_quest').text('');
            $('#mensagem_quest').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                   // $('#btn-fechar').click();
                    $('#pergunta_quest').val('')
                    listarPerguntasQuest(curso);
                } else {
                	$('#mensagem_quest').addClass('text-danger')
                    $('#mensagem_quest').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>





<script type="text/javascript">
	function listarPerguntasQuest(id){	
    $.ajax({
        url: 'paginas/' + pag + "/listar-quest.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
            $("#listar-quest").html(result);
            $('#mensagem-excluir-quest').text('');
            $('#mensagem_quest').text('')            
        }
    });
}
</script>





<script type="text/javascript">
	
$("#form-alt").submit(function () {	
	var curso = $('#id-curso-alt').val();
	var pergunta = $('#id-alt').val();
	event.preventDefault();	
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/inserir-alt.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem_alt').text('');
            $('#mensagem_alt').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                   // $('#btn-fechar').click();
                    $('#alternativa').val('')
                    $('#correta').prop('checked', false);
                    $('#mensagem_alt').text('')
                   
                    listarAlt(pergunta);
                } else {
                	$('#mensagem_alt').addClass('text-danger')
                    $('#mensagem_alt').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


</script>





<script type="text/javascript">
	function listarAlt(id){	
    $.ajax({
        url: 'paginas/' + pag + "/listar-alt.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
            $("#listar-alt").html(result);
            $('#mensagem-excluir-alt').text('');
            $('#mensagem_alt').text('')            
        }
    });
}
</script>



<script type="text/javascript">
	function carregarVideo() {		
		$('#target-video').attr('src', $('#link_aula').val());
	}
</script>

<script type="text/javascript">
	
	function arquivo(id, nome){		    	
    	

    	
    	$('#modalArquivos').modal('show');
	}
</script>


<script src="//js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
<script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script>
				