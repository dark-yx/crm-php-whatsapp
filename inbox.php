<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener conversaciones asignadas al agente actual
$stmt = $pdo->prepare("
    SELECT c.*, 
           co.name as contact_name, 
           co.email as contact_email, 
           co.phone as contact_phone,
           u.name as assigned_to_name
    FROM conversations c
    LEFT JOIN contacts co ON c.contact_id = co.id
    LEFT JOIN users u ON c.assigned_to = u.id
    WHERE c.assigned_to = ? AND c.status = 'open'
    ORDER BY c.last_activity DESC
");
$stmt->execute([$_SESSION['user_id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir el encabezado
include_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Lista de conversaciones -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Conversaciones Activas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conversation): ?>
                            <a href="#" class="list-group-item list-group-item-action" 
                               onclick="loadConversation(<?php echo $conversation['id']; ?>)">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($conversation['contact_name']); ?></h6>
                                    <small><?php echo formatDate($conversation['last_activity']); ?></small>
                                </div>
                                <p class="mb-1 text-truncate">
                                    <?php echo htmlspecialchars($conversation['last_message'] ?? 'Sin mensajes'); ?>
                                </p>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($conversation['channel']); ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                        
                        <?php if (empty($conversations)): ?>
                            <div class="list-group-item">
                                <p class="text-center text-muted mb-0">No hay conversaciones activas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Área de conversación -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Conversación</h5>
                </div>
                <div class="card-body">
                    <div id="conversationArea" class="d-none">
                        <!-- Información del contacto -->
                        <div class="mb-4">
                            <h6>Información del Contacto</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre:</strong> <span id="contactName"></span></p>
                                    <p><strong>Email:</strong> <span id="contactEmail"></span></p>
                                    <p><strong>Teléfono:</strong> <span id="contactPhone"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Canal:</strong> <span id="conversationChannel"></span></p>
                                    <p><strong>Estado:</strong> <span id="conversationStatus"></span></p>
                                    <p><strong>Última actividad:</strong> <span id="lastActivity"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Historial de mensajes -->
                        <div id="messagesContainer" class="mb-4" style="height: 400px; overflow-y: auto;">
                            <!-- Los mensajes se cargarán aquí -->
                        </div>
                        
                        <!-- Área de respuesta -->
                        <div class="border-top pt-3">
                            <form id="messageForm" onsubmit="sendMessage(event)">
                                <div class="mb-3">
                                    <textarea class="form-control" id="messageText" rows="3" 
                                              placeholder="Escribe tu mensaje..." required></textarea>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm me-2" 
                                                onclick="suggestResponse()">
                                            <i class="bi bi-lightbulb"></i> Sugerir respuesta
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                onclick="closeConversation()">
                                            <i class="bi bi-x-circle"></i> Cerrar conversación
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Enviar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div id="noConversation" class="text-center text-muted">
                        <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                        <p class="mt-3">Selecciona una conversación para comenzar</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentConversationId = null;

function loadConversation(conversationId) {
    currentConversationId = conversationId;
    
    // Mostrar el área de conversación
    document.getElementById('conversationArea').classList.remove('d-none');
    document.getElementById('noConversation').classList.add('d-none');
    
    // Aquí se haría una petición AJAX para cargar los datos de la conversación
    // Por ahora simulamos los datos
    const conversation = {
        id: conversationId,
        contact_name: 'Nombre del Contacto',
        contact_email: 'contacto@ejemplo.com',
        contact_phone: '123456789',
        channel: 'WhatsApp',
        status: 'Abierta',
        last_activity: '2024-03-20 15:30:00'
    };
    
    // Actualizar información del contacto
    document.getElementById('contactName').textContent = conversation.contact_name;
    document.getElementById('contactEmail').textContent = conversation.contact_email;
    document.getElementById('contactPhone').textContent = conversation.contact_phone;
    document.getElementById('conversationChannel').textContent = conversation.channel;
    document.getElementById('conversationStatus').textContent = conversation.status;
    document.getElementById('lastActivity').textContent = conversation.last_activity;
    
    // Simular carga de mensajes
    const messagesContainer = document.getElementById('messagesContainer');
    messagesContainer.innerHTML = `
        <div class="message received mb-3">
            <div class="card">
                <div class="card-body">
                    <p class="mb-0">Hola, ¿cómo están?</p>
                    <small class="text-muted">10:30 AM</small>
                </div>
            </div>
        </div>
        <div class="message sent mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <p class="mb-0">¡Hola! Estamos muy bien, ¿en qué podemos ayudarte?</p>
                    <small>10:32 AM</small>
                </div>
            </div>
        </div>
    `;
    
    // Desplazar al último mensaje
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function sendMessage(event) {
    event.preventDefault();
    
    if (!currentConversationId) return;
    
    const messageText = document.getElementById('messageText').value.trim();
    if (!messageText) return;
    
    // Aquí se haría una petición AJAX para enviar el mensaje
    // Por ahora simulamos el envío
    const messagesContainer = document.getElementById('messagesContainer');
    const now = new Date();
    const timeString = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    
    messagesContainer.innerHTML += `
        <div class="message sent mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <p class="mb-0">${messageText}</p>
                    <small>${timeString}</small>
                </div>
            </div>
        </div>
    `;
    
    // Limpiar el campo de mensaje
    document.getElementById('messageText').value = '';
    
    // Desplazar al último mensaje
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function suggestResponse() {
    if (!currentConversationId) return;
    
    // Aquí se haría una petición AJAX para obtener una sugerencia de respuesta
    // Por ahora simulamos la sugerencia
    const suggestedResponse = "Gracias por contactarnos. ¿Podrías proporcionarnos más detalles sobre tu consulta?";
    document.getElementById('messageText').value = suggestedResponse;
}

function closeConversation() {
    if (!currentConversationId) return;
    
    if (confirm('¿Estás seguro de que deseas cerrar esta conversación?')) {
        // Aquí se haría una petición AJAX para cerrar la conversación
        // Por ahora simulamos el cierre
        alert('Conversación cerrada correctamente');
        location.reload();
    }
}
</script>

<style>
.message {
    max-width: 80%;
}

.message.received {
    margin-right: auto;
}

.message.sent {
    margin-left: auto;
}

.message .card {
    border-radius: 15px;
}

.message.sent .card {
    border-top-right-radius: 0;
}

.message.received .card {
    border-top-left-radius: 0;
}
</style>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?> 