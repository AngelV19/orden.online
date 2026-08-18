<?php
/**
 * RESTAURANT PREMIUM — Clase de Impresora ESC/POS
 * Compatible con Epson TM-T20, TM-T88 y similares
 * Archivo: includes/Printer.php
 *
 * NO requiere librería externa — implementación directa ESC/POS
 */

require_once __DIR__ . '/../config/settings.php';

class Printer {

    // ── Comandos ESC/POS ──────────────────────────────────
    const ESC = "\x1B";
    const GS  = "\x1D";
    const NL  = "\n";

    // Inicializar impresora
    const INIT        = "\x1B\x40";
    // Alineación
    const ALIGN_LEFT   = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT  = "\x1B\x61\x02";
    // Negrita
    const BOLD_ON      = "\x1B\x45\x01";
    const BOLD_OFF     = "\x1B\x45\x00";
    // Tamaño de fuente
    const FONT_NORMAL  = "\x1B\x21\x00";
    const FONT_LARGE   = "\x1B\x21\x30"; // doble ancho y alto
    const FONT_MEDIUM  = "\x1B\x21\x10"; // doble alto
    // Subrayado
    const UNDERLINE_ON  = "\x1B\x2D\x01";
    const UNDERLINE_OFF = "\x1B\x2D\x00";
    // Corte de papel
    const CUT_FULL    = "\x1D\x56\x00";
    const CUT_PARTIAL = "\x1D\x56\x01";
    // Buzzer (si tiene)
    const BEEP        = "\x1B\x42\x03\x02";

    private string $buffer = '';
    private int    $lineWidth = 42; // caracteres por línea (Epson TM-T20 = 42)

    public function __construct() {
        $this->buffer = self::INIT;
    }

    // ── Métodos de contenido ──────────────────────────────

    public function text(string $text): self {
        $this->buffer .= $text;
        return $this;
    }

    public function line(string $text = ''): self {
        $this->buffer .= $text . self::NL;
        return $this;
    }

    public function emptyLines(int $n = 1): self {
        $this->buffer .= str_repeat(self::NL, $n);
        return $this;
    }

    public function divider(string $char = '-'): self {
        $this->buffer .= str_repeat($char, $this->lineWidth) . self::NL;
        return $this;
    }

    public function doubleDivider(): self {
        $this->buffer .= str_repeat('=', $this->lineWidth) . self::NL;
        return $this;
    }

    // Línea con texto izquierda y derecha (precio + nombre)
    public function twoColumns(string $left, string $right): self {
        $maxLeft  = $this->lineWidth - strlen($right) - 1;
        if (strlen($left) > $maxLeft) {
            $left = substr($left, 0, $maxLeft - 1) . '.';
        }
        $spaces = $this->lineWidth - strlen($left) - strlen($right);
        $this->buffer .= $left . str_repeat(' ', max(1, $spaces)) . $right . self::NL;
        return $this;
    }

    // ── Formato ───────────────────────────────────────────

    public function alignLeft(): self   { $this->buffer .= self::ALIGN_LEFT;   return $this; }
    public function alignCenter(): self { $this->buffer .= self::ALIGN_CENTER; return $this; }
    public function alignRight(): self  { $this->buffer .= self::ALIGN_RIGHT;  return $this; }

    public function bold(bool $on = true): self {
        $this->buffer .= $on ? self::BOLD_ON : self::BOLD_OFF;
        return $this;
    }

    public function large(): self  { $this->buffer .= self::FONT_LARGE;  return $this; }
    public function medium(): self { $this->buffer .= self::FONT_MEDIUM; return $this; }
    public function normal(): self { $this->buffer .= self::FONT_NORMAL; return $this; }

    public function cut(): self {
        $lineas = (int)cfg('impresora_lineas', '3');
        $this->emptyLines($lineas);
        $this->buffer .= self::CUT_PARTIAL;
        return $this;
    }

    // ── Obtener buffer raw ───────────────────────────────
    public function getBuffer(): string { return $this->buffer; }

    // ── Enviar a impresora ────────────────────────────────

    /**
     * Enviar el buffer a la impresora por red TCP/IP
     * @throws Exception si no puede conectar
     */
    public function print(): bool {
        $ip      = cfg('impresora_ip',     '192.168.1.100');
        $puerto  = (int)cfg('impresora_puerto', '9100');
        $copias  = max(1, (int)cfg('impresora_copias', '1'));
        $timeout = 5; // segundos

        $socket = @fsockopen($ip, $puerto, $errno, $errstr, $timeout);

        if (!$socket) {
            error_log("Printer error: $errstr ($errno) — IP: $ip:$puerto");
            throw new Exception("No se pudo conectar a la impresora en $ip:$puerto — $errstr");
        }

        for ($i = 0; $i < $copias; $i++) {
            fwrite($socket, $this->buffer);
        }

        fclose($socket);
        return true;
    }

