<?php
/**
 * RESTAURANT PREMIUM — Admin Login
 * Archivo: admin/login.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isAdminLogged()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa tu correo y contraseña.';
    } elseif (!adminLogin($email, $password)) {
        $error = 'Credenciales incorrectas. Intenta de nuevo.';
        // Pequeño delay para mitigar fuerza bruta
        sleep(1);
    } else {
        header('Location: ' . APP_URL . '/admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración — <?= APP_NAME ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/admin/assets/admin.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:var(--black);">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-9 col-md-7 col-lg-5 col-xl-4">

            <div class="text-center mb-4">
                <div class="rp-brand justify-content-center mb-2">
                    <span class="rp-brand__icon fs-3">✦</span>
                    <span class="rp-brand__name fs-3"><?= APP_NAME ?></span>
                </div>
                <p class="text-muted small">Panel de Administración</p>
            </div>

            <div class="bg-rp-card border border-rp rounded-3 p-4 p-md-5">
                <h1 class="rp-display fs-4 mb-4 text-center">Iniciar Sesión</h1>

                <?php if ($error): ?>
                <div class="rp-alert rp-alert--error mb-4">
                    <i class="bi bi-exclamation-circle me-2"></i><?= h($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="rp-form-label">Correo electrónico</label>
                        <input type="email" name="email" class="rp-form-control form-control"
                               placeholder="admin@restaurantpremium.com"
                               value="<?= isset($_POST['email']) ? h($_POST['email']) : '' ?>"
                               required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="rp-form-label">Contraseña</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="passInput"
                                   class="rp-form-control form-control pe-5"
                                   placeholder="••••••••" required>
                            <button type="button" class="btn p-0 position-absolute end-0 top-50 translate-middle-y me-3"
                                    onclick="togglePass()" style="background:none;border:none;color:var(--white-dim);">
                                <i class="bi bi-eye" id="passIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="rp-btn-gold btn w-100 py-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?= APP_URL ?>/" class="text-muted small">
                        <i class="bi bi-arrow-left me-1"></i>Volver al sitio
                    </a>
                </div>

                <!-- Demo hint -->
                <div class="mt-4 p-3 rounded-2" style="background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.15);">
                    <p class="text-muted small mb-1"><strong class="text-gold">Demo:</strong></p>
                    <p class="text-muted small mb-0">Email: <code>admin@restaurantpremium.com</code></p>
                    <p class="text-muted small mb-0">Pass: <code>password</code></p>
                    <p class="text-muted" style="font-size:.7rem;margin-top:.4rem">⚠ Cambia las credenciales en producción.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const inp  = document.getElementById('passInput');
    const icon = document.getElementById('passIcon');
    if (inp.type === 'password') { inp.type = 'text';     icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
