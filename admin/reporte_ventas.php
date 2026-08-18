<?php
/**
 * RESTAURANT PREMIUM — Reporte de Ventas Profesional
 * Archivo: admin/reporte_ventas.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/settings.php';
requireAdmin();

$adminTitle = 'Reporte de Ventas';
$db = db();

// ── Filtros ───────────────────────────────────────────────
$fechaDesde  = $_GET['desde']      ?? date('Y-m-01');
$fechaHasta  = $_GET['hasta']      ?? date('Y-m-d');
$agrupacion  = $_GET['agrupar']    ?? 'dia';
$tipoFilter  = $_GET['tipo']       ?? '';
$exportar    = isset($_GET['pdf']);

$agrupar_sql = match($agrupacion) {
    'semana' => "DATE_FORMAT(created_at, '%x-W%v')",
    'mes'    => "DATE_FORMAT(created_at, '%Y-%m')",
    default  => "DATE(created_at)",
};

$agrupar_label = match($agrupacion) {
    'semana' => "DATE_FORMAT(created_at, 'Sem %v · %Y')",
    'mes'    => "DATE_FORMAT(created_at, '%b %Y')",
    default  => "DATE_FORMAT(created_at, '%d/%m/%Y')",
};

// ── Parámetros base ───────────────────────────────────────
$whereBase  = "WHERE p.created_at BETWEEN :desde AND :hasta_full AND p.estado != 'cancelado'";
$params     = [':desde' => $fechaDesde . ' 00:00:00', ':hasta_full' => $fechaHasta . ' 23:59:59'];

if ($tipoFilter && in_array($tipoFilter, ['salon','llevar','domicilio'])) {
    $whereBase .= ' AND p.tipo = :tipo';
    $params[':tipo'] = $tipoFilter;
}

// ── KPIs principales ──────────────────────────────────────
$kpiStmt = $db->prepare(
    "SELECT
        COUNT(*)                    AS total_pedidos,
        COALESCE(SUM(p.total), 0)   AS ingresos_total,
        COALESCE(AVG(p.total), 0)   AS ticket_promedio,
        COALESCE(MAX(p.total), 0)   AS ticket_maximo,
        COALESCE(MIN(p.total), 0)   AS ticket_minimo,
        SUM(CASE WHEN p.tipo='salon'     THEN 1 ELSE 0 END) AS salon,
        SUM(CASE WHEN p.tipo='llevar'    THEN 1 ELSE 0 END) AS llevar,
        SUM(CASE WHEN p.tipo='domicilio' THEN 1 ELSE 0 END) AS domicilio
     FROM pedidos p $whereBase"
);
$kpiStmt->execute($params);
$kpi = $kpiStmt->fetch();

// ── Ventas agrupadas (para gráfica) ───────────────────────
$ventasStmt = $db->prepare(
    "SELECT
        {$agrupar_label}            AS periodo_label,
        {$agrupar_sql}              AS periodo_key,
        COUNT(*)                    AS pedidos,
        COALESCE(SUM(p.total), 0)   AS ingresos
     FROM pedidos p $whereBase
     GROUP BY periodo_key
     ORDER BY periodo_key ASC"
);
$ventasStmt->execute($params);
$ventasAgrupadas = $ventasStmt->fetchAll();

// ── Top platillos vendidos ────────────────────────────────
$topStmt = $db->prepare(
    "SELECT
        pi.nombre,
        SUM(pi.cantidad)  AS unidades,
        SUM(pi.subtotal)  AS ingresos,
        AVG(pi.precio)    AS precio_prom
     FROM pedido_items pi
     JOIN pedidos p ON p.id = pi.pedido_id
     $whereBase
     GROUP BY pi.nombre
     ORDER BY ingresos DESC
     LIMIT 10"
);
$topStmt->execute($params);
$topPlatillos = $topStmt->fetchAll();

// ── Desglose por tipo de pedido ───────────────────────────
$tipoStmt = $db->prepare(
    "SELECT tipo,
        COUNT(*)                  AS pedidos,
        COALESCE(SUM(total), 0)   AS ingresos
     FROM pedidos p $whereBase
     GROUP BY tipo"
);
$tipoStmt->execute($params);
$porTipo = $tipoStmt->fetchAll();

// ── Pedidos del período ───────────────────────────────────
$pedidosStmt = $db->prepare(
    "SELECT p.*,
        (SELECT COUNT(*) FROM pedido_items WHERE pedido_id = p.id) AS n_items
     FROM pedidos p $whereBase
     ORDER BY p.created_at DESC"
);
$pedidosStmt->execute($params);
$pedidos = $pedidosStmt->fetchAll();

// ── Comparativa con período anterior ─────────────────────
$dias        = max(1, (int)((strtotime($fechaHasta) - strtotime($fechaDesde)) / 86400));
$desdeAntes  = date('Y-m-d', strtotime($fechaDesde) - ($dias + 1) * 86400);
$hastaAntes  = date('Y-m-d', strtotime($fechaDesde) - 86400);

$anteriorStmt = $db->prepare(
    "SELECT COALESCE(SUM(total),0) AS ingresos, COUNT(*) AS pedidos
     FROM pedidos
     WHERE created_at BETWEEN :d AND :h AND estado != 'cancelado'"
);
$anteriorStmt->execute([':d' => $desdeAntes . ' 00:00:00', ':h' => $hastaAntes . ' 23:59:59']);
$anterior = $anteriorStmt->fetch();

$cambioIngresos = $anterior['ingresos'] > 0
    ? round((($kpi['ingresos_total'] - $anterior['ingresos']) / $anterior['ingresos']) * 100, 1)
    : 0;
$cambioPedidos  = $anterior['pedidos'] > 0
    ? round((($kpi['total_pedidos'] - $anterior['pedidos']) / $anterior['pedidos']) * 100, 1)
    : 0;

// ── EXPORTAR PDF ──────────────────────────────────────────
if ($exportar) {
    $mpdfPath = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($mpdfPath)) {
        die('<div style="font-family:sans-serif;padding:2rem;background:#111;color:#fff;">
            <h2 style="color:#c9a84c;">⚠️ mPDF no está instalado</h2>
            <p>Ejecuta en CMD dentro de la carpeta del proyecto:</p>
            <code style="background:#222;padding:.5rem 1rem;display:block;margin:1rem 0;">composer require mpdf/mpdf</code>
            <a href="javascript:history.back()" style="color:#c9a84c;">← Volver</a>
        </div>');
    }

    require_once $mpdfPath;
    $siteNombre = cfg('site_nombre', APP_NAME);

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8', 'format' => 'A4',
        'margin_top' => 0, 'margin_bottom' => 15,
        'margin_left' => 0, 'margin_right' => 0,
    ]);
    $mpdf->SetTitle("Reporte de Ventas — {$siteNombre}");

    ob_start();
    ?>
    <style>
        * { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; }
        body { margin: 0; padding: 0; color: #1a1a1a; }

        .header { background: #0d0d0d; padding: 24px 24px 18px; margin-bottom: 0; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .header h1 { margin: 0 0 4px; font-size: 20pt; color: #c9a84c; letter-spacing: 1px; }
        .header .sub { font-size: 9pt; color: #aaa; margin: 0; }
        .header .badge-periodo { background: rgba(201,168,76,.15); border: 1px solid rgba(201,168,76,.3);
            color: #c9a84c; padding: 4px 10px; border-radius: 4px; font-size: 8pt; }

        .content { padding: 18px 24px; }
        .section-title { font-size: 10pt; font-weight: bold; color: #0d0d0d;
            border-bottom: 2px solid #c9a84c; padding-bottom: 4px; margin: 16px 0 10px; }

        /* KPI grid */
        .kpi-grid { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 4px; }
        .kpi-box { background: #f8f7f4; border: 1px solid #e5dece; border-radius: 6px;
            padding: 10px 12px; text-align: center; }
        .kpi-box .val { font-size: 16pt; font-weight: bold; color: #c9a84c; line-height: 1; }
        .kpi-box .lbl { font-size: 7pt; color: #777; text-transform: uppercase;
            letter-spacing: .5px; margin-top: 3px; }
        .kpi-box .cambio { font-size: 7.5pt; margin-top: 2px; }
        .up   { color: #2e7d32; }
        .down { color: #c62828; }

        /* Tabla ventas */
        table.data { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        table.data thead tr { background: #0d0d0d; color: #c9a84c; }
        table.data thead th { padding: 7px 8px; text-align: left; font-size: 7.5pt;
            letter-spacing: .4px; text-transform: uppercase; }
        table.data tbody tr:nth-child(even) { background: #f8f7f4; }
        table.data tbody tr:nth-child(odd)  { background: #fff; }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #ece6d8; }
        table.data tfoot td { background: #0d0d0d; color: #c9a84c; font-weight: bold;
            padding: 7px 8px; font-size: 8.5pt; }

        /* Top platillos */
        .bar-track { width: 100%; height: 8px; background: #ece6d8; border-radius: 4px; }
        .bar-fill  { height: 8px; background: #c9a84c; border-radius: 4px; }

        /* Tipo breakdown */
        .tipo-grid { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .tipo-box { background: #f8f7f4; border: 1px solid #e5dece; border-radius: 6px;
            padding: 8px 12px; text-align: center; }
        .tipo-box .tipo-val { font-size: 13pt; font-weight: bold; color: #c9a84c; }
        .tipo-box .tipo-lbl { font-size: 7pt; color: #777; text-transform: uppercase; }
        .tipo-box .tipo-ing { font-size: 8pt; color: #444; margin-top: 1px; }

        .footer { text-align: center; font-size: 7.5pt; color: #999; border-top: 1px solid #e5dece;
            padding: 8px 24px; margin-top: 12px; }
        .tag  { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 7pt; font-weight: bold; }
        .tag-salon     { background: #e8f5e9; color: #2e7d32; }
        .tag-llevar    { background: #fff8e1; color: #b8860b; }
        .tag-domicilio { background: #e3f2fd; color: #1565c0; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div>
                <h1>&#10022; <?= h($siteNombre) ?></h1>
                <p class="sub">Reporte de Ventas &mdash; Generado el <?= date('d/m/Y') ?> a las <?= date('H:i') ?> hrs</p>
                <p class="sub" style="margin-top:3px;">Generado por: <?= h($_SESSION['admin_nombre']) ?></p>
            </div>
            <div style="text-align:right;">
                <div class="badge-periodo">
                    <?= date('d/m/Y', strtotime($fechaDesde)) ?> &mdash; <?= date('d/m/Y', strtotime($fechaHasta)) ?>
                </div>
                <p class="sub" style="margin-top:6px;">Agrupación: <?= ucfirst($agrupacion) ?></p>
            </div>
        </div>
    </div>

    <div class="content">

    <!-- KPIs -->
    <p class="section-title">Resumen Ejecutivo</p>
    <table class="kpi-grid">
        <tr>
            <td width="20%">
                <div class="kpi-box">
                    <div class="val">$<?= number_format((float)$kpi['ingresos_total'], 0) ?></div>
                    <div class="lbl">Ingresos Totales</div>
                    <?php if ($cambioIngresos != 0): ?>
                    <div class="cambio <?= $cambioIngresos >= 0 ? 'up' : 'down' ?>">
                        <?= $cambioIngresos >= 0 ? '▲' : '▼' ?> <?= abs($cambioIngresos) ?>% vs período ant.
                    </div>
                    <?php endif; ?>
                </div>
            </td>
            <td width="20%">
                <div class="kpi-box">
                    <div class="val"><?= $kpi['total_pedidos'] ?></div>
                    <div class="lbl">Total Pedidos</div>
                    <?php if ($cambioPedidos != 0): ?>
                    <div class="cambio <?= $cambioPedidos >= 0 ? 'up' : 'down' ?>">
                        <?= $cambioPedidos >= 0 ? '▲' : '▼' ?> <?= abs($cambioPedidos) ?>% vs período ant.
                    </div>
                    <?php endif; ?>
                </div>
            </td>
            <td width="20%">
                <div class="kpi-box">
                    <div class="val">$<?= number_format((float)$kpi['ticket_promedio'], 0) ?></div>
                    <div class="lbl">Ticket Promedio</div>
                </div>
            </td>
            <td width="20%">
                <div class="kpi-box">
                    <div class="val">$<?= number_format((float)$kpi['ticket_maximo'], 0) ?></div>
                    <div class="lbl">Ticket Máximo</div>
                </div>
            </td>
            <td width="20%">
                <div class="kpi-box">
                    <div class="val"><?= $dias ?></div>
                    <div class="lbl">Días del Período</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Por tipo -->
    <p class="section-title">Ventas por Tipo de Pedido</p>
    <table class="tipo-grid">
        <?php
        $tiposMap = ['salon'=>['En Restaurante','tag-salon'], 'llevar'=>['Para Llevar','tag-llevar'], 'domicilio'=>['A Domicilio','tag-domicilio']];
        $tipoData = [];
        foreach ($porTipo as $t) $tipoData[$t['tipo']] = $t;
        ?>
        <tr>
            <?php foreach ($tiposMap as $tipo => [$lbl,$tag]): $t = $tipoData[$tipo] ?? ['pedidos'=>0,'ingresos'=>0]; ?>
            <td width="33%">
                <div class="tipo-box">
                    <div class="tipo-val"><?= $t['pedidos'] ?></div>
                    <div class="tipo-lbl"><?= $lbl ?></div>
                    <div class="tipo-ing">$<?= number_format((float)$t['ingresos'], 0) ?> MXN</div>
                </div>
            </td>
            <?php endforeach; ?>
        </tr>
    </table>

    <!-- Ventas agrupadas -->
    <?php if ($ventasAgrupadas): ?>
    <p class="section-title">Ventas por <?= ucfirst($agrupacion) ?></p>
    <table class="data">
        <thead>
            <tr><th>Período</th><th>Pedidos</th><th style="text-align:right;">Ingresos</th><th style="text-align:right;">Ticket Prom.</th></tr>
        </thead>
        <tbody>
            <?php
            $totalPed = 0; $totalIng = 0;
            foreach ($ventasAgrupadas as $v):
                $totalPed += $v['pedidos'];
                $totalIng += $v['ingresos'];
            ?>
            <tr>
                <td><?= h($v['periodo_label']) ?></td>
                <td><?= $v['pedidos'] ?></td>
                <td style="text-align:right;color:#c9a84c;font-weight:bold;">$<?= number_format((float)$v['ingresos'],2) ?></td>
                <td style="text-align:right;">$<?= $v['pedidos'] > 0 ? number_format($v['ingresos']/$v['pedidos'],2) : '0.00' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td><strong>TOTAL</strong></td>
                <td><strong><?= $totalPed ?></strong></td>
                <td style="text-align:right;"><strong>$<?= number_format((float)$totalIng,2) ?></strong></td>
                <td style="text-align:right;"><strong>$<?= $totalPed > 0 ? number_format($totalIng/$totalPed,2) : '0.00' ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>

    <!-- Top platillos -->
    <?php if ($topPlatillos): ?>
    <p class="section-title">Top Platillos Vendidos</p>
    <table class="data">
        <thead>
            <tr><th>#</th><th>Platillo</th><th>Unidades</th><th>Precio Prom.</th><th style="text-align:right;">Ingresos</th><th style="text-align:right;">% del Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($topPlatillos as $i => $p):
                $pct = $kpi['ingresos_total'] > 0 ? round($p['ingresos']/$kpi['ingresos_total']*100, 1) : 0;
            ?>
            <tr>
                <td style="color:#c9a84c;font-weight:bold;"><?= $i+1 ?></td>
                <td><strong><?= h($p['nombre']) ?></strong></td>
                <td><?= $p['unidades'] ?></td>
                <td>$<?= number_format((float)$p['precio_prom'],2) ?></td>
                <td style="text-align:right;color:#c9a84c;font-weight:bold;">$<?= number_format((float)$p['ingresos'],2) ?></td>
                <td style="text-align:right;">
                    <?= $pct ?>%
                    <div class="bar-track" style="margin-top:3px;">
                        <div class="bar-fill" style="width:<?= $pct ?>%;"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Detalle pedidos -->
    <?php if ($pedidos): ?>
    <p class="section-title">Detalle de Pedidos</p>
    <table class="data">
        <thead>
            <tr><th>Código</th><th>Cliente</th><th>Fecha</th><th>Tipo</th><th>Items</th><th style="text-align:right;">Total</th></tr>
        </thead>
        <tbody>
            <?php
            $tiposLabels = ['salon'=>'Restaurante','llevar'=>'Para llevar','domicilio'=>'Domicilio'];
            foreach ($pedidos as $p):
            ?>
            <tr>
                <td style="color:#c9a84c;font-weight:bold;letter-spacing:.05em;"><?= h($p['codigo']) ?></td>
                <td><?= h($p['nombre']) ?><br><span style="color:#999;font-size:7.5pt;"><?= h($p['email']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                <td><?= $tiposLabels[$p['tipo']] ?? h($p['tipo']) ?></td>
                <td style="text-align:center;"><?= $p['n_items'] ?></td>
                <td style="text-align:right;font-weight:bold;color:#c9a84c;">$<?= number_format((float)$p['total'],2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5"><strong>TOTAL PERÍODO</strong></td>
                <td style="text-align:right;"><strong>$<?= number_format((float)$kpi['ingresos_total'],2) ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>

    </div><!-- /.content -->

    <div class="footer">
        <?= h($siteNombre) ?> &mdash; Reporte de Ventas &mdash;
        Período: <?= date('d/m/Y', strtotime($fechaDesde)) ?> al <?= date('d/m/Y', strtotime($fechaHasta)) ?> &mdash;
        Generado por <?= h($_SESSION['admin_nombre']) ?> el <?= date('d/m/Y H:i') ?>
    </div>

    <?php
    $html = ob_get_clean();
    $mpdf->WriteHTML($html);
    $filename = 'ventas_' . $fechaDesde . '_al_' . $fechaHasta . '.pdf';
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;
}

// ── Vista en pantalla ─────────────────────────────────────
require_once __DIR__ . '/includes/sidebar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.rp-kpi-ventas {
    background: var(--black-card);
    border: 1px solid var(--black-border);
    border-radius: var(--radius-lg);
    padding: 1.25rem 1.5rem;
    transition: border-color .2s;
}
.rp-kpi-ventas:hover { border-color: rgba(201,168,76,.3); }
.rp-kpi-ventas__val {
    font-family: var(--font-display);
    font-size: 1.9rem;
    font-weight: 700;
    color: var(--gold);
    line-height: 1;
}
.rp-kpi-ventas__label { font-size: .72rem; letter-spacing: .1em; text-transform: uppercase; color: var(--white-dim); margin-top: .3rem; }
.rp-kpi-ventas__cambio { font-size: .75rem; margin-top: .4rem; }
.up   { color: #2ea043; }
.down { color: #e05c5c; }

.rp-ventas-card {
    background: var(--black-card);
    border: 1px solid var(--black-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
}
.rp-ventas-card__title {
    font-family: var(--font-display);
    font-size: .95rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.rp-tipo-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--black);
    border: 1px solid var(--black-border);
    border-radius: var(--radius-lg);
    padding: 1rem;
    text-align: center;
    flex: 1;
    transition: border-color .2s;
}
.rp-tipo-pill:hover { border-color: rgba(201,168,76,.3); }
.rp-tipo-pill__val  { font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; color: var(--gold); }
.rp-tipo-pill__ing  { font-size: .72rem; color: var(--white-dim); }
.rp-tipo-pill__lbl  { font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; color: var(--white-dim); margin-top: .2rem; }
</style>

<!-- Filtros -->
<form method="GET" action="" class="rp-ventas-card mb-4">
    <p class="rp-ventas-card__title" style="margin-bottom:.75rem;">
        <span><i class="bi bi-funnel me-2 text-gold"></i>Filtros del Reporte</span>
    </p>
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="rp-form-label">Desde</label>
            <input type="date" name="desde" class="rp-form-control form-control" value="<?= h($fechaDesde) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="rp-form-label">Hasta</label>
            <input type="date" name="hasta" class="rp-form-control form-control" value="<?= h($fechaHasta) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="rp-form-label">Agrupar por</label>
            <select name="agrupar" class="rp-form-control form-select">
                <option value="dia"    <?= $agrupacion==='dia'    ?'selected':'' ?>>Día</option>
                <option value="semana" <?= $agrupacion==='semana' ?'selected':'' ?>>Semana</option>
                <option value="mes"    <?= $agrupacion==='mes'    ?'selected':'' ?>>Mes</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="rp-form-label">Tipo de pedido</label>
            <select name="tipo" class="rp-form-control form-select">
                <option value="">Todos</option>
                <option value="salon"     <?= $tipoFilter==='salon'     ?'selected':'' ?>>En restaurante</option>
                <option value="llevar"    <?= $tipoFilter==='llevar'    ?'selected':'' ?>>Para llevar</option>
                <option value="domicilio" <?= $tipoFilter==='domicilio' ?'selected':'' ?>>Domicilio</option>
            </select>
        </div>
        <div class="col-12 col-md-4 d-flex gap-2 align-items-end">
            <button type="submit" class="rp-btn-gold btn flex-fill">
                <i class="bi bi-search me-1"></i>Generar
            </button>
            <a href="?desde=<?= urlencode($fechaDesde) ?>&hasta=<?= urlencode($fechaHasta) ?>&agrupar=<?= $agrupacion ?>&tipo=<?= urlencode($tipoFilter) ?>&pdf=1"
               class="btn flex-fill" target="_blank"
               style="background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.3);color:var(--gold);font-weight:600;font-size:.82rem;">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="<?= APP_URL ?>/admin/reporte_ventas.php" class="rp-btn-outline btn px-3">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </div>
</form>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="rp-kpi-ventas">
            <div class="rp-kpi-ventas__val">$<?= number_format((float)$kpi['ingresos_total'],0) ?></div>
            <div class="rp-kpi-ventas__label">Ingresos Totales</div>
            <?php if ($cambioIngresos != 0): ?>
            <div class="rp-kpi-ventas__cambio <?= $cambioIngresos >= 0 ? 'up' : 'down' ?>">
                <i class="bi bi-arrow-<?= $cambioIngresos >= 0 ? 'up' : 'down' ?>"></i>
                <?= abs($cambioIngresos) ?>% vs período anterior
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-kpi-ventas">
            <div class="rp-kpi-ventas__val"><?= $kpi['total_pedidos'] ?></div>
            <div class="rp-kpi-ventas__label">Total Pedidos</div>
            <?php if ($cambioPedidos != 0): ?>
            <div class="rp-kpi-ventas__cambio <?= $cambioPedidos >= 0 ? 'up' : 'down' ?>">
                <i class="bi bi-arrow-<?= $cambioPedidos >= 0 ? 'up' : 'down' ?>"></i>
                <?= abs($cambioPedidos) ?>% vs período anterior
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-kpi-ventas">
            <div class="rp-kpi-ventas__val">$<?= number_format((float)$kpi['ticket_promedio'],0) ?></div>
            <div class="rp-kpi-ventas__label">Ticket Promedio</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-kpi-ventas">
            <div class="rp-kpi-ventas__val">$<?= number_format((float)$kpi['ticket_maximo'],0) ?></div>
            <div class="rp-kpi-ventas__label">Ticket Máximo</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-kpi-ventas">
            <div class="rp-kpi-ventas__val"><?= $dias ?></div>
            <div class="rp-kpi-ventas__label">Días Analizados</div>
        </div>
    </div>
</div>

<!-- Gráfica de ventas + Top platillos -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="rp-ventas-card">
            <p class="rp-ventas-card__title">
                <span><i class="bi bi-graph-up me-2 text-gold"></i>Ventas por <?= ucfirst($agrupacion) ?></span>
                <span class="text-muted small" style="font-family:var(--font-body);font-weight:400;">
                    <?= date('d/m/Y', strtotime($fechaDesde)) ?> – <?= date('d/m/Y', strtotime($fechaHasta)) ?>
                </span>
            </p>
            <?php if ($ventasAgrupadas): ?>
            <canvas id="chartVentas" height="260"></canvas>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-graph-down text-gold" style="font-size:2.5rem;opacity:.3"></i>
                <p class="text-muted mt-2">Sin ventas en este período</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="rp-ventas-card h-100">
            <p class="rp-ventas-card__title">
                <span><i class="bi bi-trophy me-2 text-gold"></i>Top Platillos</span>
            </p>
            <?php if ($topPlatillos):
                $maxIng = max(array_column($topPlatillos, 'ingresos'));
                foreach ($topPlatillos as $i => $p):
                    $pct = $maxIng > 0 ? round($p['ingresos']/$maxIng*100) : 0;
            ?>
            <div class="rp-ranking-bar">
                <span class="rp-ranking-bar__num"><?= $i+1 ?></span>
                <div class="flex-grow-1 overflow-hidden">
                    <p class="mb-0 small fw-semibold text-truncate" style="color:var(--white);"><?= h($p['nombre']) ?></p>
                    <div class="rp-ranking-bar__track mt-1">
                        <div class="rp-ranking-bar__fill" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <div class="text-end ms-2" style="flex-shrink:0;">
                    <p class="mb-0 text-gold small fw-semibold">$<?= number_format((float)$p['ingresos'],0) ?></p>
                    <p class="mb-0 text-muted" style="font-size:.7rem;"><?= $p['unidades'] ?> uds</p>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="text-center py-4">
                <i class="bi bi-bag-x text-gold" style="font-size:2rem;opacity:.3"></i>
                <p class="text-muted small mt-2">Sin ventas en este período</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Desglose por tipo -->
<div class="rp-ventas-card mb-4">
    <p class="rp-ventas-card__title"><i class="bi bi-bag-heart me-2 text-gold"></i>Desglose por Tipo de Pedido</p>
    <?php
    $tiposMap  = ['salon'=>['En Restaurante','bi-door-open'], 'llevar'=>['Para Llevar','bi-bag'], 'domicilio'=>['A Domicilio','bi-bicycle']];
    $tipoData  = [];
    foreach ($porTipo as $t) $tipoData[$t['tipo']] = $t;
    ?>
    <div class="d-flex gap-3 flex-wrap">
        <?php foreach ($tiposMap as $tipo => [$lbl,$ico]):
            $t = $tipoData[$tipo] ?? ['pedidos'=>0,'ingresos'=>0];
            $pct = $kpi['total_pedidos'] > 0 ? round($t['pedidos']/$kpi['total_pedidos']*100) : 0;
        ?>
        <div class="rp-tipo-pill">
            <i class="bi <?= $ico ?> text-gold fs-4 mb-1"></i>
            <div class="rp-tipo-pill__val"><?= $t['pedidos'] ?></div>
            <div class="rp-tipo-pill__lbl"><?= $lbl ?></div>
            <div class="rp-tipo-pill__ing">$<?= number_format((float)$t['ingresos'],0) ?> · <?= $pct ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tabla detalle -->
<div class="rp-ventas-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <p class="rp-ventas-card__title mb-0">
            <i class="bi bi-table me-2 text-gold"></i>Detalle de Pedidos
            <span class="text-muted ms-2" style="font-size:.78rem;font-weight:400;">(<?= count($pedidos) ?>)</span>
        </p>
        <?php if ($pedidos): ?>
        <a href="?desde=<?= urlencode($fechaDesde) ?>&hasta=<?= urlencode($fechaHasta) ?>&agrupar=<?= $agrupacion ?>&tipo=<?= urlencode($tipoFilter) ?>&pdf=1"
           class="rp-btn-gold btn btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-2"></i>Exportar PDF Completo
        </a>
        <?php endif; ?>
    </div>

    <?php if ($pedidos): ?>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr><th>Código</th><th>Cliente</th><th>Fecha y Hora</th><th>Tipo</th><th>Items</th><th>Estado</th><th style="text-align:right;">Total</th></tr>
            </thead>
            <tbody>
                <?php
                $tiposLabels  = ['salon'=>'Restaurante','llevar'=>'Para llevar','domicilio'=>'Domicilio'];
                $estadoBadge  = ['entregado'=>'confirmada','cancelado'=>'cancelada'];
                foreach ($pedidos as $p):
                ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/admin/pedidos.php?ver=<?= $p['id'] ?>"
                           class="text-gold fw-semibold" style="letter-spacing:.06em;">
                            <?= h($p['codigo']) ?>
                        </a>
                    </td>
                    <td>
                        <strong><?= h($p['nombre']) ?></strong><br>
                        <small class="text-muted"><?= h($p['email']) ?></small>
                    </td>
                    <td><small><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></small></td>
                    <td><small><?= $tiposLabels[$p['tipo']] ?? h($p['tipo']) ?></small></td>
                    <td class="text-center"><?= $p['n_items'] ?></td>
                    <td><span class="rp-badge rp-badge--<?= $estadoBadge[$p['estado']] ?? 'confirmada' ?>"><?= ucfirst($p['estado']) ?></span></td>
                    <td style="text-align:right;" class="text-gold fw-semibold">$<?= number_format((float)$p['total'],2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:rgba(201,168,76,.05);">
                    <td colspan="6" class="text-gold fw-semibold">TOTAL DEL PERÍODO</td>
                    <td style="text-align:right;" class="text-gold fw-semibold fs-6">
                        $<?= number_format((float)$kpi['ingresos_total'],2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-bag-x text-gold" style="font-size:3rem;opacity:.3"></i>
        <p class="text-muted mt-3">No hay pedidos en el período seleccionado.</p>
    </div>
    <?php endif; ?>
</div>

<?php if ($ventasAgrupadas): ?>
<script>
new Chart(document.getElementById('chartVentas'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ventasAgrupadas, 'periodo_label')) ?>,
        datasets: [
            {
                type: 'line',
                label: 'Ingresos ($)',
                data: <?= json_encode(array_map(fn($r) => round((float)$r['ingresos'],2), $ventasAgrupadas)) ?>,
                borderColor: '#c9a84c',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointBackgroundColor: '#c9a84c',
                pointRadius: 4,
                tension: 0.4,
                yAxisID: 'yIngresos',
            },
            {
                type: 'bar',
                label: 'Pedidos',
                data: <?= json_encode(array_column($ventasAgrupadas, 'pedidos')) ?>,
                backgroundColor: 'rgba(201,168,76,.15)',
                borderColor: 'rgba(201,168,76,.4)',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'yPedidos',
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: true,
                labels: { color: '#b0a99a', font: { size: 11 }, boxWidth: 12 }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label === 'Ingresos ($)'
                        ? ' $' + ctx.raw.toLocaleString('es-MX')
                        : ' ' + ctx.raw + ' pedidos'
                }
            }
        },
        scales: {
            x: { ticks: { color: '#b0a99a', font:{ size:10 } }, grid: { color: 'rgba(42,42,42,.8)' } },
            yIngresos: {
                position: 'left',
                ticks: { color: '#c9a84c', font:{ size:10 }, callback: v => '$' + (v/1000).toFixed(0)+'k' },
                grid: { color: 'rgba(42,42,42,.8)' },
                beginAtZero: true,
            },
            yPedidos: {
                position: 'right',
                ticks: { color: '#b0a99a', font:{ size:10 }, precision: 0 },
                grid: { drawOnChartArea: false },
                beginAtZero: true,
            }
        }
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