    /**
     * Guardar el buffer como archivo (para debug)
     */
    public function saveToFile(string $path): void {
        file_put_contents($path, $this->buffer);
    }

    // ── Imprimir comanda de pedido ─────────────────────────

    /**
     * Genera e imprime la comanda completa de un pedido.
     */
    public static function imprimirPedido(array $pedido, array $items): bool {
        if (!(bool)(int)cfg('impresora_activa', '0')) return false;

        $siteNombre = cfg('site_nombre', APP_NAME);
        $impLogo    = (bool)(int)cfg('impresora_logo', '1');

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

        $p = new self();

        // ── Encabezado ──────────────────────────────────
        $p->alignCenter();

        if ($impLogo) {
            $p->large()->bold()->line(mb_strtoupper($siteNombre))->normal()->bold(false);
        }

        $p->line('*** NUEVA ORDEN ***');
        $p->emptyLines(1);

        // Código y hora
        $p->large()->bold()
          ->line('#' . $pedido['codigo'])
          ->normal()->bold(false);

        $p->line(date('d/m/Y  H:i:s', strtotime($pedido['created_at'] ?? 'now')));

        // Tipo de pedido
        $tipo = $tipoLabels[$pedido['tipo']] ?? strtoupper($pedido['tipo']);
        $p->emptyLines(1)
          ->bold()->line('[ ' . $tipo . ' ]')->bold(false);

        if (!empty($pedido['mesa'])) {
            $p->line('Mesa: ' . $pedido['mesa']);
        }

        if (!empty($pedido['direccion'])) {
            $p->line('Dir: ' . $pedido['direccion']);
        }

        // Cliente
        $p->doubleDivider()
          ->alignLeft()
          ->bold()->line('CLIENTE:')->bold(false)
          ->line($pedido['nombre'])
          ->line('Tel: ' . $pedido['telefono'])
          ->divider();

        // ── Items ────────────────────────────────────────
        $p->bold()->line('PLATILLOS:')->bold(false);
        $p->emptyLines(1);

        foreach ($items as $item) {
            // Nombre y cantidad
            $p->bold()
              ->twoColumns(
                  $item['cantidad'] . 'x ' . mb_strtoupper($item['nombre']),
                  '$' . number_format((float)$item['subtotal'], 2)
              )
              ->bold(false);

            // Opciones/extras
            if (!empty($item['opciones'])) {
                foreach ($item['opciones'] as $op) {
                    $extraPrecio = (float)$op['precio'] > 0
                        ? ' +$' . number_format((float)$op['precio'], 2)
                        : '';
                    $p->line('  + ' . $op['nombre'] . $extraPrecio);
                }
            }

            // Notas del item
            if (!empty($item['notas'])) {
                $p->line('  * ' . $item['notas']);
            }

            $p->emptyLines(1);
        }

        $p->divider();

        // ── Totales ──────────────────────────────────────
        // Subtotal
        $p->twoColumns('Subtotal:', '$' . number_format((float)$pedido['subtotal'], 2));

        // Impuesto
        if ((float)($pedido['impuesto'] ?? 0) > 0) {
            $impNombre = cfg('impuesto_nombre', 'IVA');
            $p->twoColumns(
                $impNombre . ' ' . $pedido['impuesto_pct'] . '%:',
                '$' . number_format((float)$pedido['impuesto'], 2)
            );
        }

        // Total
        $p->doubleDivider()
          ->bold()->large()
          ->twoColumns('TOTAL:', '$' . number_format((float)$pedido['total'], 2) . ' MXN')
          ->normal()->bold(false);

        // Método de pago
        $mp = $metodoPago[$pedido['metodo_pago'] ?? 'efectivo'] ?? 'Efectivo';
        $p->twoColumns('Pago:', $mp);

        // ── Notas del pedido ─────────────────────────────
        if (!empty($pedido['notas'])) {
            $p->divider()
              ->bold()->line('NOTAS:')->bold(false)
              ->line($pedido['notas']);
        }

        // ── Pie ──────────────────────────────────────────
        $p->doubleDivider()
          ->alignCenter()
          ->line('Gracias por su preferencia')
          ->line(date('H:i') . ' hrs')
          ->cut();

        return $p->print();
    }
}
