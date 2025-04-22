# CRM PHP WhatsApp

Sistema de gestión de relaciones con clientes (CRM) desarrollado en PHP con integración de mensajería multiplataforma.

## 🚀 Proyecto de Código Abierto

Este es un proyecto de código abierto desarrollado inicialmente por Jonnathan P. y mantenido por la comunidad. Invitamos a todos los desarrolladores interesados a contribuir y mejorar el proyecto.

## 👥 Desarrollado por

**Jonnathan P.**
- Empresa: WEBLIFETECH
- Email: yosue@weblifetech.com
- Twitter: [@jonnathan.growth](https://twitter.com/jonnathan.growth)
- Instagram: [@jonnathan.growth](https://instagram.com/jonnathan.growth)
- GitHub: [dark-yx](https://github.com/dark-yx)
- LinkedIn: [jonnathan-growth](https://linkedin.com/in/jonnathan-growth)

## 📋 Características

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

## 🔧 Configuración de Credenciales

### 1. Base de Datos
Crear un archivo `config/database.php` con las siguientes credenciales:
```php
$db_host = 'localhost';      // Host del servidor
$db_name = 'crm_whatsapp';   // Nombre de la base de datos
$db_user = 'tu_usuario';     // Usuario de la base de datos
$db_pass = 'tu_contraseña';  // Contraseña del usuario
```

### 2. Integraciones
Crear un archivo `config/integrations.php` con las siguientes credenciales:

#### WhatsApp Business API
```php
define('WHATSAPP_PHONE_NUMBER_ID', 'tu_phone_number_id');
define('WHATSAPP_ACCESS_TOKEN', 'tu_access_token');
```

#### Telegram Bot
```php
define('TELEGRAM_BOT_TOKEN', 'tu_bot_token');
```

#### Instagram Graph API
```php
define('INSTAGRAM_ACCESS_TOKEN', 'tu_access_token');
```

#### Facebook Messenger
```php
define('MESSENGER_PAGE_TOKEN', 'tu_page_token');
```

#### OpenAI
```php
define('OPENAI_API_KEY', 'tu_api_key');
```

## 📁 Estructura del Proyecto

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
│   ├── database.php     # Configuración de base de datos
│   └── integrations.php # Configuración de APIs
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

## 🛠️ Instalación

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

## 🤝 Contribución

### Cómo Contribuir

1. **Fork del Proyecto**
   - Haz fork del repositorio en GitHub
   - Clona tu fork localmente

2. **Crear una Rama**
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```

3. **Hacer Cambios**
   - Desarrolla tu funcionalidad
   - Sigue las guías de estilo
   - Escribe pruebas si es posible

4. **Commit y Push**
   ```bash
   git add .
   git commit -m "Descripción clara de los cambios"
   git push origin feature/nueva-funcionalidad
   ```

5. **Pull Request**
   - Abre un Pull Request en GitHub
   - Describe los cambios realizados
   - Espera la revisión

### Guías de Estilo

- Usar PSR-12 para PHP
- Documentar el código con PHPDoc
- Seguir las convenciones de nombres
- Escribir pruebas unitarias
- Mantener el código limpio y organizado

### Issues y Mejoras

- Reportar bugs en Issues
- Proponer nuevas características
- Ayudar a otros desarrolladores
- Mejorar la documentación

## 📝 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 📞 Contacto

Para cualquier consulta o colaboración, puedes contactarme a través de:
- Email: yosue@weblifetech.com
- Twitter: [@jonnathan.growth](https://twitter.com/jonnathan.growth)
- LinkedIn: [jonnathan-growth](https://linkedin.com/in/jonnathan-growth)

Link del Proyecto: [https://github.com/dark-yx/crm-php-whatsapp](https://github.com/dark-yx/crm-php-whatsapp)

## 🙏 Agradecimientos

Gracias a todos los contribuidores que ayudan a mejorar este proyecto. Tu participación es valiosa para hacer este CRM cada vez mejor. 