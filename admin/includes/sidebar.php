<?php
/**
 * RESTAURANT PREMIUM — Admin Sidebar
 * Archivo: admin/includes/sidebar.php
 */
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($adminTitle ?? 'Panel') ?> — <?= APP_NAME ?> Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/admin/assets/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<!-- ── Overlay (mobile) ──────────────────────────────────── -->
<div id="sidebarOverlay" class="d-lg-none"
     style="display:none!important;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1039;"
     onclick="closeSidebar()"></div>

<!-- ── SIDEBAR ───────────────────────────────────────────── -->
<aside class="rp-sidebar" id="sidebar">

    <!-- Brand -->
    <div class="rp-sidebar__brand">
        <div class="rp-brand">
            <span class="rp-brand__icon">✦</span>
            <span class="rp-brand__name"><?= APP_NAME ?></span>
        </div>
        <p class="text-muted" style="font-size:.7rem;margin:.3rem 0 0;letter-spacing:.1em;">Panel de Administración</p>
    </div>

    <!-- Nav -->
    <nav class="rp-sidebar__nav">
        <p class="rp-sidebar__section-label">Principal</p>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="rp-sidebar__link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= APP_URL ?>/admin/reservas.php" class="rp-sidebar__link <?= $current === 'reservas.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar3"></i> Reservaciones
            <?php
            $pendientes = db()->query("SELECT COUNT(*) FROM reservas WHERE estado='pendiente'")->fetchColumn();
            if ($pendientes > 0): ?>
            <span class="ms-auto badge rounded-pill" style="background:var(--gold);color:var(--black);font-size:.65rem;"><?= $pendientes ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= APP_URL ?>/admin/kds.php" target="_blank"
           class="rp-sidebar__link <?= $current === 'kds.php' ? 'active' : '' ?>">
            <i class="bi bi-display"></i> Kitchen Display
            <span class="ms-auto" style="font-size:.65rem;opacity:.5;">↗</span>
        </a>
        <a href="<?= APP_URL ?>/admin/pedidos.php" class="rp-sidebar__link <?= $current === 'pedidos.php' ? 'active' : '' ?>">
            <i class="bi bi-bag"></i> Pedidos
            <?php
            $pedidosNuevos = db()->query("SELECT COUNT(*) FROM pedidos WHERE estado='nuevo'")->fetchColumn();
            if ($pedidosNuevos > 0): ?>
            <span class="ms-auto badge rounded-pill" style="background:var(--gold);color:var(--black);font-size:.65rem;"><?= $pedidosNuevos ?></span>
            <?php endif; ?>
        </a>

        <p class="rp-sidebar__section-label mt-2">Menú</p>
        <a href="<?= APP_URL ?>/admin/platillos.php" class="rp-sidebar__link <?= $current === 'platillos.php' ? 'active' : '' ?>">
            <i class="bi bi-egg-fried"></i> Platillos
        </a>
        <a href="<?= APP_URL ?>/admin/opciones_grupos.php" class="rp-sidebar__link <?= in_array($current, ['opciones_grupos.php','platillo_opciones.php']) ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i> Extras & Modificadores
        </a>
        <a href="<?= APP_URL ?>/admin/categorias.php" class="rp-sidebar__link <?= $current === 'categorias.php' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i> Categorías
        </a>

        <a href="<?= APP_URL ?>/admin/sugerencias.php" class="rp-sidebar__link <?= $current === 'sugerencias.php' ? 'active' : '' ?>">
            <i class="bi bi-bag-heart"></i> Sugerencias
        </a>
        <a href="<?= APP_URL ?>/admin/galeria.php" class="rp-sidebar__link <?= $current === 'galeria.php' ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Galería
        </a>

        <a href="<?= APP_URL ?>/admin/reporte_reservas.php" class="rp-sidebar__link <?= $current === 'reporte_reservas.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-pdf"></i> Reporte Reservas
        </a>
        <a href="<?= APP_URL ?>/admin/reporte_ventas.php" class="rp-sidebar__link <?= $current === 'reporte_ventas.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i> Reporte Ventas
        </a>

        <p class="rp-sidebar__section-label mt-2">Sistema</p>
        <a href="<?= APP_URL ?>/admin/configuracion.php" class="rp-sidebar__link <?= $current === 'configuracion.php' ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i> Configuración
        </a>
        <a href="<?= APP_URL ?>/admin/usuarios.php" class="rp-sidebar__link <?= $current === 'usuarios.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <a href="<?= APP_URL ?>/" class="rp-sidebar__link" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> Ver Sitio
        </a>
    </nav>

    <!-- Footer -->
    <div class="rp-sidebar__footer">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:32px;height:32px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;color:var(--black);font-size:.9rem;">
                <?= mb_strtoupper(mb_substr($_SESSION['admin_nombre'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
                <p class="mb-0 small fw-semibold" style="color:var(--white);"><?= h($_SESSION['admin_nombre'] ?? '') ?></p>
                <p class="mb-0 text-muted" style="font-size:.7rem;"><?= ucfirst($_SESSION['admin_rol'] ?? '') ?></p>
            </div>
        </div>
        <a href="<?= APP_URL ?>/admin/logout.php" class="rp-sidebar__link text-danger small" style="padding:.4rem 0;border:none;">
            <i class="bi bi-box-arrow-left"></i> Cerrar sesión
        </a>
    </div>
</aside>

<!-- ── MAIN WRAPPER ───────────────────────────────────────── -->
<div class="rp-admin-main">

    <!-- Topbar -->
    <div class="rp-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn p-0 d-lg-none" onclick="toggleSidebar()" style="background:none;border:none;color:var(--white);font-size:1.3rem;">
                <i class="bi bi-list"></i>
            </button>
            <span class="rp-topbar__title"><?= h($adminTitle ?? 'Dashboard') ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small d-none d-md-inline"><?= date('D, d M Y') ?></span>
            <a href="<?= APP_URL ?>/admin/logout.php" class="btn rp-btn-outline btn-sm d-none d-md-inline-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-left"></i> Salir
            </a>
        </div>
    </div>

    <!-- Content starts -->
    <div class="rp-content">
