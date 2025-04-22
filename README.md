# CRM PHP WhatsApp

Sistema de gestión de relaciones con clientes (CRM) desarrollado en PHP con integración de mensajería multiplataforma.

## Desarrollado por

**Jonnathan P.**
- Empresa: WEBLIFETECH
- Email: yosue@weblifetech.com
- Twitter: [@jonnathan.growth](https://twitter.com/jonnathan.growth)
- Instagram: [@jonnathan.growth](https://instagram.com/jonnathan.growth)
- GitHub: [dark-yx](https://github.com/dark-yx)
- LinkedIn: [jonnathan-growth](https://linkedin.com/in/jonnathan-growth)

## Características

- Gestión de contactos
- Gestión de conversaciones
- Integración con WhatsApp Business API
- Integración con Telegram
- Integración con Instagram
- Integración con Facebook Messenger
- Integración con OpenAI para respuestas automáticas
- Gestión de agentes
- Funeles de venta
- Plantillas de mensajes
- Estadísticas y reportes

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Composer (para dependencias PHP)
- Node.js y npm (para dependencias frontend)

## Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/dark-yx/crm-php-whatsapp.git
cd crm-php-whatsapp
```

2. Instalar dependencias PHP:
```bash
composer install
```

3. Instalar dependencias frontend:
```bash
npm install
```

4. Configurar la base de datos:
- Crear una base de datos MySQL
- Copiar `config/database.example.php` a `config/database.php`
- Configurar las credenciales de la base de datos

5. Configurar las integraciones:
- Configurar las credenciales de WhatsApp Business API
- Configurar las credenciales de Telegram Bot
- Configurar las credenciales de Instagram Graph API
- Configurar las credenciales de Facebook Messenger
- Configurar las credenciales de OpenAI

6. Inicializar la base de datos:
```bash
php database/init.php
```

## Uso

1. Acceder al sistema:
- URL: `http://localhost/crm-php-whatsapp`
- Usuario por defecto: admin@example.com
- Contraseña por defecto: admin123

2. Configurar las integraciones:
- Ir a Configuración > Integraciones
- Configurar cada plataforma de mensajería

3. Gestionar contactos:
- Ir a Contactos
- Agregar, editar o eliminar contactos

4. Gestionar conversaciones:
- Ir a Inbox
- Responder mensajes de los clientes

## Estructura del Proyecto

```
crm-php-whatsapp/
├── api/                 # Endpoints de la API
├── assets/             # Recursos estáticos
│   ├── css/           # Estilos CSS
│   ├── js/            # Scripts JavaScript
│   └── img/           # Imágenes
├── config/            # Archivos de configuración
├── database/          # Scripts de base de datos
├── includes/          # Archivos incluidos
├── integrations/      # Integraciones con plataformas
└── vendor/           # Dependencias PHP
```

## Contribución

1. Hacer fork del proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Hacer commit de tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Hacer push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Contacto

Para cualquier consulta o colaboración, puedes contactarme a través de:
- Email: yosue@weblifetech.com
- Twitter: [@jonnathan.growth](https://twitter.com/jonnathan.growth)
- LinkedIn: [jonnathan-growth](https://linkedin.com/in/jonnathan-growth)

Link del Proyecto: [https://github.com/dark-yx/crm-php-whatsapp](https://github.com/dark-yx/crm-php-whatsapp) 