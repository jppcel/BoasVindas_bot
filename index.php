<?php 

define('BOT_TOKEN', 'XXX'); 
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/'); 

function apiRequestWebhook($method, $parameters) {
	if (!is_string($method)) {
		error_log("Nome do método deve ser uma string\n"); 
		return false; 
	} 

	if (!$parameters) {
		$parameters = array(); 
	} else if (!is_array($parameters)) {
		error_log("Os parâmetros devem ser um array\n"); 
		return false; 
	} 

	$parameters["method"] = $method; 

	header("Content-Type: application/json"); 
	echo json_encode($parameters); 

	return true; 
} 

function exec_curl_request($handle) {
	$response = curl_exec($handle); 

	if ($response === false) {
		$errno = curl_errno($handle); 
		$error = curl_error($handle); 
		error_log("Curl retornou um erro $errno: $error\n"); 
		curl_close($handle); 

		return false; 
	} 

	$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE)); 
	curl_close($handle); 

	if ($http_code >= 500) {
		// do not wat to DDOS server if something goes wrong 
		sleep(10); 
		return false; 
	} else if ($http_code != 200) {
		$response = json_decode($response, true); 
		error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n"); 
		if ($http_code == 401) {
			throw new Exception('Invalid access token provided'); 
		} 

		return false; 
	} else {
		$response = json_decode($response, true); 
		if (isset($response['description'])) {
			error_log("Request was successfull: {$response['description']}\n"); 
		} 
		$response = $response['result']; 
	} 

	return $response; 
} 

function apiRequest($method, $parameters) {
	if (!is_string($method)) {
		error_log("Method name must be a string\n"); 
		return false; 
	} 
	if (!$parameters) {
		$parameters = array(); 
	} else if (!is_array($parameters)) {
		error_log("Parameters must be an array\n"); 
		return false; 
	} 

	foreach ($parameters as $key => &$val) {
		// encoding to JSON array parameters, for example reply_markup 
		if (!is_numeric($val) && !is_string($val)) {
			$val = json_encode($val); 
		} 
	} 

	$url = API_URL.$method.'?'.http_build_query($parameters); 

	$handle = curl_init($url); 
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5); 
	curl_setopt($handle, CURLOPT_TIMEOUT, 60); 

	return exec_curl_request($handle); 
} 

function apiRequestJson($method, $parameters) {
	if (!is_string($method)) {
		error_log("Method name must be a string\n"); 
		return false; 
	} 

	if (!$parameters) {
		$parameters = array(); 
	} else if (!is_array($parameters)) {
		error_log("Parameters must be an array\n"); 
		return false; 
	} 

	$parameters["method"] = $method; 

	$handle = curl_init(API_URL); 
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5); 
	curl_setopt($handle, CURLOPT_TIMEOUT, 60); 
	curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters)); 
	curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 

	return exec_curl_request($handle); 
} 

function processMessage($message) {
	// process incoming message 
	$message_id = $message['message_id'];
	$chat_id = $message['chat']['id']; 
	$user_name = $message['from']['first_name'];

	$member_name = $message['new_chat_participant']['first_name'];
	$member_user = $message['new_chat_participant']['username'];

	if (isset($member_name)) {
		if ($member_user != 'BoasVindasBot') {
			$falas = array('Olá', 'Opa', 'Salve salve', 'Fala aí', 'Olá', 'Opa', 'Salve salve', 'Fala aí', 'Olá', 'Opa', 'Salve salve', 'Fala aí', 'Olá', 'Opa', 'Salve salve', 'Fala aí', 'Olá', 'Opa', 'Salve salve', 'Fala aí', );
			$fala = array_rand($falas);
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $falas[$fala] . ' ' . $member_name . "! \nSeja bem vindo(a) ao grupo!")); 
		} else {
			apiRequest("sendMessage", array('chat_id' => $chat_id, 'text' => "Olá, eu sou o @BoasVindasBot.\nQuando este grupo receber um novo membro, darei boas vindas a ele 😉"));
		}
	}

	if (isset($message['text'])) {
		$text = $message['text']; 

		if (strpos($text, "/start") === 0) {
			$type = $message['chat']['type'];
			if($type == 'private') {
				apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Vamos começar?\nPara que eu possa receber os novos membros em seu grupo, basta me adicionar lá 😄")); 
			} else {
				apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Você já me adicionou aqui 😄\nSempre que um novo membro entrar, darei boas vindas a ele.")); 
			}
		} else if (strpos($text, "puta")) {
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Sua mãe que é uma puta! Quer cair na mão? Perdeu a noção do perigo?")); 
		}
	}
} 

define('WEBHOOK_URL', 'SEU LINK AQUI');

if (php_sapi_name() == 'cli') {
  // if run from console, set or delete webhook
  apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
  exit;
}


$content = file_get_contents("php://input"); 
$update = json_decode($content, true); 

if (!$update) {
	// receive wrong update, must not happen 
	exit; 
} 

if (isset($update["message"])) {
	processMessage($update["message"]); 
}

?>