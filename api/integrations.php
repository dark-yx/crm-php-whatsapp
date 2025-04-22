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

// Obtener el tipo de integración
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Manejar las diferentes operaciones
switch ($method) {
    case 'GET':
        // Obtener integraciones
        if ($type) {
            $integration = fetch("
                SELECT * FROM integrations 
                WHERE type = ? AND status = 'active'
            ", [$type]);
            
            if ($integration) {
                echo json_encode($integration);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Integración no encontrada']);
            }
        } else {
            $integrations = fetchAll("
                SELECT * FROM integrations 
                WHERE status = 'active'
            ");
            echo json_encode($integrations);
        }
        break;
        
    case 'POST':
        // Crear o actualizar una integración
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['type']) || empty($data['name']) || empty($data['credentials'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit();
        }
        
        try {
            // Validar credenciales según el tipo
            $credentials = json_decode($data['credentials'], true);
            $valid = false;
            
            switch ($data['type']) {
                case 'whatsapp':
                    if (!empty($credentials['phone_number_id']) && !empty($credentials['access_token'])) {
                        $whatsapp = new WhatsAppIntegration(
                            $credentials['phone_number_id'],
                            $credentials['access_token']
                        );
                        $valid = $whatsapp->sendMessage('test', 'Test message');
                    }
                    break;
                    
                case 'telegram':
                    if (!empty($credentials['bot_token'])) {
                        $telegram = new TelegramIntegration($credentials['bot_token']);
                        $valid = $telegram->sendMessage('test', 'Test message');
                    }
                    break;
                    
                case 'instagram':
                    if (!empty($credentials['access_token'])) {
                        $instagram = new InstagramIntegration($credentials['access_token']);
                        $valid = $instagram->sendMessage('test', 'Test message');
                    }
                    break;
                    
                case 'messenger':
                    if (!empty($credentials['page_token'])) {
                        $messenger = new MessengerIntegration($credentials['page_token']);
                        $valid = $messenger->sendMessage('test', 'Test message');
                    }
                    break;
                    
                case 'openai':
                    if (!empty($credentials['api_key'])) {
                        $openai = new OpenAIIntegration($credentials['api_key']);
                        $valid = $openai->generateResponse('Test');
                    }
                    break;
            }
            
            if (!$valid) {
                throw new Exception('Credenciales inválidas');
            }
            
            if (isset($data['id'])) {
                // Actualizar integración existente
                query("
                    UPDATE integrations 
                    SET name = ?, credentials = ?, status = ?
                    WHERE id = ?
                ", [
                    $data['name'],
                    $data['credentials'],
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                $integration_id = $data['id'];
            } else {
                // Crear nueva integración
                query("
                    INSERT INTO integrations (type, name, credentials, status)
                    VALUES (?, ?, ?, ?)
                ", [
                    $data['type'],
                    $data['name'],
                    $data['credentials'],
                    $data['status'] ?? 'active'
                ]);
                
                $integration_id = $pdo->lastInsertId();
            }
            
            echo json_encode(['success' => true, 'id' => $integration_id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar la integración: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Eliminar una integración
        if (!$type) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de integración requerido']);
            exit();
        }
        
        try {
            query("
                UPDATE integrations 
                SET status = 'inactive'
                WHERE type = ?
            ", [$type]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la integración: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
} 