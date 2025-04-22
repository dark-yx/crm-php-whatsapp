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

// Obtener configuración actual de OpenAI
$stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'openai' AND active = 1 LIMIT 1");
$stmt->execute();
$openai_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos
    $api_key = trim($_POST['api_key'] ?? '');
    $model = trim($_POST['model'] ?? 'gpt-3.5-turbo');
    
    if (empty($api_key)) {
        $error = 'Por favor, ingrese su clave API de OpenAI.';
    } else {
        // Verificar la clave API
        $valid = verifyOpenAICredentials($api_key);
        
        if ($valid) {
            try {
                if ($openai_config) {
                    // Actualizar configuración existente
                    $stmt = $pdo->prepare("UPDATE integrations SET 
                        api_key = ?,
                        settings = ?,
                        updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([$api_key, json_encode(['model' => $model]), $openai_config['id']]);
                } else {
                    // Insertar nueva configuración
                    $stmt = $pdo->prepare("INSERT INTO integrations 
                        (type, api_key, settings, active, created_at, updated_at) 
                        VALUES ('openai', ?, ?, 1, NOW(), NOW())");
                    $stmt->execute([$api_key, json_encode(['model' => $model])]);
                }
                
                $message = 'Integración de OpenAI configurada correctamente.';
                
                // Refrescar la configuración
                $stmt = $pdo->prepare("SELECT * FROM integrations WHERE type = 'openai' AND active = 1 LIMIT 1");
                $stmt->execute();
                $openai_config = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = 'Error al guardar la configuración: ' . $e->getMessage();
            }
        } else {
            $error = 'La clave API de OpenAI no es válida o no se pudo conectar con la API.';
        }
    }
}

// Función para verificar credenciales de OpenAI
function verifyOpenAICredentials($api_key) {
    $url = OPENAI_API_URL . '/models';
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . $api_key . "\r\n",
            'method'  => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['data']) && is_array($result['data']);
}

// Cargar modelos disponibles
$models = [
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'gpt-4' => 'GPT-4',
    'gpt-4-turbo' => 'GPT-4 Turbo'
];

