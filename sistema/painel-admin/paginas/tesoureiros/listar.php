<?php 
require_once("../../../conexao.php");
$tabela = 'tesoureiros';

echo <<<HTML
<small>
HTML;

$query = $pdo->prepare("SELECT * FROM $tabela ORDER BY id desc");
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
echo <<<HTML
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Nome</th>
	<th class="esc">Telefone</th> 
	<th class="esc">Email</th> 	
		
	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];
	$cpf = $res[$i]['cpf'];
	$nascimento = $res[$i]['nascimento'];
	$email = $res[$i]['email'];
	$telefone = $res[$i]['telefone'];
	$endereco = $res[$i]['endereco'];
	$cidade = $res[$i]['cidade'];
	$estado = $res[$i]['estado'];
	$sexo = $res[$i]['sexo'];
	$foto = $res[$i]['foto'];
	$data = $res[$i]['data'];
	$cartao = $res[$i]['cartao'];
	$ativo = $res[$i]['ativo'];
	$arquivo = $res[$i]['arquivo'];

	$dataF = implode('/', array_reverse(explode('-', $data)));

	$query2 = $pdo->prepare("SELECT * FROM usuarios where id_pessoa = :id_pessoa and nivel = 'Tesoureiro'");
	$query2->execute([':id_pessoa' => $id]);
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$senha_usuario = $res2[0]['senha'];


	if($ativo == 'Sim'){
			$icone = 'fa-check-square';
			$titulo_link = 'Desativar Item';
			$acao = 'Não';
			$classe_linha = '';
		}else{
			$icone = 'fa-square-o';
			$titulo_link = 'Ativar Item';
			$acao = 'Sim';
			$classe_linha = 'text-muted';
		}

		if($telefone == ""){
			$icone_whatsapp = '';
		}else{
			$icone_whatsapp = 'fa-whatsapp';
		}

		if($arquivo == ""){
			$esconder2 = 'ocultar';
		}else{
			$esconder2 = '';
		}


echo <<<HTML
<tr class="{$classe_linha}"> 
		<td>
		<img src="../painel-admin/img/perfil/{$foto}" width="50px" height="60px" class="mr-2">
		{$nome}	
		</td> 
		<td class="esc">
		{$telefone}
		<a target="_blank" href="https://api.whatsapp.com/send?1=pt_BR&phone=55{$telefone}" title="Chamar no Whatsapp"><i class="fa {$icone_whatsapp} verde"></i></a>
		</td>
		<td class="esc">{$email}</td>		
		
		
		<td>


		




		<big><a href="#" onclick="editar('{$id}', '{$nome}', '{$cpf}', '{$nascimento}', '{$email}','{$telefone}','{$endereco}','{$cidade}','{$estado}','{$sexo}', '{$foto}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

		<big><a href="#" onclick="mostrar('{$nome}', '{$cpf}', '{$nascimento}', '{$email}','{$telefone}','{$endereco}','{$cidade}','{$estado}','{$sexo}', '{$foto}', '{$dataF}', '{$cartao}', '{$ativo}', '{$senha_usuario}')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>



		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>



		<big><a href="#" onclick="ativar('{$id}', '{$acao}')" title="{$titulo_link}"><i class="fa {$icone} text-success"></i></a></big>


	


		

		


		</td>
</tr>

HTML;

}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>	
HTML;

}else{
	echo 'Não possui nenhum registro cadastrado!';
}
echo <<<HTML
</small>
HTML;


?>


<script type="text/javascript">

	$(document).ready( function () {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
		});
		$('#tabela_filter label input').focus();
	} );

	function editar(id, nome, cpf, nascimento, email, telefone, endereco, cidade, estado, sexo, foto){

		$('#id').val(id);
		$('#nome').val(nome);
		$('#telefone').val(telefone);
		$('#cpf').val(cpf);
		$('#nascimento').val(nascimento);
		$('#email').val(email);
		$('#endereco').val(endereco);
		$('#cidade').val(cidade);
		$('#estado').val(estado);
		$('#sexo').val(sexo);
		$('#foto').val('');
		$('#target').attr('src','../painel-aluno/img/perfil/' + foto);		
		
		$('#tituloModal').text('Editar Registro');
		$('#modalForm').modal('show');
		$('#mensagem').text('');
	}


	function mostrar(nome, cpf, nascimento, email, telefone, endereco, cidade, estado, sexo, foto, data, cartao, ativo, senha){
		
		$('#nome_mostrar').text(nome);
		$('#telefone_mostrar').text(telefone);
		$('#cpf_mostrar').text(cpf);
		$('#nascimento_mostrar').text(nascimento);
		$('#email_mostrar').text(email);
		$('#senha_mostrar').text(senha);
		$('#endereco_mostrar').text(endereco);
		$('#cidade_mostrar').text(cidade);
		$('#estado_mostrar').text(estado);
		$('#sexo_mostrar').text(sexo);
		$('#data_mostrar').text(data);
		$('#cartao_mostrar').text(cartao);
		$('#ativo_mostrar').text(ativo);		
		$('#target_mostrar').attr('src','../painel-aluno/img/perfil/' + foto);

		$('#modalMostrar').modal('show');
		
	}


	function limparCampos(){
		$('#id').val('');
		$('#nome').val('');
		$('#telefone').val('');
		$('#cpf').val('');
		$('#nascimento').val('');
		$('#email').val('');
		$('#endereco').val('');
		$('#cidade').val('');
		$('#estado').val('');
		$('#sexo').val('');	
		$('#foto').val('');
		$('#target').attr('src','img/perfil/sem-perfil.jpg');		
	}



	function editarCartoes(id){
		var cartoes = $('#cartao-'+id).val();	
		$.ajax({
        url: 'paginas/' + pag + "/editar-cartoes.php",
        method: 'POST',
        data: {id, cartoes},
        dataType: "text",

        success: function (mensagem) {
            if (mensagem.trim() == "Alterado com Sucesso") {
            	 $('#mensagem-excluir').addClass('verde')
                 $('#mensagem-excluir').text(mensagem)                 
            }else{
            	 $('#mensagem-excluir').addClass('text-danger')
                 $('#mensagem-excluir').text(mensagem)  
            }               
        },

    });
	}


	function listarCur(id){
		
		
		$.ajax({
        url: 'paginas/' + pag + "/listar-cur.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(result){
        	
            $("#listar-cursosfin_" + id).html(result);
            
        }
    });
	}


</script>

