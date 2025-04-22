<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario ya está autenticado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = false;

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validar datos
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor, complete todos los campos';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } else {
        // Verificar si el email ya está registrado
        $existing_user = fetch("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existing_user) {
            $error = 'Este correo electrónico ya está registrado';
        } else {
            // Crear nuevo usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            query("
                INSERT INTO users (name, email, password, role, status, created_at)
                VALUES (?, ?, ?, 'user', 'active', CURRENT_TIMESTAMP)
            ", [$name, $email, $hashed_password]);
            
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CRM WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f8fa;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            padding: 15px;
            margin: auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            background-color: #4e73df;
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .form-floating label {
            color: #6c757d;
        }
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <img src="assets/img/logo.png" alt="Logo" class="logo">
                <h4 class="mb-0">CRM WhatsApp</h4>
            </div>
            <div class="card-body p-4">
                <h5 class="card-title text-center mb-4">Crear Cuenta</h5>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        ¡Registro exitoso! Ahora puede <a href="login.php" class="alert-link">iniciar sesión</a> con sus credenciales.
                    </div>
                <?php else: ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="register.php">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Su nombre" required>
                            <label for="name">Nombre Completo</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com" required>
                            <label for="email">Correo Electrónico</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                            <label for="password">Contraseña</label>
                            <div class="form-text">La contraseña debe tener al menos 8 caracteres.</div>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                            <label for="confirm_password">Confirmar Contraseña</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Registrarse
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center py-3 bg-light">
                <div class="small">
                    ¿Ya tiene una cuenta? <a href="login.php" class="text-decoration-none">Iniciar Sesión</a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted">
            <small>&copy; <?php echo date('Y'); ?> CRM WhatsApp. Todos los derechos reservados.</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 