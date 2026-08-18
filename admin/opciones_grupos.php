<?php
/**
 * RESTAURANT PREMIUM — Admin: Grupos de Opciones
 * Archivo: admin/opciones_grupos.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Extras & Modificadores';
$db     = db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';
$errors = [];

// ── GUARDAR GRUPO ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'grupo') {
    $nombre    = trim($_POST['nombre']      ?? '');
    $tipo      = $_POST['tipo']             ?? 'extra';
    $desc      = trim($_POST['descripcion'] ?? '');
    $requerido = isset($_POST['requerido']) ? 1 : 0;
    $multiple  = isset($_POST['multiple'])  ? 1 : 0;
    $min       = (int)($_POST['min_sel']    ?? 0);
    $max       = (int)($_POST['max_sel']    ?? 0);
    $activo    = isset($_POST['activo'])    ? 1 : 0;
    $postId    = (int)($_POST['id']         ?? 0);

    $tipos_validos = ['extra','complemento','modificador'];
    if (strlen($nombre) < 2) $errors[] = 'El nombre debe tener al menos 2 caracteres.';
    if (!in_array($tipo, $tipos_validos)) $errors[] = 'Tipo inválido.';

    if (!$errors) {
        if ($postId) {
            $db->prepare('UPDATE opcion_grupos SET nombre=:n,tipo=:t,descripcion=:d,requerido=:r,multiple=:m,min_sel=:min,max_sel=:max,activo=:a WHERE id=:id')
               ->execute([':n'=>$nombre,':t'=>$tipo,':d'=>$desc,':r'=>$requerido,':m'=>$multiple,':min'=>$min,':max'=>$max,':a'=>$activo,':id'=>$postId]);
            $msg = 'Grupo actualizado.';
        } else {
            $db->prepare('INSERT INTO opcion_grupos (nombre,tipo,descripcion,requerido,multiple,min_sel,max_sel,activo) VALUES (:n,:t,:d,:r,:m,:min,:max,:a)')
               ->execute([':n'=>$nombre,':t'=>$tipo,':d'=>$desc,':r'=>$requerido,':m'=>$multiple,':min'=>$min,':max'=>$max,':a'=>$activo]);
            $id = $db->lastInsertId();
            $msg = 'Grupo creado. Ahora agrega las opciones.';
        }
        $action = 'opciones';
    }
}

// ── GUARDAR OPCIÓN ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'opcion') {
    $grupoId    = (int)($_POST['grupo_id'] ?? 0);
    $nombre     = trim($_POST['nombre']    ?? '');
    $desc       = trim($_POST['descripcion'] ?? '');
    $precio     = (float)($_POST['precio'] ?? 0);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    $orden      = (int)($_POST['orden']    ?? 0);
    $opId       = (int)($_POST['opcion_id'] ?? 0);

    if ($opId) {
        $db->prepare('UPDATE opciones SET nombre=:n,descripcion=:d,precio=:p,disponible=:dis,orden=:o WHERE id=:id')
           ->execute([':n'=>$nombre,':d'=>$desc,':p'=>$precio,':dis'=>$disponible,':o'=>$orden,':id'=>$opId]);
    } else {
        $db->prepare('INSERT INTO opciones (grupo_id,nombre,descripcion,precio,disponible,orden) VALUES (:g,:n,:d,:p,:dis,:o)')
           ->execute([':g'=>$grupoId,':n'=>$nombre,':d'=>$desc,':p'=>$precio,':dis'=>$disponible,':o'=>$orden]);
    }
    $id     = $grupoId;
    $action = 'opciones';
    $msg    = 'Opción guardada.';
}

// ── ELIMINAR GRUPO ────────────────────────────────────────
if ($action === 'del_grupo' && $id) {
    $db->prepare('DELETE FROM opcion_grupos WHERE id=:id')->execute([':id'=>$id]);
    header('Location: ' . APP_URL . '/admin/opciones_grupos.php?deleted=1'); exit;
}

// ── ELIMINAR OPCIÓN ───────────────────────────────────────
if ($action === 'del_opcion' && $id) {
    $grupoId = (int)($_GET['grupo'] ?? 0);
    $db->prepare('DELETE FROM opciones WHERE id=:id')->execute([':id'=>$id]);
    header('Location: ' . APP_URL . '/admin/opciones_grupos.php?action=opciones&id=' . $grupoId . '&deleted_op=1'); exit;
}

if (isset($_GET['deleted']))    $msg = 'Grupo eliminado.';
if (isset($_GET['deleted_op'])) $msg = 'Opción eliminada.';

// ── DATOS ─────────────────────────────────────────────────
$grupos = $db->query(
    'SELECT g.*, COUNT(o.id) AS n_opciones
     FROM opcion_grupos g
     LEFT JOIN opciones o ON o.grupo_id = g.id
     GROUP BY g.id ORDER BY g.tipo ASC, g.id ASC'
)->fetchAll();

$grupoActual  = [];
$opcionesGrupo = [];

if (in_array($action, ['opciones','edit_grupo']) && $id) {
    $s = $db->prepare('SELECT * FROM opcion_grupos WHERE id=:id');
    $s->execute([':id'=>$id]);
    $grupoActual = $s->fetch() ?: [];

    if ($action === 'opciones' && $grupoActual) {
        $s2 = $db->prepare('SELECT * FROM opciones WHERE grupo_id=:g ORDER BY orden ASC, id ASC');
        $s2->execute([':g'=>$id]);
        $opcionesGrupo = $s2->fetchAll();
    }
}

$tipoInfo = [
    'extra'        => ['Extra',        'bi-plus-circle-fill',  '#c9a84c', 'Ingredientes o proteínas con costo adicional'],
    'complemento'  => ['Complemento',  'bi-grid-fill',         '#2ea043', 'Guarniciones o acompañamientos opcionales'],
    'modificador'  => ['Modificador',  'bi-sliders',           '#2980b9', 'Instrucciones de preparación (puede ser sin costo)'],
];

require_once __DIR__ . '/includes/sidebar.php';
?>
<style>
.rp-tipo-tag {
    display:inline-flex;align-items:center;gap:.35rem;
    padding:.25rem .75rem;border-radius:100px;font-size:.72rem;font-weight:600;
}
.rp-tipo-extra       { background:rgba(201,168,76,.12); color:#c9a84c; }
.rp-tipo-complemento { background:rgba(46,160,67,.12);  color:#2ea043; }
.rp-tipo-modificador { background:rgba(41,128,185,.12); color:#5dade2; }

.rp-opcion-item {
    display:flex;align-items:center;gap:.75rem;
    padding:.65rem .9rem;
    background:var(--black);
    border:1px solid var(--black-border);
    border-radius:6px;
    transition:border-color .2s;
}
.rp-opcion-item:hover { border-color:rgba(201,168,76,.3); }
</style>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="rp-alert rp-alert--error mb-4"><?php foreach($errors as $e) echo h($e).'<br>'; ?></div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════ -->
<!-- LISTA DE GRUPOS                                        -->
<!-- ══════════════════════════════════════════════════════ -->
<?php if ($action === 'list'): ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="rp-display fs-5 mb-0">Grupos de Opciones</h2>
        <p class="text-muted small mt-1">Extras, complementos y modificadores reutilizables entre platillos</p>
    </div>
    <a href="?action=nuevo_grupo" class="rp-btn-gold btn">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Grupo
    </a>
</div>

<!-- Info tipos -->
<div class="row g-3 mb-4">
    <?php foreach ($tipoInfo as $tipo => [$lbl,$ico,$color,$desc]): ?>
    <div class="col-md-4">
        <div class="rp-form-card p-3">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi <?= $ico ?>" style="color:<?= $color ?>;font-size:1.1rem;"></i>
                <strong class="small"><?= $lbl ?></strong>
            </div>
            <p class="text-muted mb-0" style="font-size:.75rem;"><?= $desc ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="rp-form-card">
    <?php if ($grupos): ?>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr><th>Grupo</th><th>Tipo</th><th>Opciones</th><th>Requerido</th><th>Múltiple</th><th>Activo</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $g): ?>
                <tr>
                    <td>
                        <strong><?= h($g['nombre']) ?></strong>
                        <?php if ($g['descripcion']): ?>
                        <br><small class="text-muted"><?= h(mb_substr($g['descripcion'],0,50)) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="rp-tipo-tag rp-tipo-<?= $g['tipo'] ?>">
                            <i class="bi <?= $tipoInfo[$g['tipo']][1] ?>"></i>
                            <?= $tipoInfo[$g['tipo']][0] ?>
                        </span>
                    </td>
                    <td>
                        <a href="?action=opciones&id=<?= $g['id'] ?>" class="text-gold fw-semibold">
                            <?= $g['n_opciones'] ?> opciones
                        </a>
                    </td>
                    <td><?= $g['requerido'] ? '<i class="bi bi-check-circle-fill text-gold"></i>' : '<i class="bi bi-circle text-muted"></i>' ?></td>
                    <td><?= $g['multiple']  ? '<i class="bi bi-check-circle-fill text-gold"></i>' : '<i class="bi bi-circle text-muted"></i>' ?></td>
                    <td><span class="rp-badge rp-badge--<?= $g['activo']?'confirmada':'cancelada' ?>"><?= $g['activo']?'Sí':'No' ?></span></td>
                    <td>
                        <a href="?action=opciones&id=<?= $g['id'] ?>"   class="btn btn-sm rp-btn-outline me-1" title="Opciones"><i class="bi bi-list-ul"></i></a>
                        <a href="?action=edit_grupo&id=<?= $g['id'] ?>" class="btn btn-sm rp-btn-outline me-1" title="Editar"><i class="bi bi-pencil"></i></a>
                        <a href="?action=del_grupo&id=<?= $g['id'] ?>"
                           class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                           onclick="return confirmDelete('¿Eliminar grupo y todas sus opciones?')">
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
        <i class="bi bi-collection text-gold" style="font-size:3rem;opacity:.4"></i>
        <p class="text-muted mt-3">No hay grupos creados.</p>
        <a href="?action=nuevo_grupo" class="rp-btn-gold btn mt-2">Crear primer grupo</a>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- NUEVO / EDITAR GRUPO                                   -->
<!-- ══════════════════════════════════════════════════════ -->
<?php elseif (in_array($action, ['nuevo_grupo','edit_grupo'])): ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/opciones_grupos.php" class="btn rp-btn-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h2 class="rp-display fs-5 mb-0"><?= $grupoActual ? 'Editar Grupo' : 'Nuevo Grupo de Opciones' ?></h2>
</div>

<form method="POST" class="rp-form-card" style="max-width:620px;">
    <input type="hidden" name="form" value="grupo">
    <input type="hidden" name="id"   value="<?= $grupoActual['id'] ?? '' ?>">
    <div class="row gy-3">
        <div class="col-md-8">
            <label class="rp-form-label">Nombre del grupo *</label>
            <input type="text" name="nombre" class="rp-form-control form-control" required
                   placeholder="Ej: Guarniciones, Término de carne…"
                   value="<?= h($grupoActual['nombre'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="rp-form-label">Tipo *</label>
            <select name="tipo" class="rp-form-control form-select" required>
                <?php foreach ($tipoInfo as $val => [$lbl]): ?>
                <option value="<?= $val ?>" <?= ($grupoActual['tipo'] ?? 'extra') === $val ? 'selected' : '' ?>>
                    <?= $lbl ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="rp-form-label">Descripción (para el cliente)</label>
            <input type="text" name="descripcion" class="rp-form-control form-control" maxlength="255"
                   placeholder="Ej: Elige tu acompañamiento favorito"
                   value="<?= h($grupoActual['descripcion'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="rp-form-label">Mín. selecciones</label>
            <input type="number" name="min_sel" class="rp-form-control form-control" min="0"
                   value="<?= $grupoActual['min_sel'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
            <label class="rp-form-label">Máx. selecciones</label>
            <input type="number" name="max_sel" class="rp-form-control form-control" min="0"
                   value="<?= $grupoActual['max_sel'] ?? 0 ?>">
            <p class="text-muted" style="font-size:.7rem;margin-top:.3rem;">0 = sin límite</p>
        </div>
        <div class="col-md-4 d-flex flex-column gap-2 justify-content-center pt-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="requerido" id="chkReq"
                       <?= ($grupoActual['requerido'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label text-muted small" for="chkReq">Selección requerida</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="multiple" id="chkMul"
                       <?= ($grupoActual['multiple'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label text-muted small" for="chkMul">Permite múltiples</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="activo" id="chkAct"
                       <?= ($grupoActual['activo'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label text-muted small" for="chkAct">Activo</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="rp-btn-gold btn px-5">
                <i class="bi bi-check-lg me-2"></i><?= $grupoActual ? 'Guardar' : 'Crear Grupo' ?>
            </button>
        </div>
    </div>
</form>

<!-- ══════════════════════════════════════════════════════ -->
<!-- OPCIONES DE UN GRUPO                                   -->
<!-- ══════════════════════════════════════════════════════ -->
<?php elseif ($action === 'opciones' && $grupoActual): ?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="<?= APP_URL ?>/admin/opciones_grupos.php" class="btn rp-btn-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Grupos
    </a>
    <div>
        <h2 class="rp-display fs-5 mb-0"><?= h($grupoActual['nombre']) ?></h2>
        <span class="rp-tipo-tag rp-tipo-<?= $grupoActual['tipo'] ?> mt-1 d-inline-flex">
            <i class="bi <?= $tipoInfo[$grupoActual['tipo']][1] ?>"></i>
            <?= $tipoInfo[$grupoActual['tipo']][0] ?>
        </span>
    </div>
</div>

<div class="row gy-4">
    <!-- Lista de opciones -->
    <div class="col-lg-7">
        <div class="rp-form-card">
            <p class="rp-display fs-6 mb-3">Opciones disponibles (<?= count($opcionesGrupo) ?>)</p>
            <?php if ($opcionesGrupo): ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($opcionesGrupo as $op): ?>
                <div class="rp-opcion-item">
                    <div class="flex-grow-1">
                        <p class="fw-semibold small mb-0" style="color:var(--white);"><?= h($op['nombre']) ?></p>
                        <?php if ($op['descripcion']): ?>
                        <p class="text-muted mb-0" style="font-size:.72rem;"><?= h($op['descripcion']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-end me-2">
                        <?php if ($op['precio'] > 0): ?>
                        <span class="text-gold fw-semibold small">+$<?= number_format((float)$op['precio'],2) ?></span>
                        <?php else: ?>
                        <span class="text-muted small">Sin costo</span>
                        <?php endif; ?>
                    </div>
                    <span class="rp-badge rp-badge--<?= $op['disponible']?'confirmada':'cancelada' ?>" style="font-size:.62rem;">
                        <?= $op['disponible']?'Activa':'Inactiva' ?>
                    </span>
                    <div class="d-flex gap-1 ms-1">
                        <button class="btn btn-sm rp-btn-outline p-1 px-2"
                                onclick="editarOpcion(<?= htmlspecialchars(json_encode($op), ENT_QUOTES) ?>)"
                                title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="?action=del_opcion&id=<?= $op['id'] ?>&grupo=<?= $id ?>"
                           class="btn btn-sm p-1 px-2"
                           style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                           onclick="return confirmDelete('¿Eliminar esta opción?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-collection text-gold" style="font-size:2rem;opacity:.3"></i>
                <p class="text-muted small mt-2">Sin opciones. Agrégalas desde el formulario.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulario nueva opción -->
    <div class="col-lg-5">
        <div class="rp-form-card">
            <p class="rp-display fs-6 mb-3" id="formTitle">Agregar Opción</p>
            <form method="POST" id="opcionForm">
                <input type="hidden" name="form"      value="opcion">
                <input type="hidden" name="grupo_id"  value="<?= $id ?>">
                <input type="hidden" name="opcion_id" id="opcionId" value="">
                <div class="row gy-3">
                    <div class="col-12">
                        <label class="rp-form-label">Nombre *</label>
                        <input type="text" name="nombre" id="opNombre" class="rp-form-control form-control"
                               required placeholder="Ej: Papas fritas con trufa">
                    </div>
                    <div class="col-12">
                        <label class="rp-form-label">Descripción (opcional)</label>
                        <input type="text" name="descripcion" id="opDesc" class="rp-form-control form-control"
                               placeholder="Detalle adicional para el cliente" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="rp-form-label">Precio adicional (MXN)</label>
                        <input type="number" name="precio" id="opPrecio" class="rp-form-control form-control"
                               step="0.01" min="0" value="0"
                               placeholder="0.00 = sin costo">
                    </div>
                    <div class="col-md-3">
                        <label class="rp-form-label">Orden</label>
                        <input type="number" name="orden" id="opOrden" class="rp-form-control form-control" min="0" value="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-end pb-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="disponible" id="opDisp" checked>
                            <label class="form-check-label text-muted small" for="opDisp">Activa</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="rp-btn-gold btn flex-fill" id="btnGuardar">
                            <i class="bi bi-plus-lg me-1"></i>Agregar
                        </button>
                        <button type="button" class="rp-btn-outline btn" id="btnCancelar" style="display:none;"
                                onclick="resetForm()">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarOpcion(op) {
    document.getElementById('opcionId').value    = op.id;
    document.getElementById('opNombre').value    = op.nombre;
    document.getElementById('opDesc').value      = op.descripcion || '';
    document.getElementById('opPrecio').value    = op.precio;
    document.getElementById('opOrden').value     = op.orden;
    document.getElementById('opDisp').checked    = op.disponible == 1;
    document.getElementById('formTitle').textContent = 'Editar Opción';
    document.getElementById('btnGuardar').innerHTML  = '<i class="bi bi-check-lg me-1"></i>Guardar Cambios';
    document.getElementById('btnCancelar').style.display = '';
    document.getElementById('opcionForm').scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function resetForm() {
    document.getElementById('opcionForm').reset();
    document.getElementById('opcionId').value    = '';
    document.getElementById('formTitle').textContent = 'Agregar Opción';
    document.getElementById('btnGuardar').innerHTML  = '<i class="bi bi-plus-lg me-1"></i>Agregar';
    document.getElementById('btnCancelar').style.display = 'none';
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
