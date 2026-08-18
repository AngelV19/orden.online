<?php
/**
 * RESTAURANT PREMIUM — config/settings.php
 * Carga la configuración dinámica desde la BD.
 * Incluir DESPUÉS de database.php.
 */

function getSettings(): array {
    if (isset($_SERVER['_rp_settings'])) return $_SERVER['_rp_settings'];
    try {
        $rows = db()->query('SELECT clave, valor FROM configuracion')->fetchAll();
        $cfg  = [];
        foreach ($rows as $r) $cfg[$r['clave']] = $r['valor'];
        $_SERVER['_rp_settings'] = $cfg;
        return $cfg;
    } catch (\Throwable $e) {
        return [];
    }
}

function cfg(string $clave, string $default = ''): string {
    $s = getSettings();
    return isset($s[$clave]) && $s[$clave] !== '' ? $s[$clave] : $default;
}

function getFuentesDisplay(): array {
    return [
        'Playfair Display' => 'Playfair Display (Clásica)',
        'Cormorant Garamond'=> 'Cormorant Garamond (Elegante)',
        'Lora'             => 'Lora (Editorial)',
        'Merriweather'     => 'Merriweather (Formal)',
        'Josefin Sans'     => 'Josefin Sans (Moderna)',
        'Raleway'          => 'Raleway (Minimalista)',
    ];
}

function getFuentesBody(): array {
    return [
        'Inter'       => 'Inter (Moderna)',
        'Lato'        => 'Lato (Amigable)',
        'Poppins'     => 'Poppins (Redondeada)',
        'Nunito'      => 'Nunito (Suave)',
        'Open Sans'   => 'Open Sans (Neutral)',
        'Montserrat'  => 'Montserrat (Fuerte)',
    ];
}

function getTemasPreset(): array {
    return [
        'negro-dorado' => ['Negro & Dorado (Lujo)',    '#c9a84c', '#0d0d0d', '#f5f5f0'],
        'blanco-verde' => ['Blanco & Verde (Natural)', '#2d6a4f', '#f8f9fa', '#1a1a2e'],
        'rojo-negro'   => ['Rojo & Negro (Intenso)',   '#c0392b', '#111111', '#f0f0f0'],
        'azul-plata'   => ['Azul & Plata (Marino)',    '#2980b9', '#0a0a1a', '#e8eaf6'],
        'morado-oro'   => ['Morado & Oro (Real)',       '#8e44ad', '#1a0a2e', '#fef9e7'],
        'verde-crema'  => ['Verde & Crema (Orgánico)', '#4a7c59', '#1a2416', '#fdf6e3'],
    ];
}

function getCSSVariables(): string {
    $gold     = cfg('color_primario',   '#c9a84c');
    $black    = cfg('color_secundario', '#0d0d0d');
    $white    = cfg('color_texto',      '#f5f5f0');
    $navbar   = cfg('color_navbar',     '#0d0d0d');
    $cards    = cfg('color_cards',      '#1c1c1c');
    $radius   = cfg('tema_border_radius','4');

    $goldLight = adjustBrightness($gold, 30);
    $goldDark  = adjustBrightness($gold, -20);
    $blackSoft = adjustBrightness($black, 5);
    $blackCard = $cards;

    return "
    <style id='rp-dynamic-vars'>
        :root {
            --gold:        {$gold};
            --gold-light:  {$goldLight};
            --gold-dark:   {$goldDark};
            --black:       {$black};
            --black-soft:  {$blackSoft};
            --black-card:  {$blackCard};
            --black-border:{$blackSoft};
            --white:       {$white};
            --radius:      {$radius}px;
            --radius-lg:   " . ($radius * 2.5) . "px;
        }
        .rp-navbar.scrolled { background: " . hexToRgba($navbar, 0.96) . " !important; }
    </style>";
}

function getDynamicFonts(): string {
    $display = urlencode(cfg('fuente_display', 'Playfair Display'));
    $body    = urlencode(cfg('fuente_body',    'Inter'));
    $displayVar = 'ital,wght@0,400;0,600;0,700;1,400';
    $bodyVar    = 'wght@300;400;500;600';
    $url = "https://fonts.googleapis.com/css2?family={$display}:{$displayVar}&family={$body}:{$bodyVar}&display=swap";
    return "<link href=\"{$url}\" rel=\"stylesheet\">";
}

function getDynamicFontCSS(): string {
    $display = cfg('fuente_display', 'Playfair Display');
    $body    = cfg('fuente_body',    'Inter');
    return "
    <style id='rp-dynamic-fonts'>
        :root {
            --font-display: '{$display}', Georgia, serif;
            --font-body:    '{$body}', system-ui, sans-serif;
        }
    </style>";
}

