<?php 
require_once("../sistema/conexao.php");

$busca = '%'.$_POST['busca'].'%';

if($busca == '%%'){
  exit();
}


echo <<<HTML
<br>
<hr>
<section id="portfolio">
<div class="row" style="margin-left:5px; margin-right:5px; margin-top:-80px;">
HTML;


 $query = $pdo->prepare("SELECT * FROM pacotes where nome LIKE :busca or desc_rapida LIKE :busca ORDER BY nome asc");
$query->execute([':busca' => $busca]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){

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


    

echo <<<HTML
<a href="cursos-do-{$url}" title="Detalhes do Pacote">
    <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 portfolio-item">
                    <div class="portfolio-one">
                        <div class="portfolio-head" style="height:170px">
                            <div class="portfolio-img"><img alt="" src="sistema/painel-admin/img/pacotes/{$foto}"></div>
                           
                        </div>
                        <!-- End portfolio-head -->
                        
                        <div class="portfolio-content" style="text-align: center">
                            <h6 class="title" style="font-size: 13px">{$nome}</h6>
                            <p style="margin-top:-10px;">{$desc_rapida}</p>

                
                    <div class="product-bottom-details {$ativo}">
                        <div class="product-price-menor"><small>{$valorF}</small>R$ {$promocaoF}</div>
                    </div>
                
                    <div class="product-bottom-details {$ativo2}">
                        <div class="product-price-menor">R$ {$valorF}</div>
                    </div>
              

             </div>
                       
                        <!-- End portfolio-content -->
                    </div>
                    <!-- End portfolio-item -->
                </div>
                 </a>
                
HTML;


}


}


 $query = $pdo->prepare("SELECT * FROM cursos where status = 'Aprovado' and sistema = 'Não' and (nome LIKE :busca or desc_rapida LIKE :busca) ORDER BY nome asc");
$query->execute([':busca' => $busca]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){


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


    $query2 = $pdo->prepare("SELECT * FROM aulas where curso = :curso and num_aula = 1 order by id asc");
$query2->execute([':curso' => $id]);
                  $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
                  $total_reg2 = @count($res2);
                  if($total_reg2 > 0){
                    $primeira_aula = $res2[0]['link'];
                  }else{
                    $primeira_aula = "";
                  }    
    

echo <<<HTML
<a href="curso-de-{$url}" title="Detalhes do Curso">
    <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 portfolio-item">
                    <div class="portfolio-one">
                        <div class="portfolio-head" style="height:170px">
                            <div class="portfolio-img"><img alt="" src="sistema/painel-admin/img/cursos/{$foto}" ></div>
                          
                        </div>
                        <!-- End portfolio-head -->
                        
                        <div class="portfolio-content" style="text-align: center">
                             <h6 class="title" style="font-size: 13px">{$nome}</h6>
                            <p style="margin-top:-10px;">{$desc_rapida}</p>

                
                    <div class="product-bottom-details {$ativo}">
                        <div class="product-price-menor"><small>{$valorF}</small>R$ {$promocaoF}</div>
                    </div>
                
                    <div class="product-bottom-details {$ativo2}">
                        <div class="product-price-menor">R$ {$valorF}</div>
                    </div>
              

             </div>
                        
                        <!-- End portfolio-content -->
                    </div>
                    <!-- End portfolio-item -->
                </div>
                </a>
                
HTML;


}

}


echo <<<HTML
</div>
</section>
<br>
<br>
<br>
<br>
<hr>
HTML;      


?>
