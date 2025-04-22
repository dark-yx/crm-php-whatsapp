-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS crm_whatsapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm_whatsapp;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','user') NOT NULL DEFAULT 'user',
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `last_login` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de contactos
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `company` varchar(100) DEFAULT NULL,
    `telegram_id` varchar(50) DEFAULT NULL,
    `instagram_id` varchar(50) DEFAULT NULL,
    `messenger_id` varchar(50) DEFAULT NULL,
    `source` enum('whatsapp','telegram','instagram','messenger','manual') NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `phone` (`phone`),
    KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de conversaciones
CREATE TABLE IF NOT EXISTS `conversations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `contact_id` int(11) NOT NULL,
    `channel` enum('whatsapp','telegram','instagram','messenger') NOT NULL,
    `status` enum('active','pending','closed') NOT NULL DEFAULT 'active',
    `assigned_to` int(11) DEFAULT NULL,
    `last_message_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `contact_id` (`contact_id`),
    KEY `assigned_to` (`assigned_to`),
    CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS `messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `conversation_id` int(11) NOT NULL,
    `sender_id` int(11) DEFAULT NULL,
    `message` text NOT NULL,
    `type` enum('text','image','file','audio','video','location') NOT NULL DEFAULT 'text',
    `status` enum('sent','delivered','read','received') NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `conversation_id` (`conversation_id`),
    KEY `sender_id` (`sender_id`),
    CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de integraciones
CREATE TABLE IF NOT EXISTS `integrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` enum('whatsapp','telegram','instagram','messenger','openai') NOT NULL,
    `name` varchar(100) NOT NULL,
    `credentials` text NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `type` (`type`,`status`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de embudos
CREATE TABLE IF NOT EXISTS `funnels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de etapas de embudo
CREATE TABLE IF NOT EXISTS `funnel_stages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `funnel_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text,
    `order_position` int(11) NOT NULL DEFAULT '0',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `funnel_id` (`funnel_id`),
    CONSTRAINT `funnel_stages_ibfk_1` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de leads
CREATE TABLE IF NOT EXISTS `leads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `contact_id` int(11) DEFAULT NULL,
    `funnel_id` int(11) NOT NULL,
    `stage_id` int(11) NOT NULL,
    `value` decimal(10,2) NOT NULL DEFAULT '0.00',
    `status` enum('open','won','lost') NOT NULL DEFAULT 'open',
    `due_date` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `contact_id` (`contact_id`),
    KEY `funnel_id` (`funnel_id`),
    KEY `stage_id` (`stage_id`),
    CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`),
    CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`stage_id`) REFERENCES `funnel_stages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de actividades de lead
CREATE TABLE IF NOT EXISTS `lead_activities` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lead_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `type` enum('created','stage_change','status_change','note') NOT NULL,
    `description` text NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `lead_id` (`lead_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `lead_activities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `lead_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (hash ya aplicado)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`)
VALUES ('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW());

-- Insertar embudo de ejemplo
INSERT INTO `funnels` (`name`, `description`, `status`)
VALUES ('Ventas por WhatsApp', 'Proceso de ventas para leads capturados por WhatsApp', 'active');

-- Insertar etapas para el embudo de ejemplo
INSERT INTO `funnel_stages` (`funnel_id`, `name`, `description`, `order_position`)
VALUES 
(1, 'Contacto Inicial', 'Primer contacto con el cliente potencial', 1),
(1, 'Calificación', 'Validación de datos y necesidades', 2),
(1, 'Propuesta', 'Envío de propuesta comercial', 3),
(1, 'Negociación', 'Discusión de términos y condiciones', 4),
(1, 'Cierre', 'Cierre de la venta', 5); 