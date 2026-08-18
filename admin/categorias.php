<?php
/**
 * RESTAURANT PREMIUM — Admin: Categorías
 * Archivo: admin/categorias.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Categorías';
$db  = db();
$msg = '';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── GUARDAR ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $icono  = trim($_POST['icono'] ?? 'bi-grid');
    $orden  = (int)($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $postId = (int)($_POST['id'] ?? 0);

    if (strlen($nombre) >= 2) {
        if ($postId) {
            $db->prepare('UPDATE categorias SET nombre=:n,descripcion=:d,icono=:i,orden=:o,activo=:a WHERE id=:id')
               ->execute([':n'=>$nombre,':d'=>$desc,':i'=>$icono,':o'=>$orden,':a'=>$activo,':id'=>$postId]);
            $msg = 'Categoría actualizada.';
        } else {
            $db->prepare('INSERT INTO categorias (nombre,descripcion,icono,orden,activo) VALUES (:n,:d,:i,:o,:a)')
               ->execute([':n'=>$nombre,':d'=>$desc,':i'=>$icono,':o'=>$orden,':a'=>$activo]);
            $msg = 'Categoría creada.';
        }
        $action = 'list';
    }
}

// ── ELIMINAR ──────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $count = $db->prepare('SELECT COUNT(*) FROM platillos WHERE categoria_id=:id')->execute([':id'=>$id])
             ? (($s=$db->prepare('SELECT COUNT(*) FROM platillos WHERE categoria_id=:id'))&&$s->execute([':id'=>$id])?$s->fetchColumn():0) : 0;
    if ($count) {
        $msg = 'No se puede eliminar: la categoría tiene platillos asignados.';
    } else {
        $db->prepare('DELETE FROM categorias WHERE id=:id')->execute([':id'=>$id]);
        header('Location: ' . APP_URL . '/admin/categorias.php?deleted=1'); exit;
    }
    $action = 'list';
}

if (isset($_GET['deleted'])) $msg = 'Categoría eliminada.';

// ── DATOS ─────────────────────────────────────────────────
$categorias = getCategorias(false);
$cat        = [];

if (in_array($action, ['editar']) && $id) {
    $s = $db->prepare('SELECT * FROM categorias WHERE id=:id');
    $s->execute([':id'=>$id]);
    $cat = $s->fetch() ?: [];
    if (!$cat) $action = 'list';
}

$iconos = ['bi-grid','bi-egg-fried','bi-award','bi-cup-straw','bi-balloon-heart',
           'bi-fish','bi-cup-hot','bi-basket','bi-flower1','bi-stars'];

require_once __DIR__ . '/includes/sidebar.php';
?>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--<?= str_contains($msg,'No se') ? 'error' : 'success' ?> rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="rp-display fs-5 mb-0">Categorías</h2>
    <a href="?action=nuevo" class="rp-btn-gold btn"><i class="bi bi-plus-lg me-2"></i>Nueva Categoría</a>
</div>

<div class="rp-form-card">
    <table class="rp-table">
        <thead><tr><th>#</th><th>Icono</th><th>Nombre</th><th>Platillos</th><th>Orden</th><th>Activa</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($categorias as $c):
                $s = $db->prepare('SELECT COUNT(*) FROM platillos WHERE categoria_id=:id');
                $s->execute([':id'=>$c['id']]);
                $nPlatillos = $s->fetchColumn();
            ?>
            <tr>
                <td class="text-muted"><?= $c['id'] ?></td>
                <td><i class="bi <?= h($c['icono']) ?> text-gold fs-5"></i></td>
                <td><strong><?= h($c['nombre']) ?></strong>
                    <?php if ($c['descripcion']): ?>
                    <br><small class="text-muted"><?= h(mb_substr($c['descripcion'],0,60)) ?>…</small>
                    <?php endif; ?></td>
                <td><?= $nPlatillos ?></td>
                <td><?= $c['orden'] ?></td>
                <td><span class="rp-badge rp-badge--<?= $c['activo'] ? 'confirmada' : 'cancelada' ?>"><?= $c['activo'] ? 'Sí' : 'No' ?></span></td>
                <td>
                    <a href="?action=editar&id=<?= $c['id'] ?>" class="btn btn-sm rp-btn-outline me-1"><i class="bi bi-pencil"></i></a>
                    <a href="?action=delete&id=<?= $c['id'] ?>"
                       class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                       onclick="return confirmDelete()"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/categorias.php" class="btn rp-btn-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    <h2 class="rp-display fs-5 mb-0"><?= $cat ? 'Editar Categoría' : 'Nueva Categoría' ?></h2>
</div>

<form method="POST" action="" class="rp-form-card" style="max-width:600px;">
    <input type="hidden" name="id" value="<?= $cat['id'] ?? '' ?>">
    <div class="row gy-3">
        <div class="col-md-8">
            <label class="rp-form-label">Nombre *</label>
            <input type="text" name="nombre" class="rp-form-control form-control" required
                   value="<?= h($cat['nombre'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="rp-form-label">Orden</label>
            <input type="number" name="orden" class="rp-form-control form-control" min="0"
                   value="<?= $cat['orden'] ?? 0 ?>">
        </div>
        <div class="col-12">
            <label class="rp-form-label">Descripción</label>
            <textarea name="descripcion" class="rp-form-control form-control" rows="2"><?= h($cat['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="rp-form-label">Icono Bootstrap</label>
            <select name="icono" class="rp-form-control form-select">
                <?php foreach ($iconos as $ico): ?>
                <option value="<?= $ico ?>" <?= ($cat['icono'] ?? '') === $ico ? 'selected' : '' ?>>
                    <?= $ico ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="activo" id="chkActivo"
                       <?= ($cat['activo'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label text-muted" for="chkActivo">Categoría activa</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="rp-btn-gold btn px-5">
                <i class="bi bi-check-lg me-2"></i><?= $cat ? 'Guardar' : 'Crear' ?>
            </button>
        </div>
    </div>
</form>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
