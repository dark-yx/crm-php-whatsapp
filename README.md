# CRM PHP WhatsApp

Un sistema CRM moderno con integración de mensajería para WhatsApp, Telegram, Instagram y Messenger.

## Características Principales

- Gestión de contactos y conversaciones
- Integración con múltiples plataformas de mensajería
- Embudos de ventas personalizables
- Gestión de agentes y asignación de conversaciones
- Plantillas de mensajes
- Interfaz moderna y responsiva

## Requisitos del Sistema

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Composer
- Node.js 14.x o superior
- npm 6.x o superior
- Servidor web (Apache/Nginx)
- SSL/TLS para conexiones seguras

## Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/weblifetech/crm-whatsapp.git
cd crm-whatsapp
```

2. Instalar dependencias PHP:
```bash
composer install
```

3. Instalar dependencias JavaScript:
```bash
npm install
```

4. Configurar la base de datos:
- Crear una base de datos MySQL
- Importar el archivo `database.sql`
- Configurar las credenciales en `config/database.php`

5. Configurar el servidor web:
- Configurar el document root al directorio `public`
- Asegurar que el directorio `storage` tenga permisos de escritura
- Configurar rewrite rules para Apache/Nginx

6. Configurar las integraciones:
- WhatsApp Business API
- Telegram Bot API
- Instagram Graph API
- Messenger Platform

## Configuración de Integraciones

### WhatsApp
1. Crear una cuenta de WhatsApp Business API
2. Obtener las credenciales de API
3. Configurar en `config/integrations.php`

### Telegram
1. Crear un bot con @BotFather
2. Obtener el token del bot
3. Configurar en `config/integrations.php`

### Instagram
1. Crear una cuenta de desarrollador de Facebook
2. Configurar la aplicación de Instagram
3. Obtener las credenciales de API
4. Configurar en `config/integrations.php`

### Messenger
1. Crear una aplicación en Facebook Developers
2. Configurar la plataforma de Messenger
3. Obtener las credenciales de API
4. Configurar en `config/integrations.php`

## Uso

1. Acceder al sistema:
   - URL: `https://tu-dominio.com`
   - Usuario por defecto: admin
   - Contraseña por defecto: admin123

2. Configurar las integraciones en el panel de administración

3. Gestionar contactos y conversaciones

4. Configurar embudos de ventas

5. Asignar agentes a conversaciones

## Contribución

Por favor, lee [CONTRIBUTING.md](CONTRIBUTING.md) para detalles sobre nuestro código de conducta y el proceso de envío de pull requests.

## Seguridad

Para reportar vulnerabilidades de seguridad, por favor lee [SECURITY.md](SECURITY.md).

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Soporte

Para soporte técnico, contacta a:
- Email: yosue@weblifetech.com
- Discord: [Enlace al servidor de Discord]

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

## 🚀 Instalación en Hosting Compartido (Hostinger)

### Requisitos del Hosting
- PHP 8.0 o superior
- MySQL 5.7 o superior
- mod_rewrite habilitado
- SSL/TLS (certificado HTTPS)
- Al menos 500MB de espacio en disco
- Al menos 256MB de memoria PHP

### Pasos de Instalación

1. **Preparar el Hosting**
   - Acceder al panel de control de Hostinger
   - Crear una base de datos MySQL
   - Crear un usuario para la base de datos
   - Anotar las credenciales de la base de datos

2. **Subir los Archivos**
   - Usar el administrador de archivos de Hostinger o FTP
   - Subir todos los archivos a la carpeta `public_html`
   - Asegurarse de que los permisos sean correctos:
     ```bash
     chmod 755 directorios
     chmod 644 archivos
     chmod 777 storage/
     ```

3. **Configurar la Base de Datos**
   - Importar el archivo `database.sql` desde phpMyAdmin
   - Editar `config/database.php` con las credenciales:
     ```php
     $db_host = 'localhost';      // Usar localhost
     $db_name = 'tu_base_de_datos';
     $db_user = 'tu_usuario';
     $db_pass = 'tu_contraseña';
     ```

4. **Configurar las Integraciones**
   - Editar `config/integrations.php` con las credenciales de las APIs
   - Asegurarse de que las URLs de webhook sean accesibles públicamente

5. **Configurar SSL**
   - Activar SSL en el panel de Hostinger
   - Asegurarse de que todas las URLs usen HTTPS

6. **Verificar Permisos**
   - Asegurarse de que los directorios tengan los permisos correctos:
     ```bash
     storage/ -> 777
     public/uploads/ -> 777
     config/ -> 755
     ```

### Solución de Problemas Comunes

1. **Error 500**
   - Verificar los logs de error en el panel de Hostinger
   - Comprobar permisos de archivos
   - Verificar configuración de PHP

2. **Problemas con mod_rewrite**
   - Verificar que esté habilitado en el panel de Hostinger
   - Comprobar que el archivo .htaccess esté correcto

3. **Problemas de Conexión a la Base de Datos**
   - Verificar credenciales en config/database.php
   - Comprobar que la base de datos existe
   - Verificar que el usuario tiene los permisos correctos

4. **Problemas con SSL**
   - Verificar que el certificado SSL esté activo
   - Comprobar que todas las URLs usen HTTPS
   - Verificar que no haya contenido mixto (HTTP/HTTPS)

### Optimización para Hosting Compartido

1. **Caché**
   - Habilitar OPcache en el panel de Hostinger
   - Usar la caché del navegador configurada en .htaccess

2. **Recursos**
   - Optimizar imágenes antes de subirlas
   - Usar CDN para recursos estáticos
   - Minimizar CSS y JavaScript

3. **Base de Datos**
   - Optimizar tablas regularmente
   - Usar índices apropiados
   - Limpiar datos antiguos 