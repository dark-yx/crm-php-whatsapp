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

// Obtener configuración actual de Telegram
$stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'telegram' AND active = 1 LIMIT 1");
$stmt->execute();
$telegram_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos
    $api_token = trim($_POST['api_token'] ?? '');
    
    if (empty($api_token)) {
        $error = 'Por favor, ingrese el token de API de Telegram.';
    } else {
        // Verificar las credenciales
        $valid = verifyTelegramCredentials($api_token);
        
        if ($valid) {
            try {
                if ($telegram_config) {
                    // Actualizar configuración existente
                    $stmt = $pdo->prepare("UPDATE integrations SET 
                        api_key = ?,
                        updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([$api_token, $telegram_config['id']]);
                } else {
                    // Insertar nueva configuración
                    $stmt = $pdo->prepare("INSERT INTO integrations 
                        (type, api_key, active, created_at, updated_at) 
                        VALUES ('telegram', ?, 1, NOW(), NOW())");
                    $stmt->execute([$api_token]);
                }
                
                $message = 'Integración de Telegram configurada correctamente.';
                
                // Refrescar la configuración
                $stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'telegram' AND active = 1 LIMIT 1");
                $stmt->execute();
                $telegram_config = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = 'Error al guardar la configuración: ' . $e->getMessage();
            }
        } else {
            $error = 'Las credenciales de Telegram no son válidas.';
        }
    }
}

// Función para verificar credenciales de Telegram
function verifyTelegramCredentials($api_token) {
    $url = TELEGRAM_API_URL . '/bot' . $api_token . '/getMe';
    
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
    return isset($result['ok']) && $result['ok'] === true;
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Integración con Telegram</h5>
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
                            <label for="api_token" class="form-label">Token de API de Telegram</label>
                            <input type="text" class="form-control" id="api_token" name="api_token" 
                                   value="<?php echo htmlspecialchars($telegram_config['api_key'] ?? ''); ?>" required>
                            <div class="form-text">Obtenga su token de API de @BotFather en Telegram.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($telegram_config): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Configuración del Webhook</h5>
                </div>
                <div class="card-body">
                    <p>Para recibir mensajes de Telegram, debe configurar un webhook. Utilice la siguiente URL:</p>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="webhook_url" 
                               value="<?php echo getBaseUrl(); ?>/api/webhooks.php?source=telegram" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('webhook_url')">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                    
                    <p>Puede configurar el webhook utilizando el siguiente comando:</p>
                    
                    <div class="bg-light p-3 mb-3">
                        <code>
                            curl -F "url=<?php echo getBaseUrl(); ?>/api/webhooks.php?source=telegram" 
                            https://api.telegram.org/bot<?php echo htmlspecialchars($telegram_config['api_key']); ?>/setWebhook
                        </code>
                    </div>
                    
                    <button class="btn btn-info" type="button" onclick="setupWebhook()">
                        Configurar Webhook Automáticamente
                    </button>
                    <div id="webhook_result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Comandos del Bot</h5>
                </div>
                <div class="card-body">
                    <p>Puede configurar comandos para su bot para facilitar la interacción de los usuarios:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Comando</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>/start</td>
                                    <td>Inicia la conversación con el bot</td>
                                </tr>
                                <tr>
                                    <td>/ayuda</td>
                                    <td>Muestra los comandos disponibles</td>
                                </tr>
                                <tr>
                                    <td>/info</td>
                                    <td>Muestra información sobre el bot</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <button class="btn btn-info mt-3" type="button" onclick="setupCommands()">
                        Configurar Comandos
                    </button>
                    <div id="commands_result" class="mt-2"></div>
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

function setupWebhook() {
    const webhookUrl = '<?php echo getBaseUrl(); ?>/api/webhooks.php?source=telegram';
    const apiToken = '<?php echo htmlspecialchars($telegram_config['api_key'] ?? ''); ?>';
    
    if (!apiToken) {
        alert('Por favor, configure primero el token de API');
        return;
    }
    
    fetch(`https://api.telegram.org/bot${apiToken}/setWebhook`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ url: webhookUrl })
    })
    .then(response => response.json())
    .then(data => {
        const resultElement = document.getElementById('webhook_result');
        if (data.ok) {
            resultElement.innerHTML = `<div class="alert alert-success">Webhook configurado correctamente</div>`;
        } else {
            resultElement.innerHTML = `<div class="alert alert-danger">Error: ${data.description}</div>`;
        }
    })
    .catch(error => {
        document.getElementById('webhook_result').innerHTML = 
            `<div class="alert alert-danger">Error al conectar con Telegram: ${error.message}</div>`;
    });
}

function setupCommands() {
    const apiToken = '<?php echo htmlspecialchars($telegram_config['api_key'] ?? ''); ?>';
    const commands = [
        { command: "start", description: "Inicia la conversación con el bot" },
        { command: "ayuda", description: "Muestra los comandos disponibles" },
        { command: "info", description: "Muestra información sobre el bot" }
    ];
    
    if (!apiToken) {
        alert('Por favor, configure primero el token de API');
        return;
    }
    
    fetch(`https://api.telegram.org/bot${apiToken}/setMyCommands`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ commands: commands })
    })
    .then(response => response.json())
    .then(data => {
        const resultElement = document.getElementById('commands_result');
        if (data.ok) {
            resultElement.innerHTML = `<div class="alert alert-success">Comandos configurados correctamente</div>`;
        } else {
            resultElement.innerHTML = `<div class="alert alert-danger">Error: ${data.description}</div>`;
        }
    })
    .catch(error => {
        document.getElementById('commands_result').innerHTML = 
            `<div class="alert alert-danger">Error al conectar con Telegram: ${error.message}</div>`;
    });
}
</script>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
?> 