<?php 

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/csrf.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$allowedOrigins = ['https://www.sested-eja.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
	header('Access-Control-Allow-Origin: ' . $origin);
	header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key'); 
header('Content-Type: application/json; charset=utf-8');  

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit();
}

$apiToken = env('API_TOKEN', '');
$publicEndpoints = [
	'/api/login/login.php',
];
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptName, '/api/') !== false && !in_array($scriptName, $publicEndpoints, true)) {
	@session_start();
	if (!empty($_SESSION['id'])) {
		csrf_token();
	}
	if (!empty($_SESSION['id'])) {
		// Sessao valida, permite acesso na API
	} else {
		$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
		$token = '';
		if (preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
			$token = trim($matches[1]);
		} elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
			$token = trim($_SERVER['HTTP_X_API_KEY']);
		}

		if ($apiToken === '' || $token === '' || !hash_equals($apiToken, $token)) {
			http_response_code(401);
			echo json_encode(['success' => false, 'message' => 'Nao autorizado.']);
			exit();
		}
	}

	if (!empty($_SESSION['id'])) {
		csrf_require(true);
	}
}


date_default_timezone_set('America/Sao_Paulo');



$usuario = env('DB_USER', '');
$senha = env('DB_PASS', '');
$banco = env('DB_NAME', '');
$servidor = env('DB_HOST', '');
try {
	$dsn = "mysql:host=$servidor;dbname=$banco;charset=utf8mb4";
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
	];
	$pdo = new PDO($dsn, "$usuario", "$senha", $options);

} catch (Exception $e) {
	echo 'Erro ao conectar com o banco!!' .$e;
}

$url_sistema = "http://$_SERVER[HTTP_HOST]/";
$url = explode("//", $url_sistema);
if($url[1] == 'localhost/'){
	$url_sistema = "http://$_SERVER[HTTP_HOST]/portalead/";
}

//INSERIR DADOS INICIAIS NA TABELA CONFIG
$query = $pdo->query("SELECT * FROM config");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	$stmt = $pdo->prepare("INSERT INTO config SET nome_sistema = :nome_sistema, tel_sistema = :tel_sistema, email_sistema = :email_sistema, logo = 'logo.png', icone = 'favicon.ico', logo_rel = 'logo.jpg', itens_pag = '18', cartoes_fidelidade = '5', valor_max_cartao = '100', total_emails_por_envio = '480', intervalo_envio_email = '70', dias_email_matricula = '3', dias_excluir_matricula = '30', script_dia = curDate()");
	$stmt->execute([
		':nome_sistema' => $nome_sistema ?? '',
		':tel_sistema' => $tel_sistema ?? '',
		':email_sistema' => $email_sistema ?? '',
	]);
}else{
$nome_sistema = $res[0]['nome_sistema'];
$email_sistema = $res[0]['email_sistema'];
$tel_sistema = $res[0]['tel_sistema'];	
$cnpj_sistema = $res[0]['cnpj_sistema'];
$tipo_chave_pix = $res[0]['tipo_chave_pix'];
$chave_pix = $res[0]['chave_pix'];
$facebook_sistema = $res[0]['facebook'];
$instagram_sistema = $res[0]['instagram'];
$youtube_sistema = $res[0]['youtube'];
$itens_pag = $res[0]['itens_pag'];
$video_sobre = $res[0]['video_sobre'];
$aulas_lib = $res[0]['aulas_liberadas'];
$itens_rel = $res[0]['itens_relacionados'];
$desconto_pix = $res[0]['desconto_pix'];
$email_adm_mat = $res[0]['email_adm_mat'];
$cartoes_fidelidade = $res[0]['cartoes_fidelidade'];
$taxa_mp = $res[0]['taxa_mp'];
$taxa_paypal = $res[0]['taxa_paypal'];
$taxa_boleto = $res[0]['taxa_boleto'];
$valor_max_cartao = $res[0]['valor_max_cartao'];
$total_emails_por_envio = $res[0]['total_emails_por_envio'];
$intervalo_envio_email = $res[0]['intervalo_envio_email'];
$dias_email_matricula = $res[0]['dias_email_matricula'];
$dias_excluir_matricula = $res[0]['dias_excluir_matricula'];
$script_dia = $res[0]['script_dia'];
$professor_cad = $res[0]['professor_cad'];
$comissao_professor = $res[0]['comissao_professor'];
$dia_pgto_comissao = $res[0]['dia_pgto_comissao'];

$tel_whatsapp = '55'.preg_replace('/[ ()-]+/' , '' , $tel_sistema);
}


?>
