<?php
/**
 * RESTAURANT PREMIUM - Header común (con configuración dinámica)
 * Archivo: includes/header.php
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/database.php';
}
require_once __DIR__ . '/../config/settings.php';

// Valores dinámicos desde BD
$siteNombre  = cfg('site_nombre',  APP_NAME);
$siteSlogan  = cfg('site_slogan',  'Gastronomía de Alta Cocina');
$siteDesc    = cfg('site_descripcion', 'Experiencia gastronómica de lujo en el corazón de la ciudad.');
$siteKeywords= cfg('seo_keywords', 'restaurante premium, alta cocina, reservaciones');
$whatsappNum = cfg('whatsapp_numero', WHATSAPP_NUMBER);
$whatsappMsg = cfg('whatsapp_mensaje', WHATSAPP_MSG);

// Logo
$logoPath = cfg('site_logo');
$logoUrl  = ($logoPath && file_exists(__DIR__.'/../uploads/'.$logoPath))
            ? APP_URL . '/uploads/' . $logoPath
            : '';

// Favicon personalizado
$faviconPath = cfg('site_favicon');
$faviconUrl  = ($faviconPath && file_exists(__DIR__.'/../uploads/'.$faviconPath))
               ? APP_URL . '/uploads/' . $faviconPath
               : APP_URL . '/assets/images/favicon.svg';

// OG Image
$ogImgPath = cfg('seo_og_image');
$ogImgUrl  = ($ogImgPath && file_exists(__DIR__.'/../uploads/'.$ogImgPath))
             ? APP_URL . '/uploads/' . $ogImgPath
             : APP_URL . '/assets/images/og-image.jpg';

$pageTitle       = $pageTitle       ?? $siteNombre . ' — ' . $siteSlogan;
$pageDescription = $pageDescription ?? $siteDesc;
$pageImage       = $pageImage       ?? $ogImgUrl;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- SEO -->
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <meta name="keywords"    content="<?= h($siteKeywords) ?>">
    <meta name="author"      content="<?= h($siteNombre) ?>">
    <meta name="robots"      content="index, follow">
    <link rel="canonical"    href="<?= APP_URL ?>/">

    <!-- Open Graph -->
    <meta property="og:type"        content="restaurant">
    <meta property="og:title"       content="<?= h($pageTitle) ?>">
    <meta property="og:description" content="<?= h($pageDescription) ?>">
    <meta property="og:image"       content="<?= h($pageImage) ?>">
    <meta property="og:url"         content="<?= APP_URL ?>">
    <meta property="og:locale"      content="es_MX">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= h($pageTitle) ?>">
    <meta name="twitter:description" content="<?= h($pageDescription) ?>">
    <meta name="twitter:image"       content="<?= h($pageImage) ?>">

    <!-- Favicon dinámico -->
    <link rel="icon" href="<?= $faviconUrl ?>" type="image/svg+xml">

    <!-- Google Fonts dinámico -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?= getDynamicFonts() ?>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- GLightbox -->
    <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">

    <!-- CSS principal -->
    <link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/cart.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/opciones.css" rel="stylesheet">

    <!-- Variables CSS dinámicas (colores, radios) -->
    <?= getCSSVariables() ?>
    <?= getDynamicFontCSS() ?>
</head>
<body>

<!-- ─── NAVBAR ─────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark rp-navbar fixed-top" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand rp-brand" href="<?= APP_URL ?>/">
            <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" alt="<?= h($siteNombre) ?>"
                 style="max-height:40px;max-width:160px;object-fit:contain;">
            <?php else: ?>
            <span class="rp-brand__icon">✦</span>
            <span class="rp-brand__name"><?= h($siteNombre) ?></span>
            <?php endif; ?>
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/#inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/pages/menu.php">Menú</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/#galeria">Galería</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/#nosotros">Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/#contacto">Contacto</a></li>
                <li class="nav-item ms-lg-3">
                    <a class="btn rp-btn-gold btn-sm px-4" href="<?= APP_URL ?>/pages/reservacion.php">
                        Reservar Mesa
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <button class="rp-cart-btn" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                        <i class="bi bi-bag"></i>
                        <span class="rp-cart-count" style="display:none">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ─── CARRITO OFFCANVAS ─────────────────────────────── -->
<div class="offcanvas offcanvas-end rp-offcanvas" tabindex="-1" id="cartOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="bi bi-bag me-2 text-gold"></i>Tu Pedido
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column" style="padding:0;">
        <div id="cartItems" class="rp-cart-items flex-grow-1"></div>
        <?php
        if (!function_exists('getImpuesto')) require_once __DIR__ . '/../config/settings.php';
        $_cartImp = getImpuesto();
        ?>
        <div class="rp-cart-footer">
            <!-- Desglose impuesto — visible si impuesto activo -->
            <?php if ($_cartImp['activo'] && $_cartImp['pct'] > 0): ?>
            <div id="cartTaxBreakdown" style="margin-bottom:.5rem;">
                <div style="display:flex;justify-content:space-between;padding:.28rem 0;">
                    <span style="font-size:.74rem;color:var(--white-dim);">Subtotal</span>
                    <span id="cartSubtotalVal" style="font-size:.74rem;color:var(--white-dim);">$0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:.28rem 0;
                            border-bottom:1px dashed rgba(201,168,76,.25);margin-bottom:.4rem;">
                    <span style="font-size:.74rem;color:var(--gold);">
                        <?= h($_cartImp['nombre']) ?> <?= $_cartImp['pct'] ?>%
                        <?= $_cartImp['incluido'] ? '<span style="opacity:.6;font-size:.65rem;">(incluido)</span>' : '' ?>
                    </span>
                    <span id="cartTaxVal" style="font-size:.74rem;color:var(--gold);font-weight:600;">$0.00</span>
                </div>
            </div>
            <?php else: ?>
            <div id="cartTaxBreakdown" style="display:none;">
                <span id="cartSubtotalVal"></span>
                <span id="cartTaxVal"></span>
            </div>
            <?php endif; ?>
            <div class="rp-cart-total-row">
                <span class="rp-cart-total-label">Total</span>
                <span class="rp-cart-total-value">$0.00 MXN</span>
            </div>
            <button type="button"
               id="checkoutBtn"
               class="rp-btn-gold btn w-100"
               disabled
               onclick="SugerenciasModal.abrir()">
                <i class="bi bi-bag-check me-2"></i>Proceder al Pago
            </button>
            <button class="btn w-100 mt-2 text-muted small"
                    style="background:none;border:none;"
                    onclick="Cart.clear()">
                <i class="bi bi-trash me-1"></i>Vaciar carrito
            </button>
        </div>
    </div>
</div>

<!-- ─── MODAL DE SUGERENCIAS ────────────────────────────────── -->
<div class="modal fade rp-modal-sugerencias" id="sugerenciasModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:var(--black-soft);border:1px solid var(--black-border);border-radius:var(--radius-lg);">
            <div class="modal-header" style="border-bottom:1px solid var(--black-border);padding:1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title" style="font-family:var(--font-display);color:var(--white);">
                        🍽️ ¿Le agregas algo más?
                    </h5>
                    <p class="text-muted small mb-0">Otros clientes también pidieron esto</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);opacity:.5;"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div id="sugerenciasGrid" class="row g-3">
                    <!-- Poblado por JS -->
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--black-border);padding:1rem 1.5rem;background:var(--black-card);border-radius:0 0 var(--radius-lg) var(--radius-lg);">
                <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                    <button type="button" class="btn rp-btn-outline"
                            onclick="SugerenciasModal.irCheckout()">
                        No gracias, continuar
                    </button>
                    <button type="button" class="rp-btn-gold btn px-5"
                            onclick="SugerenciasModal.irCheckout()">
                        <i class="bi bi-bag-check me-2"></i>
                        Confirmar pedido
                        <span id="sugerenciasTotal" class="ms-2 opacity-75" style="font-size:.82rem;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── MODAL DE OPCIONES ────────────────────────────────── -->
<div class="modal fade rp-modal-opciones" id="opcionesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <img id="modalPlatilloImg" src="" alt=""
                         style="width:48px;height:38px;object-fit:cover;border-radius:4px;flex-shrink:0;">
                    <div>
                        <h5 class="modal-title mb-0" id="modalPlatilloNombre"></h5>
                        <p class="text-muted mb-0" style="font-size:.78rem;">
                            Precio base: <span id="modalPlatilloPrecio" class="text-gold"></span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Error -->
                <div id="modalError" class="rp-alert rp-alert--error mb-3" style="display:none;"></div>
                <!-- Grupos de opciones -->
                <div id="modalGrupos"></div>
                <!-- Notas adicionales -->
                <div class="mt-3 pt-3" style="border-top:1px solid var(--black-border);">
                    <label class="rp-form-label">Notas adicionales (opcional)</label>
                    <textarea id="modalNotaInput" class="rp-notas-input form-control" rows="2"
                              placeholder="Instrucciones especiales para este platillo…" maxlength="255"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
                    <div>
                        <p class="rp-modal-precio-base mb-0" id="modalPrecioBase"></p>
                        <p class="rp-modal-precio-extras mb-0" id="modalPrecioExtras"></p>
                        <p class="rp-modal-precio-total mb-0" id="modalPrecioTotal"></p>
                    </div>
                    <button type="button" class="rp-btn-gold btn px-5"
                            onclick="OpcionesModal.confirmar()">
                        <i class="bi bi-bag-plus me-2"></i>Agregar al Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
