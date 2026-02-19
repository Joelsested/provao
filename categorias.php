<?php 
require_once("cabecalho.php");

?>



<br>
<hr>


<?php 
$query = $pdo->query("SELECT * FROM categorias ORDER BY id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
   ?>


    <div class="section-heading text-center">
                <div class="col-md-12 col-xs-12">
                <h1><small><span>Categorias</span></small></h1>
             
            </div>
        </div>

        <hr>

    
                

    <section id="portfolio">
<div class="row" style="margin-left:5px; margin-right:5px; margin-top:-80px;">

                 <?php 
                    for($i=0; $i < $total_reg; $i++){
    foreach ($res[$i] as $key => $value){}
    $id = $res[$i]['id'];
    $nome = $res[$i]['nome'];
    $desc_rapida = $res[$i]['descricao'];
    $desc_rapida = mb_strimwidth($desc_rapida, 0, 18, "..."); 
    $foto = $res[$i]['imagem']; 
    $url = $res[$i]['nome_url'];     

    $query2 = $pdo->prepare("SELECT * FROM cursos where categoria = :categoria");
    $query2->execute([':categoria' => $id]);
    $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
    $cursos = @count($res2);
    

                  ?>


               <a href="categoria-<?php echo $url ?>" title="Ver cursos da Categoria">
    <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 portfolio-item">
                    <div class="portfolio-one">
                        <div class="portfolio-head">
                            <div class="portfolio-img"><img alt="" src="sistema/painel-admin/img/categorias/<?php echo $foto ?>"></div>
                           
                        </div>
                        <!-- End portfolio-head -->
                        
                        <div class="portfolio-content" style="text-align: center">
                            <h6 class="title" style="font-size: 13px"><?php echo $nome ?></h6>
                            <p style="margin-top:-10px;"><?php echo $desc_rapida ?></p>

                               
                
                    <div class="product-bottom-details {$ativo2}">
                        <div class="product-price-menor"><?php echo $cursos ?> Cursos</div>
                    </div>
              

             </div>
                       
                        <!-- End portfolio-content -->
                    </div>
                    <!-- End portfolio-item -->
                </div>
                 </a>
                

           <?php } ?>

               

               </div>
           </section>

          

        <?php } ?>



<?php 
require_once("rodape.php");
?>

