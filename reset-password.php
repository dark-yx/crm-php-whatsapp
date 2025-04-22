<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si el usuario ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$tokenValid = false;
$email = '';

// Verificar si el token es válido
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            $tokenValid = true;
            $email = $reset['email'];
        } else {
            $error = "El enlace de restablecimiento no es válido o ha expirado. Por favor, solicita un nuevo enlace.";
        }
    } catch (Exception $e) {
        error_log("Error al verificar token: " . $e->getMessage());
        $error = "Ha ocurrido un error al procesar tu solicitud. Por favor, inténtalo de nuevo más tarde.";
    }
} else {
    $error = "Token de restablecimiento no proporcionado. Por favor, utiliza el enlace enviado a tu correo electrónico.";
}

// Procesar el formulario de cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validar contraseña
    if (empty($password)) {
        $error = "Por favor, introduce una contraseña.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            // Actualizar la contraseña del usuario
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Iniciar transacción
            $pdo->beginTransaction();
            
            // Actualizar la contraseña
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            // Marcar el token como usado
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1, used_at = NOW() WHERE token = ?");
            $stmt->execute([$token]);
            
            // Confirmar transacción
            $pdo->commit();
            
            $success = "¡Tu contraseña ha sido actualizada correctamente! Ahora puedes iniciar sesión con tu nueva contraseña.";
            $tokenValid = false; // Ya no mostrar el formulario
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $pdo->rollBack();
            error_log("Error al restablecer contraseña: " . $e->getMessage());
            $error = "Ha ocurrido un error al actualizar tu contraseña. Por favor, inténtalo de nuevo más tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - CRM WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="assets/img/logo.png" alt="Logo" class="img-fluid mb-3" style="max-height: 70px;">
                            <h2 class="fw-bold">Restablecer Contraseña</h2>
                            <p class="text-muted">Introduce tu nueva contraseña para completar el proceso</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-success">Ir al Inicio de Sesión</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tokenValid): ?>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . htmlspecialchars($token); ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                    </div>
                                    <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Restablecer Contraseña</button>
                                    <a href="login.php" class="btn btn-outline-secondary">Cancelar</a>
                                </div>
                            </form>
                        <?php elseif (empty($success)): ?>
                            <div class="text-center mt-3">
                                <p>Puedes solicitar un nuevo enlace de restablecimiento:</p>
                                <a href="forgot-password.php" class="btn btn-outline-primary">Solicitar Nuevo Enlace</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted">
                    <small>&copy; <?php echo date('Y'); ?> CRM WhatsApp. Todos los derechos reservados.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 