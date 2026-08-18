<?php
/**
 * RESTAURANT PREMIUM — Página Principal
 * Archivo: index.php
 */
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/settings.php';

// Leer configuración dinámica
$heroImgPath = cfg('site_hero_imagen');
$heroImgUrl  = ($heroImgPath && file_exists(__DIR__ . '/uploads/' . $heroImgPath))
               ? APP_URL . '/uploads/' . $heroImgPath
               : 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1920&q=80';

$heroTitulo    = cfg('site_hero_titulo',    'Donde cada plato<br>cuenta una <em>historia</em>');
$heroSubtitulo = cfg('site_hero_subtitulo', 'Una experiencia gastronómica que trasciende los sentidos. Ingredientes de temporada, técnica impecable y un servicio que anticipa cada deseo.');
$nosotrosTitulo= cfg('nosotros_titulo',     'Pasión por la <em class="rp-display--italic text-gold">excelencia</em>');
$nosotrosTexto1= cfg('nosotros_texto1',     'Fundado en 2012 en el corazón de Polanco, nació de la visión del Chef Ejecutivo de demostrar que la gastronomía mexicana puede dialogar con las grandes cocinas del mundo.');
$nosotrosTexto2= cfg('nosotros_texto2',     'Trabajamos directamente con productores locales, seleccionamos vinos de pequeñas bodegas y actualizamos nuestro menú cada temporada.');
$nosotrosImgPath = cfg('nosotros_imagen');
$nosotrosImgUrl  = ($nosotrosImgPath && file_exists(__DIR__ . '/uploads/' . $nosotrosImgPath))
                   ? APP_URL . '/uploads/' . $nosotrosImgPath
                   : 'https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=800&q=80';

$pageTitle       = APP_NAME . ' — Gastronomía de Alta Cocina';
$pageDescription = 'Experiencia gastronómica de lujo en el corazón de la ciudad. Ingredientes premium, técnica impecable y un servicio que anticipa cada deseo. Reserva tu mesa hoy.';

