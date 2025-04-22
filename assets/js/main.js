// Funciones de inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeChat();
    initializeNotifications();
});

// Funciones de gráficos
function initializeCharts() {
    // Gráfico de actividad
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Mensajes',
                    data: [12, 19, 3, 5, 2, 3, 7],
                    borderColor: '#4e73df',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Gráfico de canales
    const channelsCtx = document.getElementById('channelsChart');
    if (channelsCtx) {
        new Chart(channelsCtx, {
            type: 'doughnut',
            data: {
                labels: ['WhatsApp', 'Telegram', 'Instagram', 'Messenger'],
                datasets: [{
                    data: [30, 20, 15, 35],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

// Funciones de chat
function initializeChat() {
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        // Scroll al final del chat
        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Envío de mensajes
        const messageForm = document.querySelector('.message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const messageInput = this.querySelector('input[type="text"]');
                const message = messageInput.value.trim();
                
                if (message) {
                    sendMessage(message);
                    messageInput.value = '';
                }
            });
        }
    }
}

function sendMessage(message) {
    // Aquí se implementará la lógica para enviar mensajes al servidor
    console.log('Mensaje enviado:', message);
}

// Funciones de notificaciones
function initializeNotifications() {
    // Verificar si el navegador soporta notificaciones
    if (!("Notification" in window)) {
        console.log("Este navegador no soporta notificaciones");
        return;
    }

    // Solicitar permiso para notificaciones
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }
}

function showNotification(title, body) {
    if (Notification.permission === "granted") {
        new Notification(title, {
            body: body,
            icon: '/assets/img/logo.png'
        });
    }
}

// Funciones de integración con APIs de mensajería
function connectWhatsApp(phoneNumber, apiKey) {
    // Implementar conexión con WhatsApp Business API
    console.log('Conectando WhatsApp:', phoneNumber);
}

function connectTelegram(botToken) {
    // Implementar conexión con Telegram Bot API
    console.log('Conectando Telegram:', botToken);
}

function connectInstagram(accessToken) {
    // Implementar conexión con Instagram Graph API
    console.log('Conectando Instagram:', accessToken);
}

function connectMessenger(pageToken) {
    // Implementar conexión con Messenger Platform
    console.log('Conectando Messenger:', pageToken);
}

// Funciones de chatbots
function processChatbotMessage(message) {
    // Implementar procesamiento de mensajes con OpenAI u otros servicios
    console.log('Procesando mensaje del chatbot:', message);
    return "Respuesta del chatbot";
}

// Funciones de embudos
function moveLeadToStage(leadId, stageId) {
    // Implementar movimiento de leads entre etapas
    console.log('Moviendo lead:', leadId, 'a etapa:', stageId);
}

// Funciones de búsqueda y filtrado
function searchContacts(query) {
    // Implementar búsqueda de contactos
    console.log('Buscando contactos:', query);
}

function filterContacts(filters) {
    // Implementar filtrado de contactos
    console.log('Filtrando contactos:', filters);
} 