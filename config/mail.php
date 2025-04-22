<?php
/**
 * Configuración para el envío de correos electrónicos
 * Las credenciales y configuraciones dependerán del proveedor de correo utilizado
 */

return [
    // Configuración del servidor de correo
    'host' => 'smtp.example.com',     // Servidor SMTP (ej. smtp.gmail.com, smtp.office365.com)
    'port' => 587,                    // Puerto SMTP (comúnmente 587 para TLS, 465 para SSL)
    'encryption' => 'tls',            // Tipo de encriptación: 'tls' o 'ssl'
    'username' => 'usuario@example.com', // Nombre de usuario SMTP (generalmente el correo electrónico)
    'password' => 'contraseña_segura',  // Contraseña SMTP
    
    // Dirección de remitente
    'from' => 'noreply@example.com',  // Dirección de correo electrónico remitente
    
    // Configuración adicional
    'debug' => false,                 // Activar/desactivar depuración de PHPMailer
    'reply_to' => 'soporte@example.com', // Dirección de respuesta (opcional)
    
    // Límites y configuración de envío
    'limit_per_hour' => 100,          // Límite de correos por hora (para evitar bloqueos)
    'timeout' => 30,                  // Tiempo de espera en segundos para conectar al servidor
]; 