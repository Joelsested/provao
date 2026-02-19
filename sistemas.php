<?php 
require_once("cabecalho.php");

?>



<br>
<hr>


<?php 
$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Sim' ORDER BY id desc ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
   ?>


     <div class="row" style="margin-left: 10px; margin-right: 10px">
                <div class="col-md-8 col-xs-6" >
                <p><span>Total <span class="ocultar-mobile">de Sistemas Prontos</span></span> - <?php echo $total_reg ?> Sistemas</p>
             
            </div>

             <div class="col-md-4 col-xs-6">
                  <div class="search-box-pag " style="margin-top: 10px;">
                        <button class="btn-search-pag"><i class="fa fa-search"></i></button>
                        <input onkeyup="listar()" type="text" class="input-search-pag" placeholder="Busque um Sistema..." id="buscar">
                    </div>

                  </div>

        </div>

        <hr>

    <br>
                
      <div id="listar-cursos">    


      </div>



   



       

          

        <?php } ?>



<br><br>

<?php 
require_once("rodape.php");
?>



<script type="text/javascript">
  $(document).ready(function() {
    listar();
} );



function listar(pagina){
  console.log(pagina)

  var busca = $("#buscar").val();
    $.ajax({
        url: "script/ajax-listar-sistemas.php",
        method: 'POST',
        data: {busca, pagina},
        dataType: "html",

        success:function(result){
            $("#listar-cursos").html(result);
           
        }
    });
}
</script>