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

- **Gestión de Usuarios**
  - Registro y autenticación
  - Recuperación de contraseña
  - Perfiles de usuario
  - Gestión de roles (admin/agente)

- **Gestión de Contactos**
  - Creación y edición de contactos
  - Búsqueda y filtrado avanzado
  - Historial de interacciones

- **Gestión de Conversaciones**
  - Interfaz de chat unificada
  - Soporte para múltiples canales
  - Historial de mensajes
  - Asignación de conversaciones

- **Integraciones**
  - WhatsApp Business API
  - Telegram Bot API
  - Instagram Graph API
  - Facebook Messenger
  - OpenAI para respuestas automáticas

- **Gestión de Ventas**
  - Funeles de venta personalizables
  - Seguimiento de leads
  - Plantillas de mensajes
  - Estadísticas y reportes

## Estructura del Proyecto

```
crm-php-whatsapp/
├── api/                    # Endpoints de la API
│   ├── funnels.php        # Gestión de funeles
│   ├── integrations.php   # Configuración de integraciones
│   ├── leads.php          # Gestión de leads
│   ├── messages.php       # Envío de mensajes
│   ├── templates.php      # Plantillas de mensajes
│   └── webhooks.php       # Webhooks para integraciones
│
├── assets/                # Recursos estáticos
│   ├── css/              # Estilos CSS
│   ├── js/               # Scripts JavaScript
│   └── img/              # Imágenes
│
├── config/               # Archivos de configuración
│   └── integrations.php  # Configuración de APIs
│
├── includes/            # Archivos incluidos
│   ├── functions.php    # Funciones auxiliares
│   ├── header.php       # Encabezado común
│   └── footer.php       # Pie de página común
│
├── integrations/        # Integraciones específicas
│   ├── whatsapp.php    # Integración WhatsApp
│   ├── telegram.php    # Integración Telegram
│   ├── instagram.php   # Integración Instagram
│   ├── messenger.php   # Integración Messenger
│   └── openai.php      # Integración OpenAI
│
├── agents.php          # Gestión de agentes
├── contacts.php        # Gestión de contactos
├── conversations.php   # Gestión de conversaciones
├── dashboard.php       # Panel principal
├── database.sql        # Estructura de la base de datos
├── forgot-password.php # Recuperación de contraseña
├── funnels.php         # Gestión de funeles
├── index.php           # Página principal
├── inbox.php           # Interfaz de mensajería
├── integrations.php    # Configuración de integraciones
├── leads.php           # Gestión de leads
├── login.php           # Autenticación
├── profile.php         # Perfil de usuario
├── register.php        # Registro de usuarios
└── reset-password.php  # Restablecimiento de contraseña
```

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

2. Configurar la base de datos:
- Crear una base de datos MySQL
- Importar el archivo `database.sql`
- Configurar las credenciales en `config/database.php`

3. Configurar las integraciones:
- Configurar las credenciales en `config/integrations.php`
- Configurar los webhooks en cada plataforma

4. Configurar el servidor web:
- Configurar el DocumentRoot al directorio del proyecto
- Asegurar que mod_rewrite esté habilitado (Apache)
- Configurar las reglas de reescritura si es necesario

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