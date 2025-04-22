<?php
// Configuración de WhatsApp Business API
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v17.0');
define('WHATSAPP_API_VERSION', 'v17.0');

// Configuración de Telegram Bot API
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot');

// Configuración de Instagram Graph API
define('INSTAGRAM_API_URL', 'https://graph.facebook.com/v17.0');
define('INSTAGRAM_API_VERSION', 'v17.0');

// Configuración de Messenger Platform
define('MESSENGER_API_URL', 'https://graph.facebook.com/v17.0');
define('MESSENGER_API_VERSION', 'v17.0');

// Configuración de OpenAI
define('OPENAI_API_URL', 'https://api.openai.com/v1');
define('OPENAI_MODEL', 'gpt-3.5-turbo');

// Clases de integración
class WhatsAppIntegration {
    private $phoneNumberId;
    private $accessToken;
    
    public function __construct($phoneNumberId, $accessToken) {
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken = $accessToken;
    }
    
    public function sendMessage($to, $message) {
        $url = WHATSAPP_API_URL . '/' . $this->phoneNumberId . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

class TelegramIntegration {
    private $botToken;
    
    public function __construct($botToken) {
        $this->botToken = $botToken;
    }
    
    public function sendMessage($chatId, $message) {
        $url = TELEGRAM_API_URL . $this->botToken . '/sendMessage';
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

class InstagramIntegration {
    private $accessToken;
    
    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }
    
    public function sendMessage($userId, $message) {
        $url = INSTAGRAM_API_URL . '/' . $userId . '/messages';
        
        $data = [
            'message' => $message
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

class MessengerIntegration {
    private $pageToken;
    
    public function __construct($pageToken) {
        $this->pageToken = $pageToken;
    }
    
    public function sendMessage($userId, $message) {
        $url = MESSENGER_API_URL . '/me/messages';
        
        $data = [
            'recipient' => ['id' => $userId],
            'message' => ['text' => $message]
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

class OpenAIIntegration {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function generateResponse($prompt) {
        $url = OPENAI_API_URL . '/chat/completions';
        
        $data = [
            'model' => OPENAI_MODEL,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?> 