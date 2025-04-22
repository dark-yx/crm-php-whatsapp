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

// Obtener el ID del lead si existe
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Manejar las diferentes operaciones
switch ($method) {
    case 'GET':
        // Obtener un lead específico o todos los leads
        if ($id) {
            $lead = fetch("
                SELECT l.*, 
                       c.name as contact_name, 
                       c.email as contact_email,
                       c.phone as contact_phone,
                       f.name as funnel_name,
                       fs.name as stage_name
                FROM leads l
                LEFT JOIN contacts c ON l.contact_id = c.id
                LEFT JOIN funnels f ON l.funnel_id = f.id
                LEFT JOIN funnel_stages fs ON l.stage_id = fs.id
                WHERE l.id = ?
            ", [$id]);
            
            if ($lead) {
                // Obtener actividades del lead
                $lead['activities'] = fetchAll("
                    SELECT * FROM lead_activities 
                    WHERE lead_id = ? 
                    ORDER BY created_at DESC
                ", [$id]);
                
                echo json_encode($lead);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Lead no encontrado']);
            }
        } else {
            // Obtener parámetros de filtro
            $funnel_id = isset($_GET['funnel_id']) ? intval($_GET['funnel_id']) : null;
            $stage_id = isset($_GET['stage_id']) ? intval($_GET['stage_id']) : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;
            
            // Construir consulta básica
            $query = "
                SELECT l.*, 
                       c.name as contact_name, 
                       c.email as contact_email,
                       c.phone as contact_phone,
                       f.name as funnel_name,
                       fs.name as stage_name
                FROM leads l
                LEFT JOIN contacts c ON l.contact_id = c.id
                LEFT JOIN funnels f ON l.funnel_id = f.id
                LEFT JOIN funnel_stages fs ON l.stage_id = fs.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Agregar filtros
            if ($funnel_id) {
                $query .= " AND l.funnel_id = ?";
                $params[] = $funnel_id;
            }
            
            if ($stage_id) {
                $query .= " AND l.stage_id = ?";
                $params[] = $stage_id;
            }
            
            if ($status) {
                $query .= " AND l.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $query .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR l.name LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            $query .= " ORDER BY l.created_at DESC";
            
            $leads = fetchAll($query, $params);
            echo json_encode($leads);
        }
        break;
        
    case 'POST':
        // Crear o actualizar un lead
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['funnel_id']) || empty($data['stage_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            if (isset($data['id'])) {
                // Obtener estado actual para comparar
                $current_lead = fetch("
                    SELECT * FROM leads WHERE id = ?
                ", [$data['id']]);
                
                // Actualizar lead existente
                query("
                    UPDATE leads 
                    SET name = ?, contact_id = ?, funnel_id = ?, stage_id = ?, 
                        value = ?, status = ?, due_date = ?
                    WHERE id = ?
                ", [
                    $data['name'],
                    $data['contact_id'] ?? null,
                    $data['funnel_id'],
                    $data['stage_id'],
                    $data['value'] ?? 0,
                    $data['status'] ?? 'open',
                    $data['due_date'] ?? null,
                    $data['id']
                ]);
                
                $lead_id = $data['id'];
                
                // Registrar actividad si cambió la etapa
                if ($current_lead && $current_lead['stage_id'] != $data['stage_id']) {
                    $new_stage = fetch("SELECT name FROM funnel_stages WHERE id = ?", [$data['stage_id']]);
                    
                    query("
                        INSERT INTO lead_activities (lead_id, user_id, type, description)
                        VALUES (?, ?, 'stage_change', ?)
                    ", [
                        $lead_id,
                        $_SESSION['user_id'],
                        'Lead movido a etapa: ' . $new_stage['name']
                    ]);
                }
                
                // Registrar actividad si cambió el estado
                if ($current_lead && $current_lead['status'] != $data['status']) {
                    query("
                        INSERT INTO lead_activities (lead_id, user_id, type, description)
                        VALUES (?, ?, 'status_change', ?)
                    ", [
                        $lead_id,
                        $_SESSION['user_id'],
                        'Estado cambiado a: ' . $data['status']
                    ]);
                }
            } else {
                // Crear nuevo lead
                query("
                    INSERT INTO leads (name, contact_id, funnel_id, stage_id, value, status, due_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $data['name'],
                    $data['contact_id'] ?? null,
                    $data['funnel_id'],
                    $data['stage_id'],
                    $data['value'] ?? 0,
                    $data['status'] ?? 'open',
                    $data['due_date'] ?? null
                ]);
                
                $lead_id = $pdo->lastInsertId();
                
                // Registrar actividad de creación
                query("
                    INSERT INTO lead_activities (lead_id, user_id, type, description)
                    VALUES (?, ?, 'created', 'Lead creado')
                ", [
                    $lead_id,
                    $_SESSION['user_id']
                ]);
            }
            
            // Agregar nota si se proporciona
            if (!empty($data['note'])) {
                query("
                    INSERT INTO lead_activities (lead_id, user_id, type, description)
                    VALUES (?, ?, 'note', ?)
                ", [
                    $lead_id,
                    $_SESSION['user_id'],
                    $data['note']
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $lead_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el lead: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Eliminar un lead
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de lead requerido']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            // Eliminar actividades
            query("DELETE FROM lead_activities WHERE lead_id = ?", [$id]);
            
            // Eliminar lead
            query("DELETE FROM leads WHERE id = ?", [$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el lead: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
} 