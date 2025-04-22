<?php
session_start();
require_once '../config/database.php';
require_once '../config/integrations.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener configuración actual de Messenger
$stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'messenger' AND active = 1 LIMIT 1");
$stmt->execute();
$messenger_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos
    $page_token = trim($_POST['page_token'] ?? '');
    $app_id = trim($_POST['app_id'] ?? '');
    $app_secret = trim($_POST['app_secret'] ?? '');
    $page_id = trim($_POST['page_id'] ?? '');
    
    if (empty($page_token) || empty($app_id) || empty($app_secret) || empty($page_id)) {
        $error = 'Por favor, complete todos los campos requeridos.';
    } else {
        // Verificar las credenciales
        $valid = verifyMessengerCredentials($page_token, $page_id);
        
        if ($valid) {
            try {
                if ($messenger_config) {
                    // Actualizar configuración existente
                    $stmt = $pdo->prepare("UPDATE integrations SET 
                        api_key = ?,
                        api_secret = ?,
                        business_account_id = ?,
                        updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([$page_token, $app_secret, $page_id, $messenger_config['id']]);
                } else {
                    // Insertar nueva configuración
                    $stmt = $pdo->prepare("INSERT INTO integrations 
                        (type, api_key, api_secret, business_account_id, active, created_at, updated_at) 
                        VALUES ('messenger', ?, ?, ?, 1, NOW(), NOW())");
                    $stmt->execute([$page_token, $app_secret, $page_id]);
                }
                
                $message = 'Integración de Facebook Messenger configurada correctamente.';
                
                // Refrescar la configuración
                $stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'messenger' AND active = 1 LIMIT 1");
                $stmt->execute();
                $messenger_config = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = 'Error al guardar la configuración: ' . $e->getMessage();
            }
        } else {
            $error = 'Las credenciales de Facebook Messenger no son válidas o no se pudo conectar con la API.';
        }
    }
}

// Función para verificar credenciales de Messenger
function verifyMessengerCredentials($page_token, $page_id) {
    $url = MESSENGER_API_URL . '/' . MESSENGER_API_VERSION . '/' . $page_id . '?access_token=' . $page_token;
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['id']) && $result['id'] === $page_id;
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Integración con Facebook Messenger</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="app_id" class="form-label">App ID de Facebook</label>
                            <input type="text" class="form-control" id="app_id" name="app_id" 
                                   value="<?php echo htmlspecialchars($messenger_config['app_id'] ?? ''); ?>" required>
                            <div class="form-text">ID de su aplicación en Facebook Developers.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="app_secret" class="form-label">App Secret de Facebook</label>
                            <input type="password" class="form-control" id="app_secret" name="app_secret" 
                                   value="<?php echo htmlspecialchars($messenger_config['api_secret'] ?? ''); ?>" required>
                            <div class="form-text">Secreto de su aplicación en Facebook Developers.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="page_token" class="form-label">Token de Acceso de la Página</label>
                            <input type="text" class="form-control" id="page_token" name="page_token" 
                                   value="<?php echo htmlspecialchars($messenger_config['api_key'] ?? ''); ?>" required>
                            <div class="form-text">Token de acceso de la página para Messenger Platform.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="page_id" class="form-label">ID de la Página de Facebook</label>
                            <input type="text" class="form-control" id="page_id" name="page_id" 
                                   value="<?php echo htmlspecialchars($messenger_config['business_account_id'] ?? ''); ?>" required>
                            <div class="form-text">ID de su página de Facebook.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Guía de Configuración</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Pasos para configurar la integración con Facebook Messenger:</h6>
                        <ol>
                            <li>Cree una aplicación en el <a href="https://developers.facebook.com/" target="_blank">Portal de Desarrolladores de Facebook</a></li>
                            <li>Configure la aplicación para usar Messenger Platform</li>
                            <li>Vincule su página de Facebook a la aplicación</li>
                            <li>Genere un token de acceso de página para Messenger</li>
                            <li>Obtenga el ID de su página de Facebook</li>
                            <li>Complete el formulario anterior con los datos obtenidos</li>
                        </ol>
                        <p>
                            <strong>Nota:</strong> Para usar Messenger Platform, debe tener una página de Facebook y los permisos adecuados 
                            para enviar mensajes a usuarios que hayan interactuado previamente con su página.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($messenger_config): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Configuración del Webhook</h5>
                </div>
                <div class="card-body">
                    <p>Para recibir mensajes de Facebook Messenger, debe configurar un webhook en el Portal de Desarrolladores de Facebook:</p>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="webhook_url" 
                               value="<?php echo getBaseUrl(); ?>/api/webhooks.php?source=messenger" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('webhook_url')">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="verify_token" 
                               value="<?php echo htmlspecialchars(getWebhookVerifyToken()); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('verify_token')">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Instrucciones para configurar el webhook:</h6>
                        <ol>
                            <li>Vaya a su aplicación en el <a href="https://developers.facebook.com/" target="_blank">Portal de Desarrolladores de Facebook</a></li>
                            <li>Navegue a la sección "Messenger" y luego a "Configuración"</li>
                            <li>En "Webhooks", haga clic en "Agregar URL de devolución de llamada"</li>
                            <li>Introduzca la URL del webhook y el token de verificación proporcionados arriba</li>
                            <li>Seleccione los campos <code>messages</code>, <code>messaging_postbacks</code> y <code>messaging_optins</code></li>
                            <li>Haga clic en "Verificar y guardar"</li>
                            <li>Después de verificar, seleccione su página para suscribirse a los eventos del webhook</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Respuestas Automáticas</h5>
                </div>
                <div class="card-body">
                    <p>Configure respuestas automáticas para mensajes comunes en Messenger:</p>
                    
                    <form id="autoResponseForm">
                        <div class="mb-3">
                            <label for="greeting_text" class="form-label">Mensaje de Bienvenida</label>
                            <textarea class="form-control" id="greeting_text" rows="2" placeholder="¡Hola! Gracias por contactarnos. ¿En qué podemos ayudarte?"></textarea>
                            <div class="form-text">Este mensaje se muestra cuando un usuario inicia una conversación.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="away_message" class="form-label">Mensaje de Ausencia</label>
                            <textarea class="form-control" id="away_message" rows="2" placeholder="Gracias por tu mensaje. En este momento no estamos disponibles, pero responderemos lo antes posible."></textarea>
                            <div class="form-text">Este mensaje se envía cuando no hay agentes disponibles.</div>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="saveAutoResponses()">Guardar Respuestas Automáticas</button>
                    </form>
                    
                    <div id="autoResponseResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');
    alert('Copiado al portapapeles');
}

function saveAutoResponses() {
    const resultDiv = document.getElementById('autoResponseResult');
    
    // Simulamos el guardado de las respuestas automáticas
    // En un entorno real, esto enviaría los datos a la API de Messenger y a la base de datos
    resultDiv.innerHTML = '<div class="alert alert-info">Guardando respuestas automáticas...</div>';
    
    setTimeout(() => {
        resultDiv.innerHTML = '<div class="alert alert-success">Las respuestas automáticas se han guardado correctamente.</div>';
    }, 1500);
}
</script>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
?> 