$platillosDestacados = getPlatillosDestacados();
$testimonios         = getTestimonios();
$galeria             = getGaleria();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  HERO                                                       -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="rp-hero" id="inicio">
    <div class="rp-hero__bg" style="background-image: linear-gradient(135deg, rgba(13,13,13,.92) 0%, rgba(13,13,13,.6) 60%, rgba(13,13,13,.85) 100%), url('<?= $heroImgUrl ?>'); background-size: cover; background-position: center;"></div>
    <div class="rp-hero__grain"></div>

    <div class="container rp-hero__content">
        <div class="row">
            <div class="col-lg-7">
                <div class="rp-hero__tag">
                    <span class="rp-hero__tag-dot" style="width:6px;height:6px;background:var(--gold);border-radius:50%;display:inline-block;"></span>
                    Alta Cocina · Ciudad de México
                </div>

                <h1 class="rp-hero__title">
                    <?= $heroTitulo ?>
                </h1>

                <p class="rp-hero__subtitle">
                    <?= h($heroSubtitulo) ?>
                </p>

                <div class="rp-hero__cta">
                    <a href="<?= APP_URL ?>/pages/reservacion.php" class="rp-btn-gold btn">
                        <i class="bi bi-calendar3 me-2"></i>Reservar Mesa
                    </a>
                    <a href="<?= APP_URL ?>/pages/menu.php" class="rp-btn-outline btn">
                        <i class="bi bi-journal-text me-2"></i>Ver Menú
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="rp-hero__scroll">
        <div class="rp-hero__scroll-line"></div>
        <span>scroll</span>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  STATS                                                      -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="rp-stats">
    <div class="container">
        <div class="row gy-4">
            <div class="col-6 col-md-3 rp-stats__item">
                <div class="rp-stats__num" data-count="12" data-suffix="+">0+</div>
                <div class="rp-stats__label">Años de experiencia</div>
            </div>
            <div class="col-6 col-md-3 rp-stats__item">
                <div class="rp-stats__num" data-count="48" data-suffix="">0</div>
                <div class="rp-stats__label">Platillos en menú</div>
            </div>
            <div class="col-6 col-md-3 rp-stats__item">
                <div class="rp-stats__num" data-count="15000" data-suffix="+">0+</div>
                <div class="rp-stats__label">Comensales felices</div>
            </div>
            <div class="col-6 col-md-3 rp-stats__item">
                <div class="rp-stats__num" data-count="5" data-suffix="★">0★</div>
                <div class="rp-stats__label">Calificación promedio</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  PLATILLOS DESTACADOS                                       -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="rp-section rp-section--dark" id="menu">
    <div class="container">
        <!-- Encabezado -->
        <div class="text-center mb-5 rp-reveal">
            <p class="rp-eyebrow">Creaciones del Chef</p>
            <div class="rp-divider mx-auto"></div>
            <h2 class="rp-display fs-1 mt-3">Platillos <em class="rp-display--italic text-gold">Destacados</em></h2>
            <p class="text-muted mt-2" style="max-width:50ch;margin:auto">
                Una selección de nuestras preparaciones más aclamadas,
                elaboradas con ingredientes de temporada y técnica de alto nivel.
            </p>
        </div>

        <!-- Cards -->
        <div class="row gy-4">
            <?php foreach ($platillosDestacados as $i => $p): ?>
            <div class="col-md-6 col-lg-4 rp-reveal rp-reveal--delay-<?= ($i % 3) + 1 ?>">
                <div class="rp-card">
                    <div class="rp-card__img-wrap">
                        <img src="<?= platilloImg($p['imagen'], $p['nombre']) ?>"
                             alt="<?= h($p['nombre']) ?>" loading="lazy">
                        <span class="rp-card__badge">Destacado</span>
                    </div>
                    <div class="rp-card__body d-flex flex-column">
                        <p class="rp-card__category"><?= h($p['categoria_nombre']) ?></p>
                        <h3 class="rp-card__title"><?= h($p['nombre']) ?></h3>
                        <p class="rp-card__desc"><?= h($p['descripcion']) ?></p>
                        <div class="rp-card__footer mt-auto">
                            <span class="rp-card__price"><?= formatPrecio((float)$p['precio']) ?></span>
                            <a href="<?= APP_URL ?>/pages/menu.php" class="btn rp-btn-outline btn-sm">
                                Ver menú <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5 rp-reveal">
            <a href="<?= APP_URL ?>/pages/menu.php" class="rp-btn-gold btn px-5">
                Ver Menú Completo <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  NOSOTROS                                                   -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="rp-section rp-section--soft" id="nosotros">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-5 rp-reveal">
                <div class="rp-about__img">
                    <img src="<?= $nosotrosImgUrl ?>"
                         alt="Nuestro Chef" loading="lazy">
                </div>
            </div>
            <div class="col-lg-6 offset-lg-1 rp-reveal rp-reveal--delay-2">
                <p class="rp-eyebrow">Nuestra Historia</p>
                <div class="rp-divider"></div>
                <h2 class="rp-display fs-1 mt-3 mb-4">
                    <?= $nosotrosTitulo ?>
                </h2>
                <p class="text-muted lh-lg mb-3"><?= h($nosotrosTexto1) ?></p>
                <p class="text-muted lh-lg mb-4"><?= h($nosotrosTexto2) ?></p>

                <div class="row gy-3 mb-4">
                    <div class="col-6">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-patch-check-fill text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <p class="fw-semibold mb-0 small">Ingredientes Premium</p>
                                <p class="text-muted small mb-0">Selección diaria de mercado</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-award-fill text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <p class="fw-semibold mb-0 small">Chef Reconocido</p>
                                <p class="text-muted small mb-0">12 años de experiencia</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-heart-fill text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <p class="fw-semibold mb-0 small">Cocina con Pasión</p>
                                <p class="text-muted small mb-0">Cada plato, una obra de arte</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-stars text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <p class="fw-semibold mb-0 small">Servicio 5 Estrellas</p>
                                <p class="text-muted small mb-0">Atención personalizada</p>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="<?= APP_URL ?>/pages/reservacion.php" class="rp-btn-gold btn px-5">
                    Reservar una Mesa
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  GALERÍA                                                    -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="rp-section rp-section--dark" id="galeria">
    <div class="container">
        <div class="text-center mb-5 rp-reveal">
            <p class="rp-eyebrow">Nuestros Espacios</p>
            <div class="rp-divider mx-auto"></div>
            <h2 class="rp-display fs-1 mt-3">Una <em class="rp-display--italic text-gold">Atmósfera</em> Única</h2>
        </div>

        <div class="rp-gallery-grid rp-reveal">
            <?php
            // Usar fotos de la BD; si no hay, mostrar placeholders de Unsplash
            $galleryImgs = [];
            foreach ($galeria as $g) {
                $imgPath = __DIR__ . '/uploads/' . $g['imagen'];
                $url = file_exists($imgPath)
                    ? APP_URL . '/uploads/' . h($g['imagen'])
                    : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&q=80';
                $galleryImgs[] = [$url, $g['titulo'] ?? 'Galería'];
            }
            // Fallback si la galería está vacía
            if (!$galleryImgs) {
                $galleryImgs = [
                    ['https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&q=80', 'Salón Principal'],
                    ['https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80', 'Cocina Abierta'],
                    ['https://images.unsplash.com/photo-1551218808-94e220e084d2?w=600&q=80', 'Terraza'],
                    ['https://images.unsplash.com/photo-1474755032398-4b0ed3b2ae5c?w=600&q=80', 'Bar'],
                    ['https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&q=80', 'Mesa del Chef'],
                    ['https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=600&q=80', 'Bodega'],
                ];
            }
            foreach ($galleryImgs as [$imgUrl, $title]):
            ?>
            <a href="<?= $imgUrl ?>" class="rp-gallery-item glightbox" data-gallery="restaurante" data-title="<?= h($title) ?>">
                <img src="<?= $imgUrl ?>" alt="<?= h($title) ?>" loading="lazy">
                <div class="rp-gallery-item__overlay">
                    <i class="bi bi-zoom-in"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  TESTIMONIOS (desactivado temporalmente)                   -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php if (false): ?>
