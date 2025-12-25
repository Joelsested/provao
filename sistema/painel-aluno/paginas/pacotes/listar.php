<?php
require_once("../../../conexao.php");
$tabela = 'matriculas';

@session_start();
$id_usuario = $_SESSION['id'];


echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM {$tabela} where aluno = :aluno and pacote = 'Sim' ORDER BY id desc");
$query->execute([':aluno' => $id_usuario]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$matriculas = [];

foreach ($res as $matricula) {
    $id_matricula = $matricula['id'];

    // Agora busca os pagamentos referentes a essa matrÃ­cula
    $stmt = $pdo->prepare("SELECT * FROM pagamentos_pix WHERE id_matricula = :id_matricula");
    $stmt->bindParam(':id_matricula', $id_matricula);
    $stmt->execute();
    $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Exemplo: incluir os pagamentos dentro do array da matrÃ­cula
    $matricula['pagamentos'] = $pagamentos;

    // FaÃ§a o que precisar com os dados aqui
    array_push($matriculas, $matricula);


}


// echo '<pre>';
// echo json_encode($matriculas, JSON_PRETTY_PRINT);
// echo '</pre>';
// return;


$total_reg = @count($res);
if ($total_reg > 0) {
    echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Curso</th>
	<th class="esc">Professor</th> 
	<th class="esc">Cursos</th> 	
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
        $valor = $res[$i]['subtotal'];
        $valor_cupom = $res[$i]['valor_cupom'];
        $data = $res[$i]['data'];
        $status = $res[$i]['status'];
        $professor = $res[$i]['professor'];
        $pacote = $res[$i]['pacote'];

        $qrcode = isset($matriculas[$i]['pagamentos'][0]['qrcode_url']);
        $texto_copia_cola = isset($matriculas[$i]['pagamentos'][0]['texto_copia_cola']);
        $valorC = isset($matriculas[$i]['pagamentos'][0]['valor']);
        $data_criacao = isset($matriculas[$i]['pagamentos'][0]['data_criacao']);
        $statusC = isset($matriculas[$i]['pagamentos'][0]['status']);

        if ($pacote == 'Sim') {
            $tab = 'pacotes';
            $link = 'cursos-do-';
        } else {
            $tab = 'cursos';
            $link = 'curso-de-';
        }


        $query2 = $pdo->prepare("SELECT * FROM {$tab} where id = :id");
        $query2->execute([':id' => $curso]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        if (@count($res2) > 0) {
            $nome_curso = $res2[0]['nome'];
            $nome_url = $res2[0]['nome_url'];
            $url_do_curso = $link . $nome_url;
            $id_do_curso = $res2[0]['id'];

        } else {
            $nome_curso = "";
        }


        $query2 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
        $query2->execute([':id' => $professor]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        if (@count($res2) > 0) {
            $nome_professor = $res2[0]['nome'];
        } else {
            $nome_professor = "";
        }


        $query2 = $pdo->prepare("SELECT * FROM cursos_pacotes where id_pacote = :id_pacote");
        $query2->execute([':id_pacote' => $curso]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        $cursos = @count($res2);



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



        //FORMATAR VALORES
        $valorF = number_format($valor, 2, ',', '.');
        $dataF = implode('/', array_reverse(explode('-', $data)));
        
        
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
		<form method="post" action="index.php?pagina=cursos" class="{$classe_nome} $ocultar_aulas">	
		<button  type="submit" style="background-color: transparent;  border:none!important;"><span class="{$classe_nome}" style="margin-left:2px">{$nome_curso}</span>
		</button>
		<input type="hidden" name="id_pacote" value="{$curso}">	
		</form>	
		
		<form method="post" action="../../{$url_do_curso}" target="_blank" class="{$ocultar_pagar} hidden">
		
		

		<span class="text-muted">{$nome_curso}</span>
							
									<button  type="submit" style="background-color: transparent;  border:none!important;"><i class="fa fa-money text-danger" ></i><span class="text-danger" style="margin-left:2px">Pagar</span>
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
		<td class="esc">$cursos</td>
		
		<td class="esc">R$ {$valor} </td>
        <td class="esc">
            <span style="font-size:10px">{$matriculas[$i]['forma_pgto']}</span>
        <button class="{$ocultar_pagar}" onclick="pagarCurso('AGUARDANDO', '{$id_do_curso}', '{$id}', '{$nome_curso}');"  type="submit" style="background-color: transparent;  border:none!important;">
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
		<td class="esc"><i class="fa {$icone} $classe_square"></i></td>				
		<td>
		
		<li class="dropdown head-dpdn2 {$excluir}" style="display: inline-block;">
		<a href="#" class="dropdown-toggle {$excluir}" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir(this)" data-id="{$id}"><span class="text-danger">Sim</span></a></p>
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
    echo 'Não possui nenhum pacote matrículado!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

    $(document).ready(function () {
        $('#tabela').DataTable({
            "ordering": false,
            "stateSave": true,
        });
        $('#tabela_filter label input').focus();
    });



    function aulas(id, nome, aulas, id_curso) {
        $('#nome_aula_titulo').text(nome);
        $('#aulas_aula').text(aulas);
        $('#modalAulas').modal('show');
        $('#id_da_matricula').val(id);
        $('#id_do_curso').val(id_curso);
        listarAulas(id_curso, id);
        //listarPerguntas(id);		


    }

</script>


<style>
    /* CustomizaÃ§Ã£o do SweetAlert2 */
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

    /* ConteÃºdo do Modal */
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

    /* AnimaÃ§Ãµes */
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

        // Capturar envio do formulÃ¡rio dentro do Swal
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
            for (let i = 1; i <= 6; i++) {
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
                    pacote: 'Sim'
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


