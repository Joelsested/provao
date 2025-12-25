<?php
require_once("../conexao.php");
$tabela = 'alunos';
$pag = "alunos_vendedor";
@session_start();

$id_user = $_SESSION['id'];
$email_vendedor = $_GET['vendedor'];

$queryVendedor = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$queryVendedor->execute([':usuario' => $email_vendedor]);
$resVendedor = $queryVendedor->fetchAll(PDO::FETCH_ASSOC);
$id_vendedor = $resVendedor[0]['id'];
$nome_vendedor = $resVendedor[0]['nome'];

// echo $id_vendedor;
// return;


echo <<<HTML

<div class="bs-example " style="padding:15px; margin-top:-10px">



 <div>

  <h2>Alunos do vendedor: {$nome_vendedor}</h2>

 </div>
 <br>

 </div>
<small>
HTML;

if (@$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Administrador') {
    $ocultar = 'ocultar';

} else {
    $ocultar = '';
}

if (@$_SESSION['nivel'] != 'Secretario' and @$_SESSION['nivel'] != 'Administrador' and @$_SESSION['nivel'] != 'Vendedor' and @$_SESSION['nivel'] != 'Tutor') {
    $ocultar2 = 'ocultar';

} else {
    $ocultar2 = '';
}

if (@$_SESSION['nivel'] == 'Administrador' || @$_SESSION['nivel'] == 'Tutor' || @$_SESSION['nivel'] == 'Parceiro' || @$_SESSION['nivel'] == 'Vendedor') {

    $query = $pdo->prepare("SELECT * FROM $tabela where usuario = :usuario ORDER BY id desc");
    $query->execute([':usuario' => $id_vendedor]);
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_reg = @count($res);
} else {
    $query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_reg = @count($res);
}
if ($total_reg > 0) {
    echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Nome</th>
	<th class="esc">Telefone</th> 
	<th class="esc">Email</th> 	
	<th class="esc">Professor</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

    for ($i = 0; $i < $total_reg; $i++) {
        foreach ($res[$i] as $key => $value) {
        }
        $id = $res[$i]['id'];
        $nome = $res[$i]['nome'];
        $cpf = $res[$i]['cpf'];
        $email = $res[$i]['email'];
        $telefone = $res[$i]['telefone'];
        $rg = $res[$i]['rg'];
        $orgao_expedidor = $res[$i]['orgao_expedidor'] ?? '';
        $expedicao = $res[$i]['expedicao'];
        $nascimento = $res[$i]['nascimento'];
        $cep = $res[$i]['cep'];
        $sexo = $res[$i]['sexo'];
        $endereco = $res[$i]['endereco'];
        $numero = $res[$i]['numero'];
        $bairro = $res[$i]['bairro'];
        $cidade = $res[$i]['cidade'];
        $estado = $res[$i]['estado'];
        $mae = $res[$i]['mae'];
        $pai = $res[$i]['pai'];
        $naturalidade = $res[$i]['naturalidade'];
        $professor4 = $res[$i]['usuario'];
        $foto = $res[$i]['foto'];
        $data = $res[$i]['data'];

        $ativo = $res[$i]['ativo'];
        $arquivo = $res[$i]['arquivo'];



        $query7 = $pdo->prepare("SELECT * FROM usuarios where id = :id");
        $query7->execute([':id' => $professor4]);
        $res7 = $query7->fetchAll(PDO::FETCH_ASSOC);
        $nome_professor = @$res7[0]['nome'];

        $dataF = implode('/', array_reverse(explode('-', $data)));

        $query2 = $pdo->prepare("SELECT * FROM usuarios where id_pessoa = :id_pessoa and nivel = 'Aluno'");
        $query2->execute([':id_pessoa' => $id]);
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        $senha_usuario = $res2[0]['senha'];




        if ($ativo == 'Sim') {
            $icone = 'fa-check-square';
            $titulo_link = 'Desativar Item';
            $acao = 'Não';
            $classe_linha = '';
        } else {
            $icone = 'fa-square-o';
            $titulo_link = 'Ativar Item';
            $acao = 'Sim';
            $classe_linha = 'text-muted';
        }

        if ($telefone == "") {
            $icone_whatsapp = '';
        } else {
            $icone_whatsapp = 'fa-whatsapp';
        }

        if ($arquivo == "") {
            $esconder2 = 'ocultar';
        } else {
            $esconder2 = '';
        }




        echo <<<HTML






	<tr class="{$classe_linha}"> 
		<td>
		<img src="../painel-aluno/img/perfil/{$foto}" width="27px" class="mr-2">
		{$nome}	
		</td> 
		<td class="esc">
		{$telefone}
		<a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55{$telefone}" title="Chamar no Whatsapp"><i class="fa {$icone_whatsapp} verde"></i></a>
		</td>
		<td class="esc">{$email}</td>		
		<td class="esc">{$nome_professor}</td>
		


<!-- TD SWAL HERER -->


<td class="text-center">
  <!-- Single button to open actions modal -->
  <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#actionsModal{$id}">
    <i class="fa fa-cog"></i> Ver Ações
  </button>
  
  <!-- Modal with all actions -->
  <div class="modal fade" id="actionsModal{$id}" tabindex="-1" role="dialog" aria-labelledby="actionsModalLabel{$id}">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="actionsModalLabel{$id}">Ações para {$nome}</h4>
        </div>
        <div class="modal-body">
          <div class="row">

		   <!-- View Data -->
		   <div class="col-md-4 text-center mb-3">
              <a href="#" onclick="mostrar('{$nome}','{$cpf}','{$email}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$telefone}','{$cep}','{$endereco}','{$cidade}','{$estado}','{$sexo}','{$nascimento}','{$mae}','{$pai}','{$naturalidade}', '{$foto}', '{$dataF}', '{$ativo}', '{$senha_usuario}','{$arquivo}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-info-circle text-secondary"></i><br>
                Visualizar
              </a>
            </div>

		   <!-- Edit Data -->
		   <div class="col-md-4 text-center mb-3">
              <a href="#" onclick="editar('{$id}', '{$nome}','{$cpf}','{$email}', '{$telefone}','{$rg}','{$orgao_expedidor}','{$expedicao}','{$nascimento}','{$cep}','{$sexo}','{$endereco}','{$numero}','{$bairro}','{$cidade}','{$estado}','{$mae}','{$pai}','{$naturalidade}','{$foto}','{$arquivo}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-edit text-primary"></i><br>
                Editar
              </a>
            </div>
            
           

            <!-- Payments -->
            <div class="col-md-4 text-center mb-3">
              <a href="index.php?pagina=pagamentos_aluno&aluno={$id}" class="btn btn-default btn-block">
                <i class="fa fa-money text-primary"></i><br>
                Pagamentos
              </a>
            </div>

			 <!-- Financy -->
			 <div class="col-md-4 text-center mb-3">
              <a href="index.php?pagina=relatorio_aluno&aluno={$id}" class="btn btn-default btn-block">
                <i class="fa fa-money text-primary"></i><br>
                Relatório Financeiro
              </a>
            </div>
            
            <!-- Student Files -->
            <div class="col-md-4 text-center mb-3">
              <a href="index.php?pagina=arquivos_alunos&usuario={$email}" class="btn btn-default btn-block">
                <i class="fa fa-file-pdf-o text-success"></i><br>
                Arquivos do Aluno
              </a>
            </div>
            
           
            
            <!-- Delete -->
            <div class="{$ocultar} col-md-4 text-center mb-3">
              <a href="#" onclick="if(confirm('Confirm deletion?')) { excluir('{$id}'); }" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-trash-o text-danger"></i><br>
                Apagar
              </a>
            </div>
            
            <!-- Activate/Deactivate -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="ativar('{$id}', '{$acao}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa {$icone} text-success"></i><br>
                {$titulo_link}
              </a>
            </div>
            

			<div class="col-md-4 text-center mb-3 {$ocultar2}">
				<a href="javascript:void(0);" onclick="modalAvaliacao('{$url_sistema}/sistema/rel/avaliacoes_class.php?id={$id}')" class="btn btn-default btn-block">
					<i class="fa fa-file-pdf-o text-danger"></i><br>
					Avaliações
				</a>
				</div>
            
            <!-- Student Certificate -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarCertAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-pdf-o text-primary"></i><br>
                Certificados
              </a>
            </div>
            
            <!-- Medium Declaration -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarDeclaracaoMedioAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-pdf-o text-danger"></i><br>
                Declaração Médio
              </a>
            </div>
            
            <!-- Fundamental Declaration -->
            <div class="col-md-4 text-center mb-3 {$ocultar}">
              <a href="#" onclick="gerarDeclaracaoFundamentalAluno({$id});" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-file-pdf-o text-primary"></i><br>
                Declaração Fundamental
              </a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>
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
    echo 'Não possui nenhum registro cadastrado!';
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

    function editar(id, nome, cpf, email, telefone, rg, orgao_expedidor, expedicao, nascimento, cep, sexo, endereco, numero, bairro, cidade, estado, mae, pai, naturalidade, foto,) {

        $('#id').val(id);
        $('#nome').val(nome);
        $('#cpf').val(cpf);
        $('#email').val(email);
        $('#telefone').val(telefone);
        $('#rg').val(rg);
        $('#orgao_expedidor').val(orgao_expedidor);
        $('#expedicao').val(expedicao);
        $('#nascimento').val(nascimento);
        $('#cep').val(cep);
        $('#sexo').val(sexo);
        $('#endereco').val(endereco);
        $('#numero').val(numero);
        $('#bairro').val(bairro);
        $('#cidade').val(cidade);
        $('#estado').val(estado);
        $('#mae').val(mae);
        $('#pai').val(pai);
        $('#naturalidade').val(naturalidade);
        $('#foto').val('');

        $('#target').attr('src', '../painel-aluno/img/perfil/' + foto);

        $('#tituloModal').text('Editar Registro');
        $('#modalForm').modal('show');
        $('#mensagem').text('');
    }


    function mostrar(nome, cpf, email, rg, orgao_expedidor, expedicao, telefone, cep, endereco, cidade, estado, sexo, nascimento, mae, pai, naturalidade, foto, data, ativo, senha, arquivo) {
        // Definindo cores para gradientes
        const primaryGradient = 'linear-gradient(135deg, #337ab7, #337ab7)';
        const secondaryGradient = 'linear-gradient(135deg, #337ab7, #337ab7)';

        // Status com cor apropriada
        const statusColor = ativo === 'Sim' ? '#42e695' : '#ff6b6b';
        const statusIcon = ativo === 'Sim' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';

        // Formatando dados
        const formatarData = (dataString) => {
            try {
                if (!dataString) return 'Não informado';
                const data = new Date(dataString);
                return data.toLocaleDateString('pt-BR');
            } catch (e) {
                return dataString;
            }
        };

        // Adiciona animação CSS
        const animationStyles = `
        <style>
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .profile-card {
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
                animation: fadeIn 0.6s ease-out forwards;
                background-color: #FFF;
                max-height: 100%;
            }
            
            .profile-header {
                background: ${primaryGradient};
                padding: 20px;
                color: white;
                text-align: center;
            }
            
            .profile-header h3 {
                margin: 0;
                font-weight: 600;
                letter-spacing: 1px;
            }
            
            .profile-img-container {
                position: relative;
                margin-top: -5px;
                text-align: center;
            }
            
            .profile-img {
                width: 100px;
                height: 100px;
                object-fit: cover;
                border-radius: 50%;
                border: 5px solid white;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                animation: pulse 2s infinite;
            }
            
            .profile-body {
                padding: 30px;
                margin-top: -20px;
            }
            
            .info-card {

                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                padding: 15px;
                box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .info-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 7px 14px rgba(50, 50, 93, 0.15);
            }
            
            .info-card h5 {
                margin-top: 0;
                font-size: 14px;
                font-weight: 600;
                color: #8898aa;
                text-transform: uppercase;
                letter-spacing: 1px;
                border-bottom: 1px solid #f0f0f0;
                padding-bottom: 8px;
            }
            
            .info-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                align-items: center;
            }
            
            .info-label {
                font-weight: 600;
                color: #525f7f;
            }
            
            .info-value {
                color: #32325d;
                background: #f6f9fc;
                padding: 5px 10px;
                border-radius: 6px;
                font-family: 'Roboto Mono', monospace;
                font-size: 16px;
            }
            
            .status-active {
                color: ${statusColor};
                font-weight: bold;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .location-chip {
                background: ${secondaryGradient};
                color: white;
                padding: 5px 12px;
                border-radius: 20px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 13px;
                margin-right: 8px;
            }
            
            .swal2-close {
                position: absolute !important;
                top: 35px !important;
                right: 35px !important;
                background: rgba(255, 255, 255, 0.2) !important;
                backdrop-filter: blur(5px) !important;
                border-radius: 50% !important;
                width: 36px !important;
                height: 36px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                color: white !important;
                font-size: 24px !important;
                transition: background 0.3s !important;
            }
            
            .swal2-close:hover {
                background: rgba(255, 255, 255, 0.3) !important;
                color: white !important;
            }
        </style>
    `;

        Swal.fire({
            title: '',
            width: '700px',
            padding: 0,
            background: 'transparent',
            html: `
            ${animationStyles}
            <div class="profile-card">
                <div class="profile-header">
                    <h3>${nome.toUpperCase()}</h3>
                </div>
                
                <div class="profile-img-container">
                    <img src="/sistema/painel-aluno/img/perfil/${foto}" class="profile-img" alt="Foto de Perfil">
                </div>
                
                <div class="profile-body">
                    <div class="info-card">
                        <h5>Informações Pessoais</h5>
                        <div class="info-item">
                            <span class="info-label">CPF</span>
                            <span class="info-value">${cpf}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">${email}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Telefone</span>
                            <span class="info-value">${telefone}</span>
                        </div>
                         <div class="info-item">
                            <span class="info-label">Cidade</span>
                            <span class="info-value">${cidade}</span>
                        </div>
                         <div class="info-item">
                            <span class="info-label">Estado</span>
                            <span class="info-value">${estado}</span>
                        </div>
                    </div>
                    
                   
                    
                    <div class="info-card">
                        <h5>Status da Conta</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label">Data de Cadastro</span>
                                    <span class="info-value">${data}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label">Ativo</span>
                                    <span class="status-active">${ativo}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        `,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-profile-popup',
                closeButton: 'swal-profile-close'
            }
        });
    }



    function limparCampos() {
        $('#id').val('');
        $('#nome').val('');
        $('#cpf').val('');
        $('#email').val('');
        $('#telefone').val('');
        $('#rg').val('');
        $('#orgao_expedidor').val('');
        $('#expedicao').val('');
        $('#nascimento').val('');
        $('#cep').val('');
        $('#sexo').val('');
        $('#endereco').val('');
        $('#numero').val('');
        $('#bairro').val('');
        $('#cidade').val('');
        $('#estado').val('');
        $('#mae').val('');
        $('#pai').val('');
        $('#naturalidade').val('');
        $('#foto').val('');
        $('#target').attr('src', 'img/perfil/sem-perfil.jpg');
    }



    function editarCartoes(id) {
        var cartoes = $('#cartao-' + id).val();
        $.ajax({
            url: 'paginas/' + pag + "/editar-cartoes.php",
            method: 'POST',
            data: { id, cartoes },
            dataType: "text",

            success: function (mensagem) {
                if (mensagem.trim() == "Alterado com Sucesso") {
                    $('#mensagem-excluir').addClass('verde')
                    $('#mensagem-excluir').text(mensagem)
                } else {
                    $('#mensagem-excluir').addClass('text-danger')
                    $('#mensagem-excluir').text(mensagem)
                }
            },

        });
    }


    function listarCur(id) {
        $.ajax({
            url: 'paginas/' + pag + "/listar-cur.php",
            method: 'POST',
            data: { id },
            dataType: "html",

            success: function (result) {

                $("#listar-cursosfin_" + id).html(result);

            }
        });
    }




    function gerarCertAluno(id) {
        Swal.fire({
            title: "Gerar Certificado",
            html: `
            <label for="ano_certificado">Insira o ano da conclusão:</label>
            <br>
            <input type="number" id="ano_certificado" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
            <br>
            <br>
            <label for="data_certificado">Selecione a data do certificado:</label>
            <input type="date" id="data_certificado" class="swal2-input">
        `,
            showCancelButton: true,
            confirmButtonText: "Gerar Certificado",
            cancelButtonText: "Cancelar",
            preConfirm: () => {
                const anoCertificado = document.getElementById("ano_certificado").value;
                const dataCertificado = document.getElementById("data_certificado").value;

                if (!anoCertificado || anoCertificado.length !== 4) {
                    Swal.showValidationMessage("Por favor, insira um ano válido (ex: 2025).");
                    return false;
                }
                if (!dataCertificado) {
                    Swal.showValidationMessage("Por favor, selecione uma data.");
                    return false;
                }

                return { ano: anoCertificado, data: dataCertificado };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { ano, data } = result.value;
                const url = `/sistema/rel/rel_certificado.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
                window.open(url, "_blank"); // Abre em uma nova guia
            }
        });
    }

    function gerarDeclaracaoFundamentalAluno(id) {
        Swal.fire({
            title: "Declaração Ensino Fundamental",
            // icon: "info",
            html: `
            <label for="ano_declaracao_fundamental">Insira o ano da conclusão:</label>
            <br>
            <input type="number" id="ano_declaracao_fundamental" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
            <br>
            <br>
            <label for="data_declaracao_fundamental">Selecione a data da declaração:</label>
            <input type="date" id="data_declaracao_fundamental" class="swal2-input">
        `,
            showCancelButton: true,
            confirmButtonText: "Gerar Declaração",
            cancelButtonText: "Cancelar",
            preConfirm: () => {
                const anoDeclaracaoFundamental = document.getElementById("ano_declaracao_fundamental").value;
                const dataDeclaracaoFundamental = document.getElementById("data_declaracao_fundamental").value;

                if (!anoDeclaracaoFundamental || anoDeclaracaoFundamental.length !== 4) {
                    Swal.showValidationMessage("Por favor, insira um ano válido (ex: 2025).");
                    return false;
                }
                if (!dataDeclaracaoFundamental) {
                    Swal.showValidationMessage("Por favor, selecione uma data.");
                    return false;
                }

                return { ano: anoDeclaracaoFundamental, data: dataDeclaracaoFundamental };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { ano, data } = result.value;
                const url = `/sistema/rel/declaracao_fundamental_class.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
                window.open(url, "_blank"); // Abre em uma nova guia
            }
        });
    }


    function gerarDeclaracaoMedioAluno(id) {
        Swal.fire({
            title: "Declaração Ensino Médio",
            // icon: "info",
            html: `
            <label for="ano_declaracao_medio">Insira o ano da conclusão:</label>
            <br>
            <input type="number" id="ano_declaracao_medio" class="swal2-input" style="width: 50%;" min="1900" max="2100" step="1" placeholder="Ex: 2025">
            <br>
            <br>
            <label for="data_declaracao_medio">Selecione a data da declaração:</label>
            <input type="date" id="data_declaracao_medio" class="swal2-input">
        `,
            showCancelButton: true,
            confirmButtonText: "Gerar Declaração",
            cancelButtonText: "Cancelar",
            preConfirm: () => {
                const anoDeclaracaoMedio = document.getElementById("ano_declaracao_medio").value;
                const dataDeclaracaoMedio = document.getElementById("data_declaracao_medio").value;

                if (!anoDeclaracaoMedio || anoDeclaracaoMedio.length !== 4) {
                    Swal.showValidationMessage("Por favor, insira um ano válido (ex: 2025).");
                    return false;
                }
                if (!dataDeclaracaoMedio) {
                    Swal.showValidationMessage("Por favor, selecione uma data.");
                    return false;
                }

                return { ano: anoDeclaracaoMedio, data: dataDeclaracaoMedio };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { ano, data } = result.value;
                const url = `/sistema/rel/declaracao_medio_class.php?id=${id}&ano=${encodeURIComponent(ano)}&data=${encodeURIComponent(data)}`;
                window.open(url, "_blank"); // Abre em uma nova guia
            }
        });
    }



</script>

<script>
    function modalAvaliacao(href) {
        const normalizedHref = href.replace(/([^:]\/)\/+/g, '$1');
        Swal.fire({
            title: 'Visualizar Arquivo',
            html: `
            <div id="loading-spinner" class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p>Carregando PDF...</p>
            </div>
            <iframe id="pdf-iframe" src="${normalizedHref}" width="100%" height="500px" style="border: none; display: none;" onload="hideLoading()"></iframe>
        `,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                // Função que será executada quando o modal abrir
                window.hideLoading = function () {
                    document.getElementById('loading-spinner').style.display = 'none';
                    document.getElementById('pdf-iframe').style.display = 'block';
                }
            }
        });
    }
</script>
