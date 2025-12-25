$(document).ready(function () {
    $('#telefone').mask('(00) 0.0000-0000');
    $('#cpf').mask('000.000.000-00');  
    $('#cnpj').mask('00.000.000/0000-00');
     $('#cep').mask('00000-000');
    $('#nascimento').mask('00/00/0000');
    $('#expedicao').mask('00/00/0000');


    $('#cnpj_sistema').mask('00.000.000/0000-00');
    $('#tel_sistema').mask('(00) 0.0000-0000');
    $('#cep_sistema').mask('00000-000');
    $('#nascimento_sistema').mask('00/00/0000');
    $('#expedicao_sistema').mask('00/00/0000');

     $('#telefone_usuario').mask('(00) 0.0000-0000');
    $('#cpf_usuario').mask('000.000.000-00');
    $('#cep_usuario').mask('00000-000');
    $('#nascimento_usuario').mask('00/00/0000');
    $('#expedicao_usuario').mask('00/00/0000');

    $('#cep_usu').mask('00000-000');
    $('#expedicao_usu').mask('00/00/0000');
    $('#nascimento_usu').mask('00/00/0000');
    $('#telefone_usu').mask('(00) 0.0000-0000');
    $('#cpf_usu').mask('000.000.000-00');
});
