<?php
/**
 * RESTAURANT PREMIUM — Menú Digital
 * Archivo: pages/menu.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

$pageTitle       = 'Menú — ' . APP_NAME;
$pageDescription = 'Descubre nuestra carta completa: entradas, platos fuertes, bebidas y postres elaborados con ingredientes premium.';

$categorias = getCategorias();
$platillos  = getPlatillos();

// Agrupar platillos por categoría
$byCategory = [];
foreach ($platillos as $p) {
    $byCategory[$p['categoria_id']][] = $p;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Page Hero ──────────────────────────────────────────── -->
<div style="padding-top:90px;background:var(--black-soft);border-bottom:1px solid var(--black-border);">
    <div class="container py-5 text-center">
        <p class="rp-eyebrow">Descubre</p>
        <div class="rp-divider mx-auto"></div>
        <h1 class="rp-display fs-1 mt-3">Nuestra <em class="rp-display--italic text-gold">Carta</em></h1>
        <p class="text-muted mt-2" style="max-width:50ch;margin:auto">
            Ingredientes de temporada, técnica impecable y pasión en cada preparación.
            Los precios incluyen IVA.
        </p>
    </div>
</div>

<!-- ── Filtros + Búsqueda ─────────────────────────────────── -->
<section class="rp-section rp-section--dark">
    <div class="container">

        <!-- Búsqueda -->
        <div class="rp-search mx-auto mb-4">
            <i class="bi bi-search rp-search__icon"></i>
            <input type="search" id="menuSearch" class="rp-search__input"
                   placeholder="Buscar platillo…" autocomplete="off">
        </div>

        <!-- Pills de categoría -->
        <div class="rp-filter-bar">
            <button class="rp-filter-pill active" data-cat="all">
                <i class="bi bi-grid me-1"></i>Todo
            </button>
            <?php foreach ($categorias as $cat): ?>
            <button class="rp-filter-pill" data-cat="<?= $cat['id'] ?>">
                <i class="bi <?= h($cat['icono']) ?> me-1"></i><?= h($cat['nombre']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de platillos -->
        <div class="row gy-4" id="menuGrid">
            <?php foreach ($platillos as $p): 
                $imgUrl = platilloImg($p['imagen'], $p['nombre']);
                $platilloJson = json_encode([
                    'id'     => (int)$p['id'],
                    'nombre' => $p['nombre'],
                    'precio' => (float)$p['precio'],
                    'img'    => $imgUrl,
                ], JSON_HEX_APOS | JSON_HEX_QUOT);
            ?>
            <div class="col-sm-6 col-lg-4 rp-reveal"
                 data-categoria="<?= $p['categoria_id'] ?>"
                 data-name="<?= h(strtolower($p['nombre'])) ?>"
                 data-desc="<?= h(strtolower($p['descripcion'])) ?>"
                 data-platillo-json='<?= $platilloJson ?>'>
                <div class="rp-card h-100">
                    <div class="rp-card__img-wrap">
                        <img src="<?= $imgUrl ?>"
                             alt="<?= h($p['nombre']) ?>" loading="lazy">
                        <?php if ($p['destacado']): ?>
                        <span class="rp-card__badge">Chef recomienda</span>
                        <?php endif; ?>
                    </div>
                    <div class="rp-card__body d-flex flex-column">
                        <p class="rp-card__category"><?= h($p['categoria_nombre']) ?></p>
                        <h3 class="rp-card__title"><?= h($p['nombre']) ?></h3>
                        <p class="rp-card__desc"><?= h($p['descripcion']) ?></p>
                        <div class="rp-card__footer mt-auto">
                            <span class="rp-card__price"><?= formatPrecio((float)$p['precio']) ?></span>
                            <button class="rp-add-btn" data-add-platillo data-pid="<?= $p['id'] ?>">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Estado vacío -->
        <div id="menuEmpty" class="text-center py-5" style="display:none">
            <i class="bi bi-search text-gold" style="font-size:3rem;opacity:.5"></i>
            <p class="text-muted mt-3">No encontramos platillos con esa búsqueda.</p>
            <button class="rp-btn-outline btn btn-sm mt-2" onclick="document.getElementById('menuSearch').value='';filterMenu();">
                Limpiar búsqueda
            </button>
        </div>

    </div>
</section>

<!-- ── CTA Reservación ────────────────────────────────────── -->
<section class="rp-section rp-section--soft" style="padding:3rem 0;">
    <div class="container text-center">
        <p class="text-muted mb-3">¿Listo para vivir la experiencia?</p>
        <a href="<?= APP_URL ?>/pages/reservacion.php" class="rp-btn-gold btn px-5">
            <i class="bi bi-calendar-check me-2"></i>Reservar Mesa
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
