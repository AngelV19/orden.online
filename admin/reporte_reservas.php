<?php
/**
 * RESTAURANT PREMIUM — Admin: Reporte de Reservas
 * Archivo: admin/reporte_reservas.php
 *
 * Genera reporte en pantalla y exporta a PDF usando mPDF.
 * Instalación mPDF: desde la carpeta del proyecto ejecutar:
 *   composer require mpdf/mpdf
 * Si no tienes Composer:
 *   https://getcomposer.org/download/
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Reporte de Reservas';
$db = db();

// ── Filtros ───────────────────────────────────────────────
$fechaDesde = $_GET['desde']  ?? date('Y-m-01');          // primer día del mes actual
$fechaHasta = $_GET['hasta']  ?? date('Y-m-d');           // hoy
$estado     = $_GET['estado'] ?? '';
$exportar   = isset($_GET['pdf']);

// ── Query ─────────────────────────────────────────────────
$where  = ['fecha BETWEEN :desde AND :hasta'];
$params = [':desde' => $fechaDesde, ':hasta' => $fechaHasta];

if ($estado && in_array($estado, ['pendiente','confirmada','cancelada'])) {
    $where[] = 'estado = :estado';
    $params[':estado'] = $estado;
}

$sql = 'SELECT * FROM reservas WHERE ' . implode(' AND ', $where)
     . ' ORDER BY fecha ASC, hora ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll();

// ── Estadísticas ──────────────────────────────────────────
$stats = [
    'total'      => count($reservas),
    'confirmada' => 0,
    'pendiente'  => 0,
    'cancelada'  => 0,
    'personas'   => 0,
];
foreach ($reservas as $r) {
    $stats[$r['estado']]++;
    $stats['personas'] += (int)$r['personas'];
}

// ── Exportar PDF ──────────────────────────────────────────
if ($exportar) {
    $mpdfPath = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($mpdfPath)) {
        // Si no está instalado mPDF, mostrar instrucciones
        die('
        <div style="font-family:sans-serif;padding:2rem;background:#111;color:#fff;min-height:100vh;">
            <h2 style="color:#c9a84c;">⚠️ mPDF no está instalado</h2>
            <p>Para exportar PDF necesitas instalar mPDF con Composer.</p>
            <h3 style="color:#c9a84c;">Pasos:</h3>
            <ol style="line-height:2">
                <li>Descarga Composer: <a href="https://getcomposer.org/download/" style="color:#c9a84c;" target="_blank">getcomposer.org</a></li>
                <li>Abre la terminal (CMD) en la carpeta <code>C:\xampp\htdocs\restaurant\</code></li>
                <li>Ejecuta: <code style="background:#222;padding:.2rem .5rem;border-radius:4px;">composer require mpdf/mpdf</code></li>
                <li>Espera a que termine y recarga esta página.</li>
            </ol>
            <a href="javascript:history.back()" style="color:#c9a84c;">← Volver</a>
        </div>');
    }

    require_once $mpdfPath;

    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 20,
        'margin_bottom' => 20,
        'margin_left'   => 15,
        'margin_right'  => 15,
    ]);

    $mpdf->SetTitle('Reporte de Reservas — ' . APP_NAME);
    $mpdf->SetAuthor(APP_NAME);
    $mpdf->SetCreator(APP_NAME);

    // ── HTML del PDF ──────────────────────────────────────
    $html = '
    <style>
        * { font-family: "DejaVu Sans", sans-serif; }
        body { background: #fff; color: #1a1a1a; font-size: 10pt; }

        .header { background: #0d0d0d; color: #fff; padding: 18px 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; color: #c9a84c; letter-spacing: 1px; }
        .header p  { margin: 4px 0 0; font-size: 9pt; color: #aaa; }

        .stats-grid { width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 8px; }
        .stat-box { background: #f8f8f6; border: 1px solid #e0d9cc; border-radius: 6px; padding: 12px 16px; text-align: center; }
        .stat-box .num { font-size: 20pt; font-weight: bold; color: #c9a84c; }
        .stat-box .lbl { font-size: 7.5pt; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }

        .section-title { font-size: 11pt; font-weight: bold; color: #0d0d0d;
                         border-bottom: 2px solid #c9a84c; padding-bottom: 5px; margin-bottom: 12px; }

        table.reservas { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        table.reservas thead tr { background: #0d0d0d; color: #c9a84c; }
        table.reservas thead th { padding: 8px 7px; text-align: left; font-size: 7.5pt;
                                   letter-spacing: .5px; text-transform: uppercase; }
        table.reservas tbody tr:nth-child(even) { background: #f8f8f6; }
        table.reservas tbody tr:nth-child(odd)  { background: #fff; }
        table.reservas td { padding: 7px 7px; border-bottom: 1px solid #ebe5d8; vertical-align: middle; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px;
                 font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        .badge-pendiente  { background: #fff8e1; color: #b8860b; border: 1px solid #f0d060; }
        .badge-confirmada { background: #e8f5e9; color: #2e7d32; border: 1px solid #81c784; }
        .badge-cancelada  { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        .footer { margin-top: 20px; font-size: 8pt; color: #999; text-align: center;
                  border-top: 1px solid #e0d9cc; padding-top: 10px; }

        .empty { text-align: center; padding: 30px; color: #999; font-size: 10pt; }
        .filter-info { background: #f8f8f6; border: 1px solid #e0d9cc; border-radius: 4px;
                       padding: 8px 12px; margin-bottom: 16px; font-size: 8.5pt; color: #555; }
    </style>

    <!-- Encabezado -->
    <div class="header">
        <h1>&#10022; ' . APP_NAME . '</h1>
        <p>Reporte de Reservaciones &mdash; Generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs</p>
    </div>

    <!-- Filtros aplicados -->
    <div class="filter-info">
        <strong>Periodo:</strong> ' . date('d/m/Y', strtotime($fechaDesde)) . ' al ' . date('d/m/Y', strtotime($fechaHasta)) . '
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Estado:</strong> ' . ($estado ? ucfirst($estado) : 'Todos') . '
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Resultados:</strong> ' . $stats['total'] . ' reserva(s)
    </div>

    <!-- Estadísticas -->
    <p class="section-title">Resumen</p>
    <table class="stats-grid">
        <tr>
            <td width="20%" style="padding:4px;">
                <div class="stat-box">
                    <div class="num">' . $stats['total'] . '</div>
                    <div class="lbl">Total</div>
                </div>
            </td>
            <td width="20%" style="padding:4px;">
                <div class="stat-box">
                    <div class="num" style="color:#2e7d32;">' . $stats['confirmada'] . '</div>
                    <div class="lbl">Confirmadas</div>
                </div>
            </td>
            <td width="20%" style="padding:4px;">
                <div class="stat-box">
                    <div class="num" style="color:#b8860b;">' . $stats['pendiente'] . '</div>
                    <div class="lbl">Pendientes</div>
                </div>
            </td>
            <td width="20%" style="padding:4px;">
                <div class="stat-box">
                    <div class="num" style="color:#c62828;">' . $stats['cancelada'] . '</div>
                    <div class="lbl">Canceladas</div>
                </div>
            </td>
            <td width="20%" style="padding:4px;">
                <div class="stat-box">
                    <div class="num">' . $stats['personas'] . '</div>
                    <div class="lbl">Total Personas</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabla de reservas -->
    <p class="section-title">Detalle de Reservaciones</p>';

    if ($reservas) {
        $html .= '
        <table class="reservas">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Contacto</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Pax</th>
                    <th>Nota</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($reservas as $r) {
            $nota = $r['mensaje'] ? mb_substr($r['mensaje'], 0, 35) . (mb_strlen($r['mensaje']) > 35 ? '…' : '') : '—';
            $html .= '
                <tr>
                    <td style="color:#999;">' . $r['id'] . '</td>
                    <td><strong>' . h($r['nombre']) . '</strong></td>
                    <td>' . h($r['email']) . '<br><span style="color:#999;">' . h($r['telefono']) . '</span></td>
                    <td>' . date('d/m/Y', strtotime($r['fecha'])) . '</td>
                    <td>' . date('h:i A', strtotime($r['hora'])) . '</td>
                    <td style="text-align:center;font-weight:bold;">' . $r['personas'] . '</td>
                    <td style="color:#666;">' . h($nota) . '</td>
                    <td><span class="badge badge-' . $r['estado'] . '">' . ucfirst($r['estado']) . '</span></td>
                </tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<div class="empty">No hay reservaciones en el período seleccionado.</div>';
    }

    $html .= '
    <div class="footer">
        ' . APP_NAME . ' &mdash; Panel de Administración &mdash; Reporte generado por ' . h($_SESSION['admin_nombre']) . '
    </div>';

    $mpdf->WriteHTML($html);

    $filename = 'reservas_' . $fechaDesde . '_al_' . $fechaHasta . '.pdf';
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;
}

// ── Vista normal (pantalla) ───────────────────────────────
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- ── Filtros ────────────────────────────────────────────── -->
<form method="GET" action="" class="rp-form-card mb-4">
    <h2 class="rp-display fs-5 mb-3">Filtrar Reporte</h2>
    <div class="row g-3 align-items-end">
        <div class="col-6 col-md-3">
            <label class="rp-form-label">Desde</label>
            <input type="date" name="desde" class="rp-form-control form-control"
                   value="<?= h($fechaDesde) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="rp-form-label">Hasta</label>
            <input type="date" name="hasta" class="rp-form-control form-control"
                   value="<?= h($fechaHasta) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="rp-form-label">Estado</label>
            <select name="estado" class="rp-form-control form-select">
                <option value="">Todos</option>
                <option value="pendiente"  <?= $estado==='pendiente'  ?'selected':'' ?>>Pendiente</option>
                <option value="confirmada" <?= $estado==='confirmada' ?'selected':'' ?>>Confirmada</option>
                <option value="cancelada"  <?= $estado==='cancelada'  ?'selected':'' ?>>Cancelada</option>
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="rp-btn-gold btn flex-fill">
                <i class="bi bi-search me-1"></i>Filtrar
            </button>
            <a href="<?= APP_URL ?>/admin/reporte_reservas.php" class="rp-btn-outline btn">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </div>
</form>

<!-- ── Estadísticas ───────────────────────────────────────── -->
<div class="row gy-3 mb-4">
    <div class="col-6 col-xl" >
        <div class="rp-stat-card">
            <div class="rp-stat-card__icon"><i class="bi bi-calendar3"></i></div>
            <div>
                <div class="rp-stat-card__value"><?= $stats['total'] ?></div>
                <div class="rp-stat-card__label">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-stat-card">
            <div class="rp-stat-card__icon" style="background:rgba(46,160,67,.1);border-color:rgba(46,160,67,.2);">
                <i class="bi bi-check-circle" style="color:#2ea043;"></i>
            </div>
            <div>
                <div class="rp-stat-card__value" style="color:#2ea043;"><?= $stats['confirmada'] ?></div>
                <div class="rp-stat-card__label">Confirmadas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-stat-card">
            <div class="rp-stat-card__icon" style="background:rgba(255,193,7,.1);border-color:rgba(255,193,7,.2);">
                <i class="bi bi-hourglass-split" style="color:#ffc107;"></i>
            </div>
            <div>
                <div class="rp-stat-card__value" style="color:#ffc107;"><?= $stats['pendiente'] ?></div>
                <div class="rp-stat-card__label">Pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-stat-card">
            <div class="rp-stat-card__icon" style="background:rgba(224,92,92,.1);border-color:rgba(224,92,92,.2);">
                <i class="bi bi-x-circle" style="color:#e05c5c;"></i>
            </div>
            <div>
                <div class="rp-stat-card__value" style="color:#e05c5c;"><?= $stats['cancelada'] ?></div>
                <div class="rp-stat-card__label">Canceladas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="rp-stat-card">
            <div class="rp-stat-card__icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="rp-stat-card__value"><?= $stats['personas'] ?></div>
                <div class="rp-stat-card__label">Personas</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Tabla + botón exportar ─────────────────────────────── -->
<div class="rp-form-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="rp-display fs-5 mb-0">
            Reservaciones
            <span class="text-muted small ms-2">
                <?= date('d/m/Y', strtotime($fechaDesde)) ?> – <?= date('d/m/Y', strtotime($fechaHasta)) ?>
            </span>
        </h2>

        <?php if ($reservas): ?>
        <a href="?desde=<?= urlencode($fechaDesde) ?>&hasta=<?= urlencode($fechaHasta) ?>&estado=<?= urlencode($estado) ?>&pdf=1"
           class="rp-btn-gold btn"
           target="_blank">
            <i class="bi bi-file-earmark-pdf me-2"></i>Exportar PDF
        </a>
        <?php endif; ?>
    </div>

    <?php if ($reservas): ?>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Contacto</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Pax</th>
                    <th>Nota</th>
                    <th>Estado</th>
                    <th>Registrada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $r): ?>
                <tr>
                    <td class="text-muted"><?= $r['id'] ?></td>
                    <td><strong><?= h($r['nombre']) ?></strong></td>
                    <td>
                        <a href="mailto:<?= h($r['email']) ?>" class="text-muted small d-block"><?= h($r['email']) ?></a>
                        <a href="tel:<?= h($r['telefono']) ?>" class="text-muted small"><?= h($r['telefono']) ?></a>
                    </td>
                    <td><?= formatFecha($r['fecha']) ?></td>
                    <td><?= formatHora($r['hora']) ?></td>
                    <td class="text-center"><strong><?= $r['personas'] ?></strong></td>
                    <td>
                        <?php if ($r['mensaje']): ?>
                        <span data-bs-toggle="tooltip" title="<?= h($r['mensaje']) ?>" style="cursor:help;">
                            <i class="bi bi-chat-dots text-gold"></i>
                            <small class="text-muted"><?= h(mb_substr($r['mensaje'],0,20)) ?>…</small>
                        </span>
                        <?php else: echo '<span class="text-muted">—</span>'; endif; ?>
                    </td>
                    <td><span class="rp-badge rp-badge--<?= $r['estado'] ?>"><?= ucfirst($r['estado']) ?></span></td>
                    <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Nota mPDF -->
    <div class="mt-3 p-3 rounded-2" style="background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.15);">
        <p class="text-muted small mb-0">
            <i class="bi bi-info-circle text-gold me-2"></i>
            El botón <strong class="text-gold">Exportar PDF</strong> requiere tener instalado
            <strong>mPDF</strong> via Composer.
            Si no lo tienes, al hacer clic verás las instrucciones de instalación (es un solo comando).
        </p>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-x text-gold" style="font-size:3rem;opacity:.4"></i>
        <p class="text-muted mt-3">No hay reservaciones en el período seleccionado.</p>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
