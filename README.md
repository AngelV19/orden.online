# 🍽️ Restaurant Premium — Guía de Instalación

Sistema web completo para restaurante de lujo.
PHP 8 · MySQL · Bootstrap 5 · XAMPP

---

## Requisitos

- XAMPP con PHP 8.0+ y MySQL 5.7+ / MariaDB 10.3+
- Navegador moderno

---

## Instalación paso a paso

### 1. Copiar el proyecto

Copia la carpeta `restaurant/` completa a:

```
C:\xampp\htdocs\restaurant\       (Windows)
/opt/lampp/htdocs/restaurant/     (Linux)
/Applications/XAMPP/htdocs/       (macOS)
```

### 2. Importar la base de datos

1. Inicia **XAMPP** y arranca **Apache** y **MySQL**.
2. Abre tu navegador en: `http://localhost/phpmyadmin`
3. Crea una nueva base de datos llamada: `restaurant_premium`
   - Charset: `utf8mb4`
   - Collation: `utf8mb4_unicode_ci`
4. Selecciona la BD creada → pestaña **Importar**
5. Selecciona el archivo: `restaurant/restaurant.sql`
6. Haz clic en **Importar**. ✅

### 3. Configurar la conexión

Abre `restaurant/config/database.php` y ajusta si es necesario:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_premium');
define('DB_USER', 'root');
define('DB_PASS', '');   // Tu contraseña de MySQL
```

Si la URL de tu proyecto es diferente, actualiza también:

```php
define('APP_URL', 'http://localhost/restaurant');
```

### 4. Permisos de la carpeta uploads

En Windows con XAMPP esto ya funciona. En Linux/macOS:

```bash
chmod -R 755 /opt/lampp/htdocs/restaurant/uploads/
chmod -R 755 /opt/lampp/htdocs/restaurant/admin/assets/
```

### 5. Abrir el sitio

- **Sitio público:** `http://localhost/restaurant/`
- **Panel admin:** `http://localhost/restaurant/admin/login.php`

---

## Credenciales de administración

| Campo    | Valor                         |
|----------|-------------------------------|
| Email    | admin@restaurantpremium.com   |
| Password | `password`                    |

> ⚠️ **IMPORTANTE:** Cambia la contraseña inmediatamente en producción.
>
> En el panel admin → Usuarios → Editar → nueva contraseña.

---

## Estructura de archivos

```
restaurant/
│
├── index.php                    # Página de inicio
├── restaurant.sql               # Script SQL completo
├── .htaccess                    # Seguridad Apache
│
├── config/
│   └── database.php             # Configuración BD y constantes
│
├── includes/
│   ├── functions.php            # Funciones auxiliares
│   ├── header.php               # Header HTML común
│   └── footer.php               # Footer HTML común
│
├── pages/
│   ├── menu.php                 # Menú digital con filtros
│   └── reservacion.php          # Formulario de reservas
│
├── admin/
│   ├── login.php                # Login administración
│   ├── logout.php               # Cerrar sesión
│   ├── dashboard.php            # Dashboard con estadísticas
│   ├── reservas.php             # CRUD reservaciones
│   ├── platillos.php            # CRUD platillos
│   ├── categorias.php           # CRUD categorías
│   ├── usuarios.php             # CRUD usuarios admin
│   ├── assets/
│   │   ├── admin.css            # Estilos del panel
│   │   └── admin.js             # Scripts del panel
│   ├── api/
│   │   └── update_reserva.php   # API actualización de estado
│   └── includes/
│       ├── sidebar.php          # Sidebar + head HTML admin
│       └── footer_admin.php     # Footer admin
│
├── assets/
│   ├── css/
│   │   └── main.css             # Estilos principales
│   ├── js/
│   │   └── main.js              # Scripts principales
│   └── images/
│       └── favicon.svg          # Favicon
│
└── uploads/                     # Imágenes subidas (auto-creado)
```

---

## Personalización rápida

### Cambiar nombre del restaurante
```php
// config/database.php
define('APP_NAME', 'Tu Restaurante');
```

### Cambiar número de WhatsApp
```php
// config/database.php
define('WHATSAPP_NUMBER', '5215512345678'); // sin + ni espacios
```

### Cambiar mapa de Google Maps
1. Ve a Google Maps y busca tu restaurante
2. Clic en "Compartir" → "Insertar mapa"
3. Copia la URL del `src="..."` del iframe
4. Pégala en `config/database.php` → `GMAPS_EMBED_URL`

---

## Funcionalidades incluidas

### Sitio público
- ✅ Hero section animado (Ken Burns)
- ✅ Contador estadísticas animado
- ✅ Platillos destacados
- ✅ Sección "Nosotros"
- ✅ Galería con lightbox (GLightbox)
- ✅ Testimonios carrusel automático
- ✅ CTA reservación
- ✅ Mapa embebido Google Maps
- ✅ Footer con horarios, contacto, redes
- ✅ Botón flotante WhatsApp
- ✅ Menú digital con filtros por categoría
- ✅ Búsqueda en tiempo real
- ✅ Formulario de reservas AJAX
- ✅ Scroll reveal animations
- ✅ Navbar que cambia al hacer scroll
- ✅ SEO + Open Graph + Twitter Cards
- ✅ Diseño 100% responsive (móvil, tablet, escritorio)

### Panel de administración
- ✅ Login seguro con hash de contraseña
- ✅ Dashboard con estadísticas en tiempo real
- ✅ Gráfica de reservas por mes (Chart.js)
- ✅ CRUD completo de platillos (con subida de imágenes)
- ✅ CRUD completo de categorías
- ✅ CRUD completo de reservaciones
- ✅ Cambio de estado de reservas inline
- ✅ Enlace directo a WhatsApp del cliente desde reservas
- ✅ CRUD de usuarios administradores
- ✅ Sidebar responsive con menú móvil

### Seguridad
- ✅ PDO con Prepared Statements en todas las queries
- ✅ Sanitización de inputs
- ✅ Validación server-side de formularios
- ✅ Password hashing con bcrypt (password_hash)
- ✅ Regeneración de session ID en login
- ✅ Headers de seguridad via .htaccess
- ✅ Validación de tipo real de archivos con finfo
- ✅ Protección de directorios sensibles

---

## Soporte y personalización

Para agregar funcionalidades como:
- Sistema de pedidos online
- Módulo de pagos
- Programa de lealtad
- Notificaciones por email

Contacta al desarrollador o extiende el proyecto usando la misma arquitectura MVC ligera.

---

*Restaurant Premium v1.0 — Producción lista*
