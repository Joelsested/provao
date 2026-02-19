<?php 
require_once("cabecalho.php");

?>



<br>
<hr>


<?php 
$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' ORDER BY matriculas asc limit 12");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
   ?>


    <div class="section-heading" align="center">
                <div class="col-md-12 col-xs-12">
                <h1><small><small><span>Cursos Mais Vendidos</span> - <a href="lista-cursos">Ver todos os Cursos</a></small></small></h1>
             
            </div>
        </div>

        <br>
        <hr>

    
                

   <section id="portfolio">

         <div class="row" style="margin-left:5px; margin-right:5px; margin-top:-40px;">

             <?php 
               for($i=0; $i < $total_reg; $i++){
                foreach ($res[$i] as $key => $value){}
                    $id = $res[$i]['id'];
                $nome = $res[$i]['nome'];
                $desc_rapida = $res[$i]['desc_rapida'];      
                $valor = $res[$i]['valor'];     
                $foto = $res[$i]['imagem']; 
                $promocao = $res[$i]['promocao'];
                $url = $res[$i]['nome_url'];

               $desc_rapida = mb_strimwidth($desc_rapida, 0, 20, "...");

                    $valorF = number_format($valor, 2, ',', '.');    
                    $promocaoF = number_format($promocao, 2, ',', '.');

                    if($promocao > 0){
                        $ativo = '';
                        $ativo2 = 'ocultar';
                    }else{
                        $ativo = 'ocultar';
                        $ativo2 = '';
                    } 
                


                ?>


                 <a href="curso-de-<?php echo $url ?>" title="Detalhes do Curso">
                        <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 portfolio-item">
                            <div class="portfolio-one">
                                <div class="portfolio-head">
                                    <div class="portfolio-img"><img alt="" src="sistema/painel-admin/img/cursos/<?php echo $foto ?>" ></div>
                                    
                                </div>
                                <!-- End portfolio-head -->
                                
                                <div class="portfolio-content" style="text-align: center">
                                   <h6 class="title" style="font-size: 13px"><?php echo $nome ?></h6>
                                   <p style="margin-top:-10px;"><?php echo $desc_rapida ?></p>


                                   <div class="product-bottom-details <?php echo $ativo ?>">
                                    <div class="product-price-menor"><small><?php echo $valorF ?></small>R$ <?php echo $promocaoF ?></div>
                                </div>
                                
                                <div class="product-bottom-details <?php echo $ativo2 ?>">
                                    <div class="product-price-menor">R$ <?php echo $valorF ?></div>
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










<br>
<hr>


<?php 
$query = $pdo->query("SELECT * FROM pacotes  ORDER BY matriculas asc limit 16");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
   ?>


    <div class="section-heading" align="center">
                <div class="col-md-12 col-xs-12">
                <h1><small><small><span>Pacotes Mais Vendidos</span> - <a href="pacotes">Ver todos os Pacotes</a></small></small></h1>
             
            </div>
        </div>

        <br>
        <hr>

    
                

     <section id="portfolio">

         <div class="row" style="margin-left:5px; margin-right:5px; margin-top:-40px;">

             <?php 
               for($i=0; $i < $total_reg; $i++){
                foreach ($res[$i] as $key => $value){}
                    $id = $res[$i]['id'];
                $nome = $res[$i]['nome'];
                $desc_rapida = $res[$i]['desc_rapida'];      
                $valor = $res[$i]['valor'];     
                $foto = $res[$i]['imagem']; 
                $promocao = $res[$i]['promocao'];
                $primeira_aula = $res[$i]['video'];
                $url = $res[$i]['nome_url'];
                $desc_rapida = mb_strimwidth($desc_rapida, 0, 20, "...");

                $valorF = number_format($valor, 2, ',', '.');    
        $promocaoF = number_format($promocao, 2, ',', '.'); 


        if($promocao > 0){
            $ativo = '';
            $ativo2 = 'ocultar';
        }else{
            $ativo = 'ocultar';
            $ativo2 = '';
        } 


        ?>


        <a href="cursos-do-<?php echo $url ?>" title="Detalhes do Pacote">
            <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 portfolio-item">
                <div class="portfolio-one">
                    <div class="portfolio-head">
                        <div class="portfolio-img"><img alt="" src="sistema/painel-admin/img/pacotes/<?php echo $foto ?>"></div>

                    </div>
                    <!-- End portfolio-head -->

                    <div class="portfolio-content" style="text-align: center">
                        <h6 class="title" style="font-size: 13px"><?php echo $nome ?></h6>
                        <p style="margin-top:-10px;"><?php echo $desc_rapida ?></p>


                        <div class="product-bottom-details <?php echo $ativo ?>">
                            <div class="product-price-menor"><small><?php echo $valorF ?></small>R$ <?php echo $promocaoF ?></div>
                        </div>

                        <div class="product-bottom-details <?php echo $ativo2 ?>">
                            <div class="product-price-menor">R$ <?php echo $valorF ?></div>
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









<br>
<hr>


<?php 
$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' ORDER BY id desc limit 8");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
   ?>


    <div class="section-heading" align="center">
                <div class="col-md-12 col-xs-12">
                <h1><small><small><span>Últimos Lançamentos</span> - <a href="lista-cursos">Ver todos os Cursos</a></small></small></h1>
             
            </div>
        </div>

        <br>
        <hr>

    
                

     
   <section id="portfolio">

          <div class="row" style="margin-left:5px; margin-right:5px; margin-top:-40px;">

             <?php 
               for($i=0; $i < $total_reg; $i++){
                foreach ($res[$i] as $key => $value){}
                    $id = $res[$i]['id'];
                $nome = $res[$i]['nome'];
                $desc_rapida = $res[$i]['desc_rapida'];      
                $valor = $res[$i]['valor'];     
                $foto = $res[$i]['imagem']; 
                $promocao = $res[$i]['promocao'];
                $url = $res[$i]['nome_url'];

                 $desc_rapida = mb_strimwidth($desc_rapida, 0, 20, "...");

                    $valorF = number_format($valor, 2, ',', '.');    
                    $promocaoF = number_format($promocao, 2, ',', '.');

                    if($promocao > 0){
                        $ativo = '';
                        $ativo2 = 'ocultar';
                    }else{
                        $ativo = 'ocultar';
                        $ativo2 = '';
                    } 
                


                ?>


                 <a href="curso-de-<?php echo $url ?>" title="Detalhes do Curso">
                        <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 portfolio-item">
                            <div class="portfolio-one">
                                <div class="portfolio-head">
                                    <div class="portfolio-img"><img alt="" src="sistema/painel-admin/img/cursos/<?php echo $foto ?>" ></div>
                                    
                                </div>
                                <!-- End portfolio-head -->
                                
                                <div class="portfolio-content" style="text-align: center">
                                   <h6 class="title" style="font-size: 13px"><?php echo $nome ?></h6>
                                   <p style="margin-top:-10px;"><?php echo $desc_rapida ?></p>


                                   <div class="product-bottom-details <?php echo $ativo ?>">
                                    <div class="product-price-menor"><small><?php echo $valorF ?></small>R$ <?php echo $promocaoF ?></div>
                                </div>
                                
                                <div class="product-bottom-details <?php echo $ativo2 ?>">
                                    <div class="product-price-menor">R$ <?php echo $valorF ?></div>
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












<br>
<hr>



<?php 
require_once("rodape.php");
?>

