<?php
/**
 * RESTAURANT PREMIUM — API: Sugerencias para el carrito
 * Archivo: pages/api/sugerencias.php
 *
 * Devuelve platillos sugeridos basándose en:
 * 1. Categorías no presentes en el carrito
 * 2. Platillos destacados que no están en el carrito
 * 3. Postres y bebidas siempre se sugieren
 */
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');

// IDs de platillos ya en el carrito (enviados por el cliente)
$cartIds = json_decode($_GET['ids'] ?? '[]', true);
$cartIds = array_map('intval', is_array($cartIds) ? $cartIds : []);

$db = db();

// ── Primero: sugerencias manuales del admin ───────────────
$sugerencias = [];
$excluir     = $cartIds ?: [0];
$exclP       = implode(',', array_fill(0, count($excluir), '?'));

// Verificar si existe la tabla sugerencias
try {
    $stmt = $db->prepare(
        "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen,
                c.nombre AS categoria_nombre
         FROM sugerencias s
         JOIN platillos p ON p.id = s.platillo_id
         JOIN categorias c ON c.id = p.categoria_id
         WHERE s.activo = 1
           AND p.disponible = 1
           AND p.id NOT IN ($exclP)
         ORDER BY s.orden ASC
         LIMIT 4"
    );
    $stmt->execute($excluir);
    $sugerencias = $stmt->fetchAll();
} catch (\Exception $e) {
    // Tabla no existe aún — usar automáticas
    $sugerencias = [];
}

// Si hay suficientes sugerencias manuales, devolverlas
if (count($sugerencias) >= 3) {
    $resultado = array_map(function(\$p) {
        return [
            'id'          => (int)\$p['id'],
            'nombre'      => \$p['nombre'],
            'descripcion' => mb_substr(\$p['descripcion'] ?? '', 0, 80),
            'precio'      => (float)\$p['precio'],
            'categoria'   => \$p['categoria_nombre'],
            'imagen'      => (\$p['imagen'] && \$p['imagen'] !== 'placeholder' && file_exists(__DIR__ . '/../../uploads/' . \$p['imagen']))
                             ? APP_URL . '/uploads/' . \$p['imagen']
                             : null,
        ];
    }, array_slice(\$sugerencias, 0, 4));
    echo json_encode(\$resultado);
    exit;
}

// ── Fallback: sugerencias automáticas ────────────────────
\$sugerencias = [];

// 1. Bebidas y postres — siempre sugerir si no hay en el carrito
$categoriasSiempre = $db->query(
    "SELECT id FROM categorias WHERE LOWER(nombre) REGEXP 'bebida|postre|dessert|drink' AND activo=1"
)->fetchAll();

$idsCatSiempre = array_column($categoriasSiempre, 'id');

if ($idsCatSiempre) {
    $placeholders = implode(',', array_fill(0, count($idsCatSiempre), '?'));
    $excluir      = $cartIds ?: [0];
    $exclPlaceholders = implode(',', array_fill(0, count($excluir), '?'));

    $stmt = $db->prepare(
        "SELECT p.*, c.nombre AS categoria_nombre
         FROM platillos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.categoria_id IN ($placeholders)
           AND p.id NOT IN ($exclPlaceholders)
           AND p.disponible = 1
         ORDER BY p.destacado DESC, RAND()
         LIMIT 3"
    );
    $stmt->execute(array_merge($idsCatSiempre, $excluir));
    $sugerencias = array_merge($sugerencias, $stmt->fetchAll());
}

// 2. Platillos destacados de otras categorías
if (count($sugerencias) < 4) {
    $excluirIds = array_merge(
        $cartIds,
        array_column($sugerencias, 'id'),
        [0]
    );
    $exclPlaceholders = implode(',', array_fill(0, count($excluirIds), '?'));

    $stmt = $db->prepare(
        "SELECT p.*, c.nombre AS categoria_nombre
         FROM platillos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.destacado = 1
           AND p.disponible = 1
           AND p.id NOT IN ($exclPlaceholders)
         ORDER BY RAND()
         LIMIT " . (4 - count($sugerencias))
    );
    $stmt->execute($excluirIds);
    $sugerencias = array_merge($sugerencias, $stmt->fetchAll());
}

// 3. Si aún faltan, agregar random disponibles
if (count($sugerencias) < 3) {
    $excluirIds = array_merge(
        $cartIds,
        array_column($sugerencias, 'id'),
        [0]
    );
    $exclPlaceholders = implode(',', array_fill(0, count($excluirIds), '?'));

    $stmt = $db->prepare(
        "SELECT p.*, c.nombre AS categoria_nombre
         FROM platillos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.disponible = 1
           AND p.id NOT IN ($exclPlaceholders)
         ORDER BY RAND()
         LIMIT " . (3 - count($sugerencias))
    );
    $stmt->execute($excluirIds);
    $sugerencias = array_merge($sugerencias, $stmt->fetchAll());
}

// Limitar a 4 sugerencias máximo
$sugerencias = array_slice($sugerencias, 0, 4);

// Formatear para el cliente
$resultado = array_map(function($p) {
    return [
        'id'         => (int)$p['id'],
        'nombre'     => $p['nombre'],
        'descripcion'=> mb_substr($p['descripcion'] ?? '', 0, 80),
        'precio'     => (float)$p['precio'],
        'categoria'  => $p['categoria_nombre'],
        'imagen'     => ($p['imagen'] && $p['imagen'] !== 'placeholder' && file_exists(__DIR__ . '/../../uploads/' . $p['imagen']))
                        ? APP_URL . '/uploads/' . $p['imagen']
                        : null,
    ];
}, $sugerencias);

echo json_encode($resultado);
