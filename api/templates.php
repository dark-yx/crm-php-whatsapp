<?php
session_start();
require_once '../config/database.php';
require_once '../config/integrations.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Manejar solicitudes GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? '';
    
    if (empty($type)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo de integración no especificado']);
        exit;
    }
    
    // Obtener configuración de la integración
    $stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = ? AND active = 1 LIMIT 1");
    $stmt->execute([$type]);
    $integration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$integration) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Integración no encontrada o no activada']);
        exit;
    }
    
    try {
        $templates = [];
        
        // Obtener plantillas según el tipo de integración
        switch ($type) {
            case 'whatsapp':
                $templates = fetchWhatsAppTemplates($integration);
                break;
            case 'telegram':
                // Para futuras implementaciones
                break;
            case 'instagram':
                // Para futuras implementaciones
                break;
            case 'messenger':
                // Para futuras implementaciones
                break;
            default:
                throw new Exception('Tipo de integración no soportado');
        }
        
        // Guardar las plantillas en la base de datos
        saveTemplates($type, $templates);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'templates' => $templates]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Para cualquier otro método HTTP
header('Content-Type: application/json');
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
exit;

/**
 * Obtiene las plantillas de WhatsApp desde la API
 */
function fetchWhatsAppTemplates($integration) {
    $api_key = $integration['api_key'];
    $business_account_id = $integration['business_account_id'];
    
    // Configuración de la solicitud a la API de WhatsApp
    $url = WHATSAPP_API_URL . '/' . WHATSAPP_API_VERSION . '/' . $business_account_id . '/message_templates';
    
    $options = [
        'http' => [
            'header'  => "Authorization: Bearer " . $api_key . "\r\n" .
                         "Content-Type: application/json\r\n",
            'method'  => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Error al conectar con la API de WhatsApp');
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        throw new Exception('Error en la API de WhatsApp: ' . ($result['error']['message'] ?? 'Error desconocido'));
    }
    
    $templates = [];
    
    // Procesar las plantillas recibidas
    if (isset($result['data']) && is_array($result['data'])) {
        foreach ($result['data'] as $template) {
            // Extraer la información relevante de cada plantilla
            $templates[] = [
                'template_id' => $template['id'],
                'name' => $template['name'],
                'status' => $template['status'],
                'category' => $template['category'] ?? 'UNKNOWN',
                'language' => $template['language'] ?? 'es',
                'components' => json_encode($template['components'] ?? []),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    return $templates;
}

/**
 * Guarda las plantillas en la base de datos
 */
function saveTemplates($type, $templates) {
    global $pdo;
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Actualizar plantillas existentes a inactivas
        $stmt = $pdo->prepare("UPDATE message_templates SET active = 0 WHERE type = ?");
        $stmt->execute([$type]);
        
        // Insertar o actualizar cada plantilla
        foreach ($templates as $template) {
            // Verificar si la plantilla ya existe
            $stmt = $pdo->prepare("SELECT id FROM message_templates WHERE type = ? AND template_id = ?");
            $stmt->execute([$type, $template['template_id']]);
            $existingTemplate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingTemplate) {
                // Actualizar plantilla existente
                $stmt = $pdo->prepare("UPDATE message_templates SET 
                    name = ?, 
                    status = ?, 
                    category = ?,
                    language = ?,
                    components = ?,
                    active = 1,
                    updated_at = ?
                    WHERE id = ?");
                $stmt->execute([
                    $template['name'],
                    $template['status'],
                    $template['category'],
                    $template['language'],
                    $template['components'],
                    $template['updated_at'],
                    $existingTemplate['id']
                ]);
            } else {
                // Insertar nueva plantilla
                $stmt = $pdo->prepare("INSERT INTO message_templates 
                    (type, template_id, name, status, category, language, components, active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?)");
                $stmt->execute([
                    $type,
                    $template['template_id'],
                    $template['name'],
                    $template['status'],
                    $template['category'],
                    $template['language'],
                    $template['components'],
                    $template['updated_at']
                ]);
            }
        }
        
        // Confirmar transacción
        $pdo->commit();
        
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $pdo->rollBack();
        throw $e;
    }
}
?> 