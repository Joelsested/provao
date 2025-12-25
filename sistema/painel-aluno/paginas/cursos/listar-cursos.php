<?php
require_once("../../../conexao.php");
$tabela = 'matriculas';

@session_start();
$id_usuario = $_SESSION['id'];

$id_pacote = '%' . @$_POST['id'] . '%';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM {$tabela} WHERE aluno = :aluno AND pacote != 'Sim' AND id_pacote LIKE :id_pacote ORDER BY id desc");
$query->execute(['aluno' => $id_usuario, 'id_pacote' => $id_pacote]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$matriculas = [];

foreach ($res as $matricula) {
    $id_matricula = $matricula['id'];
    $forma_pgto = $matricula['forma_pgto']; // Assumindo que este campo existe

    // Busca os pagamentos baseado na forma de pagamento
    if ($forma_pgto == 'PIX') {
        $stmt = $pdo->prepare("SELECT * FROM pagamentos_pix WHERE id_matricula = :id_matricula");
    } elseif ($forma_pgto == 'BOLETO') {
        $stmt = $pdo->prepare("SELECT * FROM pagamentos_boleto WHERE id_matricula = :id_matricula");
    } else {
        // Se não houver forma de pagamento definida, tenta PIX primeiro
        $stmt = $pdo->prepare("SELECT * FROM pagamentos_pix WHERE id_matricula = :id_matricula");
    }

    $stmt->bindParam(':id_matricula', $id_matricula);
    $stmt->execute();
    $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Se não encontrou em PIX e não era especificamente PIX, tenta BOLETO
    if (empty($pagamentos) && $forma_pgto != 'PIX') {
        $stmt = $pdo->prepare("SELECT * FROM pagamentos_boleto WHERE id_matricula = :id_matricula");
        $stmt->bindParam(':id_matricula', $id_matricula);
        $stmt->execute();
        $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $matricula['pagamentos'] = $pagamentos;
    $matricula['tipo_pagamento'] = !empty($pagamentos) ? (isset($pagamentos[0]['txid']) ? 'PIX' : 'BOLETO') : 'NENHUM';

    array_push($matriculas, $matricula);
}

$total_reg = @count($res);
if ($total_reg > 0) {
    echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Curso</th>
	<th class="esc">Professor</th> 
	<th class="esc">Aulas</th> 
	<th class="esc">Progresso</th> 
	<th class="esc">Valor</th> 	
    <th class="esc">Forma de Pagamento</th> 	
    <th class="esc">Cupom</th> 	
	<th class="esc">Data</th>
	<th class="esc">Status</th> 	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

    for ($i = 0; $i < $total_reg; $i++) {
        foreach ($matriculas[$i] as $key => $value) {
        }
        $id = $res[$i]['id'];
        $curso = $res[$i]['id_curso'];
        $aulas_concluidas = $res[$i]['aulas_concluidas'];
        $valor = $res[$i]['subtotal'];
        $valor_cupom = $res[$i]['valor_cupom'];
        $data = $res[$i]['data'];
        $status = $res[$i]['status'];
        $professor = $res[$i]['professor'];
        $pacote = $res[$i]['pacote'];
        $boleto = $res[$i]['boleto'];
        $nota = $res[$i]['nota'];

        // Verificar se existem pagamentos e definir variáveis com valores padrão
        $qrcode = '';
        $texto_copia_cola = '';
        $valorC = '';
        $data_criacao = '';
        $statusC = '';
        $url_boleto = '';
        $linha_digitavel = '';
        $nosso_numero = '';
        $tipo_pagamento = $matriculas[$i]['tipo_pagamento'];

        if (!empty($matriculas[$i]['pagamentos']) && isset($matriculas[$i]['pagamentos'][0])) {
            $pagamento = $matriculas[$i]['pagamentos'][0];

            if ($tipo_pagamento == 'PIX') {
                $qrcode = $pagamento['qrcode_url'] ?? '';
                $texto_copia_cola = $pagamento['texto_copia_cola'] ?? '';
                $valorC = $pagamento['valor'] ?? '';
                $data_criacao = $pagamento['data_criacao'] ?? '';
                $statusC = $pagamento['status'] ?? '';
            } elseif ($tipo_pagamento == 'BOLETO') {
                $url_boleto = $pagamento['url_boleto'] ?? '';
                $linha_digitavel = $pagamento['linha_digitavel'] ?? '';
                $nosso_numero = $pagamento['nosso_numero'] ?? '';
                $valorC = $pagamento['valor'] ?? '';
                $data_criacao = $pagamento['criado_em'] ?? '';
                $statusC = $pagamento['status'] ?? '';
            }
        }

        if ($boleto != "" and $status == 'Aguardando') {
            //require("../../../../pagamentos/boletos/notificacoes.php");
        }

        if ($pacote == 'Sim') {
            $tab = 'pacotes';
            $link = 'cursos-do-';
        } else {
            $tab = 'cursos';
            $link = 'curso-de-';
        }

        // Remover esta query que estava incorreta
        // $queryPix = $pdo->query("SELECT * FROM pagamentos_pix where id = '$curso'");
        // $resPix = $queryPix->fetchAll(PDO::FETCH_ASSOC);

        $query2 = $pdo->prepare("SELECT * FROM {$tab} WHERE id = :id");
        $query2->execute(['id' => $curso]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        if (@count($res2) > 0) {
            $nome_curso = $res2[0]['nome'];
            $nome_url = $res2[0]['nome_url'];
            $url_do_curso = $link . $nome_url;
            $id_do_curso = $res2[0]['id'];
            $link = $res2[0]['link'];
        } else {
            $nome_curso = "";
        }

        $query2 = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $query2->execute(['id' => $professor]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        if (@count($res2) > 0) {
            $nome_professor = $res2[0]['nome'];
        } else {
            $nome_professor = "";
        }

        $query2 = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso");
        $query2->execute(['curso' => $curso]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        $aulas = @count($res2);

        //verificar se o curso já foi avaliado
        $query2 = $pdo->prepare("SELECT * FROM avaliacoes WHERE curso = :curso AND aluno = :aluno");
        $query2->execute(['curso' => $curso, 'aluno' => $id_usuario]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        $avaliacoes = @count($res2);
        if ($avaliacoes > 0) {
            $ocultar_avaliar = 'ocultar';
        } else {
            $ocultar_avaliar = '';
        }

        $porcentagemAulas = 0;

        if ($aulas_concluidas > 0 && $aulas > 0) {
            $porcentagemAulas = ($aulas_concluidas / $aulas) * 100;
            if ($porcentagemAulas > 100) {
                $porcentagemAulas = 100;
            }
        }

        $porcentagemAulasF = round($porcentagemAulas, 2);

        if ($status == 'Aguardando') {
            $excluir = '';
            $icone = 'fa-square';
            $classe_square = 'text-danger';
            $classe_nome = 'text-muted';
            $ocultar_aulas = 'ocultar';
            $ocultar_pagar = '';
            $classe_progress = '';
            $icones_finalizados = 'ocultar';
        } else if ($status == 'Finalizado') {
            $excluir = 'ocultar';
            $icone = 'fa-square';
            $classe_square = 'azul';
            $classe_nome = 'verde_claro';
            $ocultar_aulas = '';
            $ocultar_pagar = 'ocultar';
            $classe_progress = '#015e23';
            $icones_finalizados = '';
        } else {
            $excluir = 'ocultar';
            $icone = 'fa-square';
            $classe_square = 'verde';
            $classe_nome = 'verde_claro';
            $ocultar_aulas = '';
            $ocultar_pagar = 'ocultar';
            $classe_progress = '';
            $icones_finalizados = 'ocultar';
        }

        if ($nota >= 60) {
            $ocul = '';
        } else {
            $ocul = 'ocultar';
        }

        //FORMATAR VALORES
        $valorF = number_format($valor, 2, ',', '.');
        $dataF = implode('/', array_reverse(explode('-', $data)));

        $classe_quest = 'ocultar';

        //pegar o id da matricula
        $query_m = $pdo->prepare("SELECT * FROM matriculas WHERE id = :id");
        $query_m->execute(['id' => $id]);
        $res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
        $id_mat = $res_m[0]['id'];
        $aulas_conc = $res_m[0]['aulas_concluidas'];
        $status_mat = $res_m[0]['status'];

        //verificar total de aulas do curso
        $query_m = $pdo->prepare("SELECT * FROM aulas WHERE curso = :curso");
        $query_m->execute(['curso' => $curso]);
        $res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
        $total_aulas = @count($res_m);

        if ($questionario_config == 'Sim') {
            //mudo o status do curso para finalizado
            if ($status_mat != 'Finalizado' and $status_mat != 'Aguardando' and $total_aulas == $aulas_conc) {
                $classe_quest = '';
            }
        }

        if ($nota <= $media_config and $nota != "") {
            $classe_nota = '';
        } else {
            $classe_nota = 'ocultar';
        }

        if($valor_cupom > 0){
            $classe_cupom = 'ocultar';
        }else{
            $classe_cupom = '';
        }

        if($valor_cupom > 0){
            $classe_cupom2 = '';
        }else{
            $classe_cupom2 = 'ocultar';
        }


        echo <<<HTML
<tr> 
		<td>		
		<a href="#" onclick="abrirAulas('{$id}', '{$nome_curso}', '{$aulas}', '{$id_do_curso}', '{$link}')" class="{$classe_nome} $ocultar_aulas">	
		{$nome_curso}
		<small><i class="fa fa-video-camera text-dark"></i>	</small>
		</a>

			
		
		
		<form method="post" action="../../{$url_do_curso}" target="_blank" class="hidden {$ocultar_pagar}">
		    <span class="text-muted">{$nome_curso}</span>
            <button  type="submit" style="background-color: transparent;  border:none!important;">
                <i class="fa fa-money text-danger" ></i>
                <span class="text-danger" style="margin-left:2px">Pagar</span>
            </button>
            <input type="hidden" name="painel_aluno" value="sim">
        </form>
        
        <div  class="{$ocultar_pagar}">
         <span class="text-muted">{$nome_curso}</span>
         <button onclick="pagarCurso('{$matriculas[$i]['forma_pgto']}', '{$id_do_curso}', '{$id}', '{$nome_curso}');"  type="submit" style="background-color: transparent;  border:none!important;">
                <i class="fa fa-money text-danger" ></i>
                <span class="text-danger" style="margin-left:2px">Pagar</span>
            </button>
        </div>

		

		</td> 		
		<td class="esc">{$nome_professor}</td>		
		<td class="esc">{$aulas_concluidas} / {$aulas}</td>
		<td class="esc">
			<div class="progress" style="height:15px; ">
  				<div class="progress-bar" role="progressbar" style="width: {$porcentagemAulas}%; background: {$classe_progress}" aria-valuenow="{$porcentagemAulas}" aria-valuemin="0" aria-valuemax="100">{$porcentagemAulasF}%</div>
			</div>
		</td>
		<td class="esc">R$ {$valor} </td>
        <td class="{$ocultar_pagar}">
            <span style="font-size:10px">{$matriculas[$i]['forma_pgto']}</span>
        <button onclick="pagarCurso('AGUARDANDO', '{$id_do_curso}', '{$id}', '{$nome_curso}');"  type="submit" style="background-color: transparent;  border:none!important;">
                <i class="fa fa-money text-danger" ></i>
                <span class="text-danger" style="margin-left:2px">Alterar</span>
            </button>

        </td>
        <td class="{$ocultar_pagar} ">
        
            <button class="{$classe_cupom}" onclick="aplicarCupom('{$id_do_curso}');"  type="submit" style="background-color: transparent;  border:none!important;">
                <i class="fa fa-money text-primary" ></i>
                <span class="text-primary" style="margin-left:2px">Aplicar Cupom</span>
            </button>

            <span class="text-primary {$classe_cupom2}" style="margin-left:2px">{$valor_cupom}</span>

        </td>
		<td class="esc">{$dataF}</td>
		<td class="esc">

		<i class="fa {$icone} $classe_square"></i>


		</td>				
		<td >


		
		
		

		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" onclick="listardoc('{$curso}')" class="dropdown-toggle" title="Arquivos do aluno" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-file-pdf-o text-success"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div  id="listar-docfin_{$curso}">
		
		</div>
		</li>										
		</ul>
		</li>




		



		<form method="post" action="../rel/rel_certificado.php" target="_blank" class="{$icones_finalizados}" style="display: inline-block;">

	<big>
	
		<!-- <a class='{$ocul}' href="https://{$url_sistema}/sistema/rel/avaliacoes_class2.php?id={$id_usuario}&id_curso={$curso}" target="_blank" title="Avaliações do aluno">
		<small><span class="fa fa-file-pdf-o text-danger" ></span></small>
		</a> -->

		<a class='{$ocul}' href="javascript:void(0);" onclick="modalAvaliacao('{$url_sistema}/sistema/rel/avaliacoes_class2.php?id={$id_usuario}&id_curso={$curso}')" title="Avaliações do aluno">
    	<small><span class="fa fa-file-pdf-o text-danger"></span></small>
		</a>
	
	</big>
		

		<big><a class="{$icones_finalizados} {$ocultar_avaliar}" href="#" onclick="avaliar('{$curso}', '{$nome_curso}')" title="Avaliar Curso"><i class="fa fa-star amarelo"></i></a></big>

		

		</form>

		
		<big><a class="{$classe_quest}" href="#" onclick="questionario('{$curso}', '{$nome_curso}', '{$id}')" title="Iniciar Questionário"><i class="fa fa-question-circle-o verde"></i></a></big>

		<small><span class="text-danger {$classe_nota}">Nota: {$nota}%</span></small>


		<li class="dropdown head-dpdn2 {$excluir}" style="display: flex">
		<a href="#" class="dropdown-toggle {$excluir}" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>


		</td>
</tr>
HTML;

    }

    echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>	
HTML;

} else {
    echo 'Não possui nenhum curso matriculado!';
}
echo <<<HTML
</small>
HTML;


?>
<style>
    /* Customização do SweetAlert2 */
    .financial-modal .swal2-popup {
        background: linear-gradient(135deg, #1a2035 0%, #121625 100%);
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(83, 92, 136, 0.3);
        padding: 0;
    }

    .financial-modal .swal2-title {
        color: #000;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        /*padding: 1.5rem 1.5rem 0.5rem;*/
        /*font-size: 1.5rem;*/
        border-bottom: 1px solid rgba(83, 92, 136, 0.2);
        margin: 0;
    }

    .financial-modal .swal2-html-container {
        padding: 0;
        margin: 0;
    }

    .financial-modal .swal2-actions {
        margin: 0;
        padding: 1rem;
        border-top: 1px solid rgba(83, 92, 136, 0.2);
    }

    .financial-modal .swal2-styled.swal2-confirm {
        background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
        border-radius: 8px;
        font-weight: 600;
        padding: 0.75rem 2rem;
        box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
        border: none;
    }

    .financial-modal .swal2-styled.swal2-confirm:hover {
        background: linear-gradient(135deg, #2a6ac4 0%, #00b3ee 100%);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .financial-modal .swal2-icon {
        margin: 1.5rem auto 0.5rem;
    }

    /* Conteúdo do Modal */
    .matricula-card {
        background-color: transparent;
        color: #fff;
        font-family: 'Poppins', sans-serif;
    }

    .header-info {
        background: linear-gradient(90deg, rgba(88, 103, 221, 0.1) 0%, rgba(0, 210, 255, 0.1) 100%);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-info::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #586BDD 0%, #00D2FF 100%);
    }

    .status-indicator {
        height: 60px;
        width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-pago {
        background: linear-gradient(135deg, rgba(56, 196, 138, 0.2) 0%, rgba(56, 196, 138, 0.05) 100%);
        border: 1px solid rgba(56, 196, 138, 0.3);
        color: #38C48A;
    }

    .bg-pendente {
        background: linear-gradient(135deg, rgba(255, 184, 34, 0.2) 0%, rgba(255, 184, 34, 0.05) 100%);
        border: 1px solid rgba(255, 184, 34, 0.3);
        color: #FFB822;
    }

    .bg-vencido {
        background: linear-gradient(135deg, rgba(244, 81, 108, 0.2) 0%, rgba(244, 81, 108, 0.05) 100%);
        border: 1px solid rgba(244, 81, 108, 0.3);
        color: #F4516C;
    }

    .bg-concluido {
        background: linear-gradient(135deg, rgba(85, 120, 235, 0.2) 0%, rgba(85, 120, 235, 0.05) 100%);
        border: 1px solid rgba(85, 120, 235, 0.3);
        color: #5578EB;
    }

    .status-indicator i {
        font-size: 1.5rem;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-pago {
        background: linear-gradient(135deg, #38C48A 0%, #28A745 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(56, 196, 138, 0.3);
    }

    .badge-pendente {
        background: blue;
        color: white;
        box-shadow: 0 2px 8px rgba(255, 184, 34, 0.3);
    }

    .badge-vencido {
        background: linear-gradient(135deg, #F4516C 0%, #E53935 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(244, 81, 108, 0.3);
    }

    .badge-concluido {
        background: linear-gradient(135deg, #5578EB 0%, #4E73DF 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(85, 120, 235, 0.3);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        padding: 0 1.25rem;
    }

    .info-card {
        background: rgba(83, 92, 136, 0.1);
        border-radius: 8px;
        padding: 1rem;
        position: relative;
        border: 1px solid rgba(83, 92, 136, 0.2);
    }

    .info-card .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8a94a6;
        margin-bottom: 0.3rem;
    }

    .info-card .value {
        font-size: 1rem;
        font-weight: 600;
        color: #000;
    }

    .highlight-box {
        background: linear-gradient(135deg, rgba(88, 103, 221, 0.15) 0%, rgba(0, 210, 255, 0.05) 100%);
        border: 1px solid rgba(83, 92, 136, 0.2);
        border-radius: 10px;
        padding: 1.5rem;
        margin: 2rem 1.25rem 1.5rem;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .highlight-box .icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        font-size: 2rem;
        color: rgba(0, 210, 255, 0.2);
    }

    .highlight-box .label {
        font-size: 0.85rem;
        color: #8a94a6;
        margin-bottom: 0.5rem;
    }

    .highlight-box .value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(90deg, #586BDD 0%, #00D2FF 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .matricula-id {
        font-size: 0.85rem;
        opacity: 0.7;
        margin-top: 0.5rem;
    }

    .curso-nome {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Animações */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.5s ease forwards;
    }

    .delay-1 {
        animation-delay: 0.1s;
    }

    .delay-2 {
        animation-delay: 0.2s;
    }

    .delay-3 {
        animation-delay: 0.3s;
    }

    .delay-4 {
        animation-delay: 0.4s;
    }
</style>


<script>
    function aplicarCupom(id_curso) {
        Swal.fire({
            title: 'Aplicar Cupom',
            html: `
            <form id="cupom-desconto">
                <div class="row">
                    <div class="col-sm-9 esquerda-mobile-input-botao">
                        <div class="form-group">
                            <input type="text" name="cupom" id="cupom" class="form-control" required
                                placeholder="Código do Cupom">
                        </div>
                    </div>
                    <div class="col-sm-3 direita-mobile-input-botao" style="margin-left:-20px">
                        <button id="btn-cupom" type="submit" name="submit"
                            class="btn btn-success botao-laranja submit-button">Aplicar</button>
                    </div>
                    <input type="hidden" name="id_curso_cupom" value="${id_curso}">
                </div>
            </form>
        `,
            showConfirmButton: false,
            focusConfirm: false,
            width: '600px'
        });

        // Capturar envio do formulário dentro do Swal
        document.getElementById('cupom-desconto').addEventListener('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: "<?php echo $url_sistema ?>ajax/cursos/cupom.php",
                type: 'POST',
                data: formData,
                dataType: "text",
                success: function (mensagem) {
                    mensagem = mensagem.split('-');

                    if (mensagem[0].trim() == "Cupom Utilizado") {
                        // Sucesso
                        Swal.fire({
                            icon: 'success',
                            title: 'Cupom aplicado!',
                            text: mensagem[0] + ' - Desconto ativado!',
                            confirmButtonText: 'Atualizar página'
                        }).then(() => {
                            location.reload();
                        });

                     
                  

                    } else {
                        // Erro
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: mensagem,
                            confirmButtonText: 'Tentar novamente'
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                cache: false,
                contentType: false,
                processData: false
            });
        });
    }
</script>



<script>
    function pagarCurso(forma_pgto, id_curso, id, nome_curso) {

        forma_pgto = forma_pgto.toUpperCase();

        const showPaymentModal = () => {
            Swal.fire({
                title: 'Escolha a forma de pagamento',
                showDenyButton: true, // terceiro botão
                showCancelButton: true,
                confirmButtonText: 'Boleto',
                denyButtonText: 'Boleto Parcelado',
                cancelButtonText: 'Cartão de Crédito',
                allowOutsideClick: true,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    updatePaymentMethod('BOLETO');
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    updatePaymentMethod('CARTAO_DE_CREDITO');
                } else if (result.isDenied) {
                    showInstallmentsModal();
                }
            });
        }

        const showInstallmentsModal = () => {
            let options = '';
            for (let i = 1; i <= 12; i++) {
                options += `<option value="${i}">${i}x</option>`;
            }

            Swal.fire({
                title: 'Selecione a quantidade de parcelas',
                html: `
                    <select id="parcelas" class="swal2-input">
                        ${options}
                    </select>
                `,
                confirmButtonText: 'Confirmar',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const parcelas = document.getElementById('parcelas').value;
                    if (!parcelas) {
                        Swal.showValidationMessage('Selecione uma quantidade de parcelas');
                        return false;
                    }
                    return parcelas;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updatePaymentMethod('BOLETO_PARCELADO', result.value);
                }
            });
        }

        const updatePaymentMethod = (method, parcelas = 1) => {
            $.ajax({
                url: "<?php echo $url_sistema ?>sistema/painel-aluno/paginas/cursos/set_payment_method.php",
                type: 'POST',
                data: { 
                    id_curso: id_curso, 
                    forma_pgto: method, 
                    id: id, 
                    nome_do_curso: nome_curso, 
                    quantidadeParcelas: parcelas,
                    pacote: 'Não'
                },
                dataType: "json"
            }).done(function (response) {
                if (response.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: response.message
                    });
                }
            }).fail(function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Falha na comunicação com o servidor.'
                });
            });
        };

        switch (forma_pgto) {
            case 'AGUARDANDO':
                showPaymentModal();
                break;
            case 'BOLETO':
                window.location.href = "<?php echo $url_sistema ?>sistema/painel-aluno/index.php?pagina=parcelas";
                break;
            case 'CARTAO_DE_CREDITO':
                window.location.href = "<?php echo $url_sistema ?>sistema/painel-aluno/index.php?pagina=parcelas_cartao";
                break;
            case 'BOLETO_PARCELADO':
                showInstallmentsModal();
                break;
            default:
                showPaymentModal();
                break;
        }

    }
</script>


<script type="text/javascript">

    $(document).ready(function () {
        $('#tabela').DataTable({
            "ordering": false,
            "stateSave": true,
        });
        $('#tabela_filter label input').focus();
    });

    function copiarCodigo() {
        var codigoInput = document.getElementById("pix-code");
        codigoInput.select();
        codigoInput.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("Código PIX copiado para a área de transferência!");
    }

</script>


<script>
    function modalAvaliacao(href) {
        const normalizedHref = href.replace(/([^:]\/)\/+/g, '$1');
        Swal.fire({
            title: 'Visualizar Arquivo',
            html: `<iframe src="${normalizedHref}" width="100%" height="400px" style="border: none;"></iframe>`,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });

    }
</script>
