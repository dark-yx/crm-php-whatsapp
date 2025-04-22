<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/integrations.php';

// Obtener el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Obtener el tipo de webhook
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Verificar si es una solicitud de verificación
if ($method === 'GET' && isset($_GET['hub_challenge'])) {
    echo $_GET['hub_challenge'];
    exit();
}

// Procesar el webhook según el tipo
switch ($type) {
    case 'whatsapp':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!empty($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            $phone = $message['from'];
            $text = $message['text']['body'];
            
            // Buscar o crear contacto
            $contact = fetch("
                SELECT * FROM contacts 
                WHERE phone = ?
            ", [$phone]);
            
            if (!$contact) {
                query("
                    INSERT INTO contacts (name, phone, source)
                    VALUES (?, ?, 'whatsapp')
                ", [$phone, $phone]);
                
                $contact_id = $pdo->lastInsertId();
            } else {
                $contact_id = $contact['id'];
            }
            
            // Buscar o crear conversación
            $conversation = fetch("
                SELECT * FROM conversations 
                WHERE contact_id = ? AND channel = 'whatsapp'
            ", [$contact_id]);
            
            if (!$conversation) {
                query("
                    INSERT INTO conversations (contact_id, channel, status)
                    VALUES (?, 'whatsapp', 'active')
                ", [$contact_id]);
                
                $conversation_id = $pdo->lastInsertId();
            } else {
                $conversation_id = $conversation['id'];
            }
            
            // Guardar mensaje
            query("
                INSERT INTO messages (conversation_id, message, type, status)
                VALUES (?, ?, 'text', 'received')
            ", [$conversation_id, $text]);
            
            // Actualizar última actividad
            query("
                UPDATE conversations 
                SET last_message_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$conversation_id]);
        }
        break;
        
    case 'telegram':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!empty($data['message'])) {
            $message = $data['message'];
            $chat_id = $message['chat']['id'];
            $text = $message['text'];
            
            // Buscar o crear contacto
            $contact = fetch("
                SELECT * FROM contacts 
                WHERE telegram_id = ?
            ", [$chat_id]);
            
            if (!$contact) {
                query("
                    INSERT INTO contacts (name, telegram_id, source)
                    VALUES (?, ?, 'telegram')
                ", [$message['chat']['first_name'], $chat_id]);
                
                $contact_id = $pdo->lastInsertId();
            } else {
                $contact_id = $contact['id'];
            }
            
            // Buscar o crear conversación
            $conversation = fetch("
                SELECT * FROM conversations 
                WHERE contact_id = ? AND channel = 'telegram'
            ", [$contact_id]);
            
            if (!$conversation) {
                query("
                    INSERT INTO conversations (contact_id, channel, status)
                    VALUES (?, 'telegram', 'active')
                ", [$contact_id]);
                
                $conversation_id = $pdo->lastInsertId();
            } else {
                $conversation_id = $conversation['id'];
            }
            
            // Guardar mensaje
            query("
                INSERT INTO messages (conversation_id, message, type, status)
                VALUES (?, ?, 'text', 'received')
            ", [$conversation_id, $text]);
            
            // Actualizar última actividad
            query("
                UPDATE conversations 
                SET last_message_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$conversation_id]);
        }
        break;
        
    case 'instagram':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!empty($data['entry'][0]['messaging'][0])) {
            $message = $data['entry'][0]['messaging'][0];
            $sender_id = $message['sender']['id'];
            $text = $message['message']['text'];
            
            // Buscar o crear contacto
            $contact = fetch("
                SELECT * FROM contacts 
                WHERE instagram_id = ?
            ", [$sender_id]);
            
            if (!$contact) {
                query("
                    INSERT INTO contacts (name, instagram_id, source)
                    VALUES (?, ?, 'instagram')
                ", [$sender_id, $sender_id]);
                
                $contact_id = $pdo->lastInsertId();
            } else {
                $contact_id = $contact['id'];
            }
            
            // Buscar o crear conversación
            $conversation = fetch("
                SELECT * FROM conversations 
                WHERE contact_id = ? AND channel = 'instagram'
            ", [$contact_id]);
            
            if (!$conversation) {
                query("
                    INSERT INTO conversations (contact_id, channel, status)
                    VALUES (?, 'instagram', 'active')
                ", [$contact_id]);
                
                $conversation_id = $pdo->lastInsertId();
            } else {
                $conversation_id = $conversation['id'];
            }
            
            // Guardar mensaje
            query("
                INSERT INTO messages (conversation_id, message, type, status)
                VALUES (?, ?, 'text', 'received')
            ", [$conversation_id, $text]);
            
            // Actualizar última actividad
            query("
                UPDATE conversations 
                SET last_message_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$conversation_id]);
        }
        break;
        
    case 'messenger':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!empty($data['entry'][0]['messaging'][0])) {
            $message = $data['entry'][0]['messaging'][0];
            $sender_id = $message['sender']['id'];
            $text = $message['message']['text'];
            
            // Buscar o crear contacto
            $contact = fetch("
                SELECT * FROM contacts 
                WHERE messenger_id = ?
            ", [$sender_id]);
            
            if (!$contact) {
                query("
                    INSERT INTO contacts (name, messenger_id, source)
                    VALUES (?, ?, 'messenger')
                ", [$sender_id, $sender_id]);
                
                $contact_id = $pdo->lastInsertId();
            } else {
                $contact_id = $contact['id'];
            }
            
            // Buscar o crear conversación
            $conversation = fetch("
                SELECT * FROM conversations 
                WHERE contact_id = ? AND channel = 'messenger'
            ", [$contact_id]);
            
            if (!$conversation) {
                query("
                    INSERT INTO conversations (contact_id, channel, status)
                    VALUES (?, 'messenger', 'active')
                ", [$contact_id]);
                
                $conversation_id = $pdo->lastInsertId();
            } else {
                $conversation_id = $conversation['id'];
            }
            
            // Guardar mensaje
            query("
                INSERT INTO messages (conversation_id, message, type, status)
                VALUES (?, ?, 'text', 'received')
            ", [$conversation_id, $text]);
            
            // Actualizar última actividad
            query("
                UPDATE conversations 
                SET last_message_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$conversation_id]);
        }
        break;
}

// Responder con éxito
http_response_code(200);
echo json_encode(['success' => true]); 