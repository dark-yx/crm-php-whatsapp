<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener la configuración actual de WhatsApp
$stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'whatsapp' AND active = 1 LIMIT 1");
$stmt->execute();
$whatsapp = $stmt->fetch(PDO::FETCH_ASSOC);

// Inicializar variables para el formulario
$api_key = $whatsapp['api_key'] ?? '';
$api_secret = $whatsapp['api_secret'] ?? '';
$phone_id = $whatsapp['phone_id'] ?? '';
$business_account_id = $whatsapp['business_account_id'] ?? '';
$verified = $whatsapp['verified'] ?? 0;
$message = '';

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = $_POST['api_key'] ?? '';
    $api_secret = $_POST['api_secret'] ?? '';
    $phone_id = $_POST['phone_id'] ?? '';
    $business_account_id = $_POST['business_account_id'] ?? '';
    
    // Validar que todos los campos estén completos
    if (empty($api_key) || empty($api_secret) || empty($phone_id) || empty($business_account_id)) {
        $message = "Todos los campos son obligatorios";
    } else {
        // Verificar las credenciales
        $verified = verifyWhatsAppCredentials($api_key, $api_secret, $phone_id, $business_account_id);
        
        if ($verified) {
            // Guardar o actualizar las credenciales
            if ($whatsapp) {
                $stmt = $pdo->prepare("UPDATE integrations SET 
                    api_key = ?, 
                    api_secret = ?, 
                    phone_id = ?, 
                    business_account_id = ?, 
                    verified = 1, 
                    updated_at = NOW() 
                    WHERE id = ?");
                $stmt->execute([$api_key, $api_secret, $phone_id, $business_account_id, $whatsapp['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO integrations 
                    (type, api_key, api_secret, phone_id, business_account_id, verified, active, created_at, updated_at) 
                    VALUES ('whatsapp', ?, ?, ?, ?, 1, 1, NOW(), NOW())");
                $stmt->execute([$api_key, $api_secret, $phone_id, $business_account_id]);
            }
            $message = "Integración con WhatsApp configurada correctamente";
        } else {
            $message = "No se pudieron verificar las credenciales de WhatsApp";
        }
    }
}

// Incluir header
include_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Integración con WhatsApp Business API</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $verified ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="api_key">API Key (Token de Acceso)</label>
                                    <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($api_key); ?>" required>
                                    <small class="form-text text-muted">Obtenga esto de su Dashboard de Facebook para Desarrolladores</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="api_secret">API Secret</label>
                                    <input type="password" class="form-control" id="api_secret" name="api_secret" value="<?php echo htmlspecialchars($api_secret); ?>" required>
                                    <small class="form-text text-muted">El secreto de la aplicación de Facebook</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_id">ID de Teléfono</label>
                                    <input type="text" class="form-control" id="phone_id" name="phone_id" value="<?php echo htmlspecialchars($phone_id); ?>" required>
                                    <small class="form-text text-muted">ID del número de teléfono registrado en WhatsApp Business</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_account_id">ID de Cuenta de Negocio</label>
                                    <input type="text" class="form-control" id="business_account_id" name="business_account_id" value="<?php echo htmlspecialchars($business_account_id); ?>" required>
                                    <small class="form-text text-muted">ID de su cuenta de negocios de WhatsApp</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                            <?php if ($verified): ?>
                                <span class="badge bg-success ms-2">Verificado</span>
                            <?php else: ?>
                                <span class="badge bg-warning ms-2">No Verificado</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Configuración de Webhook para WhatsApp</h6>
                </div>
                <div class="card-body">
                    <p>Para recibir mensajes de WhatsApp, debe configurar un webhook en su panel de Facebook para Desarrolladores:</p>
                    
                    <div class="mb-3">
                        <label class="form-label">URL del Webhook:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(getBaseUrl() . '/api/webhooks.php?source=whatsapp'); ?>" id="webhookUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('webhookUrl')">Copiar</button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Token de Verificación:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(getWebhookVerifyToken()); ?>" id="verifyToken" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('verifyToken')">Copiar</button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Instrucciones:</h6>
                        <ol>
                            <li>Vaya a su panel de <a href="https://developers.facebook.com/" target="_blank">Facebook para Desarrolladores</a></li>
                            <li>Seleccione su aplicación y vaya a la sección "WhatsApp" → "Configuración"</li>
                            <li>En la sección "Webhooks", haga clic en "Editar"</li>
                            <li>Pegue la URL del webhook y el token de verificación proporcionados arriba</li>
                            <li>Seleccione los campos: <code>messages</code>, <code>message_status</code> y <code>message_template_status</code></li>
                            <li>Haga clic en "Verificar y guardar"</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Plantillas de Mensajes</h6>
                </div>
                <div class="card-body">
                    <p>Las plantillas de mensajes deben ser aprobadas por WhatsApp antes de poder utilizarlas para iniciar conversaciones.</p>
                    
                    <div class="alert alert-warning">
                        <strong>Nota:</strong> La creación y administración de plantillas debe realizarse desde el <a href="https://business.facebook.com/" target="_blank">Facebook Business Manager</a> en la sección de WhatsApp.
                    </div>
                    
                    <button type="button" class="btn btn-success" onclick="refreshTemplates()">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar Plantillas
                    </button>
                    
                    <div class="table-responsive mt-3">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Idioma</th>
                                    <th>Estado</th>
                                    <th>Categoría</th>
                                    <th>Última actualización</th>
                                </tr>
                            </thead>
                            <tbody id="templatesTable">
                                <!-- Las plantillas se cargarán aquí mediante JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para copiar al portapapeles
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');
    
    // Mostrar notificación
    const tooltip = document.createElement('div');
    tooltip.className = 'position-fixed top-0 end-0 p-3';
    tooltip.style.zIndex = '1070';
    tooltip.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Notificación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Copiado al portapapeles
            </div>
        </div>
    `;
    document.body.appendChild(tooltip);
    
    setTimeout(() => {
        tooltip.remove();
    }, 2000);
}

// Función para actualizar plantillas
function refreshTemplates() {
    // Mostrar modal de carga
    showLoading('Actualizando plantillas...');
    
    fetch('../api/templates.php?type=whatsapp')
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                renderTemplates(data.templates);
            } else {
                alert('Error al actualizar plantillas: ' + data.message);
            }
        })
        .catch(error => {
            hideLoading();
            alert('Error: ' + error.message);
        });
}

// Función para renderizar plantillas en la tabla
function renderTemplates(templates) {
    const table = document.getElementById('templatesTable');
    table.innerHTML = '';
    
    if (templates.length === 0) {
        table.innerHTML = '<tr><td colspan="5" class="text-center">No hay plantillas disponibles</td></tr>';
        return;
    }
    
    templates.forEach(template => {
        const statusClass = template.status === 'APPROVED' ? 'success' : 
                           (template.status === 'PENDING' ? 'warning' : 'danger');
        
        table.innerHTML += `
            <tr>
                <td>${template.name}</td>
                <td>${template.language}</td>
                <td><span class="badge bg-${statusClass}">${template.status}</span></td>
                <td>${template.category}</td>
                <td>${new Date(template.updated_at).toLocaleString()}</td>
            </tr>
        `;
    });
}

// Cargar plantillas al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    if (<?php echo $verified ? 'true' : 'false'; ?>) {
        refreshTemplates();
    }
});
</script>

<?php include_once '../includes/footer.php'; ?> 