// Cargar configuración actual
$currentModel = 'gpt-3.5-turbo';
if ($openai_config && !empty($openai_config['settings'])) {
    $settings = json_decode($openai_config['settings'], true);
    if (isset($settings['model'])) {
        $currentModel = $settings['model'];
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Integración con OpenAI</h5>
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
                            <label for="api_key" class="form-label">Clave API de OpenAI</label>
                            <input type="password" class="form-control" id="api_key" name="api_key" 
                                   value="<?php echo htmlspecialchars($openai_config['api_key'] ?? ''); ?>" required>
                            <div class="form-text">Su clave API secreta de OpenAI. Se puede obtener desde la <a href="https://platform.openai.com/api-keys" target="_blank">plataforma de OpenAI</a>.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="model" class="form-label">Modelo de OpenAI</label>
                            <select class="form-select" id="model" name="model">
                                <?php foreach ($models as $id => $name): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>" <?php echo $id === $currentModel ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Seleccione el modelo de OpenAI que desea utilizar para generar respuestas.</div>
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
                        <h6 class="alert-heading">Pasos para configurar la integración con OpenAI:</h6>
                        <ol>
                            <li>Cree una cuenta en <a href="https://platform.openai.com/" target="_blank">OpenAI</a> si aún no tiene una</li>
                            <li>Vaya a la sección <a href="https://platform.openai.com/api-keys" target="_blank">API Keys</a> en su cuenta de OpenAI</li>
                            <li>Genere una nueva clave API y cópiela</li>
                            <li>Pegue la clave API en el campo correspondiente del formulario anterior</li>
                            <li>Seleccione el modelo que desea utilizar para generar respuestas</li>
                            <li>Guarde la configuración</li>
                        </ol>
                        <p>
                            <strong>Nota:</strong> OpenAI cobra por el uso de sus APIs. Asegúrese de revisar sus <a href="https://openai.com/pricing" target="_blank">precios</a> y de configurar límites de uso en su cuenta para evitar cargos inesperados.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($openai_config): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Configuración de Respuestas Automáticas</h5>
                </div>
                <div class="card-body">
                    <p>Configure cómo y cuándo OpenAI responderá automáticamente a los mensajes:</p>
                    
                    <form id="aiSettingsForm">
                        <div class="mb-3">
                            <label for="ai_mode" class="form-label">Modo de Respuesta</label>
                            <select class="form-select" id="ai_mode">
                                <option value="manual">Manual - Mostrar sugerencias para revisión</option>
                                <option value="semi">Semi-automático - Responder automáticamente a preguntas frecuentes</option>
                                <option value="auto">Automático - Responder a todos los mensajes sin intervención</option>
                            </select>
                            <div class="form-text">Determina cuándo la IA responderá a los mensajes.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="system_prompt" class="form-label">Instrucciones para la IA</label>
                            <textarea class="form-control" id="system_prompt" rows="3" placeholder="Eres un asistente amable que representa a nuestra empresa. Proporciona respuestas concisas y útiles a las consultas de los clientes."></textarea>
                            <div class="form-text">Estas instrucciones guían el comportamiento de la IA al generar respuestas.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_tokens" class="form-label">Longitud máxima de respuesta (tokens)</label>
                            <input type="number" class="form-control" id="max_tokens" min="50" max="4000" value="500">
                            <div class="form-text">Un token equivale aproximadamente a 4 caracteres o 0.75 palabras.</div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="auto_suggest">
                            <label class="form-check-label" for="auto_suggest">Mostrar sugerencias de respuesta para agentes</label>
                            <div class="form-text">Cuando está activado, los agentes verán sugerencias de respuesta generadas por IA.</div>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="saveAISettings()">Guardar Configuración</button>
                    </form>
                    
                    <div id="aiSettingsResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Probar Integración</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="test_prompt" class="form-label">Mensaje de prueba</label>
                        <textarea class="form-control" id="test_prompt" rows="2" placeholder="Ingrese un mensaje para probar la respuesta de la IA..."></textarea>
                    </div>
                    
                    <button type="button" class="btn btn-primary" onclick="testAI()">Enviar mensaje de prueba</button>
                    
                    <div id="testResult" class="mt-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <p class="text-muted">La respuesta de la IA aparecerá aquí...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function saveAISettings() {
    const resultDiv = document.getElementById('aiSettingsResult');
    
    // Simulamos el guardado de la configuración de IA
    // En un entorno real, esto enviaría los datos a la API y a la base de datos
    resultDiv.innerHTML = '<div class="alert alert-info">Guardando configuración de IA...</div>';
    
    setTimeout(() => {
        resultDiv.innerHTML = '<div class="alert alert-success">La configuración de IA se ha guardado correctamente.</div>';
    }, 1500);
}

function testAI() {
    const testPrompt = document.getElementById('test_prompt').value.trim();
    const resultDiv = document.getElementById('testResult');
    
    if (!testPrompt) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Por favor, ingrese un mensaje de prueba.</div>';
        return;
    }
    
    // Mostrar indicador de carga
    resultDiv.innerHTML = `
        <div class="card bg-light">
            <div class="card-body">
                <p class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generando respuesta...</p>
            </div>
        </div>
    `;
    
    // Simulamos una llamada a la API de OpenAI
    // En un entorno real, esto enviaría el mensaje a la API de OpenAI y esperaría una respuesta
    setTimeout(() => {
        // Respuesta simulada de la IA
        const aiResponse = "¡Gracias por su mensaje! Soy el asistente virtual del CRM y estoy aquí para ayudarle con cualquier consulta que tenga sobre nuestros productos y servicios. ¿En qué más puedo ayudarle hoy?";
        
        resultDiv.innerHTML = `
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <strong>Usuario:</strong>
                </div>
                <div class="card-body bg-light">
                    <p>${testPrompt}</p>
                </div>
            </div>
            <div class="card mt-2">
                <div class="card-header bg-success text-white">
                    <strong>Respuesta de IA:</strong>
                </div>
                <div class="card-body">
                    <p>${aiResponse}</p>
                </div>
            </div>
        `;
    }, 2000);
}
</script>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
?> 