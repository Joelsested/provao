<?php
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil');

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Histórico Escolar - SESTED</title>
  <style>
    @page {
      size: A4;
      margin: 4mm;
    }

    * {
      box-sizing: border-box;
    }



    .document-container {
      width: 100%;
      max-width: 210mm;
      margin: 0 auto;
    }

    .logo-header {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;
      padding-bottom: 4px;
      margin-bottom: 34px;
    }

    .logo-header img {
      width: 50px;
      height: 50px;
      flex-shrink: 0;
    }

    .logo-header span {
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
      line-height: 1.2;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid black;
      font-size: 12px;
    }

    td,
    th {
      border: 0.5px solid black;
      padding: 1px 2px;
      /* vertical-align: top; */
      text-align: left;
      line-height: 1.1;
    }

    .header-row {
      background-color: #f0f0f0;
    }

    .bold {
      font-weight: bold;
    }

    .center {
      text-align: center;
    }

    .small-text {
      font-size: 9px;
    }

    .rotated-text {
      writing-mode: vertical-lr;
      text-orientation: mixed;
      text-align: center;
      width: 18px;
      font-size: 9px;
      padding: 1px;
    }

    .grades-section td {
      text-align: center;
      font-weight: bold;
    }

    .subject-area {
      background-color: #f9f9f9;
      font-weight: bold;
      text-align: center;
      writing-mode: vertical-lr;
      text-orientation: mixed;
      font-size: 9px;
      width: 22px;
    }

    .signature-section {
      text-align: center;
      padding-top: 3px;
    }

    .signature-section p {
      margin: 2px 0;
      font-size: 9px;
    }

    .final-logo {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 5px;
      margin: 2px 0;
    }

    .final-logo img {
      width: 25px;
      height: 25px;
    }

    .final-logo span {
      font-size: 10px;
      font-weight: bold;
    }

    /* Media query para impressão */


    .uppercase {
      text-transform: uppercase;
    }

    .historic-title {
      padding: 20px;
    }

    .data-display {
      font-size: 12px;
      padding: 1px;
      font-weight: bold;

    }

    tr td.data-display:last-of-type {
      /* background: yellow; */
      /* exemplo */
    }
  </style>
</head>

<body>
  <button onclick="imprimirModal()">Imprimir</button>
  <div class="document-container" id="conteudoModal">
    <div class="logo-header">
      <img src="https://sested-eja.com/img/logo.jpg" alt="Logo SESTED" />
      <span>SESTED - Sistema de Ensino Superior Tecnológico e Educacional</span>
    </div>
    <table>
      <tr>
        <td colspan="3" class="" style="padding: 3px;">NOME: <b>SESTED</b></td>
        <td colspan="22" class="">
          AUTORIZAÇÃO: <b>PARECER CEB/CEE/RO Nº 041/18 e RESOLUÇÃO CEB/CEE/RO N°
            1296/21</b>
        </td>
      </tr>
      <tr>
        <td colspan="3" class="" style="padding: 3px;">CNPJ: <b>07.158.229/0001-06</b></td>
        <td colspan="14" class="">MUNICÍPIO: <b>BURITIS/RO</b></td>
        <td colspan="7" class="">CNPJ: <b>07.158.229/0001-06</b></td>
      </tr>
      <tr>
        <td colspan="17" class="" style="padding: 3px;">ENDEREÇO: <b>RUA NOVA UNIÃO - 2024 SETOR 02</b></td>
        <td colspan="8" class="">TEL.: <b>69 -9-92474696</b></td>
      </tr>
      <tr>
        <td colspan="17" class="uppercase" style="padding: 3px;">ALUNO (a): <b><?php echo $dadosAluno['nome']; ?></b>
        </td>
        <td colspan="8">SEXO: <b><?php echo $dadosAluno['sexo']; ?></b></td>
      </tr>
      <tr>
        <td colspan="3" class="uppercase" style="padding: 3px;">DATA DE NASC:
          <b><?php echo $dadosAluno['dataNasc']; ?></b>
        </td>
        <td colspan="14" class="uppercase">NATURALIDADE: <b><?php echo $dadosAluno['naturalidade']; ?></b></td>
        <td colspan="8" class="">NACIONALIDADE: <b>BRASILEIRO</b></td>
      </tr>
      <tr>
        <td colspan="10" style="padding: 3px;">CERTIDÃO DE NASCIMENTO:
          <b><?php echo $dadosAdicionais['certidao_nascimento']; ?></b>
        </td>
        <td colspan="14" style="padding: 3px;">CPF: <b><?php echo $dadosAluno['cpf']; ?></b></td>
      </tr>
      <tr>
        
        <td colspan="10">RG: <b><?php echo $dadosAluno['rg']; ?></b></td>
        <td colspan="10">ORGÃO EXP: <b><?php echo $dadosAluno['orgao_emissor'] ?? ''; ?></b></td>
        <td colspan="4">EMISSÃO: <b><?php echo $dadosAluno['expedicao'] ?? ''; ?></b></td>
      </tr>
      <tr>
        <td colspan="5" class="uppercase" style="padding: 3px;">PAI: <b><?php echo $dadosAluno['pai']; ?></b></td>
        <td colspan="19" class="uppercase">MÃE: <b><?php echo $dadosAluno['mae']; ?></b></td>
      </tr>

      <tr>
        <td colspan="25" class="bold center" style="font-size: 14px; padding: 15px;">HISTÓRICO ESCOLAR DO ENSINO
          MÉDIO</td>
      </tr>

      <!-- Cabeçalho da tabela de notas -->
      <tr class="header-row" ">
   <td rowspan=" 12" colspan="2" class="rotated-text">BASE NACIONAL</td>
        <td colspan="2" rowspan="3" class="center bold">ÁREAS DE CONHECIMENTO</td>
        <td colspan="7" rowspan="3" class="center bold">COMPONENTES CURRICULARES</td>
        <td colspan="13" class="center bold">ANOS/ CARGA HORÁRIA</td>
      </tr>
      <tr class="header-row">
        <td colspan="4" class="center bold">1ª SÉRIE</td>
        <td colspan="5" class="center bold">2ª SÉRIE</td>
        <td colspan="4" class="center bold">3ª SÉRIE</td>
      </tr>
      <tr class="header-row">
        <td colspan="2" class="center bold">NOTA</td>
        <td colspan="2" class="center bold small-text">CH<br>DATA</td>
        <td colspan="4" class="center bold">NOTA</td>
        <td class="center bold small-text">CH<br>DATA</td>
        <td class="center bold">NOTA</td>
        <td colspan="3" class="center bold small-text">CH<br>DATA</td>
      </tr>

      <!-- Linguagens e Tecnologias -->