<section class="rp-section rp-section--soft" id="testimonios">
    <div class="container">
        <div class="text-center mb-5 rp-reveal">
            <p class="rp-eyebrow">Lo Que Dicen</p>
            <div class="rp-divider mx-auto"></div>
            <h2 class="rp-display fs-1 mt-3">Nuestros <em class="rp-display--italic text-gold">Comensales</em></h2>
        </div>

        <?php if ($testimonios): ?>
        <div id="testimonialCarousel" class="carousel slide">
            <div class="carousel-inner">
                <?php foreach (array_chunk($testimonios, 3) as $idx => $grupo): ?>
                <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                    <div class="row gy-4">
                        <?php foreach ($grupo as $t): ?>
                        <div class="col-md-4">
                            <div class="rp-testimonial">
                                <div class="rp-testimonial__stars"><?= estrellas((int)$t['estrellas']) ?></div>
                                <p class="rp-testimonial__text"><?= h($t['comentario']) ?></p>
                                <div class="rp-testimonial__author">
                                    <div class="rp-testimonial__avatar">
                                        <?= mb_strtoupper(mb_substr($t['nombre'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="rp-testimonial__name"><?= h($t['nombre']) ?></p>
                                        <?php if ($t['cargo']): ?>
                                        <p class="rp-testimonial__role"><?= h($t['cargo']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <button class="btn rp-btn-outline btn-sm" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <button class="btn rp-btn-outline btn-sm" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  RESERVACIONES CTA                                         -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="rp-section rp-section--dark" id="reserva-cta"
         style="background: linear-gradient(135deg,#0d0d0d 60%, #1a1408 100%);">
    <div class="container text-center">
        <div class="rp-reveal">
            <p class="rp-eyebrow">Agenda tu Visita</p>
            <div class="rp-divider mx-auto"></div>
            <h2 class="rp-display fs-1 mt-3 mb-3">
                Reserva tu <em class="rp-display--italic text-gold">Mesa</em> Hoy
            </h2>
            <p class="text-muted mb-5" style="max-width:48ch;margin:auto">
                Contamos con espacios privados para cenas románticas, celebraciones
                y reuniones de negocios. Cupo limitado — reserva con anticipación.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= APP_URL ?>/pages/reservacion.php" class="rp-btn-gold btn px-5">
                    <i class="bi bi-calendar-check me-2"></i>Hacer Reservación
                </a>
                <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode(WHATSAPP_MSG) ?>"
                   class="rp-btn-outline btn px-5" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-2"></i>WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  MAPA / CONTACTO                                           -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="rp-section rp-section--soft" id="contacto">
    <div class="container">
        <div class="text-center mb-5 rp-reveal">
            <p class="rp-eyebrow">Encuéntranos</p>
            <div class="rp-divider mx-auto"></div>
            <h2 class="rp-display fs-1 mt-3">Ubicación y <em class="rp-display--italic text-gold">Contacto</em></h2>
        </div>

        <div class="row gy-5 align-items-start">
            <div class="col-lg-4 rp-reveal">
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex gap-3">
                        <div style="width:44px;height:44px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-geo-alt-fill text-gold"></i>
                        </div>
                        <div>
                            <p class="fw-semibold mb-1 small">Dirección</p>
                            <p class="text-muted small mb-0">Av. Presidente Masaryk 123<br>Polanco, CDMX CP 11560</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div style="width:44px;height:44px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-clock-fill text-gold"></i>
                        </div>
                        <div>
                            <p class="fw-semibold mb-1 small">Horarios</p>
                            <p class="text-muted small mb-0">Mar – Vie: 13:00 – 23:00<br>Sáb: 12:00 – 24:00<br>Dom: 12:00 – 22:00</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div style="width:44px;height:44px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-telephone-fill text-gold"></i>
                        </div>
                        <div>
                            <p class="fw-semibold mb-1 small">Teléfono</p>
                            <a href="tel:+525512345678" class="text-muted small">+52 55 1234 5678</a>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div style="width:44px;height:44px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-envelope-fill text-gold"></i>
                        </div>
                        <div>
                            <p class="fw-semibold mb-1 small">Email</p>
                            <a href="mailto:reservaciones@restaurantpremium.com" class="text-muted small">reservaciones@restaurantpremium.com</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 rp-reveal rp-reveal--delay-2">
                <div class="rp-map">
                    <iframe
                        src="<?= GMAPS_EMBED_URL ?>"
                        allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Ubicación de <?= APP_NAME ?>">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>