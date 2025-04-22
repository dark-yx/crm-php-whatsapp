<?php
/**
 * Punto de entrada principal de la aplicación
 */

// Definir constante para prevenir acceso directo a archivos
define('APP_ROOT', dirname(__DIR__));

// Cargar configuración
require_once APP_ROOT . '/config/hosting.php';

// Iniciar sesión
session_start();

// Manejar la URL
$url = isset($_GET['url']) ? $_GET['url'] : 'dashboard';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Determinar el controlador y la acción
$controller = isset($url[0]) ? $url[0] : 'dashboard';
$action = isset($url[1]) ? $url[1] : 'index';
$params = array_slice($url, 2);

// Verificar autenticación
if (!isset($_SESSION['user_id']) && $controller !== 'login' && $controller !== 'register') {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Cargar el controlador
$controllerFile = APP_ROOT . '/controllers/' . ucfirst($controller) . 'Controller.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = ucfirst($controller) . 'Controller';
    $controllerInstance = new $controllerClass();
    
    if (method_exists($controllerInstance, $action)) {
        call_user_func_array([$controllerInstance, $action], $params);
    } else {
        // Página no encontrada
        header("HTTP/1.0 404 Not Found");
        require_once APP_ROOT . '/views/errors/404.php';
    }
} else {
    // Página no encontrada
    header("HTTP/1.0 404 Not Found");
    require_once APP_ROOT . '/views/errors/404.php';
} 