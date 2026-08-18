<?php
/**
 * RESTAURANT PREMIUM — Página de Reservación
 * Archivo: pages/reservacion.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

$pageTitle       = 'Reservaciones — ' . APP_NAME;
$pageDescription = 'Reserva tu mesa en ' . APP_NAME . '. Contamos con espacios privados para cenas románticas, celebraciones y reuniones de negocios.';

// Manejar envío AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $result = crearReserva($_POST);
    if ($result === true) {
        echo json_encode(['ok' => true, 'message' => '¡Reservación recibida! Te confirmaremos en menos de 2 horas por correo o WhatsApp. ¡Nos vemos pronto!']);
    } else {
        echo json_encode(['ok' => false, 'message' => $result]);
    }
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Page Hero ──────────────────────────────────────────── -->
<div style="padding-top:90px;background:var(--black-soft);border-bottom:1px solid var(--black-border);">
    <div class="container py-5 text-center">
        <p class="rp-eyebrow">Reserva tu Lugar</p>
        <div class="rp-divider mx-auto"></div>
        <h1 class="rp-display fs-1 mt-3">Haz tu <em class="rp-display--italic text-gold">Reservación</em></h1>
        <p class="text-muted mt-2" style="max-width:50ch;margin:auto">
            Completa el formulario y te confirmaremos en menos de 2 horas.
            Para grupos de más de 8 personas, contáctanos directamente.
        </p>
    </div>
</div>

<section class="rp-section rp-section--dark">
    <div class="container">
        <div class="row justify-content-center gy-5">

            <!-- ── Formulario ─────────────────────────────── -->
            <div class="col-lg-7">
                <div class="bg-rp-card border border-rp rounded-3 p-4 p-md-5">
                    <h2 class="rp-display fs-4 mb-1">Información de la Reserva</h2>
                    <p class="text-muted small mb-4">Todos los campos son obligatorios.</p>

                    <!-- Alert container -->
                    <div id="formAlert" class="rp-alert mb-4" style="display:none"></div>

                    <form id="reservationForm" method="POST" action="<?= APP_URL ?>/pages/reservacion.php" novalidate>
                        <div class="row gy-3">
                            <!-- Nombre -->
                            <div class="col-md-6">
                                <label class="rp-form-label">Nombre completo</label>
                                <input type="text" name="nombre" class="rp-form-control form-control"
                                       placeholder="Ana García López" required maxlength="120">
                            </div>
                            <!-- Teléfono -->
                            <div class="col-md-6">
                                <label class="rp-form-label">Teléfono</label>
                                <input type="tel" name="telefono" class="rp-form-control form-control"
                                       placeholder="+52 55 1234 5678" required maxlength="20">
                            </div>
                            <!-- Email -->
                            <div class="col-12">
                                <label class="rp-form-label">Correo electrónico</label>
                                <input type="email" name="email" class="rp-form-control form-control"
                                       placeholder="ana@email.com" required maxlength="180">
                            </div>
                            <!-- Fecha -->
                            <div class="col-md-4">
                                <label class="rp-form-label">Fecha</label>
                                <input type="date" name="fecha" class="rp-form-control form-control" required>
                            </div>
                            <!-- Hora -->
                            <div class="col-md-4">
                                <label class="rp-form-label">Hora</label>
                                <select name="hora" class="rp-form-control form-select" required>
                                    <option value="">Seleccionar…</option>
                                    <?php
                                    $horas = ['13:00','13:30','14:00','14:30','15:00','15:30',
                                              '16:00','16:30','17:00','17:30','18:00','18:30',
                                              '19:00','19:30','20:00','20:30','21:00','21:30','22:00'];
                                    foreach ($horas as $h): ?>
                                    <option value="<?= $h ?>"><?= $h ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Personas -->
                            <div class="col-md-4">
                                <label class="rp-form-label">N.° de personas</label>
                                <select name="personas" class="rp-form-control form-select" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>>
                                        <?= $i ?> <?= $i === 1 ? 'persona' : 'personas' ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Mensaje -->
                            <div class="col-12">
                                <label class="rp-form-label">Ocasión especial (opcional)</label>
                                <textarea name="mensaje" class="rp-form-control form-control" rows="3"
                                          placeholder="Cumpleaños, aniversario, cena de negocios, alergias alimentarias…" maxlength="500"></textarea>
                            </div>
                            <!-- Submit -->
                            <div class="col-12 mt-2">
                                <button type="submit" class="rp-btn-gold btn w-100 py-3">
                                    <i class="bi bi-calendar-check me-2"></i>Confirmar Reservación
                                </button>
                                <p class="text-muted small text-center mt-3 mb-0">
                                    Al enviar, aceptas nuestros términos. Tu información es privada y nunca será compartida.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Info lateral ───────────────────────────── -->
            <div class="col-lg-4 col-md-8">
                <!-- Políticas -->
                <div class="bg-rp-card border border-rp rounded-3 p-4 mb-4">
                    <h3 class="rp-display fs-5 mb-3">Políticas de Reserva</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-clock text-gold flex-shrink-0 mt-1"></i>
                            <p class="text-muted small mb-0">Confirmaremos tu reserva en un máximo de 2 horas.</p>
                        </li>
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-person-check text-gold flex-shrink-0 mt-1"></i>
                            <p class="text-muted small mb-0">Mantenemos la mesa 15 minutos después de la hora reservada.</p>
                        </li>
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-calendar-x text-gold flex-shrink-0 mt-1"></i>
                            <p class="text-muted small mb-0">Cancelaciones con al menos 4 horas de anticipación.</p>
                        </li>
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-people text-gold flex-shrink-0 mt-1"></i>
                            <p class="text-muted small mb-0">Grupos de más de 12 personas, contáctanos directamente.</p>
                        </li>
                    </ul>
                </div>

                <!-- Contacto rápido -->
                <div class="bg-rp-card border border-rp rounded-3 p-4">
                    <h3 class="rp-display fs-5 mb-3">Contacto Directo</h3>
                    <a href="tel:+525512345678"
                       class="d-flex align-items-center gap-3 text-muted small mb-3 text-decoration-none">
                        <i class="bi bi-telephone-fill text-gold fs-5"></i>
                        +52 55 1234 5678
                    </a>
                    <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode(WHATSAPP_MSG) ?>"
                       class="rp-btn-gold btn w-100" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp me-2"></i>Reservar por WhatsApp
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
