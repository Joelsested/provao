<?php 
require_once("../sistema/conexao.php");

$busca = '%'.$_POST['busca'].'%';

// pegar a pagina atual
if(@$_POST['pagina'] == ""){
    @$_POST['pagina'] = 0;
}
$pagina = intval(@$_POST['pagina']);
$limite = $pagina * $itens_pag;



$query = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' and ano = '2021' and (nome LIKE '$busca' or desc_rapida LIKE '$busca') ORDER BY id desc LIMIT $limite, $itens_pag");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){

echo <<<HTML
<section id="portfolio">
 <div class="row" style="margin-left:5px; margin-right:5px; margin-top:-80px;">
HTML;

for($i=0; $i < $total_reg; $i++){
    foreach ($res[$i] as $key => $value){}
        $id = $res[$i]['id'];
    $nome = $res[$i]['nome'];
    $desc_rapida = $res[$i]['desc_rapida'];      
    $valor = $res[$i]['valor'];     
    $foto = $res[$i]['imagem']; 
    $promocao = $res[$i]['promocao'];
     $url = $res[$i]['nome_url'];

    $valorF = number_format($valor, 2, ',', '.');    
    $promocaoF = number_format($promocao, 2, ',', '.');

    if($promocao > 0){
        $ativo = '';
        $ativo2 = 'ocultar';
    }else{
        $ativo = 'ocultar';
         $ativo2 = '';
    } 


    $query2 = $pdo->query("SELECT * FROM aulas where curso = '$id' and num_aula = 1 order by id asc");
                  $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
                  $total_reg2 = @count($res2);
                  if($total_reg2 > 0){
                    $primeira_aula = $res2[0]['link'];
                  }else{
                    $primeira_aula = "";
                  }


    $query2 = $pdo->query("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' and ano = '2021' and (nome LIKE '$busca' or desc_rapida LIKE '$busca') ORDER BY id desc ");
    $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
    $total_reg2 = @count($res2);

     $num_paginas = ceil($total_reg2/$itens_pag);
    

echo <<<HTML
    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3 portfolio-item">
                    <div class="portfolio-one">
                        <div class="portfolio-head">
                            <div class="portfolio-img2"><img alt="" src="sistema/painel-admin/img/cursos/{$foto}"></div>
                            <div class="portfolio-hover">
                               <iframe class="video-card" src="{$primeira_aula}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                <div class="" align="center" style="margin-top:20px; ">                   
                                     <a href="curso-de-{$url}" type="button" class="btn btn-primary2">Veja Mais <i class="fa fa-caret-right"></i></a>
                                 </div>

                            </div>
                        </div>
                        <!-- End portfolio-head -->
                        <a href="curso-de-{$url}" title="Detalhes do Curso">
                        <div class="portfolio-content" style="text-align: center">
                            <h5 class="title">{$nome}</h5>
                            <p style="margin-top:-10px;">{$desc_rapida}</p>

                
                    <div class="product-bottom-details {$ativo}">
                        <div class="product-price"><small>De {$valorF}</small>Por R$ {$promocaoF}</div>
                    </div>
                
                    <div class="product-bottom-details {$ativo2}">
                        <div class="product-price">R$ {$valorF}</div>
                    </div>
              

             </div>
                        </a>
                        <!-- End portfolio-content -->
                    </div>
                    <!-- End portfolio-item -->
                </div>
                
HTML;


}


echo <<<HTML
</div>
</section>
<hr>
   <div class="row" align="center">
     <nav aria-label="Page navigation example">
          <ul class="pagination">
            <li class="page-item">
              <a onclick="listar(0)" class="paginador" href="#" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
                <span class="sr-only">Previous</span>
              </a>
            </li>
HTML;

            for($i=0;$i<$num_paginas;$i++){
            $estilo = "";
            if($pagina >= ($i - 2) and $pagina <= ($i + 2)){
            if($pagina == $i)
              $estilo = "active";

          $pag = $i+1;
          $ultimo_reg = $num_paginas - 1;

echo <<<HTML

             <li class="page-item {$estilo}">
              <a onclick="listar({$i})" class="paginador " href="#" >{$pag}
                
              </a></li>
HTML;

          } 
      } 

echo <<<HTML

            <li class="page-item">
              <a onclick="listar({$ultimo_reg})" class="paginador" href="#" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
                <span class="sr-only">Next</span>
              </a>
            </li>
          </ul>
        </nav>
      </div> 

HTML;      

}else{
    echo '<br><p align="center">Não possui nenhum curso com este nome!</p>';
}

?>