<?php 
require_once('../conexao.php');
require_once('verificar.php');
$pag = 'matriculas_aprovadas';


if(@$_SESSION['nivel'] != 'Administrador'  and @$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Tesoureiro'){
	echo "<script>window.location='../index.php'</script>";
	exit();
}


$data_hoje = date('Y-m-d');
$data_ontem = date('Y-m-d', strtotime("-1 days",strtotime($data_hoje)));

$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_mes = $ano_atual."-".$mes_atual."-01";
 ?>



 <div class="bs-example widget-shadow" style="padding:15px; margin-top:-10px" >

 	<div class="row">

	<div class="col-md-5" style="margin-bottom:5px;">			

		<div style="float:left; margin-right:10px"><span><small><i title="Data da Venda Inicial" class="fa fa-calendar-o"></i></small></span></div>
		<div  style="float:left; margin-right:20px">
			<input type="date" class="form-control " name="data-inicial"  id="data-inicial-caixa" value="<?php echo date('Y-m-d') ?>" required>
		</div>

		<div style="float:left; margin-right:10px"><span><small><i title="Data da Venda Final" class="fa fa-calendar-o"></i></small></span></div>
		<div  style="float:left; margin-right:30px">
			<input type="date" class="form-control " name="data-final"  id="data-final-caixa" value="<?php echo date('Y-m-d') ?>" required>
		</div>
	</div>

		
<div class="col-md-2" style="margin-top:5px;" align="center">	
		<div > 
		<small >
			<a title="Vendas de Ontem" class="text-muted" href="#" onclick="valorData('<?php echo $data_ontem ?>', '<?php echo $data_ontem ?>')"><span>Ontem</span></a> / 
			<a title="Vendas de Hoje" class="text-muted" href="#" onclick="valorData('<?php echo $data_hoje ?>', '<?php echo $data_hoje ?>')"><span>Hoje</span></a> / 
			<a title="Vendas do Mês" class="text-muted" href="#" onclick="valorData('<?php echo $data_mes ?>', '<?php echo $data_hoje ?>')"><span>Mês</span></a>
		</small>
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

    $.ajax({

       url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: {dataInicial, dataFinal},
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
</script>