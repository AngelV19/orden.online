<?php
/**
 * RESTAURANT PREMIUM — API: Sugerencias para el carrito
 * Archivo: pages/api/sugerencias.php
 */
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');

$cartIds = json_decode($_GET['ids'] ?? '[]', true);
$cartIds = array_map('intval', is_array($cartIds) ? $cartIds : []);

$db      = db();
$excluir = $cartIds ?: [0];
$exclP   = implode(',', array_fill(0, count($excluir), '?'));

// ── 1. Sugerencias manuales del admin ─────────────────────
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
    $manuales = $stmt->fetchAll();
} catch (Exception $e) {
    $manuales = [];
}

// Si hay sugerencias manuales, devolverlas
if (count($manuales) >= 1) {
    echo json_encode(array_map(function($p) {
        return [
            'id'          => (int)$p['id'],
            'nombre'      => $p['nombre'],
            'descripcion' => mb_substr($p['descripcion'] ?? '', 0, 80),
            'precio'      => (float)$p['precio'],
            'categoria'   => $p['categoria_nombre'],
            'imagen'      => ($p['imagen'] && $p['imagen'] !== 'placeholder'
                              && file_exists(__DIR__ . '/../../uploads/' . $p['imagen']))
                             ? APP_URL . '/uploads/' . $p['imagen']
                             : null,
        ];
    }, $manuales));
    exit;
}

// ── 2. Fallback: automáticas ──────────────────────────────
$sugerencias = [];

// Bebidas y postres
$catStmt = $db->query(
    "SELECT id FROM categorias WHERE LOWER(nombre) REGEXP 'bebida|postre|dessert|drink' AND activo=1"
);
$idsCat = array_column($catStmt->fetchAll(), 'id');

if ($idsCat) {
    $catP  = implode(',', array_fill(0, count($idsCat), '?'));
    $stmt2 = $db->prepare(
        "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen,
                c.nombre AS categoria_nombre
         FROM platillos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.categoria_id IN ($catP)
           AND p.id NOT IN ($exclP)
           AND p.disponible = 1
         ORDER BY p.destacado DESC, RAND()
         LIMIT 3"
    );
    $stmt2->execute(array_merge($idsCat, $excluir));
    $sugerencias = $stmt2->fetchAll();
}

// Destacados si faltan
if (count($sugerencias) < 4) {
    $excluirIds   = array_merge($excluir, array_column($sugerencias, 'id'));
    $exclP2       = implode(',', array_fill(0, count($excluirIds), '?'));
    $limit        = 4 - count($sugerencias);
    $stmt3        = $db->prepare(
        "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen,
                c.nombre AS categoria_nombre
         FROM platillos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.destacado = 1 AND p.disponible = 1
           AND p.id NOT IN ($exclP2)
         ORDER BY RAND() LIMIT $limit"
    );
    $stmt3->execute($excluirIds);
    $sugerencias = array_merge($sugerencias, $stmt3->fetchAll());
}

echo json_encode(array_map(function($p) {
    return [
        'id'          => (int)$p['id'],
        'nombre'      => $p['nombre'],
        'descripcion' => mb_substr($p['descripcion'] ?? '', 0, 80),
        'precio'      => (float)$p['precio'],
        'categoria'   => $p['categoria_nombre'],
        'imagen'      => ($p['imagen'] && $p['imagen'] !== 'placeholder'
                          && file_exists(__DIR__ . '/../../uploads/' . $p['imagen']))
                         ? APP_URL . '/uploads/' . $p['imagen']
                         : null,
    ];
}, array_slice($sugerencias, 0, 4)));