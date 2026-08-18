<?php
/**
 * RESTAURANT PREMIUM — Admin: Asignar Opciones a Platillos
 * Archivo: admin/platillo_opciones.php
 * Accesible desde el listado de platillos
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Opciones del Platillo';
$db  = db();
$id  = (int)($_GET['id'] ?? 0);
$msg = '';

if (!$id) { header('Location: ' . APP_URL . '/admin/platillos.php'); exit; }

// Datos del platillo
$s = $db->prepare('SELECT * FROM platillos WHERE id=:id');
$s->execute([':id' => $id]);
$platillo = $s->fetch();
if (!$platillo) { header('Location: ' . APP_URL . '/admin/platillos.php'); exit; }

// ── GUARDAR asignaciones ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Borrar asignaciones actuales
    $db->prepare('DELETE FROM platillo_opciones WHERE platillo_id=:id')->execute([':id'=>$id]);

    // Insertar las seleccionadas
    $grupos = $_POST['grupos'] ?? [];
    foreach ($grupos as $orden => $grupoId) {
        $grupoId = (int)$grupoId;
        if ($grupoId > 0) {
            $db->prepare('INSERT IGNORE INTO platillo_opciones (platillo_id,grupo_id,orden) VALUES (:p,:g,:o)')
               ->execute([':p'=>$id,':g'=>$grupoId,':o'=>(int)$orden]);
        }
    }
    $msg = 'Opciones actualizadas correctamente.';
}

// Grupos asignados a este platillo
$asignados = $db->prepare(
    'SELECT po.grupo_id, po.orden FROM platillo_opciones po WHERE po.platillo_id=:id ORDER BY po.orden ASC'
);
$asignados->execute([':id'=>$id]);
$asignadosMap = [];
foreach ($asignados->fetchAll() as $a) $asignadosMap[$a['grupo_id']] = $a['orden'];

// Todos los grupos disponibles
$todosGrupos = $db->query(
    'SELECT g.*, COUNT(o.id) AS n_opciones
     FROM opcion_grupos g
     LEFT JOIN opciones o ON o.grupo_id=g.id AND o.disponible=1
     WHERE g.activo=1
     GROUP BY g.id ORDER BY g.tipo ASC, g.id ASC'
)->fetchAll();

$tipoInfo = [
    'extra'        => ['Extra',        'bi-plus-circle-fill',  'rp-tipo-extra'],
    'complemento'  => ['Complemento',  'bi-grid-fill',         'rp-tipo-complemento'],
    'modificador'  => ['Modificador',  'bi-sliders',           'rp-tipo-modificador'],
];

require_once __DIR__ . '/includes/sidebar.php';
?>
<style>
.rp-tipo-extra       { background:rgba(201,168,76,.12);color:#c9a84c; }
.rp-tipo-complemento { background:rgba(46,160,67,.12); color:#2ea043; }
.rp-tipo-modificador { background:rgba(41,128,185,.12);color:#5dade2; }
.rp-tipo-tag { display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .65rem;border-radius:100px;font-size:.7rem;font-weight:600; }

.rp-grupo-card {
    background:var(--black);
    border:2px solid var(--black-border);
    border-radius:8px;
    padding:1rem;
    cursor:pointer;
    transition:all .2s;
}
.rp-grupo-card:hover { border-color:rgba(201,168,76,.3); }
.rp-grupo-card.selected { border-color:var(--gold); background:rgba(201,168,76,.06); }
.rp-grupo-card input[type=checkbox] { display:none; }
</style>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/platillos.php" class="btn rp-btn-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Platillos
    </a>
    <div>
        <h2 class="rp-display fs-5 mb-0">Opciones: <?= h($platillo['nombre']) ?></h2>
        <p class="text-muted small mt-1 mb-0">Selecciona los grupos de extras, complementos y modificadores para este platillo</p>
    </div>
</div>

<div class="row gy-4">
    <div class="col-lg-8">
        <form method="POST" id="opcionesForm">

        <?php
        $tiposAgrupados = ['extra'=>[], 'complemento'=>[], 'modificador'=>[]];
        foreach ($todosGrupos as $g) $tiposAgrupados[$g['tipo']][] = $g;
        $orden = 0;
        ?>

        <?php foreach ($tiposAgrupados as $tipo => $gruposDelTipo): ?>
        <?php if ($gruposDelTipo): ?>
        <div class="rp-form-card mb-3">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi <?= $tipoInfo[$tipo][1] ?>" style="font-size:1.1rem;color:var(--gold);"></i>
                <h3 class="rp-display fs-6 mb-0"><?= $tipoInfo[$tipo][0] ?>s</h3>
                <span class="text-muted small">(selecciona los que aplican a este platillo)</span>
            </div>
            <div class="row g-2">
                <?php foreach ($gruposDelTipo as $g):
                    $isSelected = isset($asignadosMap[$g['id']]);
                    $orden++;
                ?>
                <div class="col-md-6">
                    <label>
                        <input type="checkbox"
                               name="grupos[<?= $orden ?>]"
                               value="<?= $g['id'] ?>"
                               <?= $isSelected ? 'checked' : '' ?>
                               onchange="toggleCard(this)">
                        <div class="rp-grupo-card <?= $isSelected ? 'selected' : '' ?>">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div>
                                    <p class="fw-semibold small mb-1" style="color:var(--white);"><?= h($g['nombre']) ?></p>
                                    <?php if ($g['descripcion']): ?>
                                    <p class="text-muted mb-1" style="font-size:.72rem;"><?= h($g['descripcion']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex gap-1 flex-wrap mt-1">
                                        <span class="rp-tipo-tag <?= $tipoInfo[$tipo][2] ?>">
                                            <?= $tipoInfo[$tipo][0] ?>
                                        </span>
                                        <span class="rp-tipo-tag" style="background:rgba(255,255,255,.06);color:var(--white-dim);">
                                            <i class="bi bi-list-ul"></i> <?= $g['n_opciones'] ?> opciones
                                        </span>
                                        <?php if ($g['requerido']): ?>
                                        <span class="rp-tipo-tag" style="background:rgba(224,92,92,.1);color:#e05c5c;">Requerido</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <div id="check_<?= $g['id'] ?>"
                                         style="width:22px;height:22px;border-radius:50%;border:2px solid <?= $isSelected ? 'var(--gold)' : 'var(--black-border)' ?>;
                                                background:<?= $isSelected ? 'var(--gold)' : 'transparent' ?>;
                                                display:flex;align-items:center;justify-content:center;transition:all .2s;">
                                        <?php if ($isSelected): ?>
                                        <i class="bi bi-check" style="color:var(--black);font-size:.75rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="d-flex gap-2">
            <button type="submit" class="rp-btn-gold btn px-5">
                <i class="bi bi-check-lg me-2"></i>Guardar Configuración
            </button>
            <a href="<?= APP_URL ?>/admin/platillos.php" class="rp-btn-outline btn px-4">
                Cancelar
            </a>
        </div>
        </form>
    </div>

    <!-- Preview del platillo -->
    <div class="col-lg-4">
        <div class="rp-form-card" style="position:sticky;top:80px;">
            <h3 class="rp-display fs-6 mb-3">Platillo</h3>
            <img src="<?= platilloImg($platillo['imagen'], $platillo['nombre']) ?>"
                 alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:.75rem;">
            <p class="fw-semibold mb-1"><?= h($platillo['nombre']) ?></p>
            <p class="text-gold fw-bold mb-0">$<?= number_format((float)$platillo['precio'],2) ?></p>
            <p class="text-muted small mt-2 mb-0"><?= h(mb_substr($platillo['descripcion'],0,80)) ?>…</p>

            <hr class="border-rp my-3">

            <div id="resumenOpciones">
                <p class="text-muted small mb-2">Grupos seleccionados:</p>
                <!-- Poblado por JS -->
            </div>

            <a href="<?= APP_URL ?>/admin/opciones_grupos.php" class="rp-btn-outline btn w-100 mt-2 btn-sm">
                <i class="bi bi-plus-lg me-2"></i>Crear nuevo grupo
            </a>
        </div>
    </div>
</div>

<script>
function toggleCard(checkbox) {
    const card  = checkbox.closest('label').querySelector('.rp-grupo-card');
    const check = document.getElementById('check_' + checkbox.value);
    if (checkbox.checked) {
        card.classList.add('selected');
        check.style.background    = 'var(--gold)';
        check.style.borderColor   = 'var(--gold)';
        check.innerHTML           = '<i class="bi bi-check" style="color:var(--black);font-size:.75rem;"></i>';
    } else {
        card.classList.remove('selected');
        check.style.background    = 'transparent';
        check.style.borderColor   = 'var(--black-border)';
        check.innerHTML           = '';
    }
    updateResumen();
}

function updateResumen() {
    const checked = document.querySelectorAll('#opcionesForm input[type=checkbox]:checked');
    const el = document.getElementById('resumenOpciones');
    if (!checked.length) {
        el.innerHTML = '<p class="text-muted small">Ningún grupo seleccionado</p>';
        return;
    }
    let html = '<p class="text-muted small mb-2">Grupos seleccionados (' + checked.length + '):</p>';
    checked.forEach(cb => {
        const nombre = cb.closest('label').querySelector('.fw-semibold').textContent.trim();
        html += `<div class="d-flex align-items-center gap-1 mb-1">
            <i class="bi bi-check-circle-fill text-gold" style="font-size:.75rem;"></i>
            <span class="small" style="color:var(--white);">${nombre}</span>
        </div>`;
    });
    el.innerHTML = html;
}

// Inicializar resumen
updateResumen();
</script>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
