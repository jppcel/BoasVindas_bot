<?php 

define('BOT_TOKEN', 'XXX'); 
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/'); 

require_once 'functions.php';

function processMessage($message) {
	// process incoming message 
	$message_id = $message['message_id'];
	$chat_id = $message['chat']['id']; 
	$user_name = $message['from']['first_name'];

	@$member_name = $message['new_chat_participant']['first_name'];
	@$member_user = $message['new_chat_participant']['username'];

	if (isset($member_name)) {
		if ($member_user != 'BoasVindas_bot') {
			$falas = array('Olá', 'Opa', 'Salve salve', 'Fala aí');
			$keys = array_keys($falas);
			shuffle($keys);
			$fala = array_rand($keys);
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $falas[$fala] . ' ' . $member_name . "! \nSeja bem vindo(a) ao grupo!")); 
		} else {
			apiRequest("sendMessage", array('chat_id' => $chat_id, 'text' => "Olá, eu sou o @BoasVindasBot.\nQuando este grupo receber um novo membro, darei boas vindas a ele 😉"));
		}
	}

	if (isset($message['left_chat_participant'])) {
		apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'No céu tem pão? E morreu.')); 
	}

	if (isset($message['text'])) {
		$text = $message['text']; 
		if (strpos($text, "/start") === 0) {
			$type = $message['chat']['type'];
			if($type == 'private') {
				apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Vamos começar?\nPara que eu possa receber os novos membros em seu grupo, basta me adicionar lá 😄 É só clicar:\n\nhttp://telegram.me/BoasVindas_bot?startgroup=1")); 
			}
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
