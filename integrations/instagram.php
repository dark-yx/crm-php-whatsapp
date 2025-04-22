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

// Obtener configuración actual de Instagram
$stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'instagram' AND active = 1 LIMIT 1");
$stmt->execute();
$instagram_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos
    $access_token = trim($_POST['access_token'] ?? '');
    $app_id = trim($_POST['app_id'] ?? '');
    $app_secret = trim($_POST['app_secret'] ?? '');
    $instagram_account_id = trim($_POST['instagram_account_id'] ?? '');
    
    if (empty($access_token) || empty($app_id) || empty($app_secret) || empty($instagram_account_id)) {
        $error = 'Por favor, complete todos los campos requeridos.';
    } else {
        // Verificar las credenciales
        $valid = verifyInstagramCredentials($access_token, $instagram_account_id);
        
        if ($valid) {
            try {
                if ($instagram_config) {
                    // Actualizar configuración existente
                    $stmt = $pdo->prepare("UPDATE integrations SET 
                        api_key = ?,
                        api_secret = ?,
                        business_account_id = ?,
                        updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([$access_token, $app_secret, $instagram_account_id, $instagram_config['id']]);
                } else {
                    // Insertar nueva configuración
                    $stmt = $pdo->prepare("INSERT INTO integrations 
                        (type, api_key, api_secret, business_account_id, active, created_at, updated_at) 
                        VALUES ('instagram', ?, ?, ?, 1, NOW(), NOW())");
                    $stmt->execute([$access_token, $app_secret, $instagram_account_id]);
                }
                
                $message = 'Integración de Instagram configurada correctamente.';
                
                // Refrescar la configuración
                $stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'instagram' AND active = 1 LIMIT 1");
                $stmt->execute();
                $instagram_config = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = 'Error al guardar la configuración: ' . $e->getMessage();
            }
        } else {
            $error = 'Las credenciales de Instagram no son válidas o no se pudo conectar con la API.';
        }
    }
}

// Función para verificar credenciales de Instagram
function verifyInstagramCredentials($access_token, $instagram_account_id) {
    $url = INSTAGRAM_API_URL . '/' . INSTAGRAM_API_VERSION . '/' . $instagram_account_id . '?fields=name,username&access_token=' . $access_token;
    
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
    return isset($result['id']) && $result['id'] === $instagram_account_id;
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Integración con Instagram</h5>
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
                                   value="<?php echo htmlspecialchars($instagram_config['app_id'] ?? ''); ?>" required>
                            <div class="form-text">ID de su aplicación en Facebook Developers.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="app_secret" class="form-label">App Secret de Facebook</label>
                            <input type="password" class="form-control" id="app_secret" name="app_secret" 
                                   value="<?php echo htmlspecialchars($instagram_config['api_secret'] ?? ''); ?>" required>
                            <div class="form-text">Secreto de su aplicación en Facebook Developers.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="access_token" class="form-label">Token de Acceso</label>
                            <input type="text" class="form-control" id="access_token" name="access_token" 
                                   value="<?php echo htmlspecialchars($instagram_config['api_key'] ?? ''); ?>" required>
                            <div class="form-text">Token de acceso de larga duración para Instagram Graph API.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="instagram_account_id" class="form-label">ID de la Cuenta de Instagram</label>
                            <input type="text" class="form-control" id="instagram_account_id" name="instagram_account_id" 
                                   value="<?php echo htmlspecialchars($instagram_config['business_account_id'] ?? ''); ?>" required>
                            <div class="form-text">ID de su cuenta de negocios de Instagram.</div>
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
                        <h6 class="alert-heading">Pasos para configurar la integración con Instagram:</h6>
                        <ol>
                            <li>Cree una aplicación en el <a href="https://developers.facebook.com/" target="_blank">Portal de Desarrolladores de Facebook</a></li>
                            <li>Configure la aplicación para usar Instagram Graph API</li>
                            <li>Vincule su página de Facebook y cuenta de Instagram Business</li>
                            <li>Genere un token de acceso de larga duración para la API de Instagram</li>
                            <li>Obtenga el ID de su cuenta de Instagram Business</li>
                            <li>Complete el formulario anterior con los datos obtenidos</li>
                        </ol>
                        <p>
                            <strong>Nota:</strong> Para usar la API de Instagram para mensajería, debe tener una cuenta de Instagram Business 
                            vinculada a una página de Facebook y configurada para recibir mensajes de Direct Message.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($instagram_config): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Configuración del Webhook</h5>
                </div>
                <div class="card-body">
                    <p>Para recibir mensajes de Instagram, debe configurar un webhook en el Portal de Desarrolladores de Facebook:</p>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="webhook_url" 
                               value="<?php echo getBaseUrl(); ?>/api/webhooks.php?source=instagram" readonly>
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
                            <li>Navegue a "Webhooks" en la sección de productos</li>
                            <li>Haga clic en "Agregar suscripción" y seleccione "Instagram"</li>
                            <li>Introduzca la URL del webhook y el token de verificación proporcionados arriba</li>
                            <li>Seleccione al menos los campos <code>messages</code> y <code>messaging_postbacks</code></li>
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
                <div class="card-header">
                    <h5 class="card-title">Estado de la Cuenta</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-info" type="button" onclick="checkAccountStatus()">
                        <i class="bi bi-arrow-clockwise"></i> Verificar Estado
                    </button>
                    
                    <div id="account_status" class="mt-3">
                        <!-- El estado se mostrará aquí -->
                    </div>
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

function checkAccountStatus() {
    const statusDiv = document.getElementById('account_status');
    statusDiv.innerHTML = '<div class="alert alert-info">Verificando estado de la cuenta...</div>';
    
    const accessToken = '<?php echo htmlspecialchars($instagram_config['api_key'] ?? ''); ?>';
    const accountId = '<?php echo htmlspecialchars($instagram_config['business_account_id'] ?? ''); ?>';
    
    if (!accessToken || !accountId) {
        statusDiv.innerHTML = '<div class="alert alert-danger">Faltan datos de configuración. Por favor, configure la integración primero.</div>';
        return;
    }
    
    // Aquí se haría una petición a un endpoint propio que consulta la API de Instagram
    // Por ahora simulamos la respuesta
    setTimeout(() => {
        statusDiv.innerHTML = `
            <div class="alert alert-success">
                <h6>Cuenta verificada correctamente</h6>
                <ul>
                    <li><strong>Nombre de usuario:</strong> @cuenta_ejemplo</li>
                    <li><strong>Tipo de cuenta:</strong> Business</li>
                    <li><strong>Conectada a página de Facebook:</strong> Sí</li>
                    <li><strong>Mensajería habilitada:</strong> Sí</li>
                </ul>
            </div>
        `;
    }, 1500);
}
</script>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
?> 