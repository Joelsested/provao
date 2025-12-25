<?php 
require_once("../../../conexao.php");

$id_curso = $_POST['curso'];

$query = $pdo->prepare("SELECT * FROM perguntas_quest where curso = :curso ORDER BY id asc");
$query->execute([':curso' => $id_curso]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	for($i=0; $i < $total_reg; $i++){
		$id = $res[$i]['id'];		
		$pergunta = $res[$i]['pergunta'];
		$numeracao = $i + 1;
		echo '<p style="margin-bottom:5px"><b>'.$numeracao.' - '.$pergunta.'?</b></p><div style="margin-bottom:15px" ><small>';

		$query2 = $pdo->prepare("SELECT * FROM alternativas where pergunta = :pergunta ORDER BY id asc");
		$query2->execute([':pergunta' => $id]);
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$total_reg2 = @count($res2);
		if($total_reg2 > 0){			
			for($i2=0; $i2 < $total_reg2; $i2++){
				$id_alt = $res2[$i2]['id'];
				$resposta = $res2[$i2]['resposta'];
				$correta = $res2[$i2]['correta'];
				$letras = $i2 + 1;
				

				if($letras == 1){
					$letras = 'A';
				}else if($letras == 2){
					$letras = 'B';
				}else if($letras == 3){
					$letras = 'C';
				}else if($letras == 4){
					$letras = 'D';
				}else if($letras == 5){
					$letras = 'E';
				}else if($letras == 6){
					$letras = 'F';
				}else if($letras == 7){
					$letras = 'G';
				}

				echo '<div class="form-check">
  <input class="form-check-input" type="radio" name="'.$id.'" value="'.$id_alt.'" >
  <label class="form-check-label" style="font-weight:200">
    '.$letras.' - '.$resposta.'
  </label>
</div>';
			}

			echo '</small></div>';
		
		}
	}
}


?>
