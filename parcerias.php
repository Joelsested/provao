<?php 
require_once("cabecalho.php");

$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$cursos = @count($res);

?>



<br>
<hr>


  <section id="about-page-section-3">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-7 text-align">
                    <div class="section-heading">
                        <h2>Parcerias <span>de Cursos</span></h2>
                        <p class="subheading">Fazemos parcerias com algumas escolas de cursos!</p>
                    </div>
                    <p>
                       Trabalhamos atualmente apenas com contrato de locação de direitos de revenda dos cursos, é um valor fixo mensal com base na quantidade de cursos que desejar trabalhar.


                        <br><br> Não trabalhamos com comissões e nem com nenhum outro tipo de parceria, caso tenha interesse <a href="https://drive.google.com/file/d/1FsmzU4qzOzDxlXjLw352ine1GZCaMda6/view?usp=sharing" target="_blank"><span>clique aqui</span></a> para baixar o modelo de contrato no qual trabalhamos.
OBS: O valor mensal varia de acordo com a quantidade de cursos que o contratante deseja trabalhar, atualmente temo um total de <?php echo $cursos ?> cursos. 


                    </p>
                  
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-5">
                   <a href="https://drive.google.com/file/d/1FsmzU4qzOzDxlXjLw352ine1GZCaMda6/view?usp=sharing" target="_blank"> <img height="" width="auto" src="img/modelo-de-contrato.png" class="attachment-full img-responsive" alt=""></a>
                </div>
            </div>
        </div>
    </section>



<br><br><br><br>
<br>
<hr>

<?php 
require_once("rodape.php");
?>

