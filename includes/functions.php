<?php
/**
 * Funciones auxiliares para el CRM
 */

/**
 * Ejecuta una consulta SQL y devuelve un array con todos los resultados
 * 
 * @param string $query Consulta SQL con marcadores preparados
 * @param array $params Parámetros para la consulta
 * @return array Array con todos los registros encontrados
 */
function fetchAll($query, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error en consulta fetchAll: ' . $e->getMessage());
        return [];
    }
}

/**
 * Ejecuta una consulta SQL y devuelve un solo registro
 * 
 * @param string $query Consulta SQL con marcadores preparados
 * @param array $params Parámetros para la consulta
 * @return array|false Array con el registro encontrado o false si no hay resultados
 */
function fetch($query, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error en consulta fetch: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ejecuta una consulta SQL para insertar, actualizar o eliminar registros
 * 
 * @param string $query Consulta SQL con marcadores preparados
 * @param array $params Parámetros para la consulta
 * @return int|false Número de filas afectadas o false en caso de error
 */
function query($query, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Error en consulta query: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el ID del último registro insertado
 * 
 * @return string|false El ID del último registro insertado o false en caso de error
 */
function lastInsertId() {
    global $pdo;
    
    try {
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error en lastInsertId: ' . $e->getMessage());
        return false;
    }
}

/**
 * Sanitiza un string para mostrar en HTML
 * 
 * @param string $string String a sanitizar
 * @return string String sanitizado
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirecciona a una URL
 * 
 * @param string $url URL a la que redireccionar
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Verifica si el usuario está autenticado
 * 
 * @return bool True si el usuario está autenticado, false en caso contrario
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica si el usuario tiene un rol específico
 * 
 * @param string $role Rol a verificar ('admin', 'user')
 * @return bool True si el usuario tiene el rol especificado, false en caso contrario
 */
function hasRole($role) {
    return isAuthenticated() && $_SESSION['user_role'] === $role;
}

/**
 * Formatea una fecha para mostrar
 * 
 * @param string $date Fecha en formato Y-m-d H:i:s
 * @param string $format Formato de salida
 * @return string Fecha formateada
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '';
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

/**
 * Genera un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * 
 * @param string $token Token CSRF a verificar
 * @return bool True si el token es válido, false en caso contrario
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtiene el nombre de un canal formateado
 * 
 * @param string $channel Nombre del canal ('whatsapp', 'telegram', etc.)
 * @return string Nombre del canal formateado
 */
function getChannelName($channel) {
    $channels = [
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'instagram' => 'Instagram',
        'messenger' => 'Messenger'
    ];
    
    return $channels[$channel] ?? ucfirst($channel);
}

/**
 * Obtiene la clase de icono para un canal
 * 
 * @param string $channel Nombre del canal ('whatsapp', 'telegram', etc.)
 * @return string Clase CSS del icono
 */
function getChannelIcon($channel) {
    $icons = [
        'whatsapp' => 'bi-whatsapp text-success',
        'telegram' => 'bi-telegram text-primary',
        'instagram' => 'bi-instagram text-danger',
        'messenger' => 'bi-messenger text-info'
    ];
    
    return $icons[$channel] ?? 'bi-chat-dots';
}

/**
 * Obtiene un color para un índice específico (útil para gráficos)
 * 
 * @param int $index Índice del color
 * @return string Código de color en hexadecimal
 */
function getColorForIndex($index) {
    $colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#5a5c69', '#858796', '#6610f2', '#fd7e14', '#20c9a6'
    ];
    
    return $colors[$index % count($colors)];
}

/**
 * Registra un mensaje de error en el log
 * 
 * @param string $message Mensaje de error
 * @param string $level Nivel de error ('error', 'warning', 'info')
 * @return void
 */
function logError($message, $level = 'error') {
    $date = date('Y-m-d H:i:s');
    $log = "[$date] [$level] $message" . PHP_EOL;
    
    $logFile = __DIR__ . '/../logs/app.log';
    
    // Crear directorio de logs si no existe
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $log, FILE_APPEND);
}

// Funciones de autenticación
function login($email, $password) {
    $user = fetch("SELECT * FROM users WHERE email = ?", [$email]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function getUserById($id) {
    return fetch("SELECT * FROM users WHERE id = ?", [$id]);
}

// Funciones de contactos
function getTotalContacts() {
    return fetch("SELECT COUNT(*) as total FROM contacts")['total'];
}

function getActiveConversations() {
    return fetch("SELECT COUNT(*) as total FROM conversations WHERE status = 'active'")['total'];
}

function getMessagesToday() {
    $today = date('Y-m-d');
    return fetch("SELECT COUNT(*) as total FROM messages WHERE DATE(created_at) = ?", [$today])['total'];
}

function getNewLeads() {
    $today = date('Y-m-d');
    return fetch("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) = ?", [$today])['total'];
}

// Funciones de mensajería
function sendMessage($channel, $contact_id, $message, $type = 'text') {
    $sql = "INSERT INTO messages (channel, contact_id, message, type, status) VALUES (?, ?, ?, ?, 'sent')";
    return query($sql, [$channel, $contact_id, $message, $type]);
}

function getContactMessages($contact_id) {
    return fetchAll("SELECT * FROM messages WHERE contact_id = ? ORDER BY created_at DESC", [$contact_id]);
}

// Funciones de chatbots
function processChatbotResponse($message, $contact_id) {
    // Aquí se integrará con OpenAI u otros servicios de IA
    // Por ahora, devolvemos una respuesta simple
    return "Gracias por tu mensaje. Un agente se pondrá en contacto contigo pronto.";
}

// Funciones de embudos
function moveLeadToStage($lead_id, $stage_id) {
    $sql = "UPDATE leads SET stage_id = ? WHERE id = ?";
    return query($sql, [$stage_id, $lead_id]);
}

function getLeadStages() {
    return fetchAll("SELECT * FROM lead_stages ORDER BY order_position");
}

// Funciones de integración
function connectWhatsApp($phone_number, $api_key) {
    // Implementar la conexión con WhatsApp Business API
    return true;
}

function connectTelegram($bot_token) {
    // Implementar la conexión con Telegram Bot API
    return true;
}

function connectInstagram($access_token) {
    // Implementar la conexión con Instagram Graph API
    return true;
}

function connectMessenger($page_token) {
    // Implementar la conexión con Messenger Platform
    return true;
}
?> 