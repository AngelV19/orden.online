<?php
/**
 * RESTAURANT PREMIUM — Admin: Sugerencias
 * Archivo: admin/sugerencias.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Sugerencias al Checkout';
$db  = db();
$msg = '';

// ── TOGGLE activo ─────────────────────────────────────────
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    $db->prepare('UPDATE sugerencias SET activo = IF(activo=1,0,1) WHERE id=:id')
       ->execute([':id' => (int)$_GET['id']]);
    header('Location: ' . APP_URL . '/admin/sugerencias.php?toggled=1'); exit;
}

// ── ELIMINAR ──────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $db->prepare('DELETE FROM sugerencias WHERE id=:id')
       ->execute([':id' => (int)$_GET['id']]);
    header('Location: ' . APP_URL . '/admin/sugerencias.php?deleted=1'); exit;
}

// ── AGREGAR platillo ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['platillo_id'])) {
    $platilloId = (int)$_POST['platillo_id'];
    $orden      = (int)($_POST['orden'] ?? 0);
    if ($platilloId) {
        try {
            $db->prepare(
                'INSERT INTO sugerencias (platillo_id, orden) VALUES (:p, :o)
                 ON DUPLICATE KEY UPDATE orden=:o2, activo=1'
            )->execute([':p'=>$platilloId, ':o'=>$orden, ':o2'=>$orden]);
            $msg = 'Platillo agregado a sugerencias.';
        } catch (\Exception $e) {
            $msg = 'Error: ' . $e->getMessage();
        }
    }
}

// ── ACTUALIZAR ORDEN ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orden_ids'])) {
    foreach ($_POST['orden_ids'] as $idx => $id) {
        $db->prepare('UPDATE sugerencias SET orden=:o WHERE id=:id')
           ->execute([':o' => (int)$idx, ':id' => (int)$id]);
    }
    $msg = 'Orden actualizado.';
}

if (isset($_GET['toggled'])) $msg = 'Estado actualizado.';
if (isset($_GET['deleted']))  $msg = 'Sugerencia eliminada.';

// ── Cargar sugerencias actuales ───────────────────────────
$sugerencias = $db->query(
    'SELECT s.*, p.nombre, p.precio, p.imagen, p.disponible,
            c.nombre AS categoria
     FROM sugerencias s
     JOIN platillos p ON p.id = s.platillo_id
     JOIN categorias c ON c.id = p.categoria_id
     ORDER BY s.orden ASC, s.id ASC'
)->fetchAll();

// ── Platillos disponibles para agregar ────────────────────
$yaAgregados = array_column($sugerencias, 'platillo_id');
$platillosDisp = $db->query(
    'SELECT p.id, p.nombre, p.precio, c.nombre AS categoria
     FROM platillos p
     JOIN categorias c ON c.id = p.categoria_id
     WHERE p.disponible = 1
     ORDER BY c.orden, p.nombre'
)->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
?>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>

<div class="row gy-4">

    <!-- ── Lista de sugerencias ─────────────────────────── -->
    <div class="col-lg-8">
        <div class="rp-form-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="rp-display fs-5 mb-0">Sugerencias activas</h2>
                    <p class="text-muted small mt-1 mb-0">
                        Aparecen en el modal antes del checkout. Máximo recomendado: 6.
                    </p>
                </div>
                <span class="rp-badge rp-badge--<?= count(array_filter($sugerencias, fn($s) => $s['activo'])) > 0 ? 'confirmada' : 'cancelada' ?>">
                    <?= count(array_filter($sugerencias, fn($s) => $s['activo'])) ?> activas
                </span>
            </div>

            <?php if ($sugerencias): ?>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Platillo</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sugerencias as $s): ?>
                        <tr style="<?= !$s['activo'] ? 'opacity:.5;' : '' ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= platilloImg($s['imagen'], $s['nombre']) ?>"
                                         alt="" style="width:44px;height:36px;object-fit:cover;border-radius:4px;flex-shrink:0;">
                                    <strong><?= h($s['nombre']) ?></strong>
                                </div>
                            </td>
                            <td><small class="text-muted"><?= h($s['categoria']) ?></small></td>
                            <td class="text-gold"><?= formatPrecio((float)$s['precio']) ?></td>
                            <td>
                                <span class="text-muted small"><?= $s['orden'] ?></span>
                            </td>
                            <td>
                                <!-- Toggle activo/inactivo -->
                                <a href="?action=toggle&id=<?= $s['id'] ?>"
                                   class="btn btn-sm <?= $s['activo'] ? 'rp-btn-gold' : 'rp-btn-outline' ?>"
                                   style="min-width:90px;">
                                    <i class="bi bi-toggle-<?= $s['activo'] ? 'on' : 'off' ?> me-1"></i>
                                    <?= $s['activo'] ? 'Activa' : 'Inactiva' ?>
                                </a>
                            </td>
                            <td>
                                <a href="?action=delete&id=<?= $s['id'] ?>"
                                   class="btn btn-sm"
                                   style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                                   onclick="return confirmDelete('¿Quitar <?= h(addslashes($s['nombre'])) ?> de las sugerencias?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Info de cómo se muestran -->
            <div class="mt-3 p-3 rounded-2" style="background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.15);">
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle text-gold me-2"></i>
                    Si no hay sugerencias activas, el sistema mostrará automáticamente bebidas, postres y platillos destacados.
                </p>
            </div>

            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-bag-heart text-gold" style="font-size:3rem;opacity:.3"></i>
                <p class="text-muted mt-3">No hay sugerencias configuradas.</p>
                <p class="text-muted small">El sistema usará sugerencias automáticas basadas en bebidas, postres y destacados.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Agregar platillo ──────────────────────────────── -->
    <div class="col-lg-4">
        <div class="rp-form-card">
            <h3 class="rp-display fs-6 mb-3">Agregar sugerencia</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="rp-form-label">Platillo</label>
                    <select name="platillo_id" class="rp-form-control form-select" required>
                        <option value="">Seleccionar platillo…</option>
                        <?php
                        // Agrupar por categoría
                        $porCat = [];
                        foreach ($platillosDisp as $p) {
                            $porCat[$p['categoria']][] = $p;
                        }
                        foreach ($porCat as $cat => $items):
                        ?>
                        <optgroup label="<?= h($cat) ?>">
                            <?php foreach ($items as $p): 
                                $yaEsta = in_array($p['id'], $yaAgregados);
                            ?>
                            <option value="<?= $p['id'] ?>"
                                    <?= $yaEsta ? 'disabled' : '' ?>>
                                <?= h($p['nombre']) ?> — $<?= number_format((float)$p['precio'],2) ?>
                                <?= $yaEsta ? '(ya agregado)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="rp-form-label">Orden de aparición</label>
                    <input type="number" name="orden" class="rp-form-control form-control"
                           min="0" value="<?= count($sugerencias) ?>">
                    <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">0 = aparece primero</p>
                </div>
                <button type="submit" class="rp-btn-gold btn w-100">
                    <i class="bi bi-plus-lg me-2"></i>Agregar a sugerencias
                </button>
            </form>
        </div>

        <!-- Vista previa -->
        <div class="rp-form-card mt-3">
            <h3 class="rp-display fs-6 mb-2">¿Cuándo aparecen?</h3>
            <p class="text-muted small mb-0">
                El modal de sugerencias aparece cuando el cliente hace clic en
                <strong class="text-gold">"Proceder al Pago"</strong> en el carrito.
            </p>
            <div class="mt-3 d-flex flex-column gap-2">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-gold flex-shrink-0 mt-1" style="font-size:.8rem;"></i>
                    <p class="text-muted small mb-0">Se muestran las activas en el orden configurado</p>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-gold flex-shrink-0 mt-1" style="font-size:.8rem;"></i>
                    <p class="text-muted small mb-0">No muestra platillos que ya están en el carrito</p>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-gold flex-shrink-0 mt-1" style="font-size:.8rem;"></i>
                    <p class="text-muted small mb-0">Máximo 4 sugerencias por modal</p>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle text-gold flex-shrink-0 mt-1" style="font-size:.8rem;"></i>
                    <p class="text-muted small mb-0">Si todas están inactivas, usa sugerencias automáticas</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
