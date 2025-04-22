<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$source = $_GET['source'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Construir la consulta SQL
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($source) {
    $where[] = "source = ?";
    $params[] = $source;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Obtener total de contactos
$total = fetch("SELECT COUNT(*) as total FROM contacts $whereClause", $params)['total'];
$totalPages = ceil($total / $perPage);

// Obtener contactos para la página actual
$offset = ($page - 1) * $perPage;
$contacts = fetchAll(
    "SELECT * FROM contacts $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Configurar el título de la página
$page_title = 'Contactos';

// Incluir el header
include 'includes/header.php';
?>

<!-- Filtros y búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Buscar contactos..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">Todos los estados</option>
                    <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>Nuevo</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="converted" <?php echo $status === 'converted' ? 'selected' : ''; ?>>Convertido</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="source">
                    <option value="">Todas las fuentes</option>
                    <option value="whatsapp" <?php echo $source === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                    <option value="telegram" <?php echo $source === 'telegram' ? 'selected' : ''; ?>>Telegram</option>
                    <option value="instagram" <?php echo $source === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                    <option value="messenger" <?php echo $source === 'messenger' ? 'selected' : ''; ?>>Messenger</option>
                    <option value="email" <?php echo $source === 'email' ? 'selected' : ''; ?>>Email</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de contactos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lista de Contactos</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
            <i class="bi bi-plus-lg"></i> Nuevo Contacto
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Empresa</th>
                        <th>Fuente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($contact['name']); ?></td>
                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                        <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                        <td><?php echo htmlspecialchars($contact['company']); ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo ucfirst($contact['source']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($contact['status']) {
                                    'new' => 'primary',
                                    'active' => 'success',
                                    'inactive' => 'secondary',
                                    'converted' => 'warning',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($contact['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="viewContact(<?php echo $contact['id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="editContact(<?php echo $contact['id']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteContact(<?php echo $contact['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&source=<?php echo urlencode($source); ?>">
                        Anterior
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&source=<?php echo urlencode($source); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&source=<?php echo urlencode($source); ?>">
                        Siguiente
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para agregar/editar contacto -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalTitle">Nuevo Contacto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="contactForm">
                    <input type="hidden" id="contactId" name="id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company" class="form-label">Empresa</label>
                        <input type="text" class="form-control" id="company" name="company">
                    </div>
                    
                    <div class="mb-3">
                        <label for="position" class="form-label">Cargo</label>
                        <input type="text" class="form-control" id="position" name="position">
                    </div>
                    
                    <div class="mb-3">
                        <label for="source" class="form-label">Fuente</label>
                        <select class="form-select" id="source" name="source" required>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telegram">Telegram</option>
                            <option value="instagram">Instagram</option>
                            <option value="messenger">Messenger</option>
                            <option value="email">Email</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="new">Nuevo</option>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="converted">Convertido</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveContact()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones para la gestión de contactos
function viewContact(id) {
    // Implementar vista detallada del contacto
    window.location.href = `contact-details.php?id=${id}`;
}

function editContact(id) {
    // Cargar datos del contacto
    fetch(`api/contacts.php?id=${id}`)
        .then(response => response.json())
        .then(contact => {
            document.getElementById('contactModalTitle').textContent = 'Editar Contacto';
            document.getElementById('contactId').value = contact.id;
            document.getElementById('name').value = contact.name;
            document.getElementById('email').value = contact.email;
            document.getElementById('phone').value = contact.phone;
            document.getElementById('company').value = contact.company;
            document.getElementById('position').value = contact.position;
            document.getElementById('source').value = contact.source;
            document.getElementById('status').value = contact.status;
            
            new bootstrap.Modal(document.getElementById('contactModal')).show();
        });
}

function deleteContact(id) {
    showConfirm('¿Está seguro de eliminar este contacto?', function() {
        fetch(`api/contacts.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error al eliminar el contacto');
            }
        });
    });
}

function saveContact() {
    const form = document.getElementById('contactForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch('api/contacts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error al guardar el contacto');
        }
    });
}

// Inicializar el modal para nuevo contacto
document.querySelector('[data-bs-target="#addContactModal"]').addEventListener('click', function() {
    document.getElementById('contactModalTitle').textContent = 'Nuevo Contacto';
    document.getElementById('contactForm').reset();
    document.getElementById('contactId').value = '';
});
</script>

<?php include 'includes/footer.php'; ?> 