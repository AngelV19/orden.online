<?php
/**
 * RESTAURANT PREMIUM - Footer común (con configuración dinámica)
 * Archivo: includes/footer.php
 */
if (!function_exists('cfg')) require_once __DIR__ . '/../config/settings.php';

$siteNombre  = cfg('site_nombre',  APP_NAME);
$whatsappNum = cfg('whatsapp_numero', WHATSAPP_NUMBER);
$whatsappMsg = cfg('whatsapp_mensaje', WHATSAPP_MSG);
$socialIg    = cfg('social_instagram',  '#');
$socialFb    = cfg('social_facebook',   '#');
$socialTa    = cfg('social_tripadvisor','#');
$socialYt    = cfg('social_youtube',    '#');
$telefono    = cfg('contacto_telefono', '+52 55 1234 5678');
$emailC      = cfg('contacto_email',    'reservaciones@restaurantpremium.com');
$direccion   = cfg('contacto_direccion','Av. Presidente Masaryk 123, Polanco, CDMX');
$horarios    = cfg('contacto_horarios', "Mar-Vie: 13:00-23:00\nSab: 12:00-24:00\nDom: 12:00-22:00");
$siteDesc    = cfg('site_descripcion',  'Una experiencia gastronómica que trasciende los sentidos.');
?>
<!-- ─── FOOTER ───────────────────────────────────────────────────────── -->
<footer class="rp-footer">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-4">
                <div class="rp-brand mb-3">
                    <span class="rp-brand__icon fs-4">✦</span>
                    <span class="rp-brand__name fs-4"><?= h($siteNombre) ?></span>
                </div>
                <p class="text-muted small lh-lg"><?= h($siteDesc) ?></p>
                <div class="rp-social-links mt-4">
                    <a href="<?= h($socialIg) ?>" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="<?= h($socialFb) ?>" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="<?= h($socialTa) ?>" aria-label="TripAdvisor"><i class="bi bi-star-fill"></i></a>
                    <a href="<?= h($socialYt) ?>" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="rp-footer__title">Horarios</h6>
                <ul class="rp-footer__list">
                    <?php foreach (explode("\n", $horarios) as $linea): if (!trim($linea)) continue; ?>
                    <li><span class="text-muted"><?= h(trim($linea)) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="rp-footer__title">Descubrir</h6>
                <ul class="rp-footer__nav">
                    <li><a href="<?= APP_URL ?>/pages/menu.php">Menú Digital</a></li>
                    <li><a href="<?= APP_URL ?>/pages/reservacion.php">Reservaciones</a></li>
                    <li><a href="<?= APP_URL ?>/#galeria">Galería</a></li>
                    <li><a href="<?= APP_URL ?>/#nosotros">Nuestra Historia</a></li>
                    <li><a href="<?= APP_URL ?>/#contacto">Contacto</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="rp-footer__title">Contáctanos</h6>
                <ul class="rp-footer__contact">
                    <li><i class="bi bi-geo-alt-fill"></i><span><?= h($direccion) ?></span></li>
                    <li><i class="bi bi-telephone-fill"></i><a href="tel:<?= h($telefono) ?>"><?= h($telefono) ?></a></li>
                    <li><i class="bi bi-envelope-fill"></i><a href="mailto:<?= h($emailC) ?>"><?= h($emailC) ?></a></li>
                </ul>
            </div>
        </div>
        <hr class="rp-footer__divider">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <p class="small text-muted mb-0">&copy; <?= date('Y') ?> <?= h($siteNombre) ?>. Todos los derechos reservados.</p>
            <p class="small text-muted mb-0">Diseñado con <span style="color:var(--gold)">♥</span> para una experiencia inolvidable</p>
        </div>
    </div>
</footer>

<!-- ─── BOTÓN WHATSAPP FLOTANTE ──────────────────────────────────────── -->
<a href="https://wa.me/<?= h($whatsappNum) ?>?text=<?= rawurlencode($whatsappMsg) ?>"
   class="rp-whatsapp-btn" target="_blank" rel="noopener" aria-label="WhatsApp">
    <i class="bi bi-whatsapp"></i>
    <span class="rp-whatsapp-btn__tooltip">¿Tienes dudas? Escríbenos</span>
</a>

<!-- ─── SCRIPTS ───────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>window.APP_URL = '<?= APP_URL ?>';</script>
<?php
if (!function_exists('getImpuesto')) require_once __DIR__ . '/../config/settings.php';
$_imp = getImpuesto();
?>
<script>
// Configuración de impuesto disponible ANTES de cargar cart.js
window.RpImpuesto = {
    activo:   <?= $_imp['activo'] ? 'true' : 'false' ?>,
    pct:      <?= (float)$_imp['pct'] ?>,
    nombre:   '<?= h($_imp['nombre']) ?>',
    incluido: <?= $_imp['incluido'] ? 'true' : 'false' ?>
};
</script>
<script src="<?= APP_URL ?>/assets/js/cart.js"></script>
<script src="<?= APP_URL ?>/assets/js/sugerencias.js"></script>
<script src="<?= APP_URL ?>/assets/js/opciones.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
