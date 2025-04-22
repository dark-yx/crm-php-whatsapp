<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Obtener el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Obtener el ID del embudo si existe
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Manejar las diferentes operaciones
switch ($method) {
    case 'GET':
        // Obtener un embudo específico o todos los embudos
        if ($id) {
            $funnel = fetch("
                SELECT f.*, 
                       (SELECT COUNT(*) FROM leads WHERE funnel_id = f.id) as total_leads,
                       (SELECT COUNT(*) FROM leads WHERE funnel_id = f.id AND status = 'won') as won_leads
                FROM funnels f
                WHERE f.id = ?
            ", [$id]);
            
            if ($funnel) {
                // Obtener las etapas del embudo
                $funnel['stages'] = fetchAll("
                    SELECT * FROM funnel_stages 
                    WHERE funnel_id = ? 
                    ORDER BY order_position
                ", [$id]);
                
                echo json_encode($funnel);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Embudo no encontrado']);
            }
        } else {
            $funnels = fetchAll("
                SELECT f.*, 
                       (SELECT COUNT(*) FROM leads WHERE funnel_id = f.id) as total_leads,
                       (SELECT COUNT(*) FROM leads WHERE funnel_id = f.id AND status = 'won') as won_leads
                FROM funnels f
                ORDER BY f.created_at DESC
            ");
            echo json_encode($funnels);
        }
        break;
        
    case 'POST':
        // Crear o actualizar un embudo
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es requerido']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            if (isset($data['id'])) {
                // Actualizar embudo existente
                query("
                    UPDATE funnels 
                    SET name = ?, description = ?, status = ?
                    WHERE id = ?
                ", [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                $funnel_id = $data['id'];
                
                // Eliminar etapas existentes
                query("DELETE FROM funnel_stages WHERE funnel_id = ?", [$funnel_id]);
            } else {
                // Crear nuevo embudo
                query("
                    INSERT INTO funnels (name, description, status)
                    VALUES (?, ?, ?)
                ", [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['status'] ?? 'active'
                ]);
                
                $funnel_id = $pdo->lastInsertId();
            }
            
            // Insertar nuevas etapas
            if (!empty($data['stages'])) {
                foreach ($data['stages'] as $stage) {
                    query("
                        INSERT INTO funnel_stages (funnel_id, name, description, order_position)
                        VALUES (?, ?, ?, ?)
                    ", [
                        $funnel_id,
                        $stage['name'],
                        $stage['description'] ?? null,
                        $stage['order'] ?? 0
                    ]);
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $funnel_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el embudo: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Eliminar un embudo
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de embudo requerido']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            // Verificar si hay leads asociados
            $leads = fetch("SELECT COUNT(*) as total FROM leads WHERE funnel_id = ?", [$id]);
            if ($leads['total'] > 0) {
                throw new Exception('No se puede eliminar un embudo con leads asociados');
            }
            
            // Eliminar etapas
            query("DELETE FROM funnel_stages WHERE funnel_id = ?", [$id]);
            
            // Eliminar embudo
            query("DELETE FROM funnels WHERE id = ?", [$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el embudo: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
} 