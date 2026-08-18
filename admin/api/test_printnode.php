<?php
/**
 * RESTAURANT PREMIUM — API: Probar PrintNode
 * Archivo: admin/api/test_printnode.php
 */
session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/PrintNode.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['ok' => false, 'message' => 'Acceso no permitido.']);
    exit;
}

$action = $_GET['action'] ?? 'test';
$apiKey = cfg('printnode_apikey', '');

if (!$apiKey) {
    echo json_encode(['ok' => false, 'message' => '❌ Ingresa tu API Key de PrintNode primero y guarda la configuración.']);
    exit;
}

try {
    $pn = new PrintNode($apiKey);

    if ($action === 'printers') {
        // Listar impresoras disponibles
        $printers = $pn->getPrinters();

        if (empty($printers)) {
            echo json_encode([
                'ok'      => false,
                'message' => 'No hay impresoras registradas. Instala el cliente PrintNode en la PC del restaurante.',
            ]);
            exit;
        }

        $list = array_map(fn($p) => [
            'id'     => $p['id'],
            'name'   => $p['name'],
            'state'  => $p['computer']['state'] ?? 'unknown',
        ], $printers);

        echo json_encode(['ok' => true, 'printers' => $list]);

    } else {
        // Probar conexión
        $result = $pn->testConnection();

        if ($result['ok']) {
            // Enviar página de prueba
            $printerId = cfg('printnode_printer_id', '');
            if ($printerId) {
                $siteNombre = cfg('site_nombre', APP_NAME);
                $testContent = "\x1B\x40" // init
                    . "\x1B\x61\x01"      // center
                    . "\x1B\x45\x01"      // bold
                    . "\x1B\x21\x30"      // large
                    . mb_strtoupper($siteNombre) . "\n"
                    . "\x1B\x21\x00"      // normal
                    . "\x1B\x45\x00"
                    . "*** PRUEBA PRINTNODE ***\n\n"
                    . "\x1B\x61\x00"      // left
                    . "Conexion exitosa!\n"
                    . "Fecha: " . date('d/m/Y H:i:s') . "\n"
                    . "API: " . substr($apiKey, 0, 6) . "...\n"
                    . "Printer ID: " . $printerId . "\n\n"
                    . str_repeat('-', 32) . "\n"
                    . "\x1B\x61\x01"
                    . "printnode.com\n"
                    . "\x1D\x56\x01";     // cut

                $pn->printRaw($printerId, $testContent, 'Test - Restaurant Premium');
                $result['message'] = '✅ ' . $result['message'] . ' — Página de prueba enviada a la impresora.';
            } else {
                $result['message'] = '✅ ' . $result['message'] . ' — Configura el ID de la impresora para imprimir.';
            }
        } else {
            $result['message'] = '❌ ' . $result['message'];
        }

        echo json_encode($result);
    }

} catch (Exception $e) {
    echo json_encode([
        'ok'      => false,
        'message' => '❌ Error: ' . $e->getMessage()
    ]);
}
