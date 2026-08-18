<?php
/**
 * RESTAURANT PREMIUM — Admin: Galería
 * Archivo: admin/galeria.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Galería';
$db     = db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$errors = [];

// ── GUARDAR (nuevo / editar) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']      ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden       = (int)($_POST['orden']      ?? 0);
    $activo      = isset($_POST['activo'])    ? 1 : 0;
    $postId      = (int)($_POST['id']         ?? 0);

    // Imagen
    $imagenNombre = $_POST['imagen_actual'] ?? null;
    if (!empty($_FILES['imagen']['name'])) {
        $subida = subirImagen($_FILES['imagen'], 'galeria/');
        if ($subida) $imagenNombre = 'galeria/' . $subida;
        else         $errors[] = 'Error al subir imagen (máx. 3MB, JPG/PNG/WEBP).';
    }

    if (!$postId && !$imagenNombre) {
        $errors[] = 'La imagen es obligatoria para una nueva foto.';
    }

    if (!$errors) {
        if ($postId) {
            $db->prepare(
                'UPDATE galeria SET titulo=:t, descripcion=:d, imagen=:i, orden=:o, activo=:a WHERE id=:id'
            )->execute([':t'=>$titulo,':d'=>$descripcion,':i'=>$imagenNombre,':o'=>$orden,':a'=>$activo,':id'=>$postId]);
            $msg = 'Foto actualizada correctamente.';
        } else {
            $db->prepare(
                'INSERT INTO galeria (titulo, descripcion, imagen, orden, activo) VALUES (:t,:d,:i,:o,:a)'
            )->execute([':t'=>$titulo,':d'=>$descripcion,':i'=>$imagenNombre,':o'=>$orden,':a'=>$activo]);
            $msg = 'Foto agregada a la galería.';
        }
        $action = 'list';
    } else {
        $action = $postId ? 'editar' : 'nuevo';
        $id     = $postId;
    }
}

// ── ELIMINAR ──────────────────────────────────────────────
if ($action === 'delete' && $id) {
    // Obtener imagen para borrarla del disco
    $s = $db->prepare('SELECT imagen FROM galeria WHERE id=:id');
    $s->execute([':id' => $id]);
    $row = $s->fetch();
    if ($row && $row['imagen']) {
        $path = __DIR__ . '/../uploads/' . $row['imagen'];
        if (file_exists($path)) unlink($path);
    }
    $db->prepare('DELETE FROM galeria WHERE id=:id')->execute([':id' => $id]);
    header('Location: ' . APP_URL . '/admin/galeria.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) $msg = 'Foto eliminada de la galería.';

// ── DATOS para formulario ─────────────────────────────────
$foto = [];
if ($action === 'editar' && $id) {
    $s = $db->prepare('SELECT * FROM galeria WHERE id=:id');
    $s->execute([':id' => $id]);
    $foto = $s->fetch() ?: [];
    if (!$foto) $action = 'list';
}

// ── LISTA ─────────────────────────────────────────────────
$fotos = [];
if ($action === 'list') {
    $fotos = $db->query('SELECT * FROM galeria ORDER BY orden ASC, id ASC')->fetchAll();
}

require_once __DIR__ . '/includes/sidebar.php';
?>

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
    <h2 class="rp-display fs-5 mb-0">Galería <span class="text-muted small">(<?= count($fotos) ?> fotos)</span></h2>
    <a href="?action=nuevo" class="rp-btn-gold btn"><i class="bi bi-plus-lg me-2"></i>Agregar Foto</a>
</div>

<?php if ($fotos): ?>
<!-- Vista en grid tipo Instagram -->
<div class="row gy-3 mb-4">
    <?php foreach ($fotos as $f): ?>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="rp-form-card p-0 overflow-hidden" style="border-radius:10px;">
            <!-- Imagen -->
            <div style="position:relative;aspect-ratio:1;overflow:hidden;">
                <?php
                $imgPath = __DIR__ . '/../uploads/' . $f['imagen'];
                $imgUrl  = file_exists($imgPath)
                    ? APP_URL . '/uploads/' . h($f['imagen'])
                    : platilloImg(null, $f['titulo'] ?? '?');
                ?>
                <img src="<?= $imgUrl ?>" alt="<?= h($f['titulo'] ?? '') ?>"
                     style="width:100%;height:100%;object-fit:cover;">
                <!-- Overlay con estado -->
                <?php if (!$f['activo']): ?>
                <div style="position:absolute;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;">
                    <span class="rp-badge rp-badge--cancelada">Oculta</span>
                </div>
                <?php endif; ?>
            </div>
            <!-- Info + acciones -->
            <div style="padding:.75rem;">
                <p class="mb-0 small fw-semibold text-truncate" style="color:var(--white);">
                    <?= $f['titulo'] ? h($f['titulo']) : '<span class="text-muted">Sin título</span>' ?>
                </p>
                <p class="mb-2 text-muted" style="font-size:.7rem;">Orden: <?= $f['orden'] ?></p>
                <div class="d-flex gap-1">
                    <a href="?action=editar&id=<?= $f['id'] ?>"
                       class="btn btn-sm rp-btn-outline flex-fill text-center">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <!-- Toggle activo/oculto -->
                    <a href="?action=toggle&id=<?= $f['id'] ?>"
                       class="btn btn-sm flex-fill text-center"
                       style="background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.2);color:var(--gold);"
                       title="<?= $f['activo'] ? 'Ocultar' : 'Mostrar' ?>">
                        <i class="bi bi-eye<?= $f['activo'] ? '-slash' : '' ?>"></i>
                    </a>
                    <a href="?action=delete&id=<?= $f['id'] ?>"
                       class="btn btn-sm flex-fill text-center"
                       style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                       onclick="return confirmDelete('¿Eliminar esta foto de la galería?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabla de orden -->
<div class="rp-form-card">
    <h3 class="rp-display fs-6 mb-3">Vista de tabla</h3>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr><th>#</th><th>Preview</th><th>Título</th><th>Descripción</th><th>Orden</th><th>Visible</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fotos as $f):
                    $imgPath = __DIR__ . '/../uploads/' . $f['imagen'];
                    $imgUrl  = file_exists($imgPath)
                        ? APP_URL . '/uploads/' . h($f['imagen'])
                        : platilloImg(null, $f['titulo'] ?? '?');
                ?>
                <tr>
                    <td class="text-muted"><?= $f['id'] ?></td>
                    <td>
                        <img src="<?= $imgUrl ?>" alt=""
                             style="width:60px;height:45px;object-fit:cover;border-radius:4px;">
                    </td>
                    <td><strong><?= h($f['titulo'] ?? '—') ?></strong></td>
                    <td><small class="text-muted"><?= $f['descripcion'] ? h(mb_substr($f['descripcion'],0,50)).'…' : '—' ?></small></td>
                    <td><?= $f['orden'] ?></td>
                    <td><span class="rp-badge rp-badge--<?= $f['activo'] ? 'confirmada' : 'cancelada' ?>"><?= $f['activo'] ? 'Sí' : 'No' ?></span></td>
                    <td>
                        <a href="?action=editar&id=<?= $f['id'] ?>" class="btn btn-sm rp-btn-outline me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="?action=delete&id=<?= $f['id'] ?>"
                           class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                           onclick="return confirmDelete('¿Eliminar esta foto?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="rp-form-card text-center py-5">
    <i class="bi bi-images text-gold" style="font-size:3.5rem;opacity:.4"></i>
    <p class="text-muted mt-3">La galería está vacía.</p>
    <a href="?action=nuevo" class="rp-btn-gold btn mt-2">
        <i class="bi bi-plus-lg me-2"></i>Agregar primera foto
    </a>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- TOGGLE visibilidad                                     -->
<!-- ══════════════════════════════════════════════════════ -->
<?php
if ($action === 'toggle' && $id) {
    $db->prepare('UPDATE galeria SET activo = IF(activo=1,0,1) WHERE id=:id')->execute([':id'=>$id]);
    header('Location: ' . APP_URL . '/admin/galeria.php?toggled=1');
    exit;
}
if (isset($_GET['toggled'])) {
    // ya está en list, solo mostrar mensaje (manejado arriba si recargamos)
}
?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- FORMULARIO (nuevo / editar)                            -->
<!-- ══════════════════════════════════════════════════════ -->
<?php if (in_array($action, ['nuevo','editar'])): ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/galeria.php" class="btn rp-btn-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h2 class="rp-display fs-5 mb-0"><?= $foto ? 'Editar Foto' : 'Agregar Foto' ?></h2>
</div>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="id"            value="<?= $foto['id'] ?? '' ?>">
    <input type="hidden" name="imagen_actual" value="<?= h($foto['imagen'] ?? '') ?>">

    <div class="row gy-4">
        <!-- Columna imagen -->
        <div class="col-lg-5">
            <div class="rp-form-card text-center">
                <label class="rp-form-label d-block mb-3">Imagen <?= $foto ? '' : '*' ?></label>

                <?php if (!empty($foto['imagen'])):
                    $imgPath = __DIR__ . '/../uploads/' . $foto['imagen'];
                    $imgUrl  = file_exists($imgPath) ? APP_URL . '/uploads/' . h($foto['imagen']) : '';
                ?>
                <img id="imagePreview" src="<?= $imgUrl ?>" alt=""
                     style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;margin-bottom:1rem;">
                <?php else: ?>
                <div id="imagePlaceholder"
                     style="width:100%;aspect-ratio:4/3;background:var(--black);border:2px dashed var(--black-border);border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;margin-bottom:1rem;cursor:pointer;"
                     onclick="document.getElementById('imageInput').click()">
                    <i class="bi bi-cloud-arrow-up text-gold" style="font-size:2.5rem;opacity:.6"></i>
                    <p class="text-muted small mt-2 mb-0">Haz clic para seleccionar</p>
                    <p class="text-muted" style="font-size:.7rem;">JPG, PNG, WEBP · Máx 3MB</p>
                </div>
                <img id="imagePreview" src="" alt=""
                     style="display:none;width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;margin-bottom:1rem;">
                <?php endif; ?>

                <input type="file" name="imagen" id="imageInput"
                       accept="image/jpeg,image/png,image/webp"
                       class="rp-form-control form-control">
                <p class="text-muted mt-2 mb-0" style="font-size:.72rem;">
                    <?= $foto ? 'Deja vacío para conservar la imagen actual.' : '' ?>
                </p>
            </div>
        </div>

        <!-- Columna datos -->
        <div class="col-lg-7">
            <div class="rp-form-card">
                <div class="row gy-3">
                    <div class="col-12">
                        <label class="rp-form-label">Título</label>
                        <input type="text" name="titulo" class="rp-form-control form-control"
                               placeholder="Ej: Salón Principal, Terraza, Cocina…"
                               maxlength="150"
                               value="<?= h($foto['titulo'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="rp-form-label">Descripción (opcional)</label>
                        <textarea name="descripcion" class="rp-form-control form-control"
                                  rows="3" maxlength="500"
                                  placeholder="Breve descripción de la foto…"><?= h($foto['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="rp-form-label">Orden de aparición</label>
                        <input type="number" name="orden" class="rp-form-control form-control"
                               min="0" value="<?= $foto['orden'] ?? 0 ?>">
                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                            Número más bajo = aparece primero
                        </p>
                    </div>
                    <div class="col-md-6 d-flex align-items-end pb-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" id="chkActivo"
                                   <?= ($foto['activo'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted" for="chkActivo">
                                Visible en la galería
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="rp-btn-gold btn px-5">
                    <i class="bi bi-check-lg me-2"></i><?= $foto ? 'Guardar Cambios' : 'Agregar a Galería' ?>
                </button>
                <a href="<?= APP_URL ?>/admin/galeria.php" class="rp-btn-outline btn ms-2">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</form>

<script>
// Preview al seleccionar imagen
document.getElementById('imageInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('imagePreview');
        const ph   = document.getElementById('imagePlaceholder');
        prev.src = e.target.result;
        prev.style.display = '';
        if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
