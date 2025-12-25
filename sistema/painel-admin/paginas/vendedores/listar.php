<?php
require_once("../../../conexao.php");
$tabela = 'vendedores';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $pdo->query("SELECT v.*, u.wallet_id 
      FROM $tabela v 
      LEFT JOIN usuarios u ON v.id = u.id_pessoa 
      WHERE u.nivel = 'Vendedor'
      ORDER BY v.id DESC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// echo json_encode($res, JSON_PRETTY_PRINT);
// return;

$total_reg = @count($res);
if ($total_reg > 0) {
    echo <<<HTML
    <table class="table table-hover" id="tabela">
    <thead> 
    <tr> 
    <th>Nome</th>
    <th class="esc">Telefone</th> 
    <th class="esc">Email</th>  
    <th class="esc">Professor</th>  
    <th class="esc">Cadastro</th>
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
        $comissao = $res[$i]['comissao'];
        $professor = $res[$i]['professor'];
        $foto = $res[$i]['foto'];
        $data = $res[$i]['data'];
        $ativo = $res[$i]['ativo'];
        $wallet_id = $res[$i]['wallet_id'] ?? 'Não disponível';

        $resProfessor = $professor ? 'Sim' : 'Não';

        $dataF = implode('/', array_reverse(explode('-', $data)));


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


        // if($arquivo == ""){
        //  $esconder3 = 'ocultar';
        // }else{
        //  $esconder3 = '';
        // }

        echo <<<HTML
<tr class="{$classe_linha}"> 
        <td>
        <img src="img/perfil/{$foto}" width="50px" height="60px" class="mr-2">
        {$nome} 
        </td> 
        <td class="esc">
        {$telefone}
        <a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55{$telefone}" title="Chamar no Whatsapp"><i class="fa fa-whatsapp verde"></i></a>
        </td>
        <td class="esc">{$email}</td>       
        <td class="esc">{$resProfessor}</td>       
        <td class="esc">{$dataF}</td>
        <td class="text-center">

        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#actionsModal{$id}">
            <i class="fa fa-cog"></i> Ver Ações
        </button>


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
              <a href="#" onclick="mostrar('{$nome}', '{$cpf}','{$email}','{$telefone}', '{$wallet_id}', '{$comissao}', '{$professor}', '{$foto}', '{$dataF}', '{$ativo}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-info-circle text-secondary"></i><br>
                Visualizar
              </a>
            </div>

		   <!-- Edit Data -->
		   <div class="col-md-4 text-center mb-3">
              <a href="#" onclick="editar('{$id}', '{$nome}', '{$cpf}','{$email}','{$telefone}', '{$wallet_id}', '{$comissao}', '{$professor}', '{$foto}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-edit text-primary"></i><br>
                Editar
              </a>
            </div>
            
            <!-- Ver Alunos -->
        <div class=" col-md-4 text-center mb-3">
              <a href="index.php?pagina=alunos_vendedor&vendedor={$email}" class="btn btn-default btn-block">
                <i class="fa fa-graduation-cap"></i><br>
                Ver Alunos
              </a>
            </div>

           
            
            <!-- Delete -->
            <div class=" col-md-4 text-center mb-3">
              <a href="#" onclick="if(confirm('Confirm deletion?')) { excluir('{$id}'); }" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa fa-trash-o text-danger"></i><br>
                Apagar
              </a>
            </div>
            
            <!-- Activate/Deactivate -->
            <div class="col-md-4 text-center mb-3">
              <a href="#" onclick="ativar('{$id}', '{$acao}')" class="btn btn-default btn-block" data-dismiss="modal">
                <i class="fa {$icone} text-success"></i><br>
                {$titulo_link}
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

    function editar(id, nome, cpf, email, telefone, wallet_id, comissao, professor, foto) {

        $('#wallet_id').val(wallet_id);
        $('#comissao').val(comissao);
        $('#professor').text(professor == "1" ? true : false);
        $('#id').val(id);
        $('#nome').val(nome);
        $('#telefone').val(telefone);
        $('#cpf').val(cpf);
        $('#email').val(email);

        $('#foto').val('');
        $('#target').attr('src', 'img/perfil/' + foto);

        $('#tituloModal').text('Editar Registro');
        $('#modalForm').modal('show');
        $('#mensagem').text('');
    }

    function mostrar(nome, cpf, email, telefone, wallet_id, comissao, professor, foto, data, ativo) {
    // Animações e estilos herdados do design original
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
            background: linear-gradient(135deg, #337ab7, #337ab7);
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
            text-align: center;
            margin-top: -20px;
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
            margin-bottom: -15px;
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
    </style>`;

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
                <img src="/sistema/painel-admin/img/perfil/${foto}" class="profile-img" alt="Foto de Perfil">
            </div>
            <div class="profile-body">
                <div class="info-card">
                    <h5>Informações Pessoais</h5>
                    <div class="info-item">
                        <span class="info-label">CPF</span>
                        <span class="info-value">${cpf}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Telefone</span>
                        <span class="info-value">${telefone}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value">${email}</span>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-item">
                        <span class="info-label">Data de Cadastro</span>
                        <span class="info-value">${data}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Comissão</span>
                        <span class="info-value">${comissao}%</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Professor</span>
                        <span class="info-value">${professor === '1' ? 'Sim' : 'Não'}</span>
                    </div>
                </div>
                <div class="info-card">
                    <h5>Identificador do Banco</h5>
                    <div class="info-item" style="width: 100%;">
                        <input disabled type="text" value="${wallet_id}" placeholder="Digite o identificador..." required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;" />
                    </div>
                </div>
            </div>
        </div>`,
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'swal-profile-popup',
            closeButton: 'swal-profile-close'
        }
    });
}


    function mostrar2(nome, cpf, email, telefone, wallet_id, comissao, professor, foto, data, cartao, ativo) {
        $('#walletId').val(wallet_id);
        $('#comissao_mostrar').text(comissao);
        $('#professor_mostrar').text(professor == "1" ? "Sim" : "Não");
        $('#nome_mostrar').text(nome);
        $('#telefone_mostrar').text(telefone);
        $('#cpf_mostrar').text(cpf);
        $('#email_mostrar').text(email);
        $('#data_mostrar').text(data);
        $('#ativo_mostrar').text(ativo);
        $('#target_mostrar').attr('src', 'img/perfil/' + foto);

        $('#modalMostrar').modal('show');

    }


    function limparCampos() {
        $('#id').val('');
        $('#wallet_id').val('');
        $('#professor').val('');
        $('#professor_mostrar').text('');
        $('#comissao').val('');
        $('#nome').val('');
        $('#telefone').val('');
        $('#cpf').val('');
        $('#email').val('');
        $('#foto').val('');
        $('#target').attr('src', 'img/perfil/sem-perfil.jpg');
    }
</script>
