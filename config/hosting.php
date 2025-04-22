<?php
/**
 * Configuración específica para hosting compartido
 */

// Configuración de rutas
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Configuración de URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $domain);
define('ASSETS_URL', BASE_URL . '/assets');

// Configuración de sesión
ini_set('session.save_path', STORAGE_PATH . '/sessions');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de memoria y tiempo de ejecución
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

// Configuración de subida de archivos
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// Configuración de caché
if (extension_loaded('opcache')) {
    ini_set('opcache.enable', 1);
    ini_set('opcache.memory_consumption', 128);
    ini_set('opcache.interned_strings_buffer', 8);
    ini_set('opcache.max_accelerated_files', 4000);
    ini_set('opcache.revalidate_freq', 60);
    ini_set('opcache.fast_shutdown', 1);
}

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', STORAGE_PATH . '/logs/error.log');

// Configuración de compresión
if (extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

// Configuración de seguridad
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\'; connect-src \'self\';');

// Incluir configuración de base de datos
require_once __DIR__ . '/database.php';

// Incluir configuración de integraciones
require_once __DIR__ . '/integrations.php'; 