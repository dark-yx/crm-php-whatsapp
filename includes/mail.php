<?php
/**
 * Funciones para el envío de correos electrónicos
 */

/**
 * Envía un correo electrónico usando PHPMailer
 * 
 * @param string $to Dirección de correo del destinatario
 * @param string $subject Asunto del correo
 * @param string $message Cuerpo del mensaje (HTML)
 * @param string $fromName Nombre del remitente (opcional)
 * @param array $attachments Array de archivos adjuntos (opcional)
 * @return bool True si el correo se envió correctamente, False en caso contrario
 */
function sendEmail($to, $subject, $message, $fromName = '', $attachments = []) {
    // Cargar configuración de correo
    $mailConfig = getMailConfig();
    
    // Si no hay configuración, usar mail() de PHP
    if (empty($mailConfig) || $mailConfig['mail_driver'] == 'php') {
        return sendPhpMail($to, $subject, $message, $fromName);
    }
    
    // Verificar si PHPMailer está disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Si no está disponible PHPMailer, intentar con mail() de PHP
        error_log("PHPMailer no está disponible. Usando mail() de PHP.");
        return sendPhpMail($to, $subject, $message, $fromName);
    }
    
    try {
        // Incluir autoloader de PHPMailer si está disponible
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración del servidor
        if ($mailConfig['mail_driver'] == 'smtp') {
            $mail->isSMTP();
            $mail->Host = $mailConfig['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['smtp_username'];
            $mail->Password = $mailConfig['smtp_password'];
            $mail->SMTPSecure = $mailConfig['smtp_encryption']; // tls o ssl
            $mail->Port = $mailConfig['smtp_port'];
        }
        
        // Configuración del remitente
        $mail->setFrom($mailConfig['mail_from_address'], $fromName ?: $mailConfig['mail_from_name']);
        $mail->addReplyTo($mailConfig['mail_from_address'], $fromName ?: $mailConfig['mail_from_name']);
        
        // Destinatario
        $mail->addAddress($to);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->CharSet = 'UTF-8';
        
        // Generar contenido alternativo en texto plano
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
        
        // Agregar archivos adjuntos si existen
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        // Si falla PHPMailer, intentar con mail() de PHP
        return sendPhpMail($to, $subject, $message, $fromName);
    }
}

/**
 * Envía un correo usando la función mail() nativa de PHP
 */
function sendPhpMail($to, $subject, $message, $fromName = '') {
    $mailConfig = getMailConfig();
    
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    
    $from = $mailConfig['mail_from_address'] ?? $_SERVER['SERVER_ADMIN'] ?? 'noreply@' . $_SERVER['HTTP_HOST'];
    $name = $fromName ?: ($mailConfig['mail_from_name'] ?? 'CRM WhatsApp');
    
    $headers .= 'From: ' . $name . ' <' . $from . '>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Envía un correo de recuperación de contraseña
 * 
 * @param string $email Correo electrónico del usuario
 * @param string $name Nombre del usuario
 * @param string $token Token de recuperación
 * @return bool True si el correo se envió correctamente
 */
function sendPasswordResetEmail($email, $name, $token) {
    $resetLink = getBaseUrl() . '/reset-password.php?token=' . urlencode($token);
    
    $subject = 'Recuperación de contraseña - CRM WhatsApp';
    
    $message = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e8e8e8; border-radius: 5px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="' . getBaseUrl() . '/assets/img/logo.png" alt="Logo" style="max-height: 60px;">
        </div>
        
        <h2 style="color: #333; text-align: center;">Recuperación de Contraseña</h2>
        
        <p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>
        
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en CRM WhatsApp. Para completar el proceso, haz clic en el siguiente enlace:</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . $resetLink . '" style="background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Restablecer Contraseña</a>
        </p>
        
        <p>Si no solicitaste este cambio, puedes ignorar este correo y tu contraseña seguirá siendo la misma.</p>
        
        <p>Este enlace expirará en 24 horas por motivos de seguridad.</p>
        
        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e8e8e8; color: #777; font-size: 0.9em;">
            Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
            <a href="' . $resetLink . '">' . $resetLink . '</a>
        </p>
    </div>
    ';
    
    return sendEmail($email, $subject, $message, 'CRM WhatsApp');
}

/**
 * Envía un correo de bienvenida al nuevo usuario
 * 
 * @param string $email Correo electrónico del usuario
 * @param string $name Nombre del usuario
 * @return bool True si el correo se envió correctamente
 */
function sendWelcomeEmail($email, $name) {
    $loginLink = getBaseUrl() . '/login.php';
    
    $subject = '¡Bienvenido a CRM WhatsApp!';
    
    $message = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e8e8e8; border-radius: 5px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="' . getBaseUrl() . '/assets/img/logo.png" alt="Logo" style="max-height: 60px;">
        </div>
        
        <h2 style="color: #333; text-align: center;">¡Bienvenido a CRM WhatsApp!</h2>
        
        <p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>
        
        <p>¡Gracias por registrarte en nuestro sistema CRM WhatsApp! Estamos emocionados de tenerte como usuario.</p>
        
        <p>Con nuestro CRM podrás:</p>
        <ul>
            <li>Gestionar contactos y leads de manera eficiente</li>
            <li>Automatizar comunicaciones a través de WhatsApp y otras plataformas</li>
            <li>Crear embudos de ventas personalizados</li>
            <li>Analizar el rendimiento de tus campañas</li>
        </ul>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . $loginLink . '" style="background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Iniciar Sesión</a>
        </p>
        
        <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactar a nuestro equipo de soporte.</p>
        
        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e8e8e8; color: #777; font-size: 0.9em; text-align: center;">
            © ' . date('Y') . ' CRM WhatsApp. Todos los derechos reservados.
        </p>
    </div>
    ';
    
    return sendEmail($email, $subject, $message, 'CRM WhatsApp');
}

/**
 * Obtiene la configuración de correo desde la base de datos
 * 
 * @return array Configuración de correo
 */
function getMailConfig() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_group = 'mail' LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config && isset($config['settings'])) {
            return json_decode($config['settings'], true);
        }
        
        // Configuración por defecto
        return [
            'mail_driver' => 'php',
            'mail_from_name' => 'CRM WhatsApp',
            'mail_from_address' => 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com'),
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls'
        ];
    } catch (Exception $e) {
        error_log("Error al obtener configuración de correo: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene la URL base del sitio
 * 
 * @return string URL base
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = dirname($scriptName);
    $path = $path !== '/' ? $path : '';
    
    return "$protocol://$host$path";
} 