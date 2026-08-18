<?php
/**
 * RESTAURANT PREMIUM — E-Receipt (Recibo Electrónico)
 * Archivo: pages/recibo.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/settings.php';

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));

if (!$codigo) {
    header('Location: ' . APP_URL . '/');
    exit;
}

$db = db();

// Cargar pedido
$s = $db->prepare('SELECT * FROM pedidos WHERE codigo = :c');
$s->execute([':c' => $codigo]);
$pedido = $s->fetch();

if (!$pedido) {
    header('Location: ' . APP_URL . '/');
    exit;
}

// Cargar ítems con opciones
$sItems = $db->prepare('SELECT * FROM pedido_items WHERE pedido_id = :id ORDER BY id ASC');
$sItems->execute([':id' => $pedido['id']]);
$items = $sItems->fetchAll();

foreach ($items as &$item) {
    $sOp = $db->prepare('SELECT * FROM pedido_item_opciones WHERE item_id = :id');
    $sOp->execute([':id' => $item['id']]);
    $item['opciones'] = $sOp->fetchAll();
}
unset($item);

// Configuración dinámica
$siteNombre  = cfg('site_nombre', APP_NAME);
$logoPath    = cfg('site_logo');
$logoUrl     = ($logoPath && file_exists(__DIR__ . '/../uploads/' . $logoPath))
               ? APP_URL . '/uploads/' . $logoPath : '';
$direccion   = cfg('contacto_direccion', 'Av. Presidente Masaryk 123, Polanco, CDMX');
$telefono    = cfg('contacto_telefono',  '+52 55 1234 5678');
$emailC      = cfg('contacto_email',     'reservaciones@restaurantpremium.com');
$colorGold   = cfg('color_primario',     '#c9a84c');
$colorBg     = cfg('color_secundario',   '#0d0d0d');

$tipoLabels  = ['salon'=>'En Restaurante', 'llevar'=>'Para Llevar', 'domicilio'=>'A Domicilio'];
$estadoLabel = ['nuevo'=>'Recibido', 'preparando'=>'En Preparación', 'listo'=>'Listo', 'entregado'=>'Entregado', 'cancelado'=>'Cancelado'];

$pageTitle = 'Recibo #' . $codigo . ' — ' . $siteNombre;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <meta name="robots" content="noindex">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --gold:    <?= $colorGold ?>;
            --black:   <?= $colorBg ?>;
            --white:   #f5f5f0;
            --font-d:  'Playfair Display', Georgia, serif;
            --font-b:  'Inter', system-ui, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f0ede8;
            font-family: var(--font-b);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem 4rem;
            color: #1a1a1a;
        }

        /* ── Barra de acciones (no imprime) ─────────── */
        .rp-receipt-actions {
            display: flex;
            gap: .75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .rp-receipt-actions a,
        .rp-receipt-actions button {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .6rem 1.4rem;
            border-radius: 6px;
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
            border: none;
        }

        .btn-print {
            background: var(--gold);
            color: #0d0d0d;
        }
        .btn-print:hover { filter: brightness(1.1); }

        .btn-back {
            background: #fff;
            color: #1a1a1a;
            border: 1px solid #ddd !important;
        }
        .btn-back:hover { background: #f5f5f5; }

        .btn-whatsapp {
            background: #25d366;
            color: #fff;
        }
        .btn-whatsapp:hover { filter: brightness(1.1); }



        /* ── Recibo ──────────────────────────────────── */
        .rp-receipt {
            background: #fff;
            width: 100%;
            max-width: 480px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.12);
        }

        /* Header negro/dorado */
        .rp-receipt__header {
            background: var(--black);
            padding: 2rem 1.75rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .rp-receipt__logo {
            max-height: 56px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: .75rem;
        }

        .rp-receipt__brand {
            font-family: var(--font-d);
            font-size: 1.4rem;
            color: var(--gold);
            letter-spacing: .05em;
            margin-bottom: .2rem;
        }

        .rp-receipt__brand-sub {
            font-size: .72rem;
            color: rgba(245,245,240,.5);
            letter-spacing: .15em;
            text-transform: uppercase;
        }

        .rp-receipt__divider-gold {
            height: 2px;
            background: linear-gradient(to right, transparent, var(--gold), transparent);
            margin: 1.25rem 0;
        }

        .rp-receipt__codigo {
            font-family: var(--font-d);
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: .15em;
        }

        .rp-receipt__estado {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(201,168,76,.15);
            border: 1px solid rgba(201,168,76,.3);
            color: var(--gold);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .3rem .85rem;
            border-radius: 100px;
            margin-top: .5rem;
        }

        /* Cuerpo del recibo */
        .rp-receipt__body { padding: 1.5rem 1.75rem; }

        /* Info row */
        .rp-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .65rem;
            margin-bottom: 1.25rem;
        }

        .rp-info-item__label {
            font-size: .65rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: .2rem;
        }

        .rp-info-item__value {
            font-size: .88rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* Separador punteado tipo ticket */
        .rp-receipt__dotted {
            border: none;
            border-top: 2px dashed #e0d9cc;
            margin: 1.25rem 0;
        }

        /* Título de sección */
        .rp-receipt__section-title {
            font-size: .65rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: .75rem;
        }

        /* Items del pedido */
        .rp-receipt__item {
            padding: .75rem 0;
            border-bottom: 1px solid #f0ede8;
        }
        .rp-receipt__item:last-child { border-bottom: none; }

        .rp-receipt__item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
        }

        .rp-receipt__item-name {
            font-weight: 600;
            font-size: .9rem;
            color: #1a1a1a;
            flex: 1;
        }

        .rp-receipt__item-qty {
            font-size: .78rem;
            color: #888;
            margin-left: .35rem;
        }

        .rp-receipt__item-precio {
            font-weight: 700;
            font-size: .9rem;
            color: #1a1a1a;
            white-space: nowrap;
        }

        .rp-receipt__item-base {
            font-size: .75rem;
            color: #aaa;
            text-decoration: line-through;
            margin-right: .25rem;
        }

        /* Extras chips */
        .rp-receipt__extras {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-top: .4rem;
        }

        .rp-receipt__extra-chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: #fdf9f0;
            border: 1px solid #e8dfc8;
            border-radius: 100px;
            padding: .12rem .55rem;
            font-size: .68rem;
            color: #555;
        }

        .rp-receipt__extra-chip span {
            color: #9e7e35;
            font-weight: 600;
        }

        /* Nota del item */
        .rp-receipt__item-nota {
            font-size: .72rem;
            color: #888;
            font-style: italic;
            margin-top: .3rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* Totales */
        .rp-receipt__totales {
            background: #faf9f6;
            border-radius: 8px;
            padding: 1rem 1.1rem;
            margin-top: .75rem;
        }

        .rp-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .3rem 0;
            font-size: .84rem;
        }

        .rp-total-row__label { color: #666; }
        .rp-total-row__value { font-weight: 600; color: #1a1a1a; }

        .rp-total-row--final {
            border-top: 2px solid #e0d9cc;
            margin-top: .35rem;
            padding-top: .65rem;
        }

        .rp-total-row--final .rp-total-row__label {
            font-family: var(--font-d);
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .rp-total-row--final .rp-total-row__value {
            font-family: var(--font-d);
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gold);
        }

        /* Footer del recibo */
        .rp-receipt__footer {
            background: var(--black);
            padding: 1.25rem 1.75rem;
            text-align: center;
        }

        .rp-receipt__footer p {
            font-size: .72rem;
            color: rgba(245,245,240,.45);
            line-height: 1.7;
            margin: 0;
        }

        .rp-receipt__footer-brand {
            font-family: var(--font-d);
            font-size: .9rem;
            color: var(--gold);
            margin-bottom: .3rem;
        }

        /* Decoración zigzag entre header y body */
        .rp-receipt__zigzag {
            height: 16px;
            background:
                radial-gradient(circle at 8px -4px, #f0ede8 12px, transparent 13px),
                radial-gradient(circle at 8px -4px, #f0ede8 12px, transparent 13px);
            background-size: 16px 16px;
            background-position: 0 0, 8px 0;
            background-color: #fff;
        }

        /* ─── ESTILOS DE IMPRESIÓN ──────────────────── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 15mm;
            }

            /* Preservar colores exactos */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-sizing: border-box !important;
            }

            /* Body centrado */
            html, body {
                background: #f0ede8 !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                width: 100% !important;
                min-height: 0 !important;
            }

            /* Ocultar botones */
            .rp-receipt-actions,
            .rp-no-print,
            #printTip { display: none !important; }

            /* Recibo: ancho fijo, escala para caber en 1 página */
            .rp-receipt {
                width: 420px !important;
                max-width: 420px !important;
                margin: 0 auto !important;
                box-shadow: 0 2px 12px rgba(0,0,0,.12) !important;
                border-radius: 10px !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Reducir padding para compactar */
            .rp-receipt__header  { padding: 1rem 1.4rem .9rem !important; }
            .rp-receipt__body    { padding: 1rem 1.4rem !important; }
            .rp-receipt__footer  { padding: .9rem 1.4rem !important; }
            .rp-receipt__zigzag  { height: 10px !important; }

            /* Tipografía más compacta */
            .rp-receipt__brand       { font-size: 1.15rem !important; }
            .rp-receipt__brand-sub   { font-size: .62rem !important; }
            .rp-receipt__codigo      { font-size: 1.3rem !important; }
            .rp-receipt__estado      { font-size: .65rem !important; padding: .2rem .65rem !important; }
            .rp-info-item__label     { font-size: .58rem !important; }
            .rp-info-item__value     { font-size: .8rem !important; }
            .rp-info-grid            { gap: .45rem !important; margin-bottom: .9rem !important; }
            .rp-receipt__section-title { font-size: .58rem !important; margin-bottom: .5rem !important; }
            .rp-receipt__item        { padding: .5rem 0 !important; }
            .rp-receipt__item-name   { font-size: .85rem !important; }
            .rp-receipt__item-precio { font-size: .85rem !important; }
            .rp-receipt__item-qty    { font-size: .72rem !important; }
            .rp-receipt__extra-chip  { font-size: .62rem !important; padding: .08rem .4rem !important; }
            .rp-receipt__item-nota   { font-size: .65rem !important; }
            .rp-receipt__dotted      { margin: .75rem 0 !important; }
            .rp-receipt__totales     { padding: .75rem .9rem !important; margin-top: .5rem !important; }
            .rp-total-row            { padding: .22rem 0 !important; font-size: .8rem !important; }
            .rp-total-row--final     { margin-top: .25rem !important; padding-top: .5rem !important; }
            .rp-total-row--final .rp-total-row__label { font-size: .95rem !important; }
            .rp-total-row--final .rp-total-row__value { font-size: 1.15rem !important; }
            .rp-receipt__footer p    { font-size: .65rem !important; line-height: 1.5 !important; }
            .rp-receipt__footer-brand { font-size: .82rem !important; margin-bottom: .2rem !important; }
            .rp-receipt__divider-gold { margin: .75rem 0 !important; }

            /* Links sin URL */
            a { color: inherit !important; text-decoration: none !important; }
            a::after { display: none !important; content: "" !important; }
        }

        /* Responsive */
        @media (max-width: 520px) {
            .rp-receipt__body { padding: 1.25rem 1.25rem; }
            .rp-receipt__header { padding: 1.5rem 1.25rem 1.25rem; }
        }
    </style>
</head>
<body>

<!-- ── Acciones (no imprimen) ──────────────────────────── -->
<div class="rp-receipt-actions rp-no-print">
    <a href="<?= APP_URL ?>/pages/estado_pedido.php?codigo=<?= urlencode($codigo) ?>"
       class="btn-back">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <button onclick="imprimirRecibo()" class="btn-print">
        <i class="bi bi-printer"></i> Imprimir / Guardar PDF
    </button>
    <a href="https://wa.me/?text=<?= rawurlencode('Mi recibo de ' . $siteNombre . ' — Pedido #' . $codigo . ' por $' . number_format((float)$pedido['total'], 2) . ' MXN. Ver detalles: ' . APP_URL . '/pages/recibo.php?codigo=' . $codigo) ?>"
       target="_blank" class="btn-whatsapp">
        <i class="bi bi-whatsapp"></i> Compartir
    </button>
</div>

<!-- ── Recibo ───────────────────────────────────────────── -->
<div class="rp-receipt">

    <!-- Header -->
    <div class="rp-receipt__header">
        <?php if ($logoUrl): ?>
        <img src="<?= $logoUrl ?>" alt="<?= h($siteNombre) ?>" class="rp-receipt__logo">
        <?php else: ?>
        <p class="rp-receipt__brand">✦ <?= h($siteNombre) ?></p>
        <?php endif; ?>
        <p class="rp-receipt__brand-sub">Recibo Electrónico</p>

        <div class="rp-receipt__divider-gold"></div>

        <p class="rp-receipt__codigo">#<?= h($pedido['codigo']) ?></p>
        <div class="rp-receipt__estado">
            <i class="bi bi-check-circle"></i>
            <?= h($estadoLabel[$pedido['estado']] ?? ucfirst($pedido['estado'])) ?>
        </div>
    </div>

    <!-- Zigzag decorativo -->
    <div class="rp-receipt__zigzag"></div>

    <!-- Cuerpo -->
    <div class="rp-receipt__body">

        <!-- Info del pedido -->
        <div class="rp-info-grid">
            <div class="rp-info-item">
                <p class="rp-info-item__label">Fecha</p>
                <p class="rp-info-item__value"><?= date('d/m/Y', strtotime($pedido['created_at'])) ?></p>
            </div>
            <div class="rp-info-item">
                <p class="rp-info-item__label">Hora</p>
                <p class="rp-info-item__value"><?= date('h:i A', strtotime($pedido['created_at'])) ?></p>
            </div>
            <div class="rp-info-item">
                <p class="rp-info-item__label">Tipo</p>
                <p class="rp-info-item__value"><?= h($tipoLabels[$pedido['tipo']] ?? ucfirst($pedido['tipo'])) ?></p>
            </div>
            <div class="rp-info-item">
                <p class="rp-info-item__label">Código</p>
                <p class="rp-info-item__value" style="color:<?= $colorGold ?>;"><?= h($pedido['codigo']) ?></p>
            </div>
        </div>

        <!-- Info del cliente -->
        <hr class="rp-receipt__dotted">
        <p class="rp-receipt__section-title">Cliente</p>
        <div class="rp-info-grid" style="margin-bottom:0;">
            <div class="rp-info-item" style="grid-column:span 2;">
                <p class="rp-info-item__label">Nombre</p>
                <p class="rp-info-item__value"><?= h($pedido['nombre']) ?></p>
            </div>
            <div class="rp-info-item">
                <p class="rp-info-item__label">Teléfono</p>
                <p class="rp-info-item__value" style="font-size:.82rem;"><?= h($pedido['telefono']) ?></p>
            </div>
            <div class="rp-info-item">
                <p class="rp-info-item__label">Correo</p>
                <p class="rp-info-item__value" style="font-size:.75rem;word-break:break-all;"><?= h($pedido['email']) ?></p>
            </div>
            <?php if ($pedido['mesa']): ?>
            <div class="rp-info-item">
                <p class="rp-info-item__label">Mesa</p>
                <p class="rp-info-item__value"><?= h($pedido['mesa']) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($pedido['direccion']): ?>
            <div class="rp-info-item" style="grid-column:span 2;">
                <p class="rp-info-item__label">Dirección de entrega</p>
                <p class="rp-info-item__value" style="font-size:.82rem;"><?= h($pedido['direccion']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Platillos -->
        <hr class="rp-receipt__dotted">
        <p class="rp-receipt__section-title">Platillos Ordenados</p>

        <?php foreach ($items as $item):
            // Calcular precio base (sin extras)
            $totalExtras = array_sum(array_column($item['opciones'], 'precio'));
            $precioBase  = round((float)$item['precio'] - $totalExtras, 2);
        ?>
        <div class="rp-receipt__item">
            <div class="rp-receipt__item-row">
                <div>
                    <span class="rp-receipt__item-name"><?= h($item['nombre']) ?></span>
                    <span class="rp-receipt__item-qty">×<?= $item['cantidad'] ?></span>
                </div>
                <div style="text-align:right;">
                    <?php if ($totalExtras > 0): ?>
                    <span class="rp-receipt__item-base">$<?= number_format($precioBase * $item['cantidad'], 2) ?></span>
                    <?php endif; ?>
                    <span class="rp-receipt__item-precio">
                        $<?= number_format((float)$item['subtotal'], 2) ?>
                    </span>
                </div>
            </div>

            <!-- Extras -->
            <?php if ($item['opciones']): ?>
            <div class="rp-receipt__extras">
                <?php foreach ($item['opciones'] as $op): ?>
                <span class="rp-receipt__extra-chip">
                    <i class="bi bi-plus-circle" style="font-size:.6rem;color:#9e7e35;"></i>
                    <?= h($op['nombre']) ?>
                    <?php if ($op['precio'] > 0): ?>
                    <span>+$<?= number_format((float)$op['precio'], 2) ?></span>
                    <?php endif; ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Notas -->
            <?php if (!empty($item['notas'])): ?>
            <p class="rp-receipt__item-nota">
                <i class="bi bi-chat-dots" style="color:#9e7e35;font-size:.72rem;"></i>
                <?= h($item['notas']) ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Notas generales del pedido -->
        <?php if ($pedido['notas']): ?>
        <div style="background:#fdf9f0;border:1px solid #e8dfc8;border-radius:6px;padding:.75rem;margin-top:.5rem;">
            <p style="font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:.25rem;">Nota del pedido</p>
            <p style="font-size:.82rem;color:#555;font-style:italic;margin:0;"><?= h($pedido['notas']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Totales -->
        <div class="rp-receipt__totales">
            <?php
            // Calcular subtotal sin extras y total de extras
            $subtotalBase   = 0;
            $subtotalExtras = 0;
            foreach ($items as $item) {
                $extrasItem      = array_sum(array_column($item['opciones'], 'precio'));
                $subtotalExtras += round($extrasItem * $item['cantidad'], 2);
                $subtotalBase   += round(((float)$item['precio'] - $extrasItem) * $item['cantidad'], 2);
            }
            $subtotalTotal = $subtotalBase + $subtotalExtras;

            // Impuesto desde BD del pedido
            $impMonto = (float)($pedido['impuesto'] ?? 0);
            $impPct   = (float)($pedido['impuesto_pct'] ?? 0);
            $impNombre= cfg('impuesto_nombre', 'IVA');
            $impIncl  = (bool)(int)cfg('impuesto_incluido', '1');
            $impActivo= (bool)(int)cfg('impuesto_activo', '1') && $impPct > 0;
            ?>

            <div class="rp-total-row">
                <span class="rp-total-row__label">Subtotal platillos</span>
                <span class="rp-total-row__value">$<?= number_format($subtotalBase, 2) ?></span>
            </div>

            <?php if ($subtotalExtras > 0): ?>
            <div class="rp-total-row">
                <span class="rp-total-row__label">Extras y modificadores</span>
                <span class="rp-total-row__value" style="color:<?= $colorGold ?>;">
                    +$<?= number_format($subtotalExtras, 2) ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if ($impActivo): ?>
            <div class="rp-total-row">
                <span class="rp-total-row__label">
                    <?= h($impNombre) ?> <?= $impPct ?>%
                    <?= $impIncl ? '<span style="font-size:.65rem;opacity:.7;">(incluido)</span>' : '' ?>
                </span>
                <span class="rp-total-row__value" style="color:<?= $colorGold ?>;">
                    <?= $impIncl ? '' : '+' ?>$<?= number_format($impMonto, 2) ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="rp-total-row rp-total-row--final">
                <span class="rp-total-row__label">Total</span>
                <span class="rp-total-row__value">$<?= number_format((float)$pedido['total'], 2) ?> MXN</span>
            </div>
        </div>

        <!-- Seguimiento -->
        <div style="text-align:center;margin-top:1.25rem;" class="rp-no-print">
            <a href="<?= APP_URL ?>/pages/estado_pedido.php?codigo=<?= urlencode($codigo) ?>"
               style="font-size:.78rem;color:<?= $colorGold ?>;text-decoration:none;">
                <i class="bi bi-eye me-1"></i>Ver estado del pedido →
            </a>
        </div>
    </div>

    <!-- Footer del recibo -->
    <div class="rp-receipt__footer">
        <p class="rp-receipt__footer-brand">✦ <?= h($siteNombre) ?></p>
        <p>
            <?= h($direccion) ?><br>
            <?= h($telefono) ?> · <?= h($emailC) ?><br><br>
            Gracias por tu preferencia.<br>
            <em style="color:rgba(245,245,240,.3);font-size:.65rem;">
                Recibo generado el <?= date('d/m/Y H:i') ?>
            </em>
        </p>
    </div>

</div><!-- /.rp-receipt -->

<!-- Tip de impresión (solo pantalla) -->
<div class="rp-no-print" id="printTip" style="display:none;
     background:#fffbf0;border:1px solid #e8dfc8;border-radius:8px;
     padding:.85rem 1.1rem;max-width:480px;margin-bottom:1rem;font-size:.8rem;color:#555;">
    <strong style="color:#9e7e35;">💡 Para guardar como PDF:</strong><br>
    En el diálogo de impresión:<br>
    • <strong>Destino:</strong> Guardar como PDF<br>
    • <strong>Más configuraciones → Gráficos de fondo:</strong> ✅ Activado<br>
    • <strong>Márgenes:</strong> Predeterminado<br>
    El recibo quedará centrado igual que en pantalla.
</div>

<script>
function imprimirRecibo() {
    document.getElementById('printTip').style.display = 'block';
    setTimeout(() => {
        window.print();
        setTimeout(() => {
            document.getElementById('printTip').style.display = 'none';
        }, 2000);
    }, 400);
}

// Auto-imprimir si viene con ?print=1
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 800));
}

// Prevenir que links dentro del recibo abran URLs accidentalmente
// solo los botones de acción externos deben funcionar
document.querySelector('.rp-receipt')?.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (!link) return;
    // Solo bloquear links que NO sean de acción (estado del pedido está permitido)
    if (link.href && link.href.includes('wa.me')) {
        e.preventDefault();
        e.stopPropagation();
    }
});
</script>

</body>
</html>
