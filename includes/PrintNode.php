<?php
/**
 * RESTAURANT PREMIUM — Integración PrintNode
 * Archivo: includes/PrintNode.php
 *
 * PrintNode permite imprimir desde cualquier servidor
 * en la nube hacia una impresora local en el restaurante.
 *
 * Setup:
 * 1. Crear cuenta en printnode.com (gratis hasta 50 prints/mes)
 * 2. Instalar el cliente PrintNode en la PC del restaurante
 * 3. La impresora aparece en tu dashboard de PrintNode con un ID
 * 4. Copiar API Key y Printer ID al panel de configuración
 */

require_once __DIR__ . '/../config/settings.php';

class PrintNode {

    private const API_URL = 'https://api.printnode.com';

    private string $apiKey;

    public function __construct(string $apiKey = '') {
        $this->apiKey = $apiKey ?: cfg('printnode_apikey', '');
    }

    // ── Obtener impresoras disponibles ────────────────────
    public function getPrinters(): array {
        $response = $this->request('GET', '/printers');
        return json_decode($response, true) ?? [];
    }

    // ── Enviar trabajo de impresión ───────────────────────
    /**
     * @param string $printerId  ID de la impresora en PrintNode
     * @param string $content    Contenido en base64
     * @param string $type       'pdf' o 'raw_base64'
     * @param string $title      Título del trabajo
     */
    public function print(
        string $printerId,
        string $content,
        string $type  = 'pdf',
        string $title = 'Comanda'
    ): array {
        $data = [
            'printerId' => (int)$printerId,
            'title'     => $title,
            'contentType' => $type,
            'content'   => base64_encode($content),
            'source'    => 'Restaurant Premium',
        ];

        $response = $this->request('POST', '/printjobs', $data);
        $result   = json_decode($response, true);

        if (is_numeric($result)) {
            return ['ok' => true, 'jobId' => $result];
        }

        return ['ok' => false, 'error' => $response];
    }

    // ── Imprimir texto RAW (ESC/POS) ─────────────────────
    public function printRaw(string $printerId, string $rawContent, string $title = 'Comanda'): array {
        return $this->print($printerId, $rawContent, 'raw_base64', $title);
    }

    // ── Verificar conexión ────────────────────────────────
    public function testConnection(): array {
        try {
            $response = $this->request('GET', '/whoami');
            $data     = json_decode($response, true);

            if (isset($data['email'])) {
                return [
                    'ok'      => true,
                    'message' => 'Conectado como: ' . $data['email'],
                    'data'    => $data,
                ];
            }
            return ['ok' => false, 'message' => 'API Key inválida.'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Imprimir comanda completa via PrintNode ───────────
    public static function imprimirPedido(array $pedido, array $items): bool {
        $apiKey    = cfg('printnode_apikey',      '');
        $printerId = cfg('printnode_printer_id',  '');
        $activo    = (bool)(int)cfg('printnode_activo', '0');

        if (!$activo || !$apiKey || !$printerId) return false;

        // Generar contenido ESC/POS usando la clase Printer
        require_once __DIR__ . '/Printer.php';

        $siteNombre = cfg('site_nombre', APP_NAME);

        $tipoLabels = [
            'salon'     => 'EN RESTAURANTE',
            'llevar'    => 'PARA LLEVAR',
            'domicilio' => 'A DOMICILIO',
        ];

        $metodoPago = [
            'efectivo'      => 'Efectivo',
            'tarjeta'       => 'Tarjeta',
            'transferencia' => 'Transferencia',
            'paypal'        => 'PayPal',
        ];

        $p = new Printer();

        $p->alignCenter()
          ->bold()->large()->line(mb_strtoupper($siteNombre))->normal()->bold(false)
          ->line('*** NUEVA ORDEN ***')
          ->emptyLines(1)
          ->large()->bold()->line('#' . $pedido['codigo'])->normal()->bold(false)
          ->line(date('d/m/Y  H:i:s', strtotime($pedido['created_at'] ?? 'now')))
          ->emptyLines(1)
          ->bold()->line('[ ' . ($tipoLabels[$pedido['tipo']] ?? strtoupper($pedido['tipo'])) . ' ]')->bold(false);

        if (!empty($pedido['mesa']))      $p->line('Mesa: ' . $pedido['mesa']);
        if (!empty($pedido['direccion'])) $p->line('Dir: ' . $pedido['direccion']);

        $p->doubleDivider()
          ->alignLeft()
          ->bold()->line('CLIENTE:')->bold(false)
          ->line($pedido['nombre'])
          ->line('Tel: ' . $pedido['telefono'])
          ->divider()
          ->bold()->line('PLATILLOS:')->bold(false)
          ->emptyLines(1);

        foreach ($items as $item) {
            $p->bold()
              ->twoColumns(
                  $item['cantidad'] . 'x ' . mb_strtoupper($item['nombre']),
                  '$' . number_format((float)$item['subtotal'], 2)
              )
              ->bold(false);

            foreach ($item['opciones'] ?? [] as $op) {
                $extraPrecio = (float)$op['precio'] > 0 ? ' +$' . number_format((float)$op['precio'], 2) : '';
                $p->line('  + ' . $op['nombre'] . $extraPrecio);
            }

            if (!empty($item['notas'])) $p->line('  * ' . $item['notas']);
            $p->emptyLines(1);
        }

        $p->divider()
          ->twoColumns('Subtotal:', '$' . number_format((float)$pedido['subtotal'], 2));

        if ((float)($pedido['impuesto'] ?? 0) > 0) {
            $p->twoColumns(
                cfg('impuesto_nombre', 'IVA') . ' ' . $pedido['impuesto_pct'] . '%:',
                '$' . number_format((float)$pedido['impuesto'], 2)
            );
        }

        $p->doubleDivider()
          ->bold()->large()
          ->twoColumns('TOTAL:', '$' . number_format((float)$pedido['total'], 2) . ' MXN')
          ->normal()->bold(false)
          ->twoColumns('Pago:', $metodoPago[$pedido['metodo_pago'] ?? 'efectivo'] ?? 'Efectivo');

        if (!empty($pedido['notas'])) {
            $p->divider()->bold()->line('NOTAS:')->bold(false)->line($pedido['notas']);
        }

        $p->doubleDivider()
          ->alignCenter()
          ->line('Gracias por su preferencia')
          ->line(date('H:i') . ' hrs')
          ->cut();

        // Enviar a PrintNode
        $pn     = new self($apiKey);
        $result = $pn->printRaw($printerId, $p->getBuffer(), 'Comanda #' . $pedido['codigo']);

        if (!$result['ok']) {
            error_log('PrintNode error: ' . ($result['error'] ?? 'unknown'));
        }

        return $result['ok'];
    }

    // ── HTTP Request ──────────────────────────────────────
    private function request(string $method, string $endpoint, array $data = []): string {
        $url = self::API_URL . $endpoint;
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->apiKey . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) throw new Exception('cURL error: ' . $error);
        if ($httpCode >= 400) throw new Exception('PrintNode API error ' . $httpCode . ': ' . $response);

        return $response;
    }
}
