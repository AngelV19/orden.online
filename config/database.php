<?php
/**
 * RESTAURANT PREMIUM - Configuración de Base de Datos
 * Archivo: config/database.php
 */

// ── Credenciales (ajustar según tu entorno XAMPP) ──────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'restaurant_premium');
define('DB_USER',    'root');
define('DB_PASS',    '');          // En producción, usar contraseña segura
define('DB_CHARSET', 'utf8mb4');

// ── Configuración de la aplicación ────────────────────────────────────────
define('APP_NAME',   'Restaurant Premium');
define('APP_URL',    'http://localhost/restaurant');
define('APP_VERSION','1.0.0');

// WhatsApp (cambiar por número real con código de país, sin + ni espacios)
define('WHATSAPP_NUMBER', '5215512345678');
define('WHATSAPP_MSG',    'Hola, me gustaría hacer una reservación en Restaurant Premium.');

// Google Maps Embed (obtener API key en console.cloud.google.com)
define('GMAPS_EMBED_URL', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3762.661936286583!2d-99.16869492394!3d19.427024981884!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85d1ff35f5bd1563%3A0x6c366f0e2de02ff7!2sEl+%C3%81ngel+de+la+Independencia!5e0!3m2!1ses-419!2smx!4v1700000000000!5m2!1ses-419!2smx');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

/**
 * Clase de conexión a la base de datos (Singleton PDO)
 */
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // En producción: loguear el error, no mostrarlo
                error_log('DB Error: ' . $e->getMessage());
                die(json_encode(['error' => 'Error de conexión a la base de datos.']));
            }
        }
        return self::$instance;
    }
}

// Alias rápido
function db(): PDO {
    return Database::getConnection();
}
