<?php 
require_once("cabecalho.php");

?>


<?php 
$query = $pdo->query("SELECT * FROM banner_index where ativo = 'Sim' ORDER BY id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
 ?>

 <div id="myCarousel" class="carousel slide" data-interval="6000" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
        <li data-target="#myCarousel" data-slide-to="3"></li>
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner">

        <?php 
        for($i=0; $i < $total_reg; $i++){
            foreach ($res[$i] as $key => $value){}
                $id = $res[$i]['id'];
            $titulo = $res[$i]['titulo'];
            $descricao = $res[$i]['descricao'];
            $link = $res[$i]['link'];   
            $foto = $res[$i]['foto'];
            $ativo = $res[$i]['ativo'];

            $classe_ativo = '';
            if($i == 0){
             $classe_ativo = 'active'; 
         }

         ?>


         <div class="item <?php echo $classe_ativo ?>">
            <div class="fill" style="background-image:url('sistema/painel-admin/img/banners/<?php echo $foto ?>');"></div>
            <div class="carousel-caption slide-up">
                <h1 class="banner_heading"><span><?php echo $titulo ?></span></h1>
                <p class="banner_txt"><?php echo $descricao ?></p>
                <div class="slider_btn">                   
                    <a href="<?php echo $link ?>" type="button" class="btn btn-primary slide">Veja Mais <i class="fa fa-caret-right"></i></a>
                </div>
            </div>
        </div>   

    <?php } ?>     


</div>

<!-- Left and right controls -->

<a class="left carousel-control" href="#myCarousel" role="button" data-slide="prev"> <i class="fa fa-angle-left" aria-hidden="true"></i>
    <span class="sr-only">Previous</span>
</a>
<a class="right carousel-control" href="#myCarousel" role="button" data-slide="next"> <i class="fa fa-angle-right" aria-hidden="true"></i>
    <span class="sr-only">Next</span>
</a>

</div>

<?php } ?>

<hr>

<section id="about">
    <div class="image-holder col-lg-6 col-md-6 col-sm-6 col-xs-12 pull-left">
        <div class="background-imgholder">
            <img src="img/sobre2.jpeg" alt="about" class="img-responsive" style="display:none;" />
        </div>
    </div>

    <div class="container-fluid">

        <div class="col-md-7 col-md-offset-5 col-sm-8 col-sm-offset-2 col-xs-12 text-inner ">
            <div class="text-block">
                <div class="section-heading">
                    <h1>SOBRE <span>NÓS</span></h1>
                    <p class="subheading">A Escola Sested Cursos oferece os melhores benefícios, confira:</p>
                </div>

                <ul class="aboutul">
                    <li> <i class="fa fa-check"></i>Acesso imediato</li>
                    <li> <i class="fa fa-check"></i>Certificado no final do curso</li>
                    <li> <i class="fa fa-check"></i>Suporte direto com Professor</li>
                    <li> <i class="fa fa-check"></i>Curso Presencial e On-line</li>
                    <li> <i class="fa fa-check"></i>Estude onde e quando quiser</li>
                    <li> <i class="fa fa-check"></i>Painel exclusivo do aluno</li>
                </ul>

                <a href="sobre" type="button" class="btn btn-primary slide">Veja Mais  <i class="fa fa-caret-right"></i> </a>


            </div>
        </div>
    </div>
</section>



<?php 
$query = $pdo->query("SELECT * FROM categorias ORDER BY id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
 ?>

 <section id="process">
    <div class="container">
        <div class="section-heading text-center">
            <div class="col-md-12 col-xs-12">
                <h1>Principais <span>Formações</span></h1>
                <p class="subheading">Conheça nossas principais categorias de treinamentos, temos <?php echo $total_reg ?> áreas de atuação! Clique <a href="categorias"><span>aqui</span></a> para ver todas as categorias.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 col-sm-6 block process-block">
                <div class="process-icon-holder">
                    <div class="process-border">
                        <span class="process-icon"><a href="#"><i class="fa fa-globe feature_icon"></i></a></span></div>
                        <div class="clearfix"></div>
                    </div>

                    <div class="process-text-block">
                        <h4><a href="#">Eliminação de Etapas</a></h4>
                        <p>Elimine já  o Ensino Fundamental e Médio</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 block process-block">
                    <div class="process-icon-holder">
                        <div class="process-border">
                            <span class="process-icon"><a href="#"><i class="fa fa-mobile feature_icon"></i></a></span></div>
                            <div class="clearfix"></div>
                        </div>

                        <div class="process-text-block">
                             <h4><a href="#">Profissionalizantes</a></h4>
                            <p>Cursos feito pra voce se qualificar e começar no mercado hoje .</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 block process-block">
                        <div class="process-icon-holder">
                            <div class="process-border">
                                <span class="process-icon"><a href="#"><i class="fa fa-magic feature_icon"></i></a></span></div>
                                <div class="clearfix"></div>
                            </div>

                            <div class="process-text-block">
                                <h4><a href="#">Praticidade</a></h4>
                                <p>Rapido e Pratico valorizando seu tempo.</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 block process-block lastchild">
                            <div class="process-icon-holder">
                                <div class="process-border">
                                    <span class="process-icon"><a href="#"><i class="fa fa-cog feature_icon"></i></a></span></div>
                                    <div class="clearfix"></div>
                                </div>

                                 <div class="process-text-block">
                                     <div class="process-text-block">
                                    <h4><a href="#">Pacotes feitos pra voce brilhar</a></h4>
                                    <p>Nossos Pacotes foram criados pensando em uma forma facil, pratica e objetiva voce brilhar.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>

            <?php } ?>


            <hr>

            <?php 
            $query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' ORDER BY id desc limit 6");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            $total_reg = @count($res);
            if($total_reg > 0){
             ?>


             <div class="section-heading text-center">
                <div class="col-md-12 col-xs-12">
                    <h1>Últimos <span>Lançamentos</span></h1>
                    <p class="subheading">Clique <a href="cursos.php"><span>aqui</span></a> para ver todos os cursos.</p>
                </div>
            </div>



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
$query = $pdo->query("SELECT * FROM pacotes ORDER BY id desc limit 6");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
 ?>


 <div class="section-heading text-center">
    <div class="col-md-12 col-xs-12">
        <h1>Últimos <span>Pacotes</span></h1>
        <p class="subheading">Clique <a href="pacotes.php"><span>aqui</span></a> para ver todos os pacotes.</p>
    </div>
</div>

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


<section id="testimonial">
    <div class="container">
        <div class="section-heading text-center">
            <div class="col-md-12 col-xs-12">
                <h1>Depoimentos <span>Nossos Alunos</span></h1>
                <p class="subheading"></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 col-sm-12 block ">
                <div class="testimonial_box">
                    <p>"Excelentes profissionais dedicados e empenhados á espalhar educação, ensino de qualidade e acessível a todos.Ótimos Professores, Foi muito bom pra mim, indico para meus amigos!!"</p>
                </div>
                <div class="arrow-down"></div>
                <div class="testimonial_user">
                  <div class="user-image"><img src="img/user2.png" alt="user" class="img-responsive" width="55" height="55" style="border-radius: 100%;" /></div>
                  <div class="user-info">
                    <h5></h5>
                    <p>Kleber Anchieta da Cruz</p>
                </div>
            </div>
        </div>


        <div class="col-md-4 col-sm-12 block">
            <div class="testimonial_box">
                <p>"Fui aluna da escola SESTED,  fiz o meu Ensino Médio com eles. Sobre as provas? Não são só uma prova mas sim conhecimento porque a gente com elas aprende. É um lugar que faz a diferença!" </p>
            </div>
            <div class="arrow-down"></div>
            <div class="testimonial_user">
                <div class="user-image"><img src="img/user1.png" alt="user" class="img-responsive" width="55" height="55" style="border-radius: 100%;" /></div>
                <div class="user-info">
                    <h5></h5>
                    <p>Cléria da Costa Nascimento</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 block">
            <div class="testimonial_box">
               <p>"A minha experiência de estudar na Escola SESTED foi muito boa, ótimos profissionais, atendimento diferenciado, suporte de qualidade. Foi muito bom fazer parte da família SESTED!!" </p>
            </div>
            <div class="arrow-down"></div>
            <div class="testimonial_user">
                <div class="user-image"><img src="img/user3.png" alt="user" class="img-responsive" width="55" height="55" style="border-radius: 100%;" /></div>
                <div class="user-info">
                    <h5></h5>
                    <p>Cristiane dos Santos Nunes</p>
                </div>
            </div>
        </div>


    </div>
</div>

</section>







<style type="text/css">
    .alerta{
      background-color: #fa8e14; color:#FFF; padding:15px; font-family: Arial; text-align:center; position:fixed; bottom:0; width:100%; opacity: 80%; z-index: 100;
    }

     .alerta.hide{
       display:none !important;
    }

    .link-alerta{
      color:#f2f2f2; 
    }

    .link-alerta:hover{
      text-decoration: underline;
      color:#FFF;
    }

    .botao-aceitar{
      background-color: #e3e3e3; padding:7px; margin-left: 15px; border-radius: 5px; border: none; margin-top:3px;
    }

    .botao-aceitar:hover{
      background-color: #f7f7f7;
      text-decoration: none;

    }

  </style>

<div class="alerta hide">
  A gente guarda estatísticas de visitas para melhorar sua experiência de navegação, saiba mais em nossa  <a class="link-alerta" title="Ver as políticas de privacidade" href="politica" target="_blank">política de privacidade.</a>
  <a class="botao-aceitar text-dark" href="#">Aceitar</a>
</div>


<script>
        if (!localStorage.meuCookie) {
            document.querySelector(".alerta").classList.remove('hide');
        }

        const acceptCookies = () => {
            document.querySelector(".alerta").classList.add('hide');
            localStorage.setItem("meuCookie", "accept");
        };

        const btnCookies = document.querySelector(".botao-aceitar");

        btnCookies.addEventListener('click', acceptCookies);
    </script>






<?php 
require_once("rodape.php");
?>

