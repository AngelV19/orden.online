<?php
/**
 * RESTAURANT PREMIUM — Admin: Platillos (CRUD completo)
 * Archivo: admin/platillos.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Platillos';
$db     = db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$errors = [];

// ── GUARDAR (nuevo / editar) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId      = (int)($_POST['categoria_id'] ?? 0);
    $nombre     = trim($_POST['nombre']     ?? '');
    $desc       = trim($_POST['descripcion'] ?? '');
    $precio     = (float)($_POST['precio']  ?? 0);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    $destacado  = isset($_POST['destacado'])  ? 1 : 0;
    $orden      = (int)($_POST['orden']     ?? 0);
    $postId     = (int)($_POST['id']        ?? 0);

    // Validaciones
    if (!$catId)          $errors[] = 'Selecciona una categoría.';
    if (strlen($nombre) < 2) $errors[] = 'El nombre debe tener al menos 2 caracteres.';
    if ($precio <= 0)     $errors[] = 'El precio debe ser mayor a cero.';

    // Imagen
    $imagenNombre = $_POST['imagen_actual'] ?? null;
    if (!empty($_FILES['imagen']['name'])) {
        $subida = subirImagen($_FILES['imagen']);
        if ($subida) $imagenNombre = $subida;
        else         $errors[] = 'Error al subir la imagen (máx. 3MB, formatos: JPG, PNG, WEBP).';
    }

    if (!$errors) {
        if ($postId) {
            // Actualizar
            $db->prepare(
                'UPDATE platillos SET categoria_id=:c, nombre=:n, descripcion=:d, precio=:p,
                 imagen=:i, disponible=:dis, destacado=:dest, orden=:o WHERE id=:id'
            )->execute([':c'=>$catId,':n'=>$nombre,':d'=>$desc,':p'=>$precio,
                        ':i'=>$imagenNombre,':dis'=>$disponible,':dest'=>$destacado,':o'=>$orden,':id'=>$postId]);
            $msg = 'Platillo actualizado correctamente.';
        } else {
            // Crear
            $db->prepare(
                'INSERT INTO platillos (categoria_id,nombre,descripcion,precio,imagen,disponible,destacado,orden)
                 VALUES (:c,:n,:d,:p,:i,:dis,:dest,:o)'
            )->execute([':c'=>$catId,':n'=>$nombre,':d'=>$desc,':p'=>$precio,
                        ':i'=>$imagenNombre,':dis'=>$disponible,':dest'=>$destacado,':o'=>$orden]);
            $msg = 'Platillo creado correctamente.';
        }
        $action = 'list';
    } else {
        $action = $postId ? 'editar' : 'nuevo';
        $id     = $postId;
    }
}

// ── ELIMINAR ──────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $db->prepare('DELETE FROM platillos WHERE id = :id')->execute([':id' => $id]);
    header('Location: ' . APP_URL . '/admin/platillos.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) $msg = 'Platillo eliminado.';

// ── DATOS para formulario ─────────────────────────────────
$categorias = getCategorias(false);
$platillo   = [];

if (in_array($action, ['editar','nuevo']) && $id) {
    $platillo = $db->prepare('SELECT * FROM platillos WHERE id=:id')->execute([':id'=>$id])
                  ? (($s=$db->prepare('SELECT * FROM platillos WHERE id=:id')) && $s->execute([':id'=>$id])
                     ? $s->fetch() : []) : [];
    if (!$platillo) { $action = 'list'; }
}

// ── LISTA ─────────────────────────────────────────────────
$platillos = [];
if ($action === 'list') {
    $platillos = $db->query(
        'SELECT p.*, c.nombre AS cat FROM platillos p
         JOIN categorias c ON c.id=p.categoria_id ORDER BY c.orden, p.orden, p.id'
    )->fetchAll();
}

require_once __DIR__ . '/includes/sidebar.php';
?>
<script>window.ADMIN_URL = '<?= APP_URL ?>';</script>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="rp-alert rp-alert--error mb-4">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- LISTA                                                  -->
<!-- ══════════════════════════════════════════════════════ -->
<?php if ($action === 'list'): ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="rp-display fs-5 mb-0">Platillos <span class="text-muted small">(<?= count($platillos) ?>)</span></h2>
    <a href="?action=nuevo" class="rp-btn-gold btn"><i class="bi bi-plus-lg me-2"></i>Nuevo Platillo</a>
</div>

<div class="rp-form-card">
    <?php if ($platillos): ?>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr><th>#</th><th>Imagen</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($platillos as $p): ?>
                <tr>
                    <td class="text-muted"><?= $p['id'] ?></td>
                    <td>
                        <img src="<?= platilloImg($p['imagen'], $p['nombre']) ?>"
                             alt="" style="width:50px;height:40px;object-fit:cover;border-radius:4px;">
                    </td>
                    <td>
                        <strong><?= h($p['nombre']) ?></strong>
                        <?php if ($p['destacado']): ?>
                        <span class="rp-badge rp-badge--confirmada ms-1" style="font-size:.6rem;">★ Destacado</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($p['cat']) ?></td>
                    <td class="text-gold"><?= formatPrecio((float)$p['precio']) ?></td>
                    <td>
                        <span class="rp-badge rp-badge--<?= $p['disponible'] ? 'confirmada' : 'cancelada' ?>">
                            <?= $p['disponible'] ? 'Disponible' : 'No disponible' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?action=editar&id=<?= $p['id'] ?>" class="btn btn-sm rp-btn-outline me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= APP_URL ?>/admin/platillo_opciones.php?id=<?= $p['id'] ?>"
                           class="btn btn-sm rp-btn-outline me-1" title="Extras & Modificadores">
                            <i class="bi bi-sliders"></i>
                        </a>
                        <a href="?action=delete&id=<?= $p['id'] ?>"
                           class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                           onclick="return confirmDelete('¿Eliminar el platillo <?= h(addslashes($p['nombre'])) ?>?')">
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
        <i class="bi bi-egg-fried text-gold" style="font-size:3rem;opacity:.5"></i>
        <p class="text-muted mt-3">No hay platillos registrados.</p>
        <a href="?action=nuevo" class="rp-btn-gold btn mt-2">Crear el primero</a>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- FORMULARIO (nuevo / editar)                            -->
<!-- ══════════════════════════════════════════════════════ -->
<?php if (in_array($action, ['nuevo','editar'])): ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/platillos.php" class="btn rp-btn-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h2 class="rp-display fs-5 mb-0"><?= $platillo ? 'Editar Platillo' : 'Nuevo Platillo' ?></h2>
</div>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="id"            value="<?= $platillo['id'] ?? '' ?>">
    <input type="hidden" name="imagen_actual" value="<?= h($platillo['imagen'] ?? '') ?>">

    <div class="row gy-4">
        <div class="col-lg-8">
            <div class="rp-form-card">
                <div class="row gy-3">
                    <div class="col-md-8">
                        <label class="rp-form-label">Nombre del platillo *</label>
                        <input type="text" name="nombre" class="rp-form-control form-control" required maxlength="150"
                               value="<?= h($platillo['nombre'] ?? $_POST['nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="rp-form-label">Categoría *</label>
                        <select name="categoria_id" class="rp-form-control form-select" required>
                            <option value="">Seleccionar…</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= ($platillo['categoria_id'] ?? $_POST['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= h($cat['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="rp-form-label">Descripción</label>
                        <textarea name="descripcion" class="rp-form-control form-control" rows="4" maxlength="1000"><?= h($platillo['descripcion'] ?? $_POST['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="rp-form-label">Precio (MXN) *</label>
                        <input type="number" name="precio" class="rp-form-control form-control"
                               step="0.01" min="0.01" required
                               value="<?= $platillo['precio'] ?? $_POST['precio'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="rp-form-label">Orden (en menú)</label>
                        <input type="number" name="orden" class="rp-form-control form-control"
                               min="0" value="<?= $platillo['orden'] ?? 0 ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="disponible" id="chkDisp"
                                   <?= ($platillo['disponible'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted small" for="chkDisp">Disponible</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="destacado" id="chkDest"
                                   <?= ($platillo['destacado'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted small" for="chkDest">Destacado</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rp-form-card">
                <label class="rp-form-label">Imagen del platillo</label>
                <?php if (!empty($platillo['imagen']) && $platillo['imagen'] !== 'placeholder'): ?>
                <img id="imagePreview" src="<?= platilloImg($platillo['imagen'], $platillo['nombre']) ?>"
                     alt="" class="w-100 rounded mb-3" style="aspect-ratio:4/3;object-fit:cover;">
                <?php else: ?>
                <img id="imagePreview" src="" alt="" class="w-100 rounded mb-3" style="display:none;aspect-ratio:4/3;object-fit:cover;">
                <?php endif; ?>
                <input type="file" name="imagen" id="imageInput" accept="image/jpeg,image/png,image/webp"
                       class="rp-form-control form-control">
                <p class="text-muted" style="font-size:.72rem;margin-top:.5rem;">JPG, PNG o WEBP · Máx. 3 MB</p>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="rp-btn-gold btn px-5">
                <i class="bi bi-check-lg me-2"></i><?= $platillo ? 'Guardar Cambios' : 'Crear Platillo' ?>
            </button>
            <a href="<?= APP_URL ?>/admin/platillos.php" class="rp-btn-outline btn ms-2">Cancelar</a>
        </div>
    </div>
</form>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
