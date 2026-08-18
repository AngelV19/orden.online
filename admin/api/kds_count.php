<?php
/**
 * RESTAURANT PREMIUM — API: KDS count de pedidos activos
 * Archivo: admin/api/kds_count.php
 * Usado por el KDS para detectar nuevos pedidos sin recargar toda la página
 */
session_start();
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$count = db()->query(
    "SELECT COUNT(*) FROM pedidos WHERE estado IN('nuevo','preparando','listo')"
)->fetchColumn();

$nuevos = db()->query(
    "SELECT COUNT(*) FROM pedidos WHERE estado = 'nuevo'"
)->fetchColumn();

echo json_encode([
    'count'  => (int)$count,
    'nuevos' => (int)$nuevos,
    'time'   => date('H:i:s'),
]);
