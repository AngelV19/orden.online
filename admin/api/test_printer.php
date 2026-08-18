<?php
/**
 * RESTAURANT PREMIUM — API: Probar impresora
 * Archivo: admin/api/test_printer.php
 */
session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Printer.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['ok' => false, 'message' => 'Acceso no permitido.']);
    exit;
}

try {
    $siteNombre = cfg('site_nombre', APP_NAME);
    $ip         = cfg('impresora_ip', '192.168.1.100');
    $puerto     = cfg('impresora_puerto', '9100');

    $p = new Printer();
    $p->alignCenter()
      ->bold()->large()->line(mb_strtoupper($siteNombre))->normal()->bold(false)
      ->emptyLines(1)
      ->line('*** PRUEBA DE IMPRESION ***')
      ->emptyLines(1)
      ->alignLeft()
      ->line('Conexion exitosa!')
      ->line('IP: ' . $ip . ':' . $puerto)
      ->line('Fecha: ' . date('d/m/Y H:i:s'))
      ->emptyLines(1)
      ->alignCenter()
      ->line('Restaurant Premium')
      ->cut();

    $p->print();

    echo json_encode([
        'ok'      => true,
        'message' => "✅ Impresora conectada en $ip:$puerto — Se imprimió una página de prueba."
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok'      => false,
        'message' => '❌ Error: ' . $e->getMessage() . ' — Verifica que la IP y el puerto sean correctos y que la impresora esté encendida y en la misma red.'
    ]);
}
