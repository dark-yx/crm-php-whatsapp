<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar si el usuario tiene permisos de administrador
if ($_SESSION['user_role'] !== 'admin') {
    // Redirigir o mostrar mensaje de error
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Obtener integraciones activas
$integrations = fetchAll("
    SELECT * FROM integrations 
    WHERE status = 'active'
    ORDER BY type ASC
");

// Preparar array de integraciones por tipo para fácil acceso
$integration_types = [
    'whatsapp' => [
        'name' => 'WhatsApp',
        'icon' => 'bi-whatsapp',
        'color' => 'success',
        'description' => 'Integración con la API de WhatsApp Business para enviar y recibir mensajes.',
        'fields' => [
            'phone_number_id' => 'ID del número de teléfono',
            'access_token' => 'Token de acceso a la API'
        ]
    ],
    'telegram' => [
        'name' => 'Telegram',
        'icon' => 'bi-telegram',
        'color' => 'primary',
        'description' => 'Conexión con Telegram Bot API para comunicación bidireccional.',
        'fields' => [
            'bot_token' => 'Token del bot'
        ]
    ],
    'instagram' => [
        'name' => 'Instagram',
        'icon' => 'bi-instagram',
        'color' => 'danger',
        'description' => 'Integración con Instagram Messaging API para mensajes directos.',
        'fields' => [
            'access_token' => 'Token de acceso a la API'
        ]
    ],
    'messenger' => [
        'name' => 'Facebook Messenger',
        'icon' => 'bi-messenger',
        'color' => 'info',
        'description' => 'Conexión con Messenger Platform para comunicación con usuarios.',
        'fields' => [
            'page_token' => 'Token de acceso de la página'
        ]
    ],
    'openai' => [
        'name' => 'OpenAI',
        'icon' => 'bi-robot',
        'color' => 'dark',
        'description' => 'Integración con OpenAI para respuestas automáticas inteligentes.',
        'fields' => [
            'api_key' => 'Clave de API'
        ]
    ]
];

// Mapear integraciones existentes por tipo
$active_integrations = [];
foreach ($integrations as $integration) {
    $active_integrations[$integration['type']] = $integration;
}

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Integraciones</h1>
            <p class="text-muted">Configure las integraciones con plataformas de mensajería y servicios externos</p>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($integration_types as $type => $integration_info): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-<?php echo $integration_info['color']; ?> text-white">
                        <div class="d-flex align-items-center">
                            <i class="bi <?php echo $integration_info['icon']; ?> fs-3 me-2"></i>
                            <h5 class="mb-0"><?php echo $integration_info['name']; ?></h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo $integration_info['description']; ?></p>
                        
                        <?php if (isset($active_integrations[$type])): ?>
                            <div class="alert alert-success" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <div>
                                        <strong>Integración activa</strong><br>
                                        <span class="small"><?php echo htmlspecialchars($active_integrations[$type]['name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-dash-circle me-2"></i>
                                    <div>
                                        <strong>No configurado</strong><br>
                                        <span class="small">Configure esta integración para habilitar la funcionalidad</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <?php if (isset($active_integrations[$type])): ?>
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-outline-primary" onclick="editIntegration('<?php echo $type; ?>')">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="deleteIntegration('<?php echo $type; ?>', '<?php echo $integration_info['name']; ?>')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary w-100" onclick="addIntegration('<?php echo $type; ?>')">
                                <i class="bi bi-plus-lg"></i> Configurar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Guía de configuración -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Guía de configuración</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="guideAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingWhatsApp">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWhatsApp" aria-expanded="false" aria-controls="collapseWhatsApp">
                            <i class="bi bi-whatsapp me-2 text-success"></i> Configuración de WhatsApp Business API
                        </button>
                    </h2>
                    <div id="collapseWhatsApp" class="accordion-collapse collapse" aria-labelledby="headingWhatsApp" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Regístrese en Facebook Business.</li>
                                <li>Cree una aplicación en Facebook Developers.</li>
                                <li>Configure los permisos de mensajería.</li>
                                <li>Obtenga el ID del número de teléfono de WhatsApp.</li>
                                <li>Genere un token de acceso en la sección de WhatsApp.</li>
                                <li>Configure los webhooks para recibir mensajes.</li>
                            </ol>
                            <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Documentación oficial
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTelegram">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTelegram" aria-expanded="false" aria-controls="collapseTelegram">
                            <i class="bi bi-telegram me-2 text-primary"></i> Configuración de Telegram Bot
                        </button>
                    </h2>
                    <div id="collapseTelegram" class="accordion-collapse collapse" aria-labelledby="headingTelegram" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Hable con @BotFather en Telegram.</li>
                                <li>Cree un nuevo bot con el comando /newbot.</li>
                                <li>Proporcione un nombre y un nombre de usuario para su bot.</li>
                                <li>Copie el token de API que le proporcionará BotFather.</li>
                                <li>Configure el webhook para recibir mensajes.</li>
                            </ol>
                            <a href="https://core.telegram.org/bots#creating-a-new-bot" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Documentación oficial
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingInstagram">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInstagram" aria-expanded="false" aria-controls="collapseInstagram">
                            <i class="bi bi-instagram me-2 text-danger"></i> Configuración de Instagram Messaging
                        </button>
                    </h2>
                    <div id="collapseInstagram" class="accordion-collapse collapse" aria-labelledby="headingInstagram" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Cree una cuenta comercial de Instagram.</li>
                                <li>Conecte su cuenta a una página de Facebook.</li>
                                <li>Cree una aplicación en Facebook Developers.</li>
                                <li>Solicite permisos de mensajería de Instagram.</li>
                                <li>Genere un token de acceso a largo plazo.</li>
                                <li>Configure los webhooks para recibir mensajes.</li>
                            </ol>
                            <a href="https://developers.facebook.com/docs/messenger-platform/instagram/get-started" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Documentación oficial
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOpenAI">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOpenAI" aria-expanded="false" aria-controls="collapseOpenAI">
                            <i class="bi bi-robot me-2 text-dark"></i> Configuración de OpenAI
                        </button>
                    </h2>
                    <div id="collapseOpenAI" class="accordion-collapse collapse" aria-labelledby="headingOpenAI" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Cree una cuenta en OpenAI.</li>
                                <li>Vaya a la sección de API Keys en su cuenta.</li>
                                <li>Genere una nueva clave de API.</li>
                                <li>Copie la clave y configúrela en nuestro sistema.</li>
                            </ol>
                            <a href="https://platform.openai.com/docs/api-reference" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Documentación oficial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar integración -->
<div class="modal fade" id="integrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="integrationModalTitle">Configurar Integración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="integrationForm">
                    <input type="hidden" id="integration_type" name="type">
                    <input type="hidden" id="integration_id" name="id">
                    
                    <div class="mb-3">
                        <label for="integration_name" class="form-label">Nombre de la integración</label>
                        <input type="text" class="form-control" id="integration_name" name="name" required>
                    </div>
                    
                    <div id="dynamic_fields">
                        <!-- Campos dinámicos según el tipo de integración -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveIntegration">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let integrationModal;
let integrationTypes = <?php echo json_encode($integration_types); ?>;

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    integrationModal = new bootstrap.Modal(document.getElementById('integrationModal'));
    
    // Event listener para guardar integración
    document.getElementById('saveIntegration').addEventListener('click', saveIntegration);
});

// Mostrar modal para agregar integración
function addIntegration(type) {
    document.getElementById('integrationModalTitle').textContent = `Configurar ${integrationTypes[type].name}`;
    document.getElementById('integration_type').value = type;
    document.getElementById('integration_id').value = '';
    document.getElementById('integration_name').value = '';
    
    // Generar campos dinámicos
    generateFields(type, {});
    
    integrationModal.show();
}

// Mostrar modal para editar integración
function editIntegration(type) {
    document.getElementById('integrationModalTitle').textContent = `Editar ${integrationTypes[type].name}`;
    document.getElementById('integration_type').value = type;
    
    // Cargar datos de la integración
    fetch(`api/integrations.php?type=${type}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('integration_id').value = data.id;
            document.getElementById('integration_name').value = data.name;
            
            // Parsear credenciales
            const credentials = JSON.parse(data.credentials);
            
            // Generar campos dinámicos
            generateFields(type, credentials);
            
            integrationModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la información de la integración');
        });
}

// Generar campos dinámicos para el formulario
function generateFields(type, data) {
    const container = document.getElementById('dynamic_fields');
    container.innerHTML = '';
    
    if (integrationTypes[type] && integrationTypes[type].fields) {
        Object.entries(integrationTypes[type].fields).forEach(([fieldName, fieldLabel]) => {
            const fieldValue = data[fieldName] || '';
            const fieldId = `field_${fieldName}`;
            
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-3';
            
            const label = document.createElement('label');
            label.className = 'form-label';
            label.htmlFor = fieldId;
            label.textContent = fieldLabel;
            
            const input = document.createElement('input');
            input.type = fieldName.includes('token') || fieldName.includes('key') ? 'password' : 'text';
            input.className = 'form-control';
            input.id = fieldId;
            input.name = fieldName;
            input.value = fieldValue;
            input.required = true;
            
            fieldDiv.appendChild(label);
            fieldDiv.appendChild(input);
            container.appendChild(fieldDiv);
        });
    }
}

// Guardar integración
function saveIntegration() {
    const form = document.getElementById('integrationForm');
    const type = document.getElementById('integration_type').value;
    const id = document.getElementById('integration_id').value;
    const name = document.getElementById('integration_name').value;
    
    // Obtener credenciales
    const credentials = {};
    if (integrationTypes[type] && integrationTypes[type].fields) {
        Object.keys(integrationTypes[type].fields).forEach(fieldName => {
            credentials[fieldName] = document.getElementById(`field_${fieldName}`).value;
        });
    }
    
    const data = {
        type: type,
        name: name,
        credentials: JSON.stringify(credentials)
    };
    
    if (id) {
        data.id = id;
    }
    
    // Enviar datos a la API
    fetch('api/integrations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            integrationModal.hide();
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar la integración'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar la integración');
    });
}

// Eliminar integración
function deleteIntegration(type, name) {
    if (confirm(`¿Está seguro que desea eliminar la integración con ${name}?`)) {
        fetch(`api/integrations.php?type=${type}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar la integración'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la integración');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?> 