<tr>
  <td colspan="2" rowspan="5" class="bold center">LINGUAGENS e TECNOLOGIAS</td>
  <td colspan="7" style="padding: 4px;">Língua Portuguesa</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['lingua_portuguesa']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
    <?php echo isset($notas['lingua_portuguesa']['data']) ? date('d-m-Y', strtotime($notas['lingua_portuguesa']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['lingua_portuguesa']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
    <?php echo isset($notas['lingua_portuguesa']['data']) ? date('d-m-Y', strtotime($notas['lingua_portuguesa']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['lingua_portuguesa']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
    <?php echo isset($notas['lingua_portuguesa']['data']) ? date('d-m-Y', strtotime($notas['lingua_portuguesa']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Arte</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['arte']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['arte']['data']) ? date('d-m-Y', strtotime($notas['arte']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['arte']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['arte']['data']) ? date('d-m-Y', strtotime($notas['arte']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['arte']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['arte']['data']) ? date('d-m-Y', strtotime($notas['arte']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Língua Inglesa</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['lingua_inglesa']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['lingua_inglesa']['data']) ? date('d-m-Y', strtotime($notas['lingua_inglesa']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['lingua_inglesa']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['lingua_inglesa']['data']) ? date('d-m-Y', strtotime($notas['lingua_inglesa']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['lingua_inglesa']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['lingua_inglesa']['data']) ? date('d-m-Y', strtotime($notas['lingua_inglesa']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Língua Espanhola</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['lingua_espanhola']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['lingua_espanhola']['data']) ? date('d-m-Y', strtotime($notas['lingua_espanhola']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['lingua_espanhola']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['lingua_espanhola']['data']) ? date('d-m-Y', strtotime($notas['lingua_espanhola']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['lingua_espanhola']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['lingua_espanhola']['data']) ? date('d-m-Y', strtotime($notas['lingua_espanhola']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Educação Física</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['educacao_fisica']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['educacao_fisica']['data']) ? date('d-m-Y', strtotime($notas['educacao_fisica']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['educacao_fisica']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['educacao_fisica']['data']) ? date('d-m-Y', strtotime($notas['educacao_fisica']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['educacao_fisica']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['educacao_fisica']['data']) ? date('d-m-Y', strtotime($notas['educacao_fisica']['data'])) : '' ?>
  </td>
</tr>

<!-- Matemática -->
<tr>
  <td colspan="2" class="center bold" style="padding: 4px;">MATEMÁTICA</td>
  <td colspan="7">Matemática</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['matematica']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['matematica']['data']) ? date('d-m-Y', strtotime($notas['matematica']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['matematica']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['matematica']['data']) ? date('d-m-Y', strtotime($notas['matematica']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['matematica']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['matematica']['data']) ? date('d-m-Y', strtotime($notas['matematica']['data'])) : '' ?>
  </td>
</tr>

<!-- Ciências da Natureza -->
<tr>
  <td colspan="2" rowspan="3" class="bold center">CIÊNCIAS DA NATUREZA</td>
  <td colspan="7">Química</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['quimica']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['quimica']['data']) ? date('d-m-Y', strtotime($notas['quimica']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['quimica']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['quimica']['data']) ? date('d-m-Y', strtotime($notas['quimica']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['quimica']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['quimica']['data']) ? date('d-m-Y', strtotime($notas['quimica']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Física</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['fisica']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['fisica']['data']) ? date('d-m-Y', strtotime($notas['fisica']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['fisica']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['fisica']['data']) ? date('d-m-Y', strtotime($notas['fisica']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['fisica']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['fisica']['data']) ? date('d-m-Y', strtotime($notas['fisica']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Biologia</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['biologia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['biologia']['data']) ? date('d-m-Y', strtotime($notas['biologia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['biologia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['biologia']['data']) ? date('d-m-Y', strtotime($notas['biologia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['biologia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['biologia']['data']) ? date('d-m-Y', strtotime($notas['biologia']['data'])) : '' ?>
  </td>
</tr>

<!-- Ciências Humanas -->
<tr>
  <td rowspan="2" colspan="2" class="rotated-text">SUB TOTAL</td>
  <td colspan="2" rowspan="7" class="bold center">CIÊNCIAS HUMANAS</td>
  <td colspan="7">História</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['historia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['historia']['data']) ? date('d-m-Y', strtotime($notas['historia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['historia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['historia']['data']) ? date('d-m-Y', strtotime($notas['historia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['historia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['historia']['data']) ? date('d-m-Y', strtotime($notas['historia']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Geografia</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['geografia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['geografia']['data']) ? date('d-m-Y', strtotime($notas['geografia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['geografia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['geografia']['data']) ? date('d-m-Y', strtotime($notas['geografia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['geografia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['geografia']['data']) ? date('d-m-Y', strtotime($notas['geografia']['data'])) : '' ?>
  </td>
</tr>

<!-- Parte Diversificada -->
<tr>
  <td rowspan="5" colspan="2" class="rotated-text">PARTE DIVERSIFICADA</td>
  <td colspan="7">Sociologia</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['sociologia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['sociologia']['data']) ? date('d-m-Y', strtotime($notas['sociologia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['sociologia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['sociologia']['data']) ? date('d-m-Y', strtotime($notas['sociologia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['sociologia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['sociologia']['data']) ? date('d-m-Y', strtotime($notas['sociologia']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Filosofia</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['filosofia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['filosofia']['data']) ? date('d-m-Y', strtotime($notas['filosofia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['filosofia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['filosofia']['data']) ? date('d-m-Y', strtotime($notas['filosofia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['filosofia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['filosofia']['data']) ? date('d-m-Y', strtotime($notas['filosofia']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">- - - - - -</td>
  <td colspan="2" class="center">-</td>
  <td colspan="2" class="center">-</td>
  <td colspan="4" class="center">-</td>
  <td class="center">-</td>
  <td class="center">-</td>
  <td colspan="3" class="center">-</td>
</tr>

<tr>
  <td colspan="7">História do Estado de Rondônia</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['historia_do_estado_de_rondonia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['historia_do_estado_de_rondonia']['data']) ? date('d-m-Y', strtotime($notas['historia_do_estado_de_rondonia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['historia_do_estado_de_rondonia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['historia_do_estado_de_rondonia']['data']) ? date('d-m-Y', strtotime($notas['historia_do_estado_de_rondonia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['historia_do_estado_de_rondonia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['historia_do_estado_de_rondonia']['data']) ? date('d-m-Y', strtotime($notas['historia_do_estado_de_rondonia']['data'])) : '' ?>
  </td>
</tr>

<tr>
  <td colspan="7">Geografia do Estado de Rondônia</td>
  <td colspan="2" class="bold center">
    <?php echo $notas['geografia_do_estado_de_rondonia']['serie1'] ?? '' ?>
  </td>
  <td colspan="2" class="center data-display">
    <?php echo isset($notas['geografia_do_estado_de_rondonia']['data']) ? date('d-m-Y', strtotime($notas['geografia_do_estado_de_rondonia']['data'])) : '' ?>
  </td>
  <td colspan="4" class="bold center">
    <?php echo $notas['geografia_do_estado_de_rondonia']['serie2'] ?? '' ?>
  </td>
  <td class="center data-display">
    <?php echo isset($notas['geografia_do_estado_de_rondonia']['data']) ? date('d-m-Y', strtotime($notas['geografia_do_estado_de_rondonia']['data'])) : '' ?>
  </td>
  <td class="bold center">
    <?php echo $notas['geografia_do_estado_de_rondonia']['serie3'] ?? '' ?>
  </td>
  <td colspan="3" class="center data-display">
    <?php echo isset($notas['geografia_do_estado_de_rondonia']['data']) ? date('d-m-Y', strtotime($notas['geografia_do_estado_de_rondonia']['data'])) : '' ?>
  </td>
</tr>


      <!-- Totalizadores -->
      <tr>
        <td colspan="12" class="bold center">Dias Letivos</td>
        <td colspan="2" class="center">-</td>
        <td colspan="2" class="center">-</td>
        <td colspan="4" class="center">-</td>
        <td class="center">-</td>
        <td class="center bold">-</td>
        <td colspan="3" class="center">-</td>
      </tr>
      <tr>
        <td colspan="12" class="bold center">Carga Horária Anual</td>
        <td colspan="2" class="center">-</td>
        <td colspan="2" class="center">-</td>
        <td colspan="4" class="center">-</td>
        <td class="center">-</td>
        <td class="center bold">-</td>
        <td colspan="3" class="center">-</td>
      </tr>
      <tr>
        <td colspan="12" class="bold center">Carga Horária Total</td>
        <td colspan="2" class="center">-</td>
        <td colspan="2" class="center">-</td>
        <td colspan="4" class="center">-</td>
        <td class="center">-</td>
        <td class="center">-</td>
        <td colspan="3" class="center">-</td>
      </tr>
      <tr>
        <td colspan="12" class="bold center">RESULTADO FINAL</td>
        <td colspan="2" class="center">-</td>
        <td colspan="2" class="center">-</td>
        <td colspan="4" class="center">-</td>
        <td class="center">-</td>
        <td class="center">-</td>
        <td colspan="3" class="center bold" style="font-weight: bold;">
          <?php echo $dadosAdicionais['situacao'] ?? '' ?>
        </td>
      </tr>

      <!-- Estudos Realizados -->
      <tr>
        <td rowspan="4" class="rotated-text bold">Estudos Realizados</td>
        <td colspan="2" class="center bold">ANO ESCOLAR</td>
        <td colspan="3" class="center bold">ANO</td>
        <td colspan="9" class="center bold">INSTITUIÇÃO DE ENSINO</td>
        <td colspan="8" class="center bold">MUNICÍPIO</td>
        <td colspan="2" class="center bold">UF</td>
      </tr>
      <tr>
        <td colspan="2" class="center">1ª SÉRIE</td>
        <td colspan="3" class="center">
          <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
        </td>
        <td colspan="9" class="center">
          <?php echo $dadosAdicionais['escola'] ?? '' ?>
        </td>
        <td colspan="8" class="center">
          <?php echo $dadosAdicionais['municipio'] ?? '' ?>
        </td>
        <td colspan="2" class="center">
          <?php echo $dadosAdicionais['uf'] ?? '' ?>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="center">2ª SÉRIE</td>
        <td colspan="3" class="center">
          <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
        </td>
        <td colspan="9" class="center">
          <?php echo $dadosAdicionais['escola'] ?? '' ?>
        </td>
        <td colspan="8" class="center">
          <?php echo $dadosAdicionais['municipio'] ?? '' ?>
        </td>
        <td colspan="2" class="center">
          <?php echo $dadosAdicionais['uf'] ?? '' ?>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="center">3ª SÉRIE</td>
        <td colspan="3" class="center">
          <?php echo $dadosAdicionais['anoConclusao'] ?? '' ?>
        </td>
        <td colspan="9" class="center">
          <?php echo $dadosAdicionais['escola'] ?? '' ?>
        </td>
        <td colspan="8" class="center">
          <?php echo $dadosAdicionais['municipio'] ?? '' ?>
        </td>
        <td colspan="2" class="center">
          <?php echo $dadosAdicionais['uf'] ?? '' ?>
        </td>
      </tr>

      <!-- Síntese e Observações -->
      <tr>
        <td colspan="25">SÍNTESE DO SISTEMA DE AVALIAÇÃO: Será aprovado quando obtiver média igual ou superior a
          6,0(seis), nos Exames de Conclusão de Etapas do Ensino Fundamental e do Ensino Médio.</td>
      </tr>
      <tr>
        <td colspan="25">
          <p><strong>OBSERVAÇÕES:</strong></p>
          <p>
            <?php echo $dadosAdicionais['observacoes'] ?? '' ?>
          </p>
        </td>
      </tr>

    

      <!-- Data e Assinaturas -->
      <tr>
        <td colspan="3" style="text-align: left; padding: 5px;">
          Buritis/RO, <?php echo $dadosAdicionais['data_historico'] ?? '' ?>
        </td>
        <td colspan="22">
          <table style="width: 100%; text-align: center; border: none; margin: auto;">
            <tr>
              <td style="border: none; width: 50%; text-align: center;  padding: 10px;">
                ______________________________<br>
                Laura Maria Jonjob de Souza<br>
                RG: 757423 SESDEC/RO<br>
                <strong>Diretora</strong>
              </td>
              <td style="border: none; width: 50%; text-align: center;">
                ______________________________<br>
                Daniely Jonjob da Silva<br>
                RG: 1480635 SESDEC/RO<br>
                <strong>Secretária</strong>
              </td>
            </tr>
          </table>
        </td>
      </tr>

    </table>
  </div>

  <script>
    function imprimirModal() {
      // Pega o conteúdo do modal
      var conteudo = document.getElementById("conteudoModal").innerHTML;

      // Abre uma nova janela temporária
      var tela_impressao = window.open('', '', '');

      tela_impressao.document.write('<html><head><title>Impressão</title>');
      // se precisar de CSS, você pode importar aqui:
      tela_impressao.document.write('<link rel="stylesheet" href="seu-estilo.css">');
      tela_impressao.document.write(`
              <style>
    @page {
      size: A4;
      margin: 4mm;
    }

    * {
      box-sizing: border-box;
    }



    .document-container {
      width: 100%;
      max-width: 210mm;
      margin: 0 auto;
    }

    .logo-header {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;
      padding-bottom: 4px;
      margin-bottom: 34px !important;
    }

    .logo-header img {
      width: 50px;
      height: 50px;
      flex-shrink: 0;
    }

    .logo-header span {
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
      line-height: 1.2;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid black;
      font-size: 12px;
    }

    td,
    th {
      border: 0.5px solid black;
      padding: 1px 2px;
      /* vertical-align: top; */
      text-align: left;
      line-height: 1.1;
    }

    .header-row {
      background-color: #f0f0f0;
    }

    .bold {
      font-weight: bold;
    }

    .center {
      text-align: center;
    }

    .small-text {
      font-size: 9px;
    }

    .rotated-text {
      writing-mode: vertical-lr;
      text-orientation: mixed;
      text-align: center;
      width: 18px;
      font-size: 9px;
      padding: 1px;
    }

    .grades-section td {
      text-align: center;
      font-weight: bold;
    }

    .subject-area {
      background-color: #f9f9f9;
      font-weight: bold;
      text-align: center;
      writing-mode: vertical-lr;
      text-orientation: mixed;
      font-size: 9px;
      width: 22px;
    }

    .signature-section {
      text-align: center;
      padding-top: 3px;
    }

    .signature-section p {
      margin: 2px 0;
      font-size: 9px;
    }

    .final-logo {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 5px;
      margin: 2px 0;
    }

    .final-logo img {
      width: 25px;
      height: 25px;
    }

    .final-logo span {
      font-size: 10px;
      font-weight: bold;
    }

    /* Media query para impressão */


    .uppercase {
      text-transform: uppercase;
    }

    .historic-title {
      padding: 20px;
    }

    .data-display {
      font-size: 12px;
      padding: 1px;
      font-weight: bold;

    }

    tr td.data-display:last-of-type {
      /* background: yellow; */
      /* exemplo */
    }
  </style>`);
      tela_impressao.document.write('</head><body>');
      tela_impressao.document.write(conteudo);
      tela_impressao.document.write('</body></html>');

      tela_impressao.document.close(); // fecha escrita
      tela_impressao.print(); // chama impressão
    }

  </script>

</body>

</html>