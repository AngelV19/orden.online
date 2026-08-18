<?php
/**
 * RESTAURANT PREMIUM — API: Reimprimir comanda de pedido
 * Archivo: admin/api/reprint_pedido.php
 */
session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Printer.php';
    require_once __DIR__ . '/../../includes/PrintNode.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Acceso no permitido.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['ok' => false, 'message' => 'ID de pedido inválido.']);
    exit;
}

try {
    $db = db();

    // Cargar pedido
    $s = $db->prepare('SELECT * FROM pedidos WHERE id=:id');
    $s->execute([':id' => $id]);
    $pedido = $s->fetch();

    if (!$pedido) {
        echo json_encode(['ok' => false, 'message' => 'Pedido no encontrado.']);
        exit;
    }

    // Cargar items con opciones
    $sItems = $db->prepare('SELECT * FROM pedido_items WHERE pedido_id=:id');
    $sItems->execute([':id' => $id]);
    $items = $sItems->fetchAll();

    foreach ($items as &$item) {
        $sOp = $db->prepare('SELECT * FROM pedido_item_opciones WHERE item_id=:id');
        $sOp->execute([':id' => $item['id']]);
        $item['opciones'] = $sOp->fetchAll();
    }
    unset($item);

    // Usar PrintNode si está configurado, sino IP local
    $usePrintNode = (bool)(int)cfg('printnode_activo', '0')
                    && cfg('printnode_apikey', '') !== ''
                    && cfg('printnode_printer_id', '') !== '';

    if ($usePrintNode) {
        PrintNode::imprimirPedido($pedido, $items);
    } else {
        // Forzar impresión por IP aunque esté desactivada
        $configActiva = cfg('impresora_activa', '0');
        $_SERVER['_rp_settings']['impresora_activa'] = '1';
        Printer::imprimirPedido($pedido, $items);
        $_SERVER['_rp_settings']['impresora_activa'] = $configActiva;
    }

    echo json_encode([
        'ok'      => true,
        'message' => '✅ Comanda #' . $pedido['codigo'] . ' enviada a la impresora.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok'      => false,
        'message' => '❌ Error al imprimir: ' . $e->getMessage()
    ]);
}
