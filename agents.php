<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar si el usuario es administrador
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Procesar formulario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? 'agent');
        $status = trim($_POST['status'] ?? 'active');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Por favor, complete todos los campos requeridos.';
        } else {
            try {
                if ($action === 'add') {
                    if (empty($password)) {
                        $error = 'La contraseña es requerida para nuevos agentes.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, role, status, password, created_at) 
                                             VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$name, $email, $phone, $role, $status, password_hash($password, PASSWORD_DEFAULT)]);
                        $message = 'Agente agregado correctamente.';
                    }
                } else {
                    if (!empty($password)) {
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, 
                                             status = ?, password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $role, $status, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, 
                                             status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $role, $status, $id]);
                    }
                    $message = 'Agente actualizado correctamente.';
                }
            } catch (PDOException $e) {
                $error = 'Error al guardar el agente: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Agente desactivado correctamente.';
            } catch (PDOException $e) {
                $error = 'Error al desactivar el agente: ' . $e->getMessage();
            }
        }
    }
}

// Obtener lista de agentes
$stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ('agent', 'admin') ORDER BY name");
$stmt->execute();
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir el encabezado
include_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Gestión de Agentes</h5>
                    <button type="button" class="btn btn-primary" onclick="showAgentModal()">
                        <i class="bi bi-plus-lg"></i> Nuevo Agente
                    </button>
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
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Conversaciones Activas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agents as $agent): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($agent['name']); ?></td>
                                        <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                        <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $agent['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                <?php echo htmlspecialchars($agent['role'] === 'admin' ? 'Administrador' : 'Agente'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $agent['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($agent['status'] === 'active' ? 'Activo' : 'Inactivo'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversations WHERE assigned_to = ? AND status = 'open'");
                                            $stmt->execute([$agent['id']]);
                                            $active_conversations = $stmt->fetchColumn();
                                            echo $active_conversations;
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editAgent(<?php echo $agent['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($agent['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteAgent(<?php echo $agent['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar/editar agente -->
<div class="modal fade" id="agentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="agentForm" method="post">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="agent_id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role">
                            <option value="agent">Agente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">Deje en blanco para mantener la contraseña actual (solo en edición).</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveAgent()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
function showAgentModal() {
    document.getElementById('action').value = 'add';
    document.getElementById('agent_id').value = '';
    document.getElementById('agentForm').reset();
    new bootstrap.Modal(document.getElementById('agentModal')).show();
}

function editAgent(id) {
    // Aquí se haría una petición AJAX para obtener los datos del agente
    // Por ahora simulamos los datos
    const agent = {
        id: id,
        name: 'Nombre del Agente',
        email: 'agente@ejemplo.com',
        phone: '123456789',
        role: 'agent',
        status: 'active'
    };
    
    document.getElementById('action').value = 'edit';
    document.getElementById('agent_id').value = agent.id;
    document.getElementById('name').value = agent.name;
    document.getElementById('email').value = agent.email;
    document.getElementById('phone').value = agent.phone;
    document.getElementById('role').value = agent.role;
    document.getElementById('status').value = agent.status;
    document.getElementById('password').value = '';
    
    new bootstrap.Modal(document.getElementById('agentModal')).show();
}

function deleteAgent(id) {
    if (confirm('¿Está seguro de que desea desactivar este agente?')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function saveAgent() {
    document.getElementById('agentForm').submit();
}
</script>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?> 