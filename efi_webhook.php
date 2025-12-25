<?php
require_once __DIR__ . '/config/webhook.php';
webhook_require_token();

function resposta($status, $mensagem, $dados)
{
  $resposta['status'] = $status;
  $resposta['mensagem'] = $mensagem;
  $resposta['dados'] = $dados;
  $json_resposta = '<pre>' . json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</pre>';

  header("HTTP/1.1 " . $status);
  echo $json_resposta;
}

function salvar($dados)
{
  // Crie um arquivo .;json para salvar as informações
  $nomeArquivo = './efi.json';
  $dadosGravados = json_decode(file_get_contents($nomeArquivo), true);
  $arquivo = fopen($nomeArquivo, 'w');

  // Incrementa as informações enviadas com o que já havia gravado
  array_push($dadosGravados, $dados);

  if (fwrite($arquivo, json_encode($dadosGravados))) {
    resposta(200, "Requisição realizada com sucesso!", $dados);
  } else {
    resposta(300, "Falha ao salvar os dados da requisição.", $dados);
  }

  fclose($arquivo);
}

function requisicao($metodo, $body, $parametros)
{
  switch ($metodo) {
    case 'POST':
      salvar($body);
      break;
    case 'GET':
      resposta(200, "Requisição realizada com sucesso!", $body);
      break;
  }
}

// Obtém o método HTTP, body e parâmetros da requisição
$metodo = $_SERVER['REQUEST_METHOD'];
$parametros = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$body = json_decode(file_get_contents('php://input'), true);

try {
  requisicao($metodo, $body, $parametros);
} catch (Exception $e) {
  resposta(400, $e->getMessage(), $e);
}
