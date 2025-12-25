<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'comissoes';

if(@$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Tesoureiro'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}


$data_hoje = date('Y-m-d');
$data_ontem = date('Y-m-d', strtotime("-1 days",strtotime($data_hoje)));

$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_inicio_mes = $ano_atual."-".$mes_atual."-01";

if($mes_atual == '4' || $mes_atual == '6' || $mes_atual == '9' || $mes_atual == '11'){
	$dia_final_mes = '30';
}else if($mes_atual == '2'){
	$dia_final_mes = '28';
}else{
	$dia_final_mes = '31';
}

$data_final_mes = $ano_atual."-".$mes_atual."-".$dia_final_mes;
$data_inicio_mes = $ano_atual."-".$mes_atual."-01";
 ?>



 <div class="bs-example widget-shadow" style="padding:15px;" >

 	<div class="row">

	<div class="col-md-5" style="margin-bottom:5px;">			

		<div style="float:left; margin-right:10px"><span><small><i title="Data de Vencimento Inicial" class="fa fa-calendar-o"></i></small></span></div>
		<div  style="float:left; margin-right:20px">
			<input type="date" class="form-control " name="data-inicial"  id="data-inicial-caixa" value="<?php echo $data_inicio_mes ?>" required>
		</div>

		<div style="float:left; margin-right:10px"><span><small><i title="Data de Vencimento Final" class="fa fa-calendar-o"></i></small></span></div>
		<div  style="float:left; margin-right:30px">
			<input type="date" class="form-control " name="data-final"  id="data-final-caixa" value="<?php echo $data_final_mes ?>" required>
		</div>
	</div>


		
<div class="col-md-2" style="margin-top:5px;" align="center">	
		<div > 
		<small >
			<a title="Conta de Ontem" class="text-muted" href="#" onclick="valorData('<?php echo $data_ontem ?>', '<?php echo $data_ontem ?>')"><span>Ontem</span></a> / 
			<a title="Conta de Hoje" class="text-muted" href="#" onclick="valorData('<?php echo $data_hoje ?>', '<?php echo $data_hoje ?>')"><span>Hoje</span></a> / 
			<a title="Conta do Mês" class="text-muted" href="#" onclick="valorData('<?php echo $data_inicio_mes ?>', '<?php echo $data_final_mes ?>')"><span>Mês</span></a>
		</small>
		</div>
	</div>


<div class="col-md-3" style="margin-top:5px;" align="center">	
		<div > 
		<div class="form-group"> 								
								<select class="form-control sel2" name="professor" id="professor" required style="width:100%;">
								<option value="">Selecione um Funcionario</option> 
									<?php 
								$query = $pdo->query("SELECT * FROM usuarios where nivel = 'Professor' or nivel = 'Administrador' or nivel = 'Secretario' or nivel = 'Parceiro' or nivel = 'Tesoureiro' or nivel = 'Assessor' or nivel = 'Vendedor' or nivel = 'Tutor' order by nome asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){}

											?>	
										<option value="<?php echo $res[$i]['id'] ?>">(<?php echo $res[$i]['nivel'] ?>) <?php echo $res[$i]['nome'] ?></option>

									<?php } ?>

								</select>
							</div>		
		</div>
	</div>

</div>

<hr>

	<div id="listar">

	</div>
</div>




<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	function valorData(dataInicio, dataFinal){
	 $('#data-inicial-caixa').val(dataInicio);
	 $('#data-final-caixa').val(dataFinal);	
	listar();
}
</script>

<script type="text/javascript">
	function listar(){		
	
	var dataInicial = $('#data-inicial-caixa').val();
	var dataFinal = $('#data-final-caixa').val();	
	var professor = $('#professor').val();	

    $.ajax({

       url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: {dataInicial, dataFinal, professor},
        dataType: "html",

        success:function(result){
            $("#listar").html(result);
        }
    });
}
</script>

<script type="text/javascript">
	$('#data-inicial-caixa').change(function(){
			$('#tipo-busca').val('');
			listar();
		});

		$('#data-final-caixa').change(function(){						
			$('#tipo-busca').val('');
			listar();
		});	


		$('#professor').change(function(){						
			$('#tipo-busca').val('');
			listar();
		});	
</script>




		<script type="text/javascript">
			function carregarImg() {
				var target = document.getElementById('target');
				var file = document.querySelector("#arquivo").files[0];

				var arquivo = file['name'];
				resultado = arquivo.split(".", 2);

				if(resultado[1] === 'pdf'){
					$('#target').attr('src', "img/pdf.png");
					return;
				}

				if(resultado[1] === 'rar' || resultado[1] === 'zip'){
					$('#target').attr('src', "img/rar.png");
					return;
				}


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
	function baixar(id){
    $.ajax({
        url: 'paginas/' + pag + "/baixar.php",
        method: 'POST',
        data: {id},
        dataType: "text",

        success: function (mensagem) {            
            if (mensagem.trim() == "Baixado com Sucesso") {                
                listar();                
            } else {
                    $('#mensagem-excluir').addClass('text-danger')
                    $('#mensagem-excluir').text(mensagem)
                }

        },      

    });
}

</script>

<script type="text/javascript">
	$(document).ready(function() {
		$('.sel2').select2({
				
		});
});
</script>