<?php
/**
 * RESTAURANT PREMIUM — API: Opciones de un platillo
 * Archivo: pages/api/opciones_platillo.php
 */
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode([]); exit; }

$db = db();

// Grupos asignados al platillo con sus opciones
$stmt = $db->prepare(
    'SELECT g.id AS grupo_id, g.nombre AS grupo_nombre, g.tipo, g.descripcion AS grupo_desc,
            g.requerido, g.multiple, g.min_sel, g.max_sel,
            o.id AS opcion_id, o.nombre AS opcion_nombre, o.descripcion AS opcion_desc,
            o.precio
     FROM platillo_opciones po
     JOIN opcion_grupos g ON g.id = po.grupo_id AND g.activo = 1
     JOIN opciones o ON o.grupo_id = g.id AND o.disponible = 1
     WHERE po.platillo_id = :id
     ORDER BY po.orden ASC, o.orden ASC'
);
$stmt->execute([':id' => $id]);
$rows = $stmt->fetchAll();

// Agrupar por grupo
$grupos = [];
foreach ($rows as $row) {
    $gid = $row['grupo_id'];
    if (!isset($grupos[$gid])) {
        $grupos[$gid] = [
            'id'          => $gid,
            'nombre'      => $row['grupo_nombre'],
            'tipo'        => $row['tipo'],
            'descripcion' => $row['grupo_desc'],
            'requerido'   => (bool)$row['requerido'],
            'multiple'    => (bool)$row['multiple'],
            'min_sel'     => (int)$row['min_sel'],
            'max_sel'     => (int)$row['max_sel'],
            'opciones'    => [],
        ];
    }
    $grupos[$gid]['opciones'][] = [
        'id'          => $row['opcion_id'],
        'nombre'      => $row['opcion_nombre'],
        'descripcion' => $row['opcion_desc'],
        'precio'      => (float)$row['precio'],
    ];
}

echo json_encode(array_values($grupos));
