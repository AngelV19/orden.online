<?php
/**
 * RESTAURANT PREMIUM — Kitchen Display System (KDS)
 * Archivo: admin/kds.php
 *
 * Pantalla de cocina en tiempo real.
 * Diseñada para tablets y monitores en la cocina.
 * Se actualiza automáticamente cada 10 segundos.
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/settings.php';
requireAdmin();

$db = db();

$siteNombre = cfg('site_nombre', APP_NAME);

// ── Cambiar estado de pedido (AJAX) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $id     = (int)($_POST['id']     ?? 0);
    $estado = $_POST['estado']       ?? '';
    $estados = ['nuevo','preparando','listo','entregado','cancelado'];

    if ($id && in_array($estado, $estados)) {
        $db->prepare('UPDATE pedidos SET estado=:e WHERE id=:id')
           ->execute([':e'=>$estado, ':id'=>$id]);
        echo json_encode(['ok'=>true, 'estado'=>$estado]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── Cargar pedidos activos ────────────────────────────────
$filtroEstado = $_GET['filtro'] ?? 'activos';
$estadosValidos = ['nuevo','preparando','listo','entregado','cancelado'];

if ($filtroEstado === 'activos') {
    $sql = "SELECT p.* FROM pedidos p
            WHERE p.estado IN('nuevo','preparando','listo')
            ORDER BY FIELD(p.estado,'nuevo','preparando','listo') ASC, p.created_at ASC";
    $stmtPed = $db->prepare($sql);
    $stmtPed->execute();

} elseif ($filtroEstado === 'todos') {
    $sql = "SELECT p.* FROM pedidos p
            WHERE DATE(p.created_at) = CURDATE()
            ORDER BY FIELD(p.estado,'nuevo','preparando','listo','entregado','cancelado') ASC, p.created_at ASC";
    $stmtPed = $db->prepare($sql);
    $stmtPed->execute();

} elseif (in_array($filtroEstado, $estadosValidos, true)) {
    $sql = "SELECT p.* FROM pedidos p
            WHERE p.estado = :estado
            ORDER BY p.created_at ASC";
    $stmtPed = $db->prepare($sql);
    $stmtPed->execute([':estado' => $filtroEstado]);

} else {
    // Filtro inválido — usar activos por defecto
    $filtroEstado = 'activos';
    $sql = "SELECT p.* FROM pedidos p
            WHERE p.estado IN('nuevo','preparando','listo')
            ORDER BY FIELD(p.estado,'nuevo','preparando','listo') ASC, p.created_at ASC";
    $stmtPed = $db->prepare($sql);
    $stmtPed->execute();
}

$pedidos = $stmtPed->fetchAll();

// Cargar items y opciones para cada pedido
foreach ($pedidos as &$pedido) {
    $sItems = $db->prepare('SELECT * FROM pedido_items WHERE pedido_id=:id ORDER BY id ASC');
    $sItems->execute([':id' => $pedido['id']]);
    $items = $sItems->fetchAll();

    foreach ($items as &$item) {
        $sOp = $db->prepare('SELECT * FROM pedido_item_opciones WHERE item_id=:id');
        $sOp->execute([':id' => $item['id']]);
        $item['opciones'] = $sOp->fetchAll();
    }
    unset($item);
    $pedido['items'] = $items;

    // Tiempo transcurrido desde el pedido
    $pedido['minutos'] = round((time() - strtotime($pedido['created_at'])) / 60);
}
unset($pedido);

$tipoLabels = [
    'salon'     => ['En Restaurante', 'bi-door-open'],
    'llevar'    => ['Para Llevar',    'bi-bag'],
    'domicilio' => ['A Domicilio',    'bi-bicycle'],
];

$estadoConfig = [
    'nuevo'      => ['Nuevo',       'rp-kds-nuevo',      '#ffc107'],
    'preparando' => ['Preparando',  'rp-kds-preparando', '#fd7e14'],
    'listo'      => ['Listo ✓',    'rp-kds-listo',      '#2ea043'],
    'entregado'  => ['Entregado',   'rp-kds-entregado',  '#6c757d'],
    'cancelado'  => ['Cancelado',   'rp-kds-cancelado',  '#e05c5c'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>KDS — <?= h($siteNombre) ?></title>
    <meta name="robots" content="noindex">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
    :root {
        --gold:    #c9a84c;
        --black:   #0a0a0a;
        --panel:   #111111;
        --card:    #1a1a1a;
        --border:  #2a2a2a;
        --white:   #f0f0f0;
        --muted:   #888;

        --nuevo:      #ffc107;
        --preparando: #fd7e14;
        --listo:      #2ea043;
        --entregado:  #555;
        --cancelado:  #e05c5c;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        background: var(--black);
        color: var(--white);
        font-family: 'Inter', system-ui, sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* ── Topbar ────────────────────────────────────────── */
    .kds-topbar {
        background: var(--panel);
        border-bottom: 2px solid var(--gold);
        padding: .75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        gap: 1rem;
    }

    .kds-brand {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        color: var(--gold);
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-shrink: 0;
    }

    .kds-title {
        font-size: .95rem;
        font-weight: 600;
        color: var(--white);
        letter-spacing: .05em;
    }

    .kds-clock {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--gold);
        font-variant-numeric: tabular-nums;
        flex-shrink: 0;
    }

    .kds-stats {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .kds-stat {
        text-align: center;
        min-width: 60px;
    }

    .kds-stat__num {
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1;
    }

    .kds-stat__lbl {
        font-size: .62rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--muted);
        margin-top: .1rem;
    }

    /* ── Filtros ─────────────────────────────────────── */
    .kds-filters {
        background: var(--panel);
        border-bottom: 1px solid var(--border);
        padding: .6rem 1.5rem;
        display: flex;
        gap: .5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .kds-filter-btn {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--muted);
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        padding: .35rem 1rem;
        border-radius: 100px;
        cursor: pointer;
        text-decoration: none;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }

    .kds-filter-btn:hover,
    .kds-filter-btn.active {
        border-color: var(--gold);
        color: var(--gold);
        background: rgba(201,168,76,.08);
    }

    .kds-auto-badge {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .75rem;
        color: var(--muted);
    }

    .kds-pulse {
        width: 8px; height: 8px;
        background: #2ea043;
        border-radius: 50%;
        animation: kdsPulse 2s infinite;
        flex-shrink: 0;
    }

    @keyframes kdsPulse {
        0%,100% { opacity:1; box-shadow: 0 0 0 0 rgba(46,160,67,.4); }
        50%      { opacity:.7; box-shadow: 0 0 0 5px rgba(46,160,67,0); }
    }

    /* ── Grid de pedidos ─────────────────────────────── */
    .kds-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        align-items: start;
    }

    /* ── Tarjeta de pedido ───────────────────────────── */
    .kds-card {
        background: var(--card);
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid var(--border);
        transition: border-color .3s, transform .2s;
        animation: kdsSlideIn .3s ease;
    }

    @keyframes kdsSlideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .kds-card.estado-nuevo      { border-color: var(--nuevo); }
    .kds-card.estado-preparando { border-color: var(--preparando); }
    .kds-card.estado-listo      { border-color: var(--listo); }
    .kds-card.estado-entregado  { border-color: var(--entregado); opacity: .6; }
    .kds-card.estado-cancelado  { border-color: var(--cancelado); opacity: .5; }

    /* Header de la tarjeta */
    .kds-card__header {
        padding: .85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .kds-card__header.estado-nuevo      { background: rgba(255,193,7,.12); }
    .kds-card__header.estado-preparando { background: rgba(253,126,20,.12); }
    .kds-card__header.estado-listo      { background: rgba(46,160,67,.12); }
    .kds-card__header.estado-entregado  { background: rgba(85,85,85,.12); }
    .kds-card__header.estado-cancelado  { background: rgba(224,92,92,.12); }

    .kds-card__codigo {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: .1em;
    }

    .kds-card__header.estado-nuevo      .kds-card__codigo { color: var(--nuevo); }
    .kds-card__header.estado-preparando .kds-card__codigo { color: var(--preparando); }
    .kds-card__header.estado-listo      .kds-card__codigo { color: var(--listo); }
    .kds-card__header.estado-entregado  .kds-card__codigo { color: var(--entregado); }
    .kds-card__header.estado-cancelado  .kds-card__codigo { color: var(--cancelado); }

    .kds-card__meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .2rem;
    }

    .kds-card__tipo {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    .kds-timer {
        font-size: .78rem;
        font-weight: 700;
        padding: .2rem .55rem;
        border-radius: 100px;
        font-variant-numeric: tabular-nums;
    }

    .kds-timer.ok      { background: rgba(46,160,67,.15);  color: #2ea043; }
    .kds-timer.warning { background: rgba(255,193,7,.15);  color: #ffc107; }
    .kds-timer.urgent  { background: rgba(224,92,92,.15);  color: #e05c5c;
                         animation: timerUrgent 1s infinite; }

    @keyframes timerUrgent {
        0%,100% { background: rgba(224,92,92,.15); }
        50%      { background: rgba(224,92,92,.3); }
    }

    /* Info del cliente */
    .kds-card__cliente {
        padding: .6rem 1rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .kds-card__nombre {
        font-weight: 600;
        font-size: .88rem;
        color: var(--white);
    }

    .kds-card__tel {
        font-size: .75rem;
        color: var(--muted);
    }

    .kds-card__mesa {
        font-size: .72rem;
        background: rgba(201,168,76,.1);
        border: 1px solid rgba(201,168,76,.2);
        color: var(--gold);
        padding: .2rem .6rem;
        border-radius: 4px;
        white-space: nowrap;
    }

    /* Items del pedido */
    .kds-card__items { padding: .75rem 1rem; }

    .kds-item {
        padding: .6rem 0;
        border-bottom: 1px solid var(--border);
    }

    .kds-item:last-child { border-bottom: none; }

    .kds-item__row {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .kds-item__qty {
        width: 28px; height: 28px;
        background: var(--gold);
        color: var(--black);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700;
        font-size: .85rem;
        flex-shrink: 0;
    }

    .kds-item__nombre {
        font-weight: 600;
        font-size: .92rem;
        color: var(--white);
        flex: 1;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    /* Extras */
    .kds-extras {
        margin-top: .35rem;
        padding-left: 36px;
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
    }

    .kds-extra-tag {
        background: rgba(201,168,76,.08);
        border: 1px solid rgba(201,168,76,.2);
        color: #c9a84c;
        font-size: .68rem;
        padding: .12rem .5rem;
        border-radius: 100px;
    }

    /* Notas */
    .kds-nota {
        margin-top: .35rem;
        padding-left: 36px;
        font-size: .75rem;
        color: #fd7e14;
        font-style: italic;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* Notas generales */
    .kds-card__notas {
        padding: .5rem 1rem;
        background: rgba(253,126,20,.06);
        border-top: 1px solid rgba(253,126,20,.2);
        font-size: .78rem;
        color: #fd7e14;
        display: flex;
        align-items: flex-start;
        gap: .4rem;
    }

    /* Acciones */
    .kds-card__actions {
        padding: .75rem 1rem;
        border-top: 1px solid var(--border);
        display: flex;
        gap: .5rem;
    }

    .kds-btn {
        flex: 1;
        padding: .6rem;
        border-radius: 6px;
        border: none;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
    }

    .kds-btn-preparar {
        background: rgba(253,126,20,.15);
        border: 1px solid var(--preparando);
        color: var(--preparando);
    }
    .kds-btn-preparar:hover { background: var(--preparando); color: #fff; }

    .kds-btn-listo {
        background: rgba(46,160,67,.15);
        border: 1px solid var(--listo);
        color: var(--listo);
    }
    .kds-btn-listo:hover { background: var(--listo); color: #fff; }

    .kds-btn-entregar {
        background: rgba(85,85,85,.15);
        border: 1px solid var(--entregado);
        color: #aaa;
    }
    .kds-btn-entregar:hover { background: var(--entregado); color: #fff; }

    .kds-btn-cancelar {
        background: rgba(224,92,92,.1);
        border: 1px solid var(--cancelado);
        color: var(--cancelado);
        flex: 0 0 auto;
        padding: .6rem .75rem;
    }
    .kds-btn-cancelar:hover { background: var(--cancelado); color: #fff; }

    /* ── Sin pedidos ─────────────────────────────────── */
    .kds-empty {
        text-align: center;
        padding: 5rem 2rem;
        grid-column: 1 / -1;
    }

    .kds-empty__icon {
        font-size: 5rem;
        opacity: .15;
        color: var(--gold);
        margin-bottom: 1rem;
    }

    .kds-empty__title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: var(--muted);
        margin-bottom: .5rem;
    }

    .kds-empty__sub {
        font-size: .85rem;
        color: #555;
    }

    /* ── Responsive ─────────────────────────────────── */
    @media (max-width: 768px) {
        .kds-grid { grid-template-columns: 1fr; padding: .75rem; gap: .75rem; }
        .kds-topbar { padding: .6rem 1rem; }
        .kds-stats { display: none; }
    }

    @media (max-width: 480px) {
        .kds-topbar .kds-title { display: none; }
    }
    </style>
</head>
<body>

<!-- ── Topbar ───────────────────────────────────────────── -->
<div class="kds-topbar">
    <div class="kds-brand">
        <i class="bi bi-fire"></i>
        <?= h($siteNombre) ?>
    </div>

    <span class="kds-title">🍽️ Kitchen Display System</span>

    <!-- Estadísticas rápidas -->
    <div class="kds-stats">
        <?php
        $cNuevo      = count(array_filter($pedidos, fn($p) => $p['estado'] === 'nuevo'));
        $cPreparando = count(array_filter($pedidos, fn($p) => $p['estado'] === 'preparando'));
        $cListo      = count(array_filter($pedidos, fn($p) => $p['estado'] === 'listo'));
        ?>
        <div class="kds-stat">
            <div class="kds-stat__num" style="color:var(--nuevo);"><?= $cNuevo ?></div>
            <div class="kds-stat__lbl">Nuevos</div>
        </div>
        <div class="kds-stat">
            <div class="kds-stat__num" style="color:var(--preparando);"><?= $cPreparando ?></div>
            <div class="kds-stat__lbl">En cocina</div>
        </div>
        <div class="kds-stat">
            <div class="kds-stat__num" style="color:var(--listo);"><?= $cListo ?></div>
            <div class="kds-stat__lbl">Listos</div>
        </div>
    </div>

    <div class="kds-clock" id="kdsClock">--:--:--</div>
</div>

<!-- ── Filtros ──────────────────────────────────────────── -->
<div class="kds-filters">
    <a href="?filtro=activos"
       class="kds-filter-btn <?= $filtroEstado==='activos' ? 'active' : '' ?>">
        <i class="bi bi-fire"></i> Activos
    </a>
    <a href="?filtro=nuevo"
       class="kds-filter-btn <?= $filtroEstado==='nuevo' ? 'active' : '' ?>">
        <i class="bi bi-bell" style="color:var(--nuevo);"></i> Nuevos
    </a>
    <a href="?filtro=preparando"
       class="kds-filter-btn <?= $filtroEstado==='preparando' ? 'active' : '' ?>">
        <i class="bi bi-fire" style="color:var(--preparando);"></i> En cocina
    </a>
    <a href="?filtro=listo"
       class="kds-filter-btn <?= $filtroEstado==='listo' ? 'active' : '' ?>">
        <i class="bi bi-check-circle" style="color:var(--listo);"></i> Listos
    </a>
    <a href="?filtro=todos"
       class="kds-filter-btn <?= $filtroEstado==='todos' ? 'active' : '' ?>">
        <i class="bi bi-calendar-day"></i> Todos hoy
    </a>

    <div class="kds-auto-badge">
        <div class="kds-pulse"></div>
        <span id="kdsRefreshLabel">Actualiza en <span id="kdsCountdown">30</span>s</span>
    </div>
</div>

<!-- ── Grid de pedidos ──────────────────────────────────── -->
<div class="kds-grid" id="kdsGrid">

<?php if (empty($pedidos)): ?>
    <div class="kds-empty">
        <div class="kds-empty__icon"><i class="bi bi-check2-all"></i></div>
        <p class="kds-empty__title">Todo al día</p>
        <p class="kds-empty__sub">No hay pedidos pendientes en este momento.</p>
    </div>

<?php else: foreach ($pedidos as $p):
    $estado  = $p['estado'];
    $cfg     = $estadoConfig[$estado] ?? ['', '', '#888'];
    [$eLbl, $eCls, $eColor] = $cfg;

    $tipo    = $tipoLabels[$p['tipo']] ?? [ucfirst($p['tipo']), 'bi-bag'];
    [$tipoLbl, $tipoIco] = $tipo;

    // Color del timer según tiempo
    $min = $p['minutos'];
    $timerClass = $min < 15 ? 'ok' : ($min < 30 ? 'warning' : 'urgent');
    $timerTxt   = $min < 60 ? $min . ' min' : round($min/60,1) . ' hrs';
?>
    <div class="kds-card estado-<?= $estado ?>" id="card_<?= $p['id'] ?>">

        <!-- Header -->
        <div class="kds-card__header estado-<?= $estado ?>">
            <div>
                <div class="kds-card__codigo">#<?= h($p['codigo']) ?></div>
                <div style="font-size:.7rem;color:var(--muted);margin-top:.1rem;">
                    <?= date('H:i', strtotime($p['created_at'])) ?> hrs
                </div>
            </div>
            <div class="kds-card__meta">
                <div class="kds-card__tipo">
                    <i class="bi <?= $tipoIco ?>"></i> <?= $tipoLbl ?>
                </div>
                <span class="kds-timer <?= $timerClass ?>">
                    ⏱ <?= $timerTxt ?>
                </span>
            </div>
        </div>

        <!-- Cliente -->
        <div class="kds-card__cliente">
            <div>
                <p class="kds-card__nombre"><?= h($p['nombre']) ?></p>
                <p class="kds-card__tel"><?= h($p['telefono']) ?></p>
            </div>
            <?php if ($p['mesa']): ?>
            <span class="kds-card__mesa">
                <i class="bi bi-door-open me-1"></i><?= h($p['mesa']) ?>
            </span>
            <?php endif; ?>
            <?php if ($p['direccion']): ?>
            <span class="kds-card__mesa" style="background:rgba(41,128,185,.1);border-color:rgba(41,128,185,.3);color:#5dade2;">
                <i class="bi bi-geo-alt me-1"></i><?= h(mb_substr($p['direccion'],0,20)) ?>…
            </span>
            <?php endif; ?>
        </div>

        <!-- Items -->
        <div class="kds-card__items">
            <?php foreach ($p['items'] as $item): ?>
            <div class="kds-item">
                <div class="kds-item__row">
                    <span class="kds-item__qty"><?= $item['cantidad'] ?></span>
                    <span class="kds-item__nombre"><?= h($item['nombre']) ?></span>
                </div>

                <!-- Extras/Opciones -->
                <?php if (!empty($item['opciones'])): ?>
                <div class="kds-extras">
                    <?php foreach ($item['opciones'] as $op): ?>
                    <span class="kds-extra-tag">+ <?= h($op['nombre']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Nota del item -->
                <?php if (!empty($item['notas'])): ?>
                <div class="kds-nota">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= h($item['notas']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Notas generales del pedido -->
        <?php if (!empty($p['notas'])): ?>
        <div class="kds-card__notas">
            <i class="bi bi-chat-dots flex-shrink-0 mt-1"></i>
            <?= h($p['notas']) ?>
        </div>
        <?php endif; ?>

        <!-- Acciones según estado -->
        <div class="kds-card__actions">
            <?php if ($estado === 'nuevo'): ?>
                <button class="kds-btn kds-btn-preparar"
                        onclick="cambiarEstado(<?= $p['id'] ?>, 'preparando')">
                    <i class="bi bi-fire"></i> En Cocina
                </button>
                <button class="kds-btn kds-btn-cancelar"
                        onclick="cambiarEstado(<?= $p['id'] ?>, 'cancelado')"
                        title="Cancelar pedido">
                    <i class="bi bi-x-lg"></i>
                </button>

            <?php elseif ($estado === 'preparando'): ?>
                <button class="kds-btn kds-btn-listo"
                        onclick="cambiarEstado(<?= $p['id'] ?>, 'listo')">
                    <i class="bi bi-check-circle"></i> ¡Listo!
                </button>
                <button class="kds-btn kds-btn-cancelar"
                        onclick="cambiarEstado(<?= $p['id'] ?>, 'cancelado')"
                        title="Cancelar">
                    <i class="bi bi-x-lg"></i>
                </button>

            <?php elseif ($estado === 'listo'): ?>
                <button class="kds-btn kds-btn-entregar"
                        onclick="cambiarEstado(<?= $p['id'] ?>, 'entregado')">
                    <i class="bi bi-bag-check"></i> Entregado
                </button>

            <?php elseif ($estado === 'entregado'): ?>
                <button class="kds-btn" style="background:rgba(85,85,85,.1);border:1px solid var(--border);color:var(--muted);cursor:default;" disabled>
                    <i class="bi bi-check2-all"></i> Completado
                </button>

            <?php elseif ($estado === 'cancelado'): ?>
                <button class="kds-btn" style="background:rgba(224,92,92,.1);border:1px solid var(--cancelado);color:var(--cancelado);cursor:default;" disabled>
                    <i class="bi bi-x-circle"></i> Cancelado
                </button>
            <?php endif; ?>
        </div>
    </div>

<?php endforeach; endif; ?>
</div>

<!-- ── Notificación de nuevo pedido ─────────────────────── -->
<div id="kdsNotif" style="
    display:none;position:fixed;top:80px;right:1.5rem;z-index:9999;
    background:#ffc107;color:#000;border-radius:10px;
    padding:1rem 1.5rem;font-weight:700;font-size:.95rem;
    box-shadow:0 8px 32px rgba(255,193,7,.4);
    animation:kdsNotifIn .3s ease;">
    🔔 ¡Nuevo pedido recibido!
</div>

<style>
@keyframes kdsNotifIn {
    from { opacity:0; transform:translateX(20px); }
    to   { opacity:1; transform:translateX(0); }
}
</style>

<script>
const APP_URL     = '<?= APP_URL ?>';
const FILTRO      = '<?= $filtroEstado ?>';
let   countdown   = 30;
let   lastCount   = <?= count($pedidos) ?>;
let   audioCtx    = null;

// ── Reloj ────────────────────────────────────────────────
function updateClock() {
    const now = new Date();
    document.getElementById('kdsClock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
}
setInterval(updateClock, 1000);
updateClock();

// ── Countdown de actualización ───────────────────────────
const countdownEl = document.getElementById('kdsCountdown');
setInterval(() => {
    countdown--;
    if (countdownEl) countdownEl.textContent = countdown;
    if (countdown <= 0) {
        countdown = 30;
        checkNuevosPedidos();
    }
}, 1000);

// ── Cambiar estado (AJAX) ─────────────────────────────────
async function cambiarEstado(id, estado) {
    const card = document.getElementById('card_' + id);
    if (card) {
        card.style.opacity = '.5';
        card.style.pointerEvents = 'none';
    }

    try {
        const form = new FormData();
        form.append('id', id);
        form.append('estado', estado);

        const res  = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        });
        const data = await res.json();

        if (data.ok) {
            // Recargar la página para reflejar el cambio
            location.reload();
        } else {
            if (card) { card.style.opacity = '1'; card.style.pointerEvents = ''; }
        }
    } catch(e) {
        if (card) { card.style.opacity = '1'; card.style.pointerEvents = ''; }
    }
}

// ── Verificar nuevos pedidos ──────────────────────────────
async function checkNuevosPedidos() {
    try {
        const res  = await fetch(APP_URL + '/admin/api/kds_count.php', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.count > lastCount) {
            // Hay pedidos nuevos — mostrar notificación y recargar
            mostrarNotificacion();
            setTimeout(() => location.reload(), 1500);
        } else {
            lastCount = data.count;
        }
    } catch(e) {
        // Si falla, recargar de todas formas
        location.reload();
    }
}

// ── Sonido de notificación ────────────────────────────────
function beep() {
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = 880;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
        osc.start(audioCtx.currentTime);
        osc.stop(audioCtx.currentTime + 0.5);
    } catch(e) {}
}

// ── Notificación visual ────────────────────────────────────
function mostrarNotificacion() {
    beep();
    const notif = document.getElementById('kdsNotif');
    notif.style.display = 'block';
    setTimeout(() => notif.style.display = 'none', 3000);
}

// Inicializar AudioContext con interacción del usuario
document.addEventListener('click', () => {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
}, { once: true });
</script>

</body>
</html>
