<?php
/**
 * RESTAURANT PREMIUM — Checkout / Realizar Pedido
 * Archivo: pages/checkout.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/settings.php';

$pageTitle = 'Realizar Pedido — ' . APP_NAME;

// ── Procesar pedido POST ──────────────────────────────────
$error = '';
$success = false;
$codigoPedido = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $tipo      = $_POST['tipo']           ?? 'salon';
    $direccion = trim($_POST['direccion'] ?? '');
    $mesa      = trim($_POST['mesa']      ?? '');
    $notas       = trim($_POST['notas']       ?? '');
    $metodoPago  = trim($_POST['metodo_pago'] ?? 'efectivo');
    $cartJson    = $_POST['cart_data']        ?? '[]';
    $metodosValidos = ['efectivo','tarjeta','transferencia','paypal'];
    if (!in_array($metodoPago, $metodosValidos)) $metodoPago = 'efectivo';

    // Validar
    if (!$nombre || !$telefono || !$email) {
        $error = 'Por favor completa todos los campos obligatorios.';
    } elseif (!sanitizeEmail($email)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $items = json_decode($cartJson, true);
        if (!$items || !count($items)) {
            $error = 'El carrito está vacío.';
        } else {
            // Calcular total verificando precios en BD (no confiar en el cliente)
            $subtotal = 0;
            $itemsVerificados = [];
            $db = db();
            foreach ($items as $item) {
                $s = $db->prepare('SELECT id, nombre, precio FROM platillos WHERE id=:id AND disponible=1');
                $s->execute([':id' => (int)$item['id']]);
                $p = $s->fetch();
                if ($p) {
                    $qty = max(1, min(20, (int)$item['qty']));

                    // Calcular extras del cliente
                    $extrasCliente = [];
                    $precioExtras  = 0;
                    foreach ($item['extras'] ?? [] as $extra) {
                        $extraPrecio    = max(0, (float)($extra['precio'] ?? 0));
                        $precioExtras  += $extraPrecio;
                        $extrasCliente[] = [
                            'id'     => (int)($extra['id'] ?? 0),
                            'nombre' => substr(trim($extra['nombre'] ?? ''), 0, 150),
                            'precio' => $extraPrecio,
                        ];
                    }

                    // Precio final = precio base BD + extras (una sola vez)
                    $precioFinal = round($p['precio'] + $precioExtras, 2);
                    $subFinal    = round($precioFinal * $qty, 2);
                    $subtotal   += $subFinal;  // solo esta suma, NO la anterior

                    $itemsVerificados[] = [
                        'platillo_id' => $p['id'],
                        'nombre'      => $p['nombre'],
                        'precio'      => $precioFinal,
                        'cantidad'    => $qty,
                        'subtotal'    => $subFinal,
                        'notas'       => substr(trim($item['notas'] ?? ''), 0, 255),
                        'extras'      => $extrasCliente,
                    ];
                }
            }

            if (!$itemsVerificados) {
                $error = 'No se encontraron platillos válidos en tu pedido.';
            } else {
                // Generar código único
                $codigo = strtoupper(substr(md5(uniqid()), 0, 8));

                // Tipos válidos
                $tiposValidos = ['salon', 'llevar', 'domicilio'];
                if (!in_array($tipo, $tiposValidos)) $tipo = 'salon';

                // Calcular impuesto
                $impCalc    = calcularImpuesto($subtotal);
                $impMonto   = $impCalc['impuesto'];
                $impPct     = $impCalc['pct'];
                $totalFinal = $impCalc['total'];

                // Insertar pedido
                $db->prepare(
                    'INSERT INTO pedidos (codigo, nombre, telefono, email, tipo, direccion, mesa, subtotal, impuesto, impuesto_pct, total, notas, metodo_pago)
                     VALUES (:codigo,:nombre,:telefono,:email,:tipo,:dir,:mesa,:sub,:imp,:imppct,:total,:notas,:mpago)'
                )->execute([
                    ':codigo'   => $codigo,
                    ':nombre'   => $nombre,
                    ':telefono' => sanitizePhone($telefono),
                    ':email'    => sanitizeEmail($email),
                    ':tipo'     => $tipo,
                    ':dir'      => $tipo === 'domicilio' ? $direccion : null,
                    ':mesa'     => $tipo === 'salon'     ? $mesa      : null,
                    ':sub'      => $subtotal,
                    ':imp'      => $impMonto,
                    ':imppct'   => $impPct,
                    ':total'    => $totalFinal,
                    ':notas'    => $notas,
                    ':mpago'    => $metodoPago,
                ]);

                $pedidoId = $db->lastInsertId();

                // Insertar ítems y sus opciones
                foreach ($itemsVerificados as $it) {
                    $db->prepare(
                        'INSERT INTO pedido_items (pedido_id, platillo_id, nombre, precio, cantidad, subtotal, notas)
                         VALUES (:pid,:plid,:nom,:prec,:qty,:sub,:notas)'
                    )->execute([
                        ':pid'   => $pedidoId,
                        ':plid'  => $it['platillo_id'],
                        ':nom'   => $it['nombre'],
                        ':prec'  => $it['precio'],
                        ':qty'   => $it['cantidad'],
                        ':sub'   => $it['subtotal'],
                        ':notas' => $it['notas'] ?? '',
                    ]);
                    $itemId = $db->lastInsertId();

                    // Guardar opciones/extras seleccionados
                    foreach ($it['extras'] ?? [] as $extra) {
                        if (empty($extra['nombre'])) continue;
                        $db->prepare(
                            'INSERT INTO pedido_item_opciones (item_id, opcion_id, nombre, precio)
                             VALUES (:iid, :oid, :nom, :prec)'
                        )->execute([
                            ':iid'  => $itemId,
                            ':oid'  => (int)($extra['id'] ?? 0),
                            ':nom'  => substr($extra['nombre'], 0, 150),
                            ':prec' => (float)($extra['precio'] ?? 0),
                        ]);
                    }
                }

                $codigoPedido = $codigo;
                $success      = true;

                // ── Imprimir comanda automáticamente ──────
                try {
                    require_once __DIR__ . '/../includes/Printer.php';
                    require_once __DIR__ . '/../includes/PrintNode.php';

                    // Cargar pedido completo con items y opciones
                    $sPed = $db->prepare('SELECT * FROM pedidos WHERE id=:id');
                    $sPed->execute([':id' => $pedidoId]);
                    $pedidoImprimir = $sPed->fetch();

                    $sItems = $db->prepare('SELECT * FROM pedido_items WHERE pedido_id=:id');
                    $sItems->execute([':id' => $pedidoId]);
                    $itemsImprimir = $sItems->fetchAll();

                    foreach ($itemsImprimir as &$itImp) {
                        $sOp = $db->prepare('SELECT * FROM pedido_item_opciones WHERE item_id=:id');
                        $sOp->execute([':id' => $itImp['id']]);
                        $itImp['opciones'] = $sOp->fetchAll();
                    }
                    unset($itImp);

                    // Intentar PrintNode primero (producción/nube)
                    // Si no está activo, usar impresora local por IP (XAMPP/LAN)
                    $usePrintNode = (bool)(int)cfg('printnode_activo', '0')
                                    && cfg('printnode_apikey', '') !== ''
                                    && cfg('printnode_printer_id', '') !== '';

                    if ($usePrintNode) {
                        PrintNode::imprimirPedido($pedidoImprimir, $itemsImprimir);
                    } else {
                        Printer::imprimirPedido($pedidoImprimir, $itemsImprimir);
                    }

                } catch (Exception $e) {
                    error_log('Print error: ' . $e->getMessage());
                    $printError = $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="padding-top:90px;background:var(--black-soft);border-bottom:1px solid var(--black-border);">
    <div class="container py-4 text-center">
        <p class="rp-eyebrow">Tu Pedido</p>
        <div class="rp-divider mx-auto"></div>
        <h1 class="rp-display fs-1 mt-3">
            <?= $success ? 'Pedido <em class="rp-display--italic text-gold">Confirmado</em>' : 'Confirmar <em class="rp-display--italic text-gold">Pedido</em>' ?>
        </h1>
    </div>
</div>

<section class="rp-section rp-section--dark">
    <div class="container">

    <?php if ($success): ?>
    <!-- ── Confirmación ──────────────────────────────────── -->
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="bg-rp-card border border-rp rounded-3 p-5">
                <div style="width:72px;height:72px;background:rgba(46,160,67,.12);border:2px solid #2ea043;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:#2ea043;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2 class="rp-display fs-3 mb-2">¡Pedido recibido!</h2>
                <p class="text-muted mb-3">Estamos preparando tu orden. Puedes seguir su estado con tu código.</p>

                <div style="background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.2);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;">
                    <p class="text-muted small mb-1">Código de pedido</p>
                    <p class="rp-display fs-2 text-gold mb-0" style="letter-spacing:.15em;"><?= h($codigoPedido) ?></p>
                </div>

                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="<?= APP_URL ?>/pages/recibo.php?codigo=<?= urlencode($codigoPedido) ?>"
                       class="rp-btn-gold btn px-4">
                        <i class="bi bi-receipt me-2"></i>Ver Recibo
                    </a>
                    <a href="<?= APP_URL ?>/pages/estado_pedido.php?codigo=<?= urlencode($codigoPedido) ?>"
                       class="rp-btn-outline btn px-4">
                        <i class="bi bi-eye me-2"></i>Ver Estado
                    </a>
                    <a href="<?= APP_URL ?>/pages/menu.php" class="rp-btn-outline btn px-4">
                        <i class="bi bi-arrow-left me-2"></i>Seguir ordenando
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Vaciar carrito tras pedido exitoso
        localStorage.removeItem('rp_cart');
    </script>

    <?php else: ?>
    <!-- ── Formulario checkout ───────────────────────────── -->
    <?php if ($error): ?>
    <div class="rp-alert rp-alert--error mb-4 container" style="max-width:900px;"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="row justify-content-center gy-4">
        <div class="col-lg-7">
            <form id="checkoutForm" method="POST" action="">
                <input type="hidden" name="cart_data" id="cartDataInput" value="">

                <!-- Datos personales -->
                <div class="bg-rp-card border border-rp rounded-3 p-4 mb-4">
                    <h2 class="rp-display fs-5 mb-3">Tus datos</h2>
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="rp-form-label">Nombre completo *</label>
                            <input type="text" name="nombre" class="rp-form-control form-control" required maxlength="120"
                                   placeholder="Tu nombre">
                        </div>
                        <div class="col-md-6">
                            <label class="rp-form-label">Teléfono *</label>
                            <input type="tel" name="telefono" class="rp-form-control form-control" required maxlength="20"
                                   placeholder="+52 55 1234 5678">
                        </div>
                        <div class="col-12">
                            <label class="rp-form-label">Correo electrónico *</label>
                            <input type="email" name="email" class="rp-form-control form-control" required maxlength="180"
                                   placeholder="tu@email.com">
                        </div>
                    </div>
                </div>

                <!-- Tipo de pedido -->
                <div class="bg-rp-card border border-rp rounded-3 p-4 mb-4">
                    <h2 class="rp-display fs-5 mb-3">Tipo de pedido</h2>
                    <div class="row g-2 mb-3">
                        <?php
                        $tipos = [
                            'salon'     => ['bi-door-open',   'En el restaurante', 'Come aquí con nosotros'],
                            'llevar'    => ['bi-bag',         'Para llevar',       'Recoges en mostrador'],
                            'domicilio' => ['bi-bicycle',     'A domicilio',       'Te lo llevamos a casa'],
                        ];
                        foreach ($tipos as $val => [$ico, $label, $desc]):
                        ?>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="tipo" id="tipo_<?= $val ?>"
                                   value="<?= $val ?>" <?= $val==='salon'?'checked':'' ?>>
                            <label class="btn w-100 h-100 text-start rp-tipo-btn border border-rp rounded-3 p-3"
                                   for="tipo_<?= $val ?>"
                                   style="background:var(--black);color:var(--white-dim);transition:all .2s;">
                                <i class="bi <?= $ico ?> d-block text-gold mb-1 fs-5"></i>
                                <strong class="d-block small"><?= $label ?></strong>
                                <span style="font-size:.72rem;color:var(--white-dim);"><?= $desc ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mesa (salon) -->
                    <div id="campoMesa">
                        <label class="rp-form-label">Número de mesa (opcional)</label>
                        <input type="text" name="mesa" class="rp-form-control form-control"
                               maxlength="20" placeholder="Ej: Mesa 5, Terraza 2…">
                    </div>
                    <!-- Dirección (domicilio) -->
                    <div id="campoDireccion" style="display:none;">
                        <label class="rp-form-label">Dirección de entrega *</label>
                        <textarea name="direccion" class="rp-form-control form-control" rows="2"
                                  placeholder="Calle, número, colonia, referencias…"></textarea>
                    </div>
                </div>

                <!-- Notas -->
                <div class="bg-rp-card border border-rp rounded-3 p-4 mb-4">
                    <h2 class="rp-display fs-5 mb-3">Notas adicionales</h2>
                    <textarea name="notas" class="rp-form-control form-control" rows="2"
                              placeholder="Alergias, preferencias de cocción, instrucciones especiales…" maxlength="500"></textarea>
                </div>

                <!-- Método de pago -->
                <div class="bg-rp-card border border-rp rounded-3 p-4 mb-4" id="seccionPago">
                    <h2 class="rp-display fs-5 mb-1">Método de Pago</h2>
                    <p class="text-muted small mb-3">Selecciona cómo deseas pagar</p>
                    <input type="hidden" name="metodo_pago" id="metodoPagoInput" value="efectivo">

                    <!-- Opciones de método -->
                    <div class="row g-2 mb-4" id="metodoOpciones">

                        <!-- Efectivo -->
                        <div class="col-6 col-md-3">
                            <div class="rp-pago-opcion selected" data-metodo="efectivo" onclick="seleccionarPago(this)">
                                <i class="bi bi-cash-coin"></i>
                                <span>Efectivo</span>
                            </div>
                        </div>

                        <!-- Tarjeta -->
                        <div class="col-6 col-md-3">
                            <div class="rp-pago-opcion" data-metodo="tarjeta" onclick="seleccionarPago(this)">
                                <i class="bi bi-credit-card"></i>
                                <span>Tarjeta</span>
                            </div>
                        </div>

                        <!-- Transferencia -->
                        <div class="col-6 col-md-3">
                            <div class="rp-pago-opcion" data-metodo="transferencia" onclick="seleccionarPago(this)">
                                <i class="bi bi-bank"></i>
                                <span>Transferencia</span>
                            </div>
                        </div>

                        <!-- PayPal -->
                        <div class="col-6 col-md-3">
                            <div class="rp-pago-opcion" data-metodo="paypal" onclick="seleccionarPago(this)">
                                <i class="bi bi-paypal"></i>
                                <span>PayPal</span>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Efectivo -->
                    <div id="panel-efectivo" class="rp-pago-panel">
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3"
                             style="background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.15);">
                            <i class="bi bi-info-circle text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <p class="fw-semibold small mb-1">Pago en efectivo</p>
                                <p class="text-muted small mb-0">
                                    Puedes pagar en efectivo al momento de recibir tu pedido o al llegar al restaurante.
                                    Por favor ten el monto exacto si es posible.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Tarjeta -->
                    <div id="panel-tarjeta" class="rp-pago-panel" style="display:none;">
                        <div class="d-flex gap-2 mb-3 flex-wrap">
                            <img src="https://cdn.jsdelivr.net/npm/payment-icons@1.1.0/min/flat/visa.svg"       alt="Visa"       style="height:28px;border-radius:4px;">
                            <img src="https://cdn.jsdelivr.net/npm/payment-icons@1.1.0/min/flat/mastercard.svg" alt="Mastercard" style="height:28px;border-radius:4px;">
                            <img src="https://cdn.jsdelivr.net/npm/payment-icons@1.1.0/min/flat/amex.svg"       alt="Amex"       style="height:28px;border-radius:4px;">
                        </div>
                        <div class="row gy-3">
                            <div class="col-12">
                                <label class="rp-form-label">Número de tarjeta</label>
                                <div class="position-relative">
                                    <input type="text" id="cardNumber" class="rp-form-control form-control pe-5"
                                           placeholder="1234 5678 9012 3456" maxlength="19"
                                           oninput="formatCard(this)" autocomplete="cc-number">
                                    <i class="bi bi-credit-card position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="rp-form-label">Nombre en la tarjeta</label>
                                <input type="text" id="cardName" class="rp-form-control form-control"
                                       placeholder="ANGEL DAVID" maxlength="60"
                                       autocomplete="cc-name" oninput="this.value=this.value.toUpperCase()">
                            </div>
                            <div class="col-7">
                                <label class="rp-form-label">Fecha de vencimiento</label>
                                <input type="text" id="cardExpiry" class="rp-form-control form-control"
                                       placeholder="MM / AA" maxlength="7"
                                       oninput="formatExpiry(this)" autocomplete="cc-exp">
                            </div>
                            <div class="col-5">
                                <label class="rp-form-label">CVV</label>
                                <div class="position-relative">
                                    <input type="password" id="cardCvv" class="rp-form-control form-control"
                                           placeholder="•••" maxlength="4"
                                           autocomplete="cc-csc">
                                    <i class="bi bi-shield-lock position-absolute top-50 end-0 translate-middle-y me-3 text-muted" style="font-size:.85rem;"></i>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 p-2 rounded-2"
                                     style="background:rgba(46,160,67,.06);border:1px solid rgba(46,160,67,.2);">
                                    <i class="bi bi-shield-check" style="color:#2ea043;"></i>
                                    <span class="small" style="color:#2ea043;">Pago seguro con encriptación SSL</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Transferencia (datos desde configuración) -->
                    <div id="panel-transferencia" class="rp-pago-panel" style="display:none;">
                        <?php
                        $pgBanco   = cfg('pago_banco',         'BBVA México');
                        $pgTitular = cfg('pago_titular',       cfg('site_nombre', APP_NAME));
                        $pgClabe   = cfg('pago_clabe',         '000000000000000000');
                        $pgCuenta  = cfg('pago_cuenta',        '');
                        $pgConcepto= cfg('pago_concepto',      'Pago de pedido');
                        $pgInstruc = cfg('pago_instrucciones', 'Envía tu comprobante por WhatsApp para confirmar tu pedido.');
                        ?>
                        <div class="p-3 rounded-3" style="background:var(--black);border:1px solid var(--black-border);">
                            <p class="rp-eyebrow mb-3">Datos bancarios</p>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Banco</span>
                                    <strong class="small"><?= h($pgBanco) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Titular</span>
                                    <strong class="small"><?= h($pgTitular) ?></strong>
                                </div>
                                <?php if ($pgCuenta): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Cuenta</span>
                                    <strong class="small"><?= h($pgCuenta) ?></strong>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">CLABE</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <strong class="small text-gold" id="clabeNum"><?= h($pgClabe) ?></strong>
                                        <button type="button" onclick="copiarClabe()" class="btn p-0"
                                                style="background:none;border:none;color:var(--gold);font-size:.8rem;"
                                                title="Copiar CLABE">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Concepto</span>
                                    <strong class="small text-gold"><?= h($pgConcepto) ?> #<?= date('ymd') . rand(100,999) ?></strong>
                                </div>
                            </div>
                            <?php if ($pgInstruc): ?>
                            <div class="mt-3 p-2 rounded-2"
                                 style="background:rgba(255,193,7,.06);border:1px solid rgba(255,193,7,.2);">
                                <p class="small mb-0" style="color:#ffc107;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <?= h($pgInstruc) ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Panel: PayPal -->
                    <div id="panel-paypal" class="rp-pago-panel" style="display:none;">
                        <div class="text-center p-3 rounded-3"
                             style="background:var(--black);border:1px solid var(--black-border);">
                            <div class="mb-3">
                                <svg viewBox="0 0 124 33" style="height:32px;fill:#003087;" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M46.211 6.749h-6.839a.95.95 0 0 0-.939.802l-2.766 17.537a.57.57 0 0 0 .564.658h3.265a.95.95 0 0 0 .939-.803l.746-4.73a.95.95 0 0 1 .938-.803h2.165c4.505 0 7.105-2.18 7.784-6.5.306-1.89.013-3.375-.872-4.415-.972-1.142-2.696-1.746-4.985-1.746zM47 13.154c-.374 2.454-2.249 2.454-4.062 2.454h-1.032l.724-4.583a.57.57 0 0 1 .563-.481h.473c1.235 0 2.4 0 3.002.704.359.42.469 1.044.332 1.906zM66.654 13.075h-3.275a.57.57 0 0 0-.563.481l-.145.916-.229-.332c-.709-1.029-2.29-1.373-3.868-1.373-3.619 0-6.71 2.741-7.312 6.586-.313 1.918.132 3.752 1.22 5.031.998 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .562.66h2.95a.95.95 0 0 0 .939-.803l1.77-11.209a.568.568 0 0 0-.561-.658zm-4.565 6.374c-.316 1.871-1.801 3.127-3.695 3.127-.951 0-1.711-.305-2.199-.883-.484-.574-.668-1.391-.514-2.301.295-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.499.589.697 1.411.554 2.317zM84.096 13.075h-3.291a.954.954 0 0 0-.787.417l-4.539 6.686-1.924-6.425a.953.953 0 0 0-.912-.678h-3.234a.57.57 0 0 0-.541.754l3.625 10.638-3.408 4.811a.57.57 0 0 0 .465.9h3.287a.949.949 0 0 0 .781-.408l10.946-15.8a.57.57 0 0 0-.468-.895z" fill="#003087"/>
                                    <path d="M94.992 6.749h-6.84a.95.95 0 0 0-.938.802l-2.766 17.537a.569.569 0 0 0 .562.658h3.51a.665.665 0 0 0 .656-.562l.785-4.971a.95.95 0 0 1 .938-.803h2.164c4.506 0 7.105-2.18 7.785-6.5.307-1.89.012-3.375-.873-4.415-.971-1.142-2.694-1.746-4.983-1.746zm.789 6.405c-.373 2.454-2.248 2.454-4.062 2.454h-1.031l.725-4.583a.568.568 0 0 1 .562-.481h.473c1.234 0 2.4 0 3.002.704.359.42.468 1.044.331 1.906zM115.434 13.075h-3.273a.567.567 0 0 0-.562.481l-.145.916-.23-.332c-.709-1.029-2.289-1.373-3.867-1.373-3.619 0-6.710 2.741-7.312 6.586-.312 1.918.131 3.752 1.219 5.031 1 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .564.66h2.949a.95.95 0 0 0 .938-.803l1.771-11.209a.571.571 0 0 0-.564-.658zm-4.565 6.374c-.314 1.871-1.801 3.127-3.695 3.127-.949 0-1.711-.305-2.199-.883-.484-.574-.666-1.391-.514-2.301.297-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.501.589.699 1.411.554 2.317zM119.295 7.23l-2.807 17.858a.569.569 0 0 0 .562.658h2.822c.469 0 .867-.340.939-.803l2.768-17.536a.57.57 0 0 0-.562-.659h-3.16a.571.571 0 0 0-.562.482z" fill="#009cde"/>
                                </svg>
                            </div>
                            <p class="text-muted small mb-3">
                                Serás redirigido a PayPal para completar el pago de forma segura.
                            </p>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-lock-fill" style="color:#2ea043;font-size:.85rem;"></i>
                                <span class="small" style="color:#2ea043;">Protegido por PayPal Buyer Protection</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="rp-btn-gold btn w-100 py-3 fs-6">
                    <i class="bi bi-bag-check me-2"></i>Confirmar Pedido
                </button>
            </form>
        </div>

        <!-- Resumen del carrito -->
        <div class="col-lg-4">
            <div class="rp-checkout-summary">
                <div class="rp-checkout-summary__header">
                    <i class="bi bi-bag me-2 text-gold"></i>Resumen del pedido
                </div>

                <!-- Items del carrito -->
                <div class="rp-checkout-items" id="checkoutItemsPreview">
                    <!-- Poblado por cart.js -->
                </div>

                <!-- Desglose de impuesto (poblado por cart.js) -->
                <div id="checkoutTaxSummary" style="display:none;padding:0 1.25rem;"></div>

                <!-- Total final -->
                <div class="rp-checkout-total" style="flex-direction:column;align-items:stretch;gap:.1rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total a pagar</span>
                        <span class="rp-cart-total-value rp-checkout-total-val rp-display fs-4 text-gold">$0.00 MXN</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Mostrar/ocultar campos según tipo de pedido
    document.querySelectorAll('[name="tipo"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const v = radio.value;
            document.getElementById('campoMesa').style.display      = v === 'salon'     ? '' : 'none';
            document.getElementById('campoDireccion').style.display = v === 'domicilio' ? '' : 'none';
        });
    });

    // Estilo activo en botones de tipo
    document.querySelectorAll('.rp-tipo-btn').forEach(lbl => {
        const radio = document.getElementById(lbl.getAttribute('for'));
        const update = () => {
            document.querySelectorAll('.rp-tipo-btn').forEach(l => {
                l.style.borderColor = 'var(--black-border)';
                l.style.background  = 'var(--black)';
            });
            if (radio.checked) {
                lbl.style.borderColor = 'var(--gold)';
                lbl.style.background  = 'rgba(201,168,76,.06)';
            }
        };
        radio.addEventListener('change', () => document.querySelectorAll('[name="tipo"]').forEach(() => update()));
        if (radio.checked) update();
    });
    </script>
    <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
