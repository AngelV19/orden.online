<?php
/**
 * RESTAURANT PREMIUM — Admin: Usuarios
 * Archivo: admin/usuarios.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Solo admins pueden gestionar usuarios
if ($_SESSION['admin_rol'] !== 'admin') {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$adminTitle = 'Usuarios';
$db   = db();
$msg  = '';
$err  = '';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── GUARDAR ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = sanitizeEmail($_POST['email'] ?? '');
    $rol    = in_array($_POST['rol'] ?? '', ['admin','editor']) ? $_POST['rol'] : 'editor';
    $activo = isset($_POST['activo']) ? 1 : 0;
    $postId = (int)($_POST['id'] ?? 0);
    $pass   = $_POST['password'] ?? '';

    if (!$nombre || !$email) { $err = 'Nombre y email son requeridos.'; }
    elseif (!$postId && strlen($pass) < 8) { $err = 'La contraseña debe tener al menos 8 caracteres.'; }
    else {
        if ($postId) {
            if ($pass) {
                $db->prepare('UPDATE usuarios SET nombre=:n,email=:e,rol=:r,activo=:a,password=:p WHERE id=:id')
                   ->execute([':n'=>$nombre,':e'=>$email,':r'=>$rol,':a'=>$activo,':p'=>password_hash($pass,PASSWORD_DEFAULT),':id'=>$postId]);
            } else {
                $db->prepare('UPDATE usuarios SET nombre=:n,email=:e,rol=:r,activo=:a WHERE id=:id')
                   ->execute([':n'=>$nombre,':e'=>$email,':r'=>$rol,':a'=>$activo,':id'=>$postId]);
            }
            $msg = 'Usuario actualizado.';
        } else {
            $db->prepare('INSERT INTO usuarios (nombre,email,password,rol,activo) VALUES (:n,:e,:p,:r,:a)')
               ->execute([':n'=>$nombre,':e'=>$email,':p'=>password_hash($pass,PASSWORD_DEFAULT),':r'=>$rol,':a'=>$activo]);
            $msg = 'Usuario creado.';
        }
        $action = 'list';
    }
}

// ── ELIMINAR ──────────────────────────────────────────────
if ($action === 'delete' && $id) {
    if ($id == $_SESSION['admin_id']) { $msg = 'No puedes eliminarte a ti mismo.'; $action = 'list'; }
    else { $db->prepare('DELETE FROM usuarios WHERE id=:id')->execute([':id'=>$id]); header('Location: '.$_SERVER['PHP_SELF'].'?deleted=1'); exit; }
}

if (isset($_GET['deleted'])) $msg = 'Usuario eliminado.';

$usuarios = $db->query('SELECT * FROM usuarios ORDER BY id ASC')->fetchAll();
$usuario  = [];

if (in_array($action, ['editar']) && $id) {
    $s = $db->prepare('SELECT * FROM usuarios WHERE id=:id');
    $s->execute([':id'=>$id]);
    $usuario = $s->fetch() ?: [];
}

require_once __DIR__ . '/includes/sidebar.php';
?>

<?php if ($msg): ?><div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err):  ?><div class="rp-alert rp-alert--error mb-4"><?= h($err) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="rp-display fs-5 mb-0">Usuarios Admin</h2>
    <a href="?action=nuevo" class="rp-btn-gold btn"><i class="bi bi-plus-lg me-2"></i>Nuevo Usuario</a>
</div>

<div class="rp-form-card">
    <table class="rp-table">
        <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Último acceso</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td class="text-muted"><?= $u['id'] ?></td>
                <td><strong><?= h($u['nombre']) ?></strong></td>
                <td><small><?= h($u['email']) ?></small></td>
                <td><span class="rp-badge rp-badge--<?= $u['rol']==='admin'?'confirmada':'pendiente' ?>"><?= ucfirst($u['rol']) ?></span></td>
                <td><small class="text-muted"><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?></small></td>
                <td><span class="rp-badge rp-badge--<?= $u['activo']?'confirmada':'cancelada' ?>"><?= $u['activo']?'Sí':'No' ?></span></td>
                <td>
                    <a href="?action=editar&id=<?= $u['id'] ?>" class="btn btn-sm rp-btn-outline me-1"><i class="bi bi-pencil"></i></a>
                    <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                    <a href="?action=delete&id=<?= $u['id'] ?>"
                       class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                       onclick="return confirmDelete('¿Eliminar usuario <?= h(addslashes($u['nombre'])) ?>?')">
                        <i class="bi bi-trash"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/usuarios.php" class="btn rp-btn-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    <h2 class="rp-display fs-5 mb-0"><?= $usuario ? 'Editar Usuario' : 'Nuevo Usuario' ?></h2>
</div>

<form method="POST" action="" class="rp-form-card" style="max-width:540px;">
    <input type="hidden" name="id" value="<?= $usuario['id'] ?? '' ?>">
    <div class="row gy-3">
        <div class="col-12">
            <label class="rp-form-label">Nombre completo *</label>
            <input type="text" name="nombre" class="rp-form-control form-control" required
                   value="<?= h($usuario['nombre'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="rp-form-label">Correo electrónico *</label>
            <input type="email" name="email" class="rp-form-control form-control" required
                   value="<?= h($usuario['email'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="rp-form-label">Contraseña <?= $usuario ? '(dejar vacío para no cambiar)' : '*' ?></label>
            <input type="password" name="password" class="rp-form-control form-control"
                   <?= $usuario ? '' : 'required' ?> minlength="8" placeholder="Mínimo 8 caracteres">
        </div>
        <div class="col-md-6">
            <label class="rp-form-label">Rol</label>
            <select name="rol" class="rp-form-control form-select">
                <option value="editor" <?= ($usuario['rol']??'editor')==='editor'?'selected':'' ?>>Editor</option>
                <option value="admin"  <?= ($usuario['rol']??'')==='admin'?'selected':'' ?>>Administrador</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="activo" id="chkA"
                       <?= ($usuario['activo']??1)?'checked':'' ?>>
                <label class="form-check-label text-muted" for="chkA">Usuario activo</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="rp-btn-gold btn px-5">
                <i class="bi bi-check-lg me-2"></i><?= $usuario?'Guardar':'Crear Usuario' ?>
            </button>
        </div>
    </div>
</form>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