function adjustBrightness(string $hex, int $steps): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = max(0, min(255, hexdec(substr($hex,0,2)) + $steps));
    $g = max(0, min(255, hexdec(substr($hex,2,2)) + $steps));
    $b = max(0, min(255, hexdec(substr($hex,4,2)) + $steps));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function hexToRgba(string $hex, float $alpha = 1): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    return "rgba({$r},{$g},{$b},{$alpha})";
}

// ── Funciones de impuesto ─────────────────────────────────

function getImpuesto(): array {
    return [
        'activo'    => (bool)(int)cfg('impuesto_activo',    '1'),
        'nombre'    => cfg('impuesto_nombre',    'IVA'),
        'pct'       => (float)cfg('impuesto_porcentaje', '16'),
        'incluido'  => (bool)(int)cfg('impuesto_incluido', '1'),
    ];
}

function calcularImpuesto(float $monto): array {
    $imp = getImpuesto();

    if (!$imp['activo'] || $imp['pct'] <= 0) {
        return [
            'subtotal'  => $monto,
            'impuesto'  => 0.0,
            'total'     => $monto,
            'nombre'    => $imp['nombre'],
            'pct'       => 0.0,
            'incluido'  => true,
        ];
    }

    if ($imp['incluido']) {
        $factor   = $imp['pct'] / 100;
        $base     = round($monto / (1 + $factor), 2);
        $impuesto = round($monto - $base, 2);
        return [
            'subtotal'  => $base,
            'impuesto'  => $impuesto,
            'total'     => $monto,
            'nombre'    => $imp['nombre'],
            'pct'       => $imp['pct'],
            'incluido'  => true,
        ];
    } else {
        $impuesto = round($monto * $imp['pct'] / 100, 2);
        return [
            'subtotal'  => $monto,
            'impuesto'  => $impuesto,
            'total'     => round($monto + $impuesto, 2),
            'nombre'    => $imp['nombre'],
            'pct'       => $imp['pct'],
            'incluido'  => false,
        ];
    }
}

// ── Funciones de moneda ───────────────────────────────────

/**
 * Lista de monedas disponibles con su símbolo y nombre.
 */
function getMonedasDisponibles(): array {
    return [
        'MXN' => ['Peso Mexicano',      '$'],
        'USD' => ['Dólar Americano',    '$'],
        'EUR' => ['Euro',               '€'],
        'GBP' => ['Libra Esterlina',    '£'],
        'CAD' => ['Dólar Canadiense',   '$'],
        'ARS' => ['Peso Argentino',     '$'],
        'COP' => ['Peso Colombiano',    '$'],
        'CLP' => ['Peso Chileno',       '$'],
        'PEN' => ['Sol Peruano',        'S/'],
        'BRL' => ['Real Brasileño',     'R$'],
        'GTQ' => ['Quetzal Guatemalteco','Q'],
        'HNL' => ['Lempira Hondureño',  'L'],
        'CRC' => ['Colón Costarricense','₡'],
        'DOP' => ['Peso Dominicano',    'RD$'],
        'PAB' => ['Balboa Panameño',    'B/.'],
        'VES' => ['Bolívar Venezolano', 'Bs.'],
        'UYU' => ['Peso Uruguayo',      '$U'],
    ];
}

/**
 * Obtiene la configuración completa de moneda.
 */
function getMonedaConfig(): array {
    $codigo    = cfg('moneda_codigo', 'MXN');
    $monedas   = getMonedasDisponibles();
    $simboloDefault = $monedas[$codigo][1] ?? '$';

    return [
        'codigo'           => $codigo,
        'nombre'           => $monedas[$codigo][0] ?? 'Peso Mexicano',
        'simbolo'          => cfg('moneda_simbolo', $simboloDefault),
        'posicion'         => cfg('moneda_posicion', 'antes'), // antes | despues
        'decimales'        => (int)cfg('moneda_decimales', '2'),
        'separadorDecimal' => cfg('moneda_separador_decimal', '.'),
        'separadorMiles'   => cfg('moneda_separador_miles', ','),
    ];
}

/**
 * Formatea un monto según la configuración de moneda del restaurante.
 * Reemplaza a number_format/formatPrecio en toda la app.
 */
function formatMoneda(float $monto, bool $conCodigo = true): string {
    $m = getMonedaConfig();

    $numeroFormateado = number_format(
        $monto,
        $m['decimales'],
        $m['separadorDecimal'],
        $m['separadorMiles']
    );

    $resultado = $m['posicion'] === 'antes'
        ? $m['simbolo'] . $numeroFormateado
        : $numeroFormateado . ' ' . $m['simbolo'];

    if ($conCodigo) {
        $resultado .= ' ' . $m['codigo'];
    }

    return $resultado;
}

/**
 * Versión corta sin código de moneda (para espacios reducidos).
 */
function formatMonedaCorta(float $monto): string {
    return formatMoneda($monto, false);
}
