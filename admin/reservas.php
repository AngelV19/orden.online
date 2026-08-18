<?php
/**
 * RESTAURANT PREMIUM — Admin: Reservaciones
 * Archivo: admin/reservas.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Reservaciones';
$db  = db();
$msg = '';

// ── Acciones ──────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

// Actualizar estado (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estado'], $_POST['id'])) {
    $estados = ['pendiente', 'confirmada', 'cancelada'];
    $est     = in_array($_POST['estado'], $estados, true) ? $_POST['estado'] : 'pendiente';
    $stmt    = $db->prepare('UPDATE reservas SET estado = :e WHERE id = :id');
    $stmt->execute([':e' => $est, ':id' => (int)$_POST['id']]);
    $msg = 'Estado actualizado correctamente.';
}

// Eliminar
if ($action === 'delete' && $id) {
    $db->prepare('DELETE FROM reservas WHERE id = :id')->execute([':id' => $id]);
    header('Location: ' . APP_URL . '/admin/reservas.php?deleted=1');
    exit;
}

// Flash
if (isset($_GET['deleted'])) $msg = 'Reservación eliminada.';

// ── Filtros ───────────────────────────────────────────────
$filterEstado = $_GET['estado'] ?? '';
$filterFecha  = $_GET['fecha']  ?? '';
$search       = trim($_GET['q'] ?? '');

$where   = [];
$params  = [];

if ($filterEstado && in_array($filterEstado, ['pendiente','confirmada','cancelada'])) {
    $where[] = 'estado = :est'; $params[':est'] = $filterEstado;
}
if ($filterFecha) {
    $where[] = 'fecha = :f'; $params[':f'] = $filterFecha;
}
if ($search) {
    $where[] = '(nombre LIKE :q OR email LIKE :q OR telefono LIKE :q)';
    $params[':q'] = "%$search%";
}

$sql = 'SELECT * FROM reservas'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
?>
<script>window.ADMIN_URL = '<?= APP_URL ?>';</script>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>

<!-- Filtros -->
<form method="GET" action="" class="row g-2 mb-4">
    <div class="col-12 col-md-4 col-lg-3">
        <input type="search" name="q" class="rp-form-control form-control" placeholder="Buscar nombre, email…" value="<?= h($search) ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <select name="estado" class="rp-form-control form-select">
            <option value="">Todos los estados</option>
            <option value="pendiente"  <?= $filterEstado==='pendiente'  ?'selected':'' ?>>Pendiente</option>
            <option value="confirmada" <?= $filterEstado==='confirmada' ?'selected':'' ?>>Confirmada</option>
            <option value="cancelada"  <?= $filterEstado==='cancelada'  ?'selected':'' ?>>Cancelada</option>
        </select>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <input type="date" name="fecha" class="rp-form-control form-control" value="<?= h($filterFecha) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="rp-btn-gold btn"><i class="bi bi-search me-1"></i>Filtrar</button>
        <a href="<?= APP_URL ?>/admin/reservas.php" class="rp-btn-outline btn ms-1">Limpiar</a>
    </div>
</form>

<!-- Tabla -->
<div class="rp-form-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="rp-display fs-5 mb-0">Reservaciones <span class="text-muted small ms-2">(<?= count($reservas) ?>)</span></h2>
    </div>

    <?php if ($reservas): ?>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Pax</th>
                    <th>Nota</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $r): ?>
                <tr>
                    <td class="text-muted"><?= $r['id'] ?></td>
                    <td>
                        <strong><?= h($r['nombre']) ?></strong><br>
                        <small><a href="mailto:<?= h($r['email']) ?>" class="text-muted"><?= h($r['email']) ?></a></small><br>
                        <small><a href="tel:<?= h($r['telefono']) ?>" class="text-muted"><?= h($r['telefono']) ?></a></small>
                    </td>
                    <td><?= formatFecha($r['fecha']) ?></td>
                    <td><?= formatHora($r['hora']) ?></td>
                    <td><?= $r['personas'] ?></td>
                    <td>
                        <?php if ($r['mensaje']): ?>
                        <span data-bs-toggle="tooltip" title="<?= h($r['mensaje']) ?>">
                            <i class="bi bi-chat-dots text-gold"></i>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Inline status change -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <select name="estado" class="rp-form-control form-select form-select-sm rp-badge rp-badge--<?= $r['estado'] ?>"
                                    onchange="this.form.submit()" style="width:auto;display:inline;padding:.2rem .5rem;border-radius:100px;">
                                <option value="pendiente"  <?= $r['estado']==='pendiente'  ?'selected':''?>>Pendiente</option>
                                <option value="confirmada" <?= $r['estado']==='confirmada' ?'selected':''?>>Confirmada</option>
                                <option value="cancelada"  <?= $r['estado']==='cancelada'  ?'selected':''?>>Cancelada</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <a href="https://wa.me/<?= preg_replace('/\D/','',$r['telefono']) ?>?text=<?= rawurlencode('Hola '.$r['nombre'].', confirmamos tu reservación para '.$r['personas'].' personas el '.formatFecha($r['fecha']).' a las '.formatHora($r['hora']).'. ¡Te esperamos en '.APP_NAME.'!') ?>"
                           target="_blank" class="btn btn-sm rp-btn-outline me-1" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <a href="<?= APP_URL ?>/admin/reservas.php?action=delete&id=<?= $r['id'] ?>"
                           class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                           onclick="return confirmDelete('¿Eliminar la reservación de <?= h(addslashes($r['nombre'])) ?>?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-x text-gold" style="font-size:3rem;opacity:.5"></i>
        <p class="text-muted mt-3">No hay reservaciones con esos filtros.</p>
    </div>
    <?php endif; ?>
</div>

<script>
// Bootstrap tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
