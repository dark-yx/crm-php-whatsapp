<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener información del usuario actual
$user_id = $_SESSION['user_id'];
$user = fetchRow("SELECT * FROM users WHERE id = ?", [$user_id]);

// Manejar solicitud de actualización de perfil
$profile_updated = false;
$password_updated = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Actualizar información de perfil
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_SESSION['user_role']; // Mantener el mismo rol
        
        // Validaciones básicas
        if (empty($name)) {
            $errors[] = 'El nombre es obligatorio';
        }
        
        if (empty($email)) {
            $errors[] = 'El correo electrónico es obligatorio';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo electrónico es inválido';
        }
        
        // Verificar que el email no esté en uso por otro usuario
        $existingUser = fetchRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
        if ($existingUser) {
            $errors[] = 'El correo electrónico ya está en uso por otro usuario';
        }
        
        // Si no hay errores, actualizar el perfil
        if (empty($errors)) {
            $result = query(
                "UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?",
                [$name, $email, $user_id]
            );
            
            if ($result) {
                $profile_updated = true;
                // Actualizar información de sesión
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                // Actualizar la variable $user para mostrar los datos actualizados
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $errors[] = 'No se pudo actualizar el perfil';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Cambiar contraseña
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validaciones
        if (empty($current_password)) {
            $errors[] = 'La contraseña actual es obligatoria';
        }
        
        if (empty($new_password)) {
            $errors[] = 'La nueva contraseña es obligatoria';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Las contraseñas no coinciden';
        }
        
        // Verificar que la contraseña actual sea correcta
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'La contraseña actual es incorrecta';
        }
        
        // Si no hay errores, cambiar la contraseña
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $result = query(
                "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
                [$hashed_password, $user_id]
            );
            
            if ($result) {
                $password_updated = true;
            } else {
                $errors[] = 'No se pudo cambiar la contraseña';
            }
        }
    }
}

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Perfil de Usuario</h1>
            <p class="text-muted">Gestiona tu información personal y contraseña</p>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($profile_updated): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> Perfil actualizado correctamente
        </div>
    <?php endif; ?>
    
    <?php if ($password_updated): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> Contraseña cambiada correctamente
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Información del perfil -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-fill me-2"></i> Información Personal
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" readonly>
                            <div class="form-text">El rol solo puede ser cambiado por un administrador</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="created_at" class="form-label">Fecha de Registro</label>
                            <input type="text" class="form-control" id="created_at" value="<?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($user['created_at']))); ?>" readonly>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Cambio de contraseña -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-key-fill me-2"></i> Cambiar Contraseña
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            <div class="form-text">La contraseña debe tener al menos 8 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-secondary">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Preferencias de notificación (opcional) -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-bell-fill me-2"></i> Preferencias de Notificación
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="email_notifications" checked>
                            <label class="form-check-label" for="email_notifications">Notificaciones por correo electrónico</label>
                            <div class="form-text">Recibir notificaciones de nuevos mensajes y asignaciones por correo</div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="desktop_notifications" checked>
                            <label class="form-check-label" for="desktop_notifications">Notificaciones del navegador</label>
                            <div class="form-text">Recibir notificaciones del navegador cuando esté usando el sistema</div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="sound_notifications" checked>
                            <label class="form-check-label" for="sound_notifications">Notificaciones sonoras</label>
                            <div class="form-text">Reproducir sonidos para nuevos mensajes y alertas</div>
                        </div>
                        
                        <button type="button" class="btn btn-info text-white">
                            <i class="bi bi-save"></i> Guardar Preferencias
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Actividad reciente -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-clock-history me-2"></i> Actividad Reciente
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-item-content">
                                <span class="tag">
                                    <i class="bi bi-person-fill text-primary"></i>
                                </span>
                                <time>Hace 2 días</time>
                                <p>Actualización de perfil</p>
                                <span class="circle"></span>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-item-content">
                                <span class="tag">
                                    <i class="bi bi-chat-left-text-fill text-success"></i>
                                </span>
                                <time>Hace 5 días</time>
                                <p>Conversación asignada: Cliente XYZ</p>
                                <span class="circle"></span>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-item-content">
                                <span class="tag">
                                    <i class="bi bi-key-fill text-warning"></i>
                                </span>
                                <time>Hace 1 semana</time>
                                <p>Cambio de contraseña</p>
                                <span class="circle"></span>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-item-content">
                                <span class="tag">
                                    <i class="bi bi-door-open-fill text-info"></i>
                                </span>
                                <time>Hace 1 semana</time>
                                <p>Último inicio de sesión: <?php echo date('d/m/Y H:i:s'); ?></p>
                                <span class="circle"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para la línea de tiempo */
.timeline {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
}

.timeline-item {
    padding-left: 40px;
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline::after {
    content: '';
    position: absolute;
    width: 2px;
    background-color: #e0e0e0;
    top: 0;
    bottom: 0;
    left: 10px;
    margin-left: 0;
}

.timeline-item-content {
    position: relative;
    padding: 10px 15px;
    border-radius: 5px;
    background-color: #f8f9fa;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
}

.timeline-item-content time {
    font-size: 0.8rem;
    color: #6c757d;
    display: block;
    margin-bottom: 5px;
}

.timeline-item-content .tag {
    font-size: 1.2rem;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    left: -50px;
    z-index: 1;
}

.circle {
    background-color: white;
    border: 3px solid #e0e0e0;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    left: -47px;
    width: 15px;
    height: 15px;
    z-index: 1;
}
</style>

<?php include 'includes/footer.php'; ?> 