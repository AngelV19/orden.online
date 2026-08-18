<?php
/**
 * RESTAURANT PREMIUM — Dashboard Admin (mejorado)
 * Archivo: admin/dashboard.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Dashboard';
$db = db();

// ── Estadísticas principales ──────────────────────────────
$stats = [
    'reservas_hoy'        => $db->query("SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE()")->fetchColumn(),
    'reservas_pendientes' => $db->query("SELECT COUNT(*) FROM reservas WHERE estado='pendiente'")->fetchColumn(),
    'pedidos_activos'     => $db->query("SELECT COUNT(*) FROM pedidos WHERE estado IN('nuevo','preparando','listo')")->fetchColumn(),
    'pedidos_hoy'         => $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'platillos'           => $db->query("SELECT COUNT(*) FROM platillos WHERE disponible=1")->fetchColumn(),
    'ingresos_hoy'        => $db->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE DATE(created_at)=CURDATE() AND estado!='cancelado'")->fetchColumn(),
    'ingresos_mes'        => $db->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND estado!='cancelado'")->fetchColumn(),
    'reservas_mes'        => $db->query("SELECT COUNT(*) FROM reservas WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")->fetchColumn(),
];

// ── Reservas de hoy ───────────────────────────────────────
$reservasHoy = $db->query(
    "SELECT * FROM reservas WHERE fecha = CURDATE() ORDER BY hora ASC"
)->fetchAll();

// ── Pedidos activos ───────────────────────────────────────
$pedidosActivos = $db->query(
    "SELECT * FROM pedidos WHERE estado IN('nuevo','preparando','listo') ORDER BY created_at DESC"
)->fetchAll();

// ── Reservas por mes (últimos 6 meses) ────────────────────
$reservasPorMes = $db->query(
    "SELECT DATE_FORMAT(fecha,'%b') AS mes,
            DATE_FORMAT(fecha,'%Y-%m') AS mes_key,
            COUNT(*) AS total
     FROM reservas
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha,'%Y-%m')
     ORDER BY mes_key ASC"
)->fetchAll();

// ── Ingresos por mes (últimos 6 meses) ────────────────────
$ingresosPorMes = $db->query(
    "SELECT DATE_FORMAT(created_at,'%b') AS mes,
            DATE_FORMAT(created_at,'%Y-%m') AS mes_key,
            COALESCE(SUM(total),0) AS total
     FROM pedidos
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
       AND estado != 'cancelado'
     GROUP BY DATE_FORMAT(created_at,'%Y-%m')
     ORDER BY mes_key ASC"
)->fetchAll();

// ── Platillos más pedidos ─────────────────────────────────
$platillosMasPedidos = $db->query(
    "SELECT pi.nombre, SUM(pi.cantidad) AS total_pedido, SUM(pi.subtotal) AS ingresos
     FROM pedido_items pi
     JOIN pedidos p ON p.id = pi.pedido_id
     WHERE p.estado != 'cancelado'
     GROUP BY pi.nombre
     ORDER BY total_pedido DESC
     LIMIT 6"
)->fetchAll();

// ── Horas pico (pedidos + reservas) ──────────────────────
$horasPico = $db->query(
    "SELECT hora_num, SUM(total) AS total FROM (
        SELECT HOUR(hora) AS hora_num, COUNT(*) AS total FROM reservas GROUP BY HOUR(hora)
        UNION ALL
        SELECT HOUR(created_at) AS hora_num, COUNT(*) AS total FROM pedidos GROUP BY HOUR(created_at)
     ) t GROUP BY hora_num ORDER BY hora_num ASC"
)->fetchAll();

// Llenar horas faltantes con 0
$horasPicoFull = array_fill(0, 24, 0);
foreach ($horasPico as $h) $horasPicoFull[(int)$h['hora_num']] = (int)$h['total'];

// Solo horas de servicio (11-23)
$horasLabels = [];
$horasData   = [];
for ($i = 11; $i <= 23; $i++) {
    $horasLabels[] = $i . ':00';
    $horasData[]   = $horasPicoFull[$i];
}

// ── Distribución de estados de reservas ──────────────────
$estadosReservas = $db->query(
    "SELECT estado, COUNT(*) AS total FROM reservas GROUP BY estado"
)->fetchAll();

$estadosMap = ['pendiente' => 0, 'confirmada' => 0, 'cancelada' => 0];
foreach ($estadosReservas as $e) $estadosMap[$e['estado']] = (int)$e['total'];

require_once __DIR__ . '/includes/sidebar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>window.ADMIN_URL = '<?= APP_URL ?>';</script>

<style>
/* Dashboard styles */
.rp-dash-grid { display: grid; gap: 1.25rem; }
.rp-dash-grid--4 { grid-template-columns: repeat(4, 1fr); }
.rp-dash-grid--3 { grid-template-columns: repeat(3, 1fr); }
.rp-dash-grid--2 { grid-template-columns: repeat(2, 1fr); }

.rp-chart-card {
    background: var(--black-card);
    border: 1px solid var(--black-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
}

.rp-chart-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.rp-chart-card__title {
    font-family: var(--font-display);
    font-size: .95rem;
    font-weight: 600;
    color: var(--white);
}

.rp-chart-card__sub {
    font-size: .72rem;
    color: var(--white-dim);
    margin-top: .15rem;
}

.rp-live-dot {
    width: 8px; height: 8px;
    background: #2ea043;
    border-radius: 50%;
    display: inline-block;
    margin-right: .4rem;
    animation: livePulse 1.5s infinite;
}

@keyframes livePulse {
    0%,100% { opacity:1; box-shadow: 0 0 0 0 rgba(46,160,67,.4); }
    50%      { opacity:.7; box-shadow: 0 0 0 5px rgba(46,160,67,0); }
}

.rp-ranking-bar {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .55rem 0;
    border-bottom: 1px solid var(--black-border);
}
.rp-ranking-bar:last-child { border-bottom: none; }
.rp-ranking-bar__num {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: rgba(201,168,76,.12);
    border: 1px solid rgba(201,168,76,.2);
    color: var(--gold);
    font-size: .7rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.rp-ranking-bar__name  { font-size: .83rem; color: var(--white); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rp-ranking-bar__track { flex: 2; height: 6px; background: var(--black-border); border-radius: 3px; overflow: hidden; }
.rp-ranking-bar__fill  { height: 100%; background: var(--gold); border-radius: 3px; transition: width .8s ease; }
.rp-ranking-bar__val   { font-size: .78rem; color: var(--gold); font-weight: 600; min-width: 28px; text-align: right; }

.rp-agenda-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .6rem 0;
    border-bottom: 1px solid var(--black-border);
}
.rp-agenda-item:last-child { border-bottom: none; }
.rp-agenda-time {
    background: rgba(201,168,76,.1);
    border: 1px solid rgba(201,168,76,.2);
    color: var(--gold);
    font-size: .72rem;
    font-weight: 600;
    padding: .25rem .6rem;
    border-radius: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.rp-pedido-card {
    background: var(--black);
    border: 1px solid var(--black-border);
    border-radius: 8px;
    padding: .85rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    transition: border-color .2s;
}
.rp-pedido-card:hover { border-color: rgba(201,168,76,.3); }
.rp-pedido-card__code { font-family: var(--font-display); font-size: .95rem; color: var(--gold); font-weight: 700; letter-spacing: .08em; }
.rp-pedido-card__name { font-size: .82rem; color: var(--white); }
.rp-pedido-card__time { font-size: .72rem; color: var(--white-dim); }

@media (max-width:1199px) { .rp-dash-grid--4 { grid-template-columns: repeat(2,1fr); } }
@media (max-width:767px)  { .rp-dash-grid--4, .rp-dash-grid--3, .rp-dash-grid--2 { grid-template-columns: 1fr; } }
</style>

<!-- ══ FILA 1: Stats KPI ════════════════════════════════════ -->
<div class="rp-dash-grid rp-dash-grid--4 mb-4">

    <div class="rp-stat-card">
        <div class="rp-stat-card__icon"><i class="bi bi-calendar-check"></i></div>
        <div>
            <div class="rp-stat-card__value"><?= $stats['reservas_hoy'] ?></div>
            <div class="rp-stat-card__label">Reservas Hoy</div>
            <div class="text-muted" style="font-size:.7rem;margin-top:.2rem;"><?= $stats['reservas_mes'] ?> este mes</div>
        </div>
    </div>

    <div class="rp-stat-card">
        <div class="rp-stat-card__icon" style="background:rgba(46,160,67,.1);border-color:rgba(46,160,67,.2);">
            <i class="bi bi-bag-check" style="color:#2ea043;"></i>
        </div>
        <div>
            <div class="rp-stat-card__value" style="color:#2ea043;"><?= $stats['pedidos_activos'] ?></div>
            <div class="rp-stat-card__label">Pedidos Activos</div>
            <div class="text-muted" style="font-size:.7rem;margin-top:.2rem;"><?= $stats['pedidos_hoy'] ?> hoy</div>
        </div>
    </div>

    <div class="rp-stat-card">
        <div class="rp-stat-card__icon"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div class="rp-stat-card__value" style="font-size:1.4rem;">$<?= number_format((float)$stats['ingresos_hoy'],0) ?></div>
            <div class="rp-stat-card__label">Ingresos Hoy</div>
            <div class="text-muted" style="font-size:.7rem;margin-top:.2rem;">$<?= number_format((float)$stats['ingresos_mes'],0) ?> este mes</div>
        </div>
    </div>

    <div class="rp-stat-card">
        <div class="rp-stat-card__icon" style="background:rgba(255,193,7,.1);border-color:rgba(255,193,7,.2);">
            <i class="bi bi-hourglass-split" style="color:#ffc107;"></i>
        </div>
        <div>
            <div class="rp-stat-card__value" style="color:#ffc107;"><?= $stats['reservas_pendientes'] ?></div>
            <div class="rp-stat-card__label">Reservas Pendientes</div>
            <div class="text-muted" style="font-size:.7rem;margin-top:.2rem;"><?= $stats['platillos'] ?> platillos activos</div>
        </div>
    </div>
</div>

<!-- ══ FILA 2: Gráficas principales ════════════════════════ -->
<div class="rp-dash-grid rp-dash-grid--2 mb-4">

    <!-- Ingresos por mes -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">Ingresos por Mes</p>
                <p class="rp-chart-card__sub">Últimos 6 meses · pedidos completados</p>
            </div>
            <i class="bi bi-graph-up text-gold fs-5"></i>
        </div>
        <canvas id="chartIngresos" height="200"></canvas>
    </div>

    <!-- Reservas por mes -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">Reservas por Mes</p>
                <p class="rp-chart-card__sub">Últimos 6 meses</p>
            </div>
            <i class="bi bi-calendar3 text-gold fs-5"></i>
        </div>
        <canvas id="chartReservas" height="200"></canvas>
    </div>
</div>

<!-- ══ FILA 3: Horas pico + Distribución ══════════════════ -->
<div class="rp-dash-grid rp-dash-grid--2 mb-4">

    <!-- Horas pico -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">Horas Pico</p>
                <p class="rp-chart-card__sub">Actividad acumulada por hora del día</p>
            </div>
            <i class="bi bi-clock-history text-gold fs-5"></i>
        </div>
        <canvas id="chartHoras" height="200"></canvas>
    </div>

    <!-- Distribución estados + Donut -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">Estado de Reservas</p>
                <p class="rp-chart-card__sub">Distribución total histórica</p>
            </div>
            <i class="bi bi-pie-chart text-gold fs-5"></i>
        </div>
        <div class="d-flex align-items-center gap-4">
            <canvas id="chartDonut" style="max-width:160px;max-height:160px;"></canvas>
            <div class="flex-grow-1">
                <?php
                $totalRes = array_sum($estadosMap);
                $colores  = ['pendiente'=>['#ffc107','Pendiente'], 'confirmada'=>['#2ea043','Confirmada'], 'cancelada'=>['#e05c5c','Cancelada']];
                foreach ($colores as $key => [$color, $label]):
                    $pct = $totalRes > 0 ? round($estadosMap[$key] / $totalRes * 100) : 0;
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span style="color:<?= $color ?>;">● <?= $label ?></span>
                        <span class="text-muted"><?= $estadosMap[$key] ?> (<?= $pct ?>%)</span>
                    </div>
                    <div style="height:5px;background:var(--black-border);border-radius:3px;">
                        <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:3px;transition:width .8s;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <p class="text-muted small mt-2 mb-0">Total: <?= $totalRes ?> reservas</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ FILA 4: Ranking + Agenda + Pedidos activos ══════════ -->
<div class="rp-dash-grid rp-dash-grid--3 mb-4">

    <!-- Platillos más pedidos -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">Top Platillos</p>
                <p class="rp-chart-card__sub">Más ordenados en total</p>
            </div>
            <i class="bi bi-trophy text-gold fs-5"></i>
        </div>
        <?php if ($platillosMasPedidos):
            $maxVal = max(array_column($platillosMasPedidos, 'total_pedido'));
            foreach ($platillosMasPedidos as $i => $p):
                $pct = $maxVal > 0 ? round($p['total_pedido'] / $maxVal * 100) : 0;
        ?>
        <div class="rp-ranking-bar">
            <span class="rp-ranking-bar__num"><?= $i+1 ?></span>
            <span class="rp-ranking-bar__name" title="<?= h($p['nombre']) ?>"><?= h($p['nombre']) ?></span>
            <div class="rp-ranking-bar__track">
                <div class="rp-ranking-bar__fill" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="rp-ranking-bar__val"><?= $p['total_pedido'] ?></span>
        </div>
        <?php endforeach; else: ?>
        <div class="text-center py-4">
            <i class="bi bi-bag-x text-gold" style="font-size:2rem;opacity:.3"></i>
            <p class="text-muted small mt-2">Sin pedidos aún</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Agenda del día -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">Agenda de Hoy</p>
                <p class="rp-chart-card__sub"><?= date('l d \d\e F') ?></p>
            </div>
            <a href="<?= APP_URL ?>/admin/reservas.php" class="btn rp-btn-outline btn-sm" style="font-size:.72rem;">Ver todas</a>
        </div>
        <?php if ($reservasHoy): ?>
        <div style="max-height:280px;overflow-y:auto;">
            <?php foreach ($reservasHoy as $r): ?>
            <div class="rp-agenda-item">
                <span class="rp-agenda-time"><?= date('H:i', strtotime($r['hora'])) ?></span>
                <div class="flex-grow-1 min-width-0">
                    <p class="fw-semibold small mb-0 text-truncate"><?= h($r['nombre']) ?></p>
                    <p class="text-muted mb-0" style="font-size:.72rem;">
                        <i class="bi bi-people me-1"></i><?= $r['personas'] ?> pax
                        <?php if ($r['mensaje']): ?>
                        · <i class="bi bi-chat-dots"></i>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="rp-badge rp-badge--<?= $r['estado'] ?>" style="font-size:.65rem;"><?= ucfirst($r['estado']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="bi bi-calendar-x text-gold" style="font-size:2rem;opacity:.3"></i>
            <p class="text-muted small mt-2">Sin reservas hoy</p>
            <a href="<?= APP_URL ?>/admin/reservas.php" class="btn rp-btn-outline btn-sm mt-1">Ver próximas</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pedidos activos -->
    <div class="rp-chart-card">
        <div class="rp-chart-card__header">
            <div>
                <p class="rp-chart-card__title">
                    <span class="rp-live-dot"></span>Pedidos Activos
                </p>
                <p class="rp-chart-card__sub">En proceso ahora mismo</p>
            </div>
            <a href="<?= APP_URL ?>/admin/pedidos.php" class="btn rp-btn-outline btn-sm" style="font-size:.72rem;">Ver todos</a>
        </div>
        <?php if ($pedidosActivos):
            $estadoPedBadge = ['nuevo'=>'pendiente','preparando'=>'pendiente','listo'=>'confirmada'];
            $estadoPedLabel = ['nuevo'=>'Nuevo','preparando'=>'Preparando','listo'=>'Listo'];
        ?>
        <div class="d-flex flex-column gap-2" style="max-height:280px;overflow-y:auto;">
            <?php foreach ($pedidosActivos as $p): ?>
            <a href="<?= APP_URL ?>/admin/pedidos.php?ver=<?= $p['id'] ?>" class="rp-pedido-card text-decoration-none">
                <div class="flex-grow-1">
                    <p class="rp-pedido-card__code mb-0"><?= h($p['codigo']) ?></p>
                    <p class="rp-pedido-card__name mb-0"><?= h($p['nombre']) ?></p>
                    <p class="rp-pedido-card__time mb-0"><?= date('H:i', strtotime($p['created_at'])) ?> · $<?= number_format((float)$p['total'],0) ?></p>
                </div>
                <span class="rp-badge rp-badge--<?= $estadoPedBadge[$p['estado']] ?? 'pendiente' ?>">
                    <?= $estadoPedLabel[$p['estado']] ?? $p['estado'] ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="bi bi-bag-check text-gold" style="font-size:2rem;opacity:.3"></i>
            <p class="text-muted small mt-2">Sin pedidos activos</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ Acciones rápidas ════════════════════════════════════ -->
<div class="rp-chart-card">
    <p class="rp-chart-card__title mb-3">Acciones Rápidas</p>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= APP_URL ?>/admin/platillos.php?action=nuevo" class="rp-btn-gold btn btn-sm px-4">
            <i class="bi bi-plus-circle me-2"></i>Nuevo Platillo
        </a>
        <a href="<?= APP_URL ?>/admin/reservas.php" class="rp-btn-outline btn btn-sm px-4">
            <i class="bi bi-calendar3 me-2"></i>Reservaciones
        </a>
        <a href="<?= APP_URL ?>/admin/pedidos.php" class="rp-btn-outline btn btn-sm px-4">
            <i class="bi bi-bag me-2"></i>Pedidos
        </a>
        <a href="<?= APP_URL ?>/admin/galeria.php" class="rp-btn-outline btn btn-sm px-4">
            <i class="bi bi-images me-2"></i>Galería
        </a>
        <a href="<?= APP_URL ?>/admin/reporte_reservas.php" class="rp-btn-outline btn btn-sm px-4">
            <i class="bi bi-file-earmark-pdf me-2"></i>Reporte PDF
        </a>
        <a href="<?= APP_URL ?>/admin/configuracion.php" class="rp-btn-outline btn btn-sm px-4">
            <i class="bi bi-sliders me-2"></i>Configuración
        </a>
        <a href="<?= APP_URL ?>/" target="_blank" class="rp-btn-outline btn btn-sm px-4">
            <i class="bi bi-box-arrow-up-right me-2"></i>Ver Sitio
        </a>
    </div>
</div>

<!-- ══ Chart.js ════════════════════════════════════════════ -->
<script>
const goldColor  = '#c9a84c';
const goldFade   = 'rgba(201,168,76,.15)';
const gridColor  = 'rgba(42,42,42,.8)';
const tickColor  = '#b0a99a';

const chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: tickColor, font:{ size:10 } }, grid: { color: gridColor } },
        y: { ticks: { color: tickColor, font:{ size:10 } }, grid: { color: gridColor }, beginAtZero: true }
    }
};

// ── Ingresos por mes ─────────────────────────────────────
new Chart(document.getElementById('chartIngresos'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($ingresosPorMes, 'mes')) ?>,
        datasets: [{
            data:  <?= json_encode(array_map(fn($r) => round((float)$r['total'],2), $ingresosPorMes)) ?>,
            borderColor: goldColor,
            backgroundColor: goldFade,
            borderWidth: 2,
            pointBackgroundColor: goldColor,
            pointRadius: 4,
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        ...chartDefaults,
        plugins: { legend: { display: false }, tooltip: {
            callbacks: { label: ctx => ' $' + ctx.raw.toLocaleString('es-MX') }
        }},
        scales: {
            ...chartDefaults.scales,
            y: { ...chartDefaults.scales.y, ticks: { ...chartDefaults.scales.y.ticks,
                callback: v => '$' + (v/1000).toFixed(0) + 'k' } }
        }
    }
});

// ── Reservas por mes ─────────────────────────────────────
new Chart(document.getElementById('chartReservas'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($reservasPorMes, 'mes')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($reservasPorMes, 'total')) ?>,
            backgroundColor: goldFade,
            borderColor: goldColor,
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: chartDefaults
});

// ── Horas pico ───────────────────────────────────────────
const horasData  = <?= json_encode($horasData) ?>;
const maxHora    = Math.max(...horasData, 1);
new Chart(document.getElementById('chartHoras'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($horasLabels) ?>,
        datasets: [{
            data: horasData,
            backgroundColor: horasData.map(v =>
                v === maxHora ? goldColor : goldFade
            ),
            borderColor: goldColor,
            borderWidth: 1,
            borderRadius: 3,
        }]
    },
    options: {
        ...chartDefaults,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.raw + ' actividades' } }
        }
    }
});

// ── Donut estados ────────────────────────────────────────
new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Pendiente', 'Confirmada', 'Cancelada'],
        datasets: [{
            data: [
                <?= $estadosMap['pendiente'] ?>,
                <?= $estadosMap['confirmada'] ?>,
                <?= $estadosMap['cancelada'] ?>
            ],
            backgroundColor: ['#ffc107','#2ea043','#e05c5c'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.raw } }
        }
    }
});

// Auto-refresh cada 60 segundos si hay pedidos activos
<?php if ($stats['pedidos_activos'] > 0): ?>
setTimeout(() => location.reload(), 60000);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
