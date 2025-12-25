<?php 
require_once("cabecalho.php");
?>


<br>

<hr>

  <section id="about-page-section-3">

        <div class="container">

            <div class="row">

                <div class="col-xs-12 col-sm-5 col-md-5 col-lg-5 text-align">

                    <div class="section-heading">

                        <h2>Consultar <span>Certificados</span></h2>

                        <p class="subheading">Consulte um certificado para comprovar a legitimidade do mesmo.</p>

                    </div>

                    <p style="margin-top: -15px">
                         <form method="post" action="sistema/rel/rel_certificado.php" target="_blank">
                        <div class="row">
                            <div class="col-md-8">
                       <div class="form-group">
                            <label>Código de Legitimidade do Certificado *</label>
                            <input type="text" name="id_mat" id="id_mat" class="form-control" required="required">
                        </div>
                    </div>
                    <div class="col-md-4" style="margin-top:30px">
                        <div class="form-group">
                            <button id="btn-enviar" type="submit" name="submit" class="btn btn-info ">Gerar</button>
                        </div>
                    </div>
                </div>
                        


                        <br><br> <i class="neutra">O código será apresentado no canto superior direito do certificado, um número dentro de uma marca d'agua, conforme mostrado na imagem ao lado, basta inserir aqui este número ou usar a url <span style="color:blue"><?php echo $url_sistema ?>certificado/codigo.</span></i>



                    </p>

                 

                </div>

                <div class="col-xs-12 col-sm-7 col-md-7 col-lg-7">                 

                   <img src="img/codigo.png" width="100%">
                  

                </div>

            </div>

        </div>

    </section>













<br>

<hr>



<?php 

require_once("rodape.php");

?>



