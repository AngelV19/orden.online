<?php
/**
 * RESTAURANT PREMIUM - Funciones Auxiliares
 * Archivo: includes/functions.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

// ── Sanitización y seguridad ──────────────────────────────────────────────

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizeEmail(string $email): string|false {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

function sanitizePhone(string $phone): string {
    return preg_replace('/[^\d\+\-\s\(\)]/', '', trim($phone));
}

// ── Utilidades de imagen ──────────────────────────────────────────────────

function platilloImg(string|null $img, string $nombre = ''): string {
    if ($img && $img !== 'placeholder' && file_exists(__DIR__ . '/../uploads/' . $img)) {
        return APP_URL . '/uploads/' . h($img);
    }
    $letter = mb_strtoupper(mb_substr($nombre, 0, 1));
    return "data:image/svg+xml;utf8," . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
        . '<rect width="400" height="300" fill="#1a1a1a"/>'
        . '<text x="200" y="160" font-family="serif" font-size="80" fill="#c9a84c" '
        . 'text-anchor="middle" dominant-baseline="middle">' . $letter . '</text>'
        . '<text x="200" y="230" font-family="sans-serif" font-size="14" fill="#888" '
        . 'text-anchor="middle">Sin imagen</text>'
        . '</svg>'
    );
}

function subirImagen(array $file, string $destDir = ''): string|false {
    $allowed   = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize   = 3 * 1024 * 1024;
    $uploadDir = __DIR__ . '/../uploads/' . ltrim($destDir, '/');

    if ($file['error'] !== UPLOAD_ERR_OK)      return false;
    if ($file['size'] > $maxSize)               return false;
    if (!in_array($file['type'], $allowed, true)) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (!in_array($finfo->file($file['tmp_name']), $allowed, true)) return false;

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = match($finfo->file($file['tmp_name'])) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;

    return move_uploaded_file($file['tmp_name'], $uploadDir . $filename)
        ? $filename
        : false;
}

// ── Consultas de la BD ────────────────────────────────────────────────────

function getCategorias(bool $soloActivas = true): array {
    $sql = 'SELECT * FROM categorias' . ($soloActivas ? ' WHERE activo = 1' : '') . ' ORDER BY orden ASC';
    return db()->query($sql)->fetchAll();
}

function getPlatillos(bool $soloDisponibles = true, ?int $categoriaId = null): array {
    $where = [];
    $params = [];

    if ($soloDisponibles)    { $where[] = 'p.disponible = 1'; }
    if ($categoriaId !== null) { $where[] = 'p.categoria_id = :cat'; $params[':cat'] = $categoriaId; }

    $sql = 'SELECT p.*, c.nombre AS categoria_nombre
            FROM platillos p
            JOIN categorias c ON c.id = p.categoria_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY p.orden ASC, p.id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getTestimonios(): array {
    return db()->query('SELECT * FROM testimonios WHERE activo = 1 ORDER BY RAND() LIMIT 6')->fetchAll();
}

function getGaleria(): array {
    return db()->query('SELECT * FROM galeria WHERE activo = 1 ORDER BY orden ASC')->fetchAll();
}

function getPlatillosDestacados(): array {
    $stmt = db()->prepare(
        'SELECT p.*, c.nombre AS categoria_nombre
         FROM platillos p JOIN categorias c ON c.id = p.categoria_id
         WHERE p.destacado = 1 AND p.disponible = 1 LIMIT 6'
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

// ── Reservas ──────────────────────────────────────────────────────────────

function crearReserva(array $data): bool|string {
    if (empty($data['nombre']) || strlen($data['nombre']) < 2) return 'Nombre inválido.';
    if (empty($data['email']) || !sanitizeEmail($data['email'])) return 'Correo inválido.';
    if (empty($data['telefono'])) return 'Teléfono requerido.';
    if (empty($data['fecha']) || strtotime($data['fecha']) < strtotime('today')) return 'Fecha inválida.';
    if (empty($data['hora'])) return 'Hora requerida.';
    $personas = (int)($data['personas'] ?? 2);
    if ($personas < 1 || $personas > 20) return 'Número de personas inválido.';

    $stmt = db()->prepare(
        'INSERT INTO reservas (nombre, telefono, email, fecha, hora, personas, mensaje)
         VALUES (:nombre, :telefono, :email, :fecha, :hora, :personas, :mensaje)'
    );
    $stmt->execute([
        ':nombre'   => trim($data['nombre']),
        ':telefono' => sanitizePhone($data['telefono']),
        ':email'    => sanitizeEmail($data['email']),
        ':fecha'    => $data['fecha'],
        ':hora'     => $data['hora'],
        ':personas' => $personas,
        ':mensaje'  => trim($data['mensaje'] ?? ''),
    ]);
    return true;
}

// ── Sesión admin ──────────────────────────────────────────────────────────

function adminLogin(string $email, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = :email AND activo = 1 LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']    = $user['id'];
        $_SESSION['admin_nombre']= $user['nombre'];
        $_SESSION['admin_rol']   = $user['rol'];
        db()->prepare('UPDATE usuarios SET last_login = NOW() WHERE id = :id')
             ->execute([':id' => $user['id']]);
        return true;
    }
    return false;
}

function isAdminLogged(): bool {
    return isset($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLogged()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function adminLogout(): void {
    session_destroy();
    header('Location: ' . APP_URL . '/admin/login.php');
    exit;
}

// ── Formato ───────────────────────────────────────────────────────────────

/**
 * Formatea un precio usando la configuración de moneda del restaurante.
 * Antes hardcodeado a "$X.XX MXN" — ahora dinámico desde Admin > Configuración > Moneda.
 */
function formatPrecio(float $precio): string {
    return formatMoneda($precio);
}

function formatFecha(string $fecha): string {
    return date('d/m/Y', strtotime($fecha));
}

function formatHora(string $hora): string {
    return date('h:i A', strtotime($hora));
}

function estrellas(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
