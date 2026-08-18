<?php
/**
 * RESTAURANT PREMIUM — API: Actualizar estado de reserva
 * Archivo: admin/api/update_reserva.php
 */
session_start();
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$id     = (int)($_POST['id']     ?? 0);
$estado = $_POST['estado'] ?? '';
$valid  = ['pendiente','confirmada','cancelada'];

if (!$id || !in_array($estado, $valid, true)) {
    echo json_encode(['ok' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$stmt = db()->prepare('UPDATE reservas SET estado=:e WHERE id=:id');
$stmt->execute([':e' => $estado, ':id' => $id]);

echo json_encode(['ok' => true, 'message' => 'Estado actualizado.']);
