<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$funnel = isset($_GET['funnel']) ? $_GET['funnel'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir la consulta SQL base
$query = "
    SELECT l.*, 
           c.name as contact_name, 
           c.email as contact_email,
           c.phone as contact_phone,
           f.name as funnel_name,
           fs.name as stage_name
    FROM leads l
    LEFT JOIN contacts c ON l.contact_id = c.id
    LEFT JOIN funnels f ON l.funnel_id = f.id
    LEFT JOIN funnel_stages fs ON l.stage_id = fs.id
    WHERE 1=1
";

$params = [];

// Agregar filtros a la consulta
if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR l.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($funnel)) {
    $query .= " AND l.funnel_id = ?";
    $params[] = $funnel;
}

if (!empty($status)) {
    $query .= " AND l.status = ?";
    $params[] = $status;
}

// Contar el total de leads
$count_query = str_replace("l.*, 
           c.name as contact_name, 
           c.email as contact_email,
           c.phone as contact_phone,
           f.name as funnel_name,
           fs.name as stage_name", "COUNT(*) as total", $query);
$count_result = fetch($count_query, $params);
$total_leads = $count_result['total'];
$total_pages = ceil($total_leads / $per_page);

// Obtener leads para la página actual
$query .= " ORDER BY l.created_at DESC LIMIT $offset, $per_page";
$leads = fetchAll($query, $params);

// Obtener embudos para el filtro
$funnels = fetchAll("SELECT * FROM funnels WHERE status = 'active' ORDER BY name");

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col">
            <h1>Gestión de Leads</h1>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" onclick="showLeadModal()">
                <i class="bi bi-plus"></i> Nuevo Lead
            </button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card my-3">
        <div class="card-body">
            <form method="get" action="leads.php" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="funnel" name="funnel">
                        <option value="">Todos los embudos</option>
                        <?php foreach ($funnels as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $funnel == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos los estados</option>
                        <option value="open" <?php echo $status == 'open' ? 'selected' : ''; ?>>Abierto</option>
                        <option value="won" <?php echo $status == 'won' ? 'selected' : ''; ?>>Ganado</option>
                        <option value="lost" <?php echo $status == 'lost' ? 'selected' : ''; ?>>Perdido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de leads -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Embudo</th>
                            <th>Etapa</th>
                            <th>Valor</th>
                            <th>Estado</th>
                            <th>Fecha límite</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No se encontraron leads</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                    <td>
                                        <?php if ($lead['contact_id']): ?>
                                            <div><?php echo htmlspecialchars($lead['contact_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($lead['contact_email']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Sin contacto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($lead['funnel_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['stage_name']); ?></td>
                                    <td><?php echo number_format($lead['value'], 2); ?> €</td>
                                    <td>
                                        <?php if ($lead['status'] == 'open'): ?>
                                            <span class="badge bg-primary">Abierto</span>
                                        <?php elseif ($lead['status'] == 'won'): ?>
                                            <span class="badge bg-success">Ganado</span>
                                        <?php elseif ($lead['status'] == 'lost'): ?>
                                            <span class="badge bg-danger">Perdido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($lead['due_date']): ?>
                                            <?php echo date('d/m/Y', strtotime($lead['due_date'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin fecha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewLead(<?php echo $lead['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editLead(<?php echo $lead['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteLead(<?php echo $lead['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&funnel=<?php echo urlencode($funnel); ?>&status=<?php echo urlencode($status); ?>">Anterior</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&funnel=<?php echo urlencode($funnel); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&funnel=<?php echo urlencode($funnel); ?>&status=<?php echo urlencode($status); ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Lead -->
<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadModalTitle">Nuevo Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="leadForm">
                    <input type="hidden" id="lead_id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Nombre del Lead</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="value" class="form-label">Valor (€)</label>
                            <input type="number" class="form-control" id="value" name="value" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_id" class="form-label">Contacto</label>
                            <select class="form-select" id="contact_id" name="contact_id">
                                <option value="">Seleccione un contacto</option>
                                <!-- Se cargará dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Fecha límite</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="funnel_id" class="form-label">Embudo</label>
                            <select class="form-select" id="funnel_id" name="funnel_id" required>
                                <option value="">Seleccione un embudo</option>
                                <!-- Se cargará dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="stage_id" class="form-label">Etapa</label>
                            <select class="form-select" id="stage_id" name="stage_id" required>
                                <option value="">Seleccione una etapa</option>
                                <!-- Se cargará dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="lead_status" name="status">
                                <option value="open">Abierto</option>
                                <option value="won">Ganado</option>
                                <option value="lost">Perdido</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="noteContainer">
                        <label for="note" class="form-label">Nota</label>
                        <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                    </div>
                    
                    <div id="activitiesContainer" class="mb-3 d-none">
                        <h5>Actividades</h5>
                        <div id="activitiesList" class="list-group">
                            <!-- Se cargará dinámicamente -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveLead">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let leadModal;
let viewMode = false;

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    leadModal = new bootstrap.Modal(document.getElementById('leadModal'));
    
    // Cargar listas desplegables
    loadDropdowns();
    
    // Event listeners
    document.getElementById('funnel_id').addEventListener('change', loadStages);
    document.getElementById('saveLead').addEventListener('click', saveLead);
});

// Cargar listas desplegables
function loadDropdowns() {
    // Cargar contactos
    fetch('api/contacts.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('contact_id');
            select.innerHTML = '<option value="">Seleccione un contacto</option>';
            
            data.forEach(contact => {
                const option = document.createElement('option');
                option.value = contact.id;
                option.textContent = contact.name + (contact.email ? ` (${contact.email})` : '');
                select.appendChild(option);
            });
        });
    
    // Cargar embudos
    fetch('api/funnels.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('funnel_id');
            select.innerHTML = '<option value="">Seleccione un embudo</option>';
            
            data.forEach(funnel => {
                const option = document.createElement('option');
                option.value = funnel.id;
                option.textContent = funnel.name;
                select.appendChild(option);
            });
        });
}

// Cargar etapas según el embudo seleccionado
function loadStages() {
    const funnelId = document.getElementById('funnel_id').value;
    if (!funnelId) return;
    
    fetch(`api/funnels.php?id=${funnelId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('stage_id');
            select.innerHTML = '<option value="">Seleccione una etapa</option>';
            
            if (data.stages && data.stages.length > 0) {
                data.stages.forEach(stage => {
                    const option = document.createElement('option');
                    option.value = stage.id;
                    option.textContent = stage.name;
                    select.appendChild(option);
                });
            }
        });
}

// Mostrar modal para nuevo lead
function showLeadModal() {
    viewMode = false;
    document.getElementById('leadModalTitle').textContent = 'Nuevo Lead';
    document.getElementById('leadForm').reset();
    document.getElementById('lead_id').value = '';
    document.getElementById('activitiesContainer').classList.add('d-none');
    document.getElementById('noteContainer').classList.remove('d-none');
    
    // Habilitar campos para edición
    enableFormFields(true);
    
    leadModal.show();
}

// Ver detalles de un lead
function viewLead(id) {
    viewMode = true;
    document.getElementById('leadModalTitle').textContent = 'Detalles del Lead';
    document.getElementById('activitiesContainer').classList.remove('d-none');
    document.getElementById('noteContainer').classList.add('d-none');
    
    // Cargar datos del lead
    fetch(`api/leads.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Llenar formulario
            fillLeadForm(data);
            
            // Cargar actividades
            const activitiesList = document.getElementById('activitiesList');
            activitiesList.innerHTML = '';
            
            if (data.activities && data.activities.length > 0) {
                data.activities.forEach(activity => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item';
                    
                    let iconClass = 'bi-info-circle';
                    if (activity.type === 'created') iconClass = 'bi-plus-circle';
                    if (activity.type === 'stage_change') iconClass = 'bi-arrow-right-circle';
                    if (activity.type === 'status_change') iconClass = 'bi-check-circle';
                    if (activity.type === 'note') iconClass = 'bi-chat-left-text';
                    
                    const date = new Date(activity.created_at);
                    const formattedDate = date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
                    
                    item.innerHTML = `
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="bi ${iconClass}"></i> ${activity.description}</h6>
                            <small>${formattedDate}</small>
                        </div>
                    `;
                    
                    activitiesList.appendChild(item);
                });
            } else {
                activitiesList.innerHTML = '<div class="list-group-item">No hay actividades registradas</div>';
            }
            
            // Deshabilitar campos para solo lectura
            enableFormFields(false);
            
            leadModal.show();
        });
}

// Editar un lead
function editLead(id) {
    viewMode = false;
    document.getElementById('leadModalTitle').textContent = 'Editar Lead';
    document.getElementById('activitiesContainer').classList.add('d-none');
    document.getElementById('noteContainer').classList.remove('d-none');
    
    // Cargar datos del lead
    fetch(`api/leads.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Llenar formulario
            fillLeadForm(data);
            
            // Habilitar campos para edición
            enableFormFields(true);
            
            leadModal.show();
        });
}

// Llenar formulario de lead con datos
function fillLeadForm(data) {
    document.getElementById('lead_id').value = data.id;
    document.getElementById('name').value = data.name;
    document.getElementById('value').value = data.value;
    document.getElementById('lead_status').value = data.status;
    document.getElementById('due_date').value = data.due_date ? data.due_date.split(' ')[0] : '';
    
    // Manejar contacto, embudo y etapa (requiere cargar opciones dinámicamente)
    document.getElementById('contact_id').value = data.contact_id || '';
    document.getElementById('funnel_id').value = data.funnel_id || '';
    
    // Cargar etapas del embudo y luego seleccionar la correcta
    if (data.funnel_id) {
        fetch(`api/funnels.php?id=${data.funnel_id}`)
            .then(response => response.json())
            .then(funnel => {
                const select = document.getElementById('stage_id');
                select.innerHTML = '<option value="">Seleccione una etapa</option>';
                
                if (funnel.stages && funnel.stages.length > 0) {
                    funnel.stages.forEach(stage => {
                        const option = document.createElement('option');
                        option.value = stage.id;
                        option.textContent = stage.name;
                        select.appendChild(option);
                    });
                }
                
                // Seleccionar la etapa correcta
                document.getElementById('stage_id').value = data.stage_id || '';
            });
    }
}

// Habilitar o deshabilitar campos del formulario
function enableFormFields(enable) {
    const form = document.getElementById('leadForm');
    const elements = form.elements;
    
    for (let i = 0; i < elements.length; i++) {
        if (elements[i].id !== 'lead_id') {
            elements[i].disabled = !enable;
        }
    }
    
    // Mostrar u ocultar botón de guardar
    document.getElementById('saveLead').style.display = enable ? 'block' : 'none';
}

// Guardar un lead
function saveLead() {
    const form = document.getElementById('leadForm');
    const formData = new FormData(form);
    const jsonData = {};
    
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });
    
    fetch('api/leads.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal y recargar página
            leadModal.hide();
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar el lead'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar el lead');
    });
}

// Eliminar un lead
function deleteLead(id) {
    if (confirm('¿Está seguro de que desea eliminar este lead? Esta acción no se puede deshacer.')) {
        fetch(`api/leads.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar el lead'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el lead');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?> 