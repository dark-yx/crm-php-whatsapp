<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/integrations.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Obtener el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Obtener parámetros
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : null;
$contact_id = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : null;

// Manejar las diferentes operaciones
switch ($method) {
    case 'GET':
        // Obtener mensajes de una conversación
        if ($conversation_id) {
            $messages = fetchAll("
                SELECT m.*, u.name as sender_name
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ", [$conversation_id]);
            
            echo json_encode($messages);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID de conversación requerido']);
        }
        break;
        
    case 'POST':
        // Enviar un mensaje
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['conversation_id']) || empty($data['message'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit();
        }
        
        try {
            // Obtener información de la conversación
            $conversation = fetch("
                SELECT c.*, i.type, i.credentials
                FROM conversations c
                JOIN integrations i ON c.channel = i.type
                WHERE c.id = ? AND i.status = 'active'
            ", [$data['conversation_id']]);
            
            if (!$conversation) {
                throw new Exception('Conversación no encontrada');
            }
            
            // Obtener información del contacto
            $contact = fetch("
                SELECT * FROM contacts 
                WHERE id = ?
            ", [$conversation['contact_id']]);
            
            if (!$contact) {
                throw new Exception('Contacto no encontrado');
            }
            
            // Enviar mensaje según el canal
            $credentials = json_decode($conversation['credentials'], true);
            $sent = false;
            
            switch ($conversation['channel']) {
                case 'whatsapp':
                    $whatsapp = new WhatsAppIntegration(
                        $credentials['phone_number_id'],
                        $credentials['access_token']
                    );
                    $sent = $whatsapp->sendMessage($contact['phone'], $data['message']);
                    break;
                    
                case 'telegram':
                    $telegram = new TelegramIntegration($credentials['bot_token']);
                    $sent = $telegram->sendMessage($contact['telegram_id'], $data['message']);
                    break;
                    
                case 'instagram':
                    $instagram = new InstagramIntegration($credentials['access_token']);
                    $sent = $instagram->sendMessage($contact['instagram_id'], $data['message']);
                    break;
                    
                case 'messenger':
                    $messenger = new MessengerIntegration($credentials['page_token']);
                    $sent = $messenger->sendMessage($contact['messenger_id'], $data['message']);
                    break;
            }
            
            if (!$sent) {
                throw new Exception('Error al enviar el mensaje');
            }
            
            // Guardar mensaje en la base de datos
            query("
                INSERT INTO messages (conversation_id, sender_id, message, type, status)
                VALUES (?, ?, ?, ?, 'sent')
            ", [
                $data['conversation_id'],
                $_SESSION['user_id'],
                $data['message'],
                $data['type'] ?? 'text'
            ]);
            
            // Actualizar última actividad de la conversación
            query("
                UPDATE conversations 
                SET last_message_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$data['conversation_id']]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al enviar el mensaje: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Actualizar estado de un mensaje
        if (empty($data['message_id']) || empty($data['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit();
        }
        
        try {
            query("
                UPDATE messages 
                SET status = ?
                WHERE id = ?
            ", [$data['status'], $data['message_id']]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el mensaje: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
} 