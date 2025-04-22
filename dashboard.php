<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener periodo para las métricas (día, semana, mes, año)
$period = isset($_GET['period']) ? $_GET['period'] : 'month';

// Definir el formato de fecha y la condición SQL según el periodo
switch ($period) {
    case 'day':
        $date_format = '%H:00';
        $date_condition = "DATE(created_at) = CURDATE()";
        $group_by = "HOUR(created_at)";
        $interval = "HOUR";
        break;
    case 'week':
        $date_format = '%a';
        $date_condition = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $group_by = "DAYOFWEEK(created_at)";
        $interval = "DAY";
        break;
    case 'year':
        $date_format = '%b';
        $date_condition = "created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        $group_by = "MONTH(created_at)";
        $interval = "MONTH";
        break;
    default: // month
        $date_format = '%d';
        $date_condition = "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $group_by = "DAY(created_at)";
        $interval = "DAY";
        break;
}

// Obtener métricas generales
$total_contacts = fetch("SELECT COUNT(*) as total FROM contacts")['total'];
$total_conversations = fetch("SELECT COUNT(*) as total FROM conversations")['total'];
$total_leads = fetch("SELECT COUNT(*) as total FROM leads")['total'];
$won_leads = fetch("SELECT COUNT(*) as total FROM leads WHERE status = 'won'")['total'];
$conversion_rate = $total_leads > 0 ? round(($won_leads / $total_leads) * 100, 2) : 0;

// Obtener estadísticas de mensajes por canal
$messages_by_channel = fetchAll("
    SELECT c.channel, COUNT(m.id) as total_messages
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    GROUP BY c.channel
    ORDER BY total_messages DESC
");

// Obtener leads por embudo
$leads_by_funnel = fetchAll("
    SELECT f.name, COUNT(l.id) as total, 
           SUM(CASE WHEN l.status = 'won' THEN 1 ELSE 0 END) as won,
           SUM(CASE WHEN l.status = 'lost' THEN 1 ELSE 0 END) as lost,
           SUM(CASE WHEN l.status = 'open' THEN 1 ELSE 0 END) as open
    FROM leads l
    JOIN funnels f ON l.funnel_id = f.id
    GROUP BY f.id
    ORDER BY total DESC
");

// Obtener tendencia de contactos
$contacts_trend = fetchAll("
    SELECT DATE_FORMAT(created_at, '$date_format') as date_label, 
           COUNT(*) as total
    FROM contacts
    WHERE $date_condition
    GROUP BY $group_by
    ORDER BY created_at
");

// Obtener tendencia de mensajes
$messages_trend = fetchAll("
    SELECT DATE_FORMAT(m.created_at, '$date_format') as date_label, 
           COUNT(*) as total
    FROM messages m
    WHERE $date_condition
    GROUP BY $group_by
    ORDER BY m.created_at
");

// Obtener leads recientes
$recent_leads = fetchAll("
    SELECT l.*, c.name as contact_name, f.name as funnel_name, fs.name as stage_name
    FROM leads l
    LEFT JOIN contacts c ON l.contact_id = c.id
    LEFT JOIN funnels f ON l.funnel_id = f.id
    LEFT JOIN funnel_stages fs ON l.stage_id = fs.id
    ORDER BY l.created_at DESC
    LIMIT 5
");

// Obtener conversaciones recientes
$recent_conversations = fetchAll("
    SELECT c.*, ct.name as contact_name, 
           (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM conversations c
    JOIN contacts ct ON c.contact_id = ct.id
    ORDER BY c.last_message_at DESC
    LIMIT 5
");

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Dashboard</h1>
        <div class="btn-group">
            <a href="?period=day" class="btn btn-outline-primary <?php echo $period == 'day' ? 'active' : ''; ?>">Hoy</a>
            <a href="?period=week" class="btn btn-outline-primary <?php echo $period == 'week' ? 'active' : ''; ?>">Semana</a>
            <a href="?period=month" class="btn btn-outline-primary <?php echo $period == 'month' ? 'active' : ''; ?>">Mes</a>
            <a href="?period=year" class="btn btn-outline-primary <?php echo $period == 'year' ? 'active' : ''; ?>">Año</a>
        </div>
    </div>
    
    <!-- Tarjetas de métricas -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Contactos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_contacts); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Conversaciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_conversations); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-chat-dots-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-left-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Leads</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_leads); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-funnel-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Tasa de Conversión</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $conversion_rate; ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $conversion_rate; ?>%" aria-valuenow="<?php echo $conversion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos y tablas -->
    <div class="row">
        <!-- Tendencia de contactos -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Tendencia de Contactos</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="contactsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tendencia de mensajes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Tendencia de Mensajes</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="messagesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Mensajes por canal -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mensajes por Canal</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="channelChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($messages_by_channel as $index => $channel): ?>
                            <span class="mr-2">
                                <i class="fas fa-circle" style="color: <?php echo getColorForIndex($index); ?>"></i> <?php echo ucfirst($channel['channel']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Leads por embudo -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Leads por Embudo</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Embudo</th>
                                    <th>Total</th>
                                    <th>Abiertos</th>
                                    <th>Ganados</th>
                                    <th>Perdidos</th>
                                    <th>Tasa de Éxito</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads_by_funnel as $funnel): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($funnel['name']); ?></td>
                                        <td><?php echo $funnel['total']; ?></td>
                                        <td><?php echo $funnel['open']; ?></td>
                                        <td><?php echo $funnel['won']; ?></td>
                                        <td><?php echo $funnel['lost']; ?></td>
                                        <td>
                                            <?php 
                                                $success_rate = ($funnel['won'] + $funnel['lost'] > 0) ? 
                                                    round(($funnel['won'] / ($funnel['won'] + $funnel['lost'])) * 100, 2) : 0;
                                                echo $success_rate . '%';
                                            ?>
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
    
    <div class="row">
        <!-- Leads recientes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Leads Recientes</h6>
                    <a href="leads.php" class="btn btn-sm btn-primary">Ver todos</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Contacto</th>
                                    <th>Embudo</th>
                                    <th>Etapa</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_leads)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No hay leads recientes</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_leads as $lead): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                            <td><?php echo htmlspecialchars($lead['contact_name'] ?? 'Sin contacto'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['funnel_name']); ?></td>
                                            <td><?php echo htmlspecialchars($lead['stage_name']); ?></td>
                                            <td>
                                                <?php if ($lead['status'] == 'open'): ?>
                                                    <span class="badge bg-primary">Abierto</span>
                                                <?php elseif ($lead['status'] == 'won'): ?>
                                                    <span class="badge bg-success">Ganado</span>
                                                <?php elseif ($lead['status'] == 'lost'): ?>
                                                    <span class="badge bg-danger">Perdido</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Conversaciones recientes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Conversaciones Recientes</h6>
                    <a href="conversations.php" class="btn btn-sm btn-primary">Ver todas</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Contacto</th>
                                    <th>Canal</th>
                                    <th>Último Mensaje</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_conversations)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay conversaciones recientes</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_conversations as $conversation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($conversation['contact_name']); ?></td>
                                            <td>
                                                <?php 
                                                    $channel = $conversation['channel'];
                                                    $icon = '';
                                                    switch ($channel) {
                                                        case 'whatsapp': $icon = 'bi-whatsapp text-success'; break;
                                                        case 'telegram': $icon = 'bi-telegram text-primary'; break;
                                                        case 'instagram': $icon = 'bi-instagram text-danger'; break;
                                                        case 'messenger': $icon = 'bi-messenger text-info'; break;
                                                    }
                                                    echo '<i class="bi '.$icon.'"></i> ' . ucfirst($channel);
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars(mb_substr($conversation['last_message'], 0, 30)) . (mb_strlen($conversation['last_message']) > 30 ? '...' : ''); ?>
                                            </td>
                                            <td>
                                                <?php if ($conversation['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php elseif ($conversation['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Pendiente</span>
                                                <?php elseif ($conversation['status'] == 'closed'): ?>
                                                    <span class="badge bg-secondary">Cerrada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para generar colores basados en índice
function getChartColors(count) {
    const colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#5a5c69', '#858796', '#6610f2', '#fd7e14', '#20c9a6'
    ];
    
    return Array.from({length: count}, (_, i) => colors[i % colors.length]);
}

document.addEventListener('DOMContentLoaded', function() {
    // Datos para gráfico de contactos
    const contactsData = {
        labels: <?php echo json_encode(array_column($contacts_trend, 'date_label')); ?>,
        data: <?php echo json_encode(array_column($contacts_trend, 'total')); ?>
    };
    
    // Datos para gráfico de mensajes
    const messagesData = {
        labels: <?php echo json_encode(array_column($messages_trend, 'date_label')); ?>,
        data: <?php echo json_encode(array_column($messages_trend, 'total')); ?>
    };
    
    // Datos para gráfico de canales
    const channelData = {
        labels: <?php echo json_encode(array_column($messages_by_channel, 'channel')); ?>,
        data: <?php echo json_encode(array_column($messages_by_channel, 'total_messages')); ?>
    };
    
    // Crear gráfico de contactos
    const contactsCtx = document.getElementById('contactsChart').getContext('2d');
    new Chart(contactsCtx, {
        type: 'line',
        data: {
            labels: contactsData.labels,
            datasets: [{
                label: 'Nuevos Contactos',
                lineTension: 0.3,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: 'rgba(78, 115, 223, 1)',
                pointHoverRadius: 3,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: contactsData.data
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Crear gráfico de mensajes
    const messagesCtx = document.getElementById('messagesChart').getContext('2d');
    new Chart(messagesCtx, {
        type: 'line',
        data: {
            labels: messagesData.labels,
            datasets: [{
                label: 'Mensajes',
                lineTension: 0.3,
                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                borderColor: 'rgba(28, 200, 138, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                pointBorderColor: 'rgba(28, 200, 138, 1)',
                pointHoverRadius: 3,
                pointHoverBackgroundColor: 'rgba(28, 200, 138, 1)',
                pointHoverBorderColor: 'rgba(28, 200, 138, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: messagesData.data
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Crear gráfico de canales
    const channelCtx = document.getElementById('channelChart').getContext('2d');
    new Chart(channelCtx, {
        type: 'doughnut',
        data: {
            labels: channelData.labels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
            datasets: [{
                data: channelData.data,
                backgroundColor: getChartColors(channelData.labels.length),
                hoverBackgroundColor: getChartColors(channelData.labels.length),
                hoverBorderColor: 'rgba(234, 236, 244, 1)'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%'
        }
    });
});

function getColorForIndex(index) {
    const colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#5a5c69', '#858796', '#6610f2', '#fd7e14', '#20c9a6'
    ];
    return colors[index % colors.length];
}
</script>

<?php include 'includes/footer.php'; ?> 