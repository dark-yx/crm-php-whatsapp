<?php
/**
 * Configuración de la base de datos
 */

// Credenciales de la base de datos remota
$db_host = 'IP_REMOTA'; // Reemplazar con la IP de tu servidor remoto
$db_name = 'crm_whatsapp';
$db_user = 'usuario_remoto';
$db_pass = 'contraseña_remota';

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Intentar conexión
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    // Registrar el error
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    
    // Mostrar mensaje amigable al usuario
    die("Lo sentimos, estamos experimentando problemas técnicos. Por favor, intente más tarde.");
}

// Función para ejecutar consultas
function query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta SQL: " . $e->getMessage());
        return false;
    }
}

// Función para obtener un solo registro
function fetch($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

// Función para obtener múltiples registros
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

// Función para obtener el último ID insertado
function lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// Función para iniciar una transacción
function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

// Función para confirmar una transacción
function commit() {
    global $pdo;
    return $pdo->commit();
}

// Función para revertir una transacción
function rollBack() {
    global $pdo;
    return $pdo->rollBack();
}
?> 