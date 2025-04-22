<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$channel = isset($_GET['channel']) ? $_GET['channel'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$assigned = isset($_GET['assigned']) ? $_GET['assigned'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir la consulta SQL base
$query = "
    SELECT c.*, 
           ct.name as contact_name, 
           ct.email as contact_email,
           ct.phone as contact_phone,
           u.name as assigned_name,
           (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM conversations c
    LEFT JOIN contacts ct ON c.contact_id = ct.id
    LEFT JOIN users u ON c.assigned_to = u.id
    WHERE 1=1
";

$params = [];

// Agregar filtros a la consulta
if (!empty($search)) {
    $query .= " AND (ct.name LIKE ? OR ct.email LIKE ? OR ct.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($channel)) {
    $query .= " AND c.channel = ?";
    $params[] = $channel;
}

if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

if (!empty($assigned)) {
    if ($assigned === 'unassigned') {
        $query .= " AND c.assigned_to IS NULL";
    } else {
        $query .= " AND c.assigned_to = ?";
        $params[] = $assigned;
    }
}

// Contar el total de conversaciones
$count_query = str_replace("c.*, 
           ct.name as contact_name, 
           ct.email as contact_email,
           ct.phone as contact_phone,
           u.name as assigned_name,
           (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message", "COUNT(*) as total", $query);
$count_result = fetch($count_query, $params);
$total_conversations = $count_result['total'];
$total_pages = ceil($total_conversations / $per_page);

// Obtener conversaciones para la página actual
$query .= " ORDER BY c.last_message_at DESC LIMIT $offset, $per_page";
$conversations = fetchAll($query, $params);

// Obtener usuarios para el filtro de asignación
$users = fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col">
            <h1>Conversaciones</h1>
        </div>
        <div class="col-auto">
            <a href="chat.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Nueva Conversación
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card my-3">
        <div class="card-body">
            <form method="get" action="conversations.php" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="channel" name="channel">
                        <option value="">Todos los canales</option>
                        <option value="whatsapp" <?php echo $channel == 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                        <option value="telegram" <?php echo $channel == 'telegram' ? 'selected' : ''; ?>>Telegram</option>
                        <option value="instagram" <?php echo $channel == 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                        <option value="messenger" <?php echo $channel == 'messenger' ? 'selected' : ''; ?>>Messenger</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos los estados</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Activas</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pendientes</option>
                        <option value="closed" <?php echo $status == 'closed' ? 'selected' : ''; ?>>Cerradas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="assigned" name="assigned">
                        <option value="">Todos los agentes</option>
                        <option value="unassigned" <?php echo $assigned == 'unassigned' ? 'selected' : ''; ?>>Sin asignar</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $assigned == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de conversaciones -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Contacto</th>
                            <th>Canal</th>
                            <th>Asignado a</th>
                            <th>Último mensaje</th>
                            <th>Última actividad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($conversations)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron conversaciones</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="ms-2">
                                                <div class="fw-bold"><?php echo htmlspecialchars($conversation['contact_name']); ?></div>
                                                <?php if ($conversation['contact_email']): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($conversation['contact_email']); ?></div>
                                                <?php elseif ($conversation['contact_phone']): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($conversation['contact_phone']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                            $icon = getChannelIcon($conversation['channel']);
                                            $channel_name = getChannelName($conversation['channel']);
                                            echo "<i class='bi {$icon}'></i> {$channel_name}";
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($conversation['assigned_to']): ?>
                                            <?php echo htmlspecialchars($conversation['assigned_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($conversation['last_message']): ?>
                                            <?php echo htmlspecialchars(mb_substr($conversation['last_message'], 0, 30)) . (mb_strlen($conversation['last_message']) > 30 ? '...' : ''); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin mensajes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($conversation['last_message_at']): ?>
                                            <?php echo formatDate($conversation['last_message_at'], 'd/m/Y H:i'); ?>
                                        <?php else: ?>
                                            <?php echo formatDate($conversation['created_at'], 'd/m/Y H:i'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($conversation['status'] == 'active'): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php elseif ($conversation['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                        <?php elseif ($conversation['status'] == 'closed'): ?>
                                            <span class="badge bg-secondary">Cerrada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="chat.php?id=<?php echo $conversation['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="assignConversation(<?php echo $conversation['id']; ?>, '<?php echo htmlspecialchars(addslashes($conversation['contact_name'])); ?>')">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                            <?php if ($conversation['status'] != 'closed'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="closeConversation(<?php echo $conversation['id']; ?>, '<?php echo htmlspecialchars(addslashes($conversation['contact_name'])); ?>')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="reopenConversation(<?php echo $conversation['id']; ?>, '<?php echo htmlspecialchars(addslashes($conversation['contact_name'])); ?>')">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&channel=<?php echo urlencode($channel); ?>&status=<?php echo urlencode($status); ?>&assigned=<?php echo urlencode($assigned); ?>">Anterior</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&channel=<?php echo urlencode($channel); ?>&status=<?php echo urlencode($status); ?>&assigned=<?php echo urlencode($assigned); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&channel=<?php echo urlencode($channel); ?>&status=<?php echo urlencode($status); ?>&assigned=<?php echo urlencode($assigned); ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para asignar conversación -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Conversación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="assignModalText">Seleccione un agente para asignar esta conversación:</p>
                <input type="hidden" id="conversation_id">
                <div class="mb-3">
                    <select class="form-select" id="agent_id">
                        <option value="">Sin asignación</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveAssignment()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables para modales
let assignModal;
let confirmModal;

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
});

// Asignar conversación
function assignConversation(id, contactName) {
    document.getElementById('conversation_id').value = id;
    document.getElementById('assignModalText').innerText = `Seleccione un agente para asignar la conversación con ${contactName}:`;
    
    // Obtener asignación actual
    fetch(`api/conversations.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.assigned_to) {
                document.getElementById('agent_id').value = data.assigned_to;
            } else {
                document.getElementById('agent_id').value = '';
            }
            assignModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al obtener la información de la conversación');
        });
}

// Guardar asignación
function saveAssignment() {
    const id = document.getElementById('conversation_id').value;
    const agent_id = document.getElementById('agent_id').value;
    
    fetch('api/conversations.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            assigned_to: agent_id,
            action: 'assign'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            assignModal.hide();
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo asignar la conversación'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al asignar la conversación');
    });
}

// Cerrar conversación
function closeConversation(id, contactName) {
    if (confirm(`¿Está seguro que desea cerrar la conversación con ${contactName}?`)) {
        fetch('api/conversations.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: 'closed',
                action: 'status'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo cerrar la conversación'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cerrar la conversación');
        });
    }
}

// Reabrir conversación
function reopenConversation(id, contactName) {
    if (confirm(`¿Está seguro que desea reabrir la conversación con ${contactName}?`)) {
        fetch('api/conversations.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: 'active',
                action: 'status'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo reabrir la conversación'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al reabrir la conversación');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?> 