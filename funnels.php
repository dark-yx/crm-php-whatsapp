<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener lista de embudos
$funnels = fetchAll("
    SELECT f.*, 
           (SELECT COUNT(*) FROM leads WHERE funnel_id = f.id) as total_leads,
           (SELECT COUNT(*) FROM leads WHERE funnel_id = f.id AND status = 'won') as won_leads
    FROM funnels f
    ORDER BY f.created_at DESC
");

// Configurar el título de la página
$page_title = 'Embudos de Ventas';

// Incluir el header
include 'includes/header.php';
?>

<!-- Botones de acción -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Embudos de Ventas</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#funnelModal">
        <i class="bi bi-plus-lg"></i> Nuevo Embudo
    </button>
</div>

<!-- Lista de embudos -->
<div class="row">
    <?php foreach ($funnels as $funnel): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($funnel['name']); ?></h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" 
                            onclick="editFunnel(<?php echo $funnel['id']; ?>)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="deleteFunnel(<?php echo $funnel['id']; ?>)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="card-text"><?php echo htmlspecialchars($funnel['description']); ?></p>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Progreso</span>
                        <span><?php echo $funnel['won_leads']; ?> / <?php echo $funnel['total_leads']; ?></span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $funnel['total_leads'] ? ($funnel['won_leads'] / $funnel['total_leads'] * 100) : 0; ?>%">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between text-muted small">
                    <div>
                        <i class="bi bi-people"></i>
                        <?php echo $funnel['total_leads']; ?> leads
                    </div>
                    <div>
                        <i class="bi bi-trophy"></i>
                        <?php echo $funnel['won_leads']; ?> ganados
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="funnel-details.php?id=<?php echo $funnel['id']; ?>" class="btn btn-primary w-100">
                    Ver Detalles
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal para agregar/editar embudo -->
<div class="modal fade" id="funnelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="funnelModalTitle">Nuevo Embudo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="funnelForm">
                    <input type="hidden" id="funnelId" name="id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Etapas</label>
                        <div id="stagesContainer">
                            <!-- Las etapas se agregarán dinámicamente aquí -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addStage()">
                            <i class="bi bi-plus-lg"></i> Agregar Etapa
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveFunnel()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones para la gestión de embudos
function editFunnel(id) {
    // Cargar datos del embudo
    fetch(`api/funnels.php?id=${id}`)
        .then(response => response.json())
        .then(funnel => {
            document.getElementById('funnelModalTitle').textContent = 'Editar Embudo';
            document.getElementById('funnelId').value = funnel.id;
            document.getElementById('name').value = funnel.name;
            document.getElementById('description').value = funnel.description;
            
            // Cargar etapas
            const stagesContainer = document.getElementById('stagesContainer');
            stagesContainer.innerHTML = '';
            
            funnel.stages.forEach((stage, index) => {
                addStage(stage.name, stage.description, stage.order_position);
            });
            
            new bootstrap.Modal(document.getElementById('funnelModal')).show();
        });
}

function deleteFunnel(id) {
    showConfirm('¿Está seguro de eliminar este embudo?', function() {
        fetch(`api/funnels.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error al eliminar el embudo');
            }
        });
    });
}

function addStage(name = '', description = '', order = null) {
    const stagesContainer = document.getElementById('stagesContainer');
    const stageIndex = stagesContainer.children.length;
    
    const stageDiv = document.createElement('div');
    stageDiv.className = 'card mb-2';
    stageDiv.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Etapa ${stageIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeStage(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="mb-2">
                <input type="text" class="form-control" name="stages[${stageIndex}][name]" 
                       placeholder="Nombre de la etapa" value="${name}" required>
            </div>
            <div>
                <textarea class="form-control" name="stages[${stageIndex}][description]" 
                          placeholder="Descripción" rows="2">${description}</textarea>
            </div>
            <input type="hidden" name="stages[${stageIndex}][order]" value="${order ?? stageIndex}">
        </div>
    `;
    
    stagesContainer.appendChild(stageDiv);
}

function removeStage(button) {
    button.closest('.card').remove();
    // Reordenar las etapas restantes
    const stagesContainer = document.getElementById('stagesContainer');
    Array.from(stagesContainer.children).forEach((stage, index) => {
        stage.querySelector('h6').textContent = `Etapa ${index + 1}`;
        stage.querySelector('input[name$="[order]"]').value = index;
    });
}

function saveFunnel() {
    const form = document.getElementById('funnelForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Procesar las etapas
    const stages = [];
    const stageInputs = form.querySelectorAll('input[name^="stages["]');
    stageInputs.forEach(input => {
        const index = input.name.match(/\[(\d+)\]/)[1];
        if (!stages[index]) {
            stages[index] = {};
        }
        const field = input.name.match(/\[([^\]]+)\]$/)[1];
        stages[index][field] = input.value;
    });
    
    data.stages = stages.filter(Boolean);
    
    fetch('api/funnels.php', {
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
            alert('Error al guardar el embudo');
        }
    });
}

// Inicializar el modal para nuevo embudo
document.querySelector('[data-bs-target="#funnelModal"]').addEventListener('click', function() {
    document.getElementById('funnelModalTitle').textContent = 'Nuevo Embudo';
    document.getElementById('funnelForm').reset();
    document.getElementById('funnelId').value = '';
    document.getElementById('stagesContainer').innerHTML = '';
    addStage(); // Agregar primera etapa por defecto
});
</script>

<?php include 'includes/footer.php'; ?> 