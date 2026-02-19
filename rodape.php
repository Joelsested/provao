<?php 
require_once("sistema/conexao.php");
?>

<?php 
$query = $pdo->query("SELECT * FROM alertas where data > curDate() ORDER BY id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if(@count($res) > 0){
    $classe_link = '';

    $titulo = $res[0]['titulo'];
    $descricao = $res[0]['descricao'];
    $link = $res[0]['link'];
    $video = $res[0]['video'];
    $foto = $res[0]['imagem'];
    $data = $res[0]['data'];
  }else{
    $classe_link = 'hide';
    $titulo = '';
    $descricao = '';
    $link = '';
    $video = '';
    $foto = '';
    $data = '';
  }
  
 ?>
</div>

   <section id="bottom-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12 btm-footer-links ocultar-mobile">
                    <a href="politica">Politica de Privacidade</a>
                    <a href="temos">Termos de Uso</a>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12 copyright">
                   <span class="cor-branca ocultar-mobile"><?php echo $nome_sistema ?> / </span>  
                   <i class="fa fa-envelope" style="color:#FFF; margin-right:5px">  </i><a href="#" style="color:#FFF"><?php echo $email_sistema ?></a> / 

                   <i class="fa fa-whatsapp" style="color:#FFF; margin-right:5px"></i><a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" target="_blank" style="color:#FFF"><?php echo $tel_sistema ?></a>
                </div>
            </div>
        </div>
    </section>

     <?php 
            $url = basename($_SERVER['PHP_SELF'],'.php');

        if($url == "index"){

            ?>

    <div id="panel">
        <div id="panel-admin">
            <div class="panel-admin-box">
                <div id="tootlbar_colors">
                    <button class="color" style="background-color:#1abac8;" onclick="mytheme(0)"></button>
                    <button class="color" style="background-color:#ff8a00;" onclick="mytheme(1)"> </button>
                    <button class="color" style="background-color:#b4de50;" onclick="mytheme(2)"> </button>
                    <button class="color" style="background-color:#e54e53;" onclick="mytheme(3)"> </button>
                    <button class="color" style="background-color:#1abc9c;" onclick="mytheme(4)"> </button>
                    <button class="color" style="background-color:#159eee;" onclick="mytheme(5)"> </button>
                </div>
            </div>

        </div>
       
          <a class="open" href="#"><span><i class="fa fa-gear fa-spin"></i></span></a>
       
        
    </div>

<?php } ?>

</html>




<!-- ModalMostrar -->
<div class="modal fade" id="modalMostrar_rod" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModal_rod"><span class="neutra" id="nome_mostrar_rod"> </span>  </h4>
                <button id="btn-fechar-excluir_rod" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
                    <span class="neutra" aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">            



                <div class="row" style="border-bottom: 1px solid #cac7c7; margin-bottom:5px">
                    <div class="col-md-12"> 
                        <span class="neutra" id="descricao_mostrar_rod"></span>                            
                    </div>                  

                </div>

                <?php if($link != ""){ ?>
                <div class="row" style="border-bottom: 1px solid #cac7c7; margin-bottom:5px">
                    <div class="col-md-12">                          
                        <span class="neutra"><a id="link_mostrar_rod"><i>Clique aqui</i></a> para comprar ou ver mais detalhes sobre nossa promoção!!</span>
                       
                    </div>                  
                
                </div>
             <?php } ?>


                
                <div class="row" style="margin-top:10px">
                    <?php if($foto != "sem-foto.png" and $foto != ""){ ?>
                    <div class="col-md-6" align="center" style="margin-top:5px">       
                        <img  width="100%" id="target_mostrar_rod"> 
                    </div>
                <?php } ?>

                <?php if($video != ""){ ?>
                    <div class="col-md-6" align="center" style="margin-top:10px">       
                        <iframe width="100%" height="250" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen id="target_video_mostrar_rod"></iframe>
                    </div>
                <?php } ?>
                </div>



            </div>


        </div>
    </div>
</div>






<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>


<!-- Mascaras JS -->
<script type="text/javascript" src="sistema/js/mascaras.js"></script>
        <!-- Ajax para funcionar Mascaras JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script> 




<style type="text/css">
    .alerta{
      background-color: #fa8e14; color:#FFF; padding:15px; font-family: Arial; text-align:center; position:fixed; bottom:0; width:350px; opacity: 80%; z-index: 100;
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

<div class="alerta <?php echo $classe_link ?>">
  <?php echo $titulo ?>
  <a class="botao-aceitar text-dark" href="#" onclick="mostrarAlerta('<?php echo $titulo ?>', '<?php echo $descricao ?>','<?php echo $link ?>','<?php echo $foto ?>','<?php echo $video ?>')" title="Clique para ver mais detalhes">Veja Mais</a>
</div>


<script type="text/javascript">
        function mostrarAlerta(titulo, descricao, link, foto, video){   


        $('#nome_mostrar_rod').text(titulo);
        $('#descricao_mostrar_rod').html(descricao);
        $('#link_mostrar_rod').attr('href', link); 
                     
                
        $('#target_mostrar_rod').attr('src','sistema/painel-admin/img/alertas/' + foto);
        $('#target_video_mostrar_rod').attr('src', video);      

        $('#modalMostrar_rod').modal('show');
        
    }
</script>