<?php
/**
 * RESTAURANT PREMIUM — Admin: Pedidos
 * Archivo: admin/pedidos.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminTitle = 'Pedidos en Línea';
$db  = db();
$msg = '';

// ── Cambiar estado ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estado'], $_POST['id'])) {
    $estados = ['nuevo','preparando','listo','entregado','cancelado'];
    $est     = in_array($_POST['estado'], $estados, true) ? $_POST['estado'] : 'nuevo';
    $db->prepare('UPDATE pedidos SET estado=:e WHERE id=:id')
       ->execute([':e' => $est, ':id' => (int)$_POST['id']]);
    $msg = 'Estado actualizado.';
}

// ── Eliminar ──────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $db->prepare('DELETE FROM pedidos WHERE id=:id')->execute([':id' => (int)$_GET['id']]);
    header('Location: ' . APP_URL . '/admin/pedidos.php?deleted=1'); exit;
}
if (isset($_GET['deleted'])) $msg = 'Pedido eliminado.';

// ── Detalle de un pedido ──────────────────────────────────
$detalle    = null;
$detalleItems = [];
if (isset($_GET['ver'])) {
    $s = $db->prepare('SELECT * FROM pedidos WHERE id=:id');
    $s->execute([':id' => (int)$_GET['ver']]);
    $detalle = $s->fetch();
    if ($detalle) {
        $s2 = $db->prepare('SELECT * FROM pedido_items WHERE pedido_id=:id');
        $s2->execute([':id' => $detalle['id']]);
        $detalleItems = $s2->fetchAll();
    }
}

// ── Filtros ───────────────────────────────────────────────
$filterEstado = $_GET['estado'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($filterEstado && in_array($filterEstado, ['nuevo','preparando','listo','entregado','cancelado'])) {
    $where[] = 'estado=:est'; $params[':est'] = $filterEstado;
}
if ($search) {
    $where[] = '(nombre LIKE :q OR codigo LIKE :q OR email LIKE :q)';
    $params[':q'] = "%$search%";
}

$sql = 'SELECT * FROM pedidos'
     . ($where ? ' WHERE '.implode(' AND ',$where) : '')
     . ' ORDER BY created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// Contar nuevos
$nuevos = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado='nuevo'")->fetchColumn();

$tipoLabels   = ['salon'=>'Restaurante','llevar'=>'Para llevar','domicilio'=>'Domicilio'];
$estadoLabels = ['nuevo'=>'Nuevo','preparando'=>'Preparando','listo'=>'Listo','entregado'=>'Entregado','cancelado'=>'Cancelado'];
$estadoBadge  = ['nuevo'=>'pendiente','preparando'=>'pendiente','listo'=>'confirmada','entregado'=>'confirmada','cancelado'=>'cancelada'];

require_once __DIR__ . '/includes/sidebar.php';
?>
<script>window.ADMIN_URL = '<?= APP_URL ?>';</script>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4"><?= h($msg) ?></div>
<?php endif; ?>

<?php if ($detalle): ?>
<!-- ══════════════════════════════════════════════════════ -->
<!-- DETALLE DEL PEDIDO                                     -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/pedidos.php" class="btn rp-btn-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h2 class="rp-display fs-5 mb-0">Pedido <span class="text-gold"><?= h($detalle['codigo']) ?></span></h2>
</div>

<div class="row gy-4">
    <div class="col-lg-7">
        <div class="rp-form-card mb-4">
            <p class="rp-eyebrow mb-3">Platillos</p>
            <table class="rp-table">
                <thead><tr><th>Platillo</th><th>Precio</th><th>Cant.</th><th>Subtotal</th></tr></thead>
                <tbody>
                    <?php foreach ($detalleItems as $it):
                        // Cargar opciones del ítem
                        $sOp = db()->prepare('SELECT * FROM pedido_item_opciones WHERE item_id=:id');
                        $sOp->execute([':id'=>$it['id']]);
                        $opcionesItem = $sOp->fetchAll();
                    ?>
                    <tr>
                        <td>
                            <strong><?= h($it['nombre']) ?></strong>
                            <?php if ($opcionesItem): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:.25rem;margin-top:.35rem;">
                                <?php foreach ($opcionesItem as $op): ?>
                                <span style="background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.2);
                                             border-radius:100px;padding:.1rem .5rem;font-size:.68rem;color:#b0a99a;">
                                    <?= h($op['nombre']) ?>
                                    <?php if ($op['precio'] > 0): ?>
                                    <span style="color:#c9a84c;">+$<?= number_format((float)$op['precio'],2) ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($it['notas'])): ?>
                            <p style="font-size:.72rem;color:#b0a99a;font-style:italic;margin:.3rem 0 0;">
                                <i class="bi bi-chat-dots me-1"></i><?= h($it['notas']) ?>
                            </p>
                            <?php endif; ?>
                        </td>
                        <td><?= formatPrecio((float)$it['precio']) ?></td>
                        <td class="text-center"><?= $it['cantidad'] ?></td>
                        <td class="text-gold"><?= formatPrecio((float)$it['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-rp">
                <span class="text-muted">Total del pedido</span>
                <span class="rp-display fs-4 text-gold"><?= formatPrecio((float)$detalle['total']) ?></span>
            </div>
        </div>

        <?php if ($detalle['notas']): ?>
        <div class="rp-form-card">
            <p class="rp-eyebrow mb-2">Notas del cliente</p>
            <p class="text-muted mb-0"><?= h($detalle['notas']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="rp-form-card mb-4">
            <p class="rp-eyebrow mb-3">Cliente</p>
            <p class="fw-semibold mb-1"><?= h($detalle['nombre']) ?></p>
            <p class="text-muted small mb-1"><i class="bi bi-envelope me-1 text-gold"></i><?= h($detalle['email']) ?></p>
            <p class="text-muted small mb-1"><i class="bi bi-telephone me-1 text-gold"></i>
                <a href="tel:<?= h($detalle['telefono']) ?>" class="text-muted"><?= h($detalle['telefono']) ?></a>
            </p>
            <hr class="border-rp">
            <p class="text-muted small mb-1">
                <i class="bi bi-bag me-1 text-gold"></i>
                <strong>Tipo:</strong> <?= $tipoLabels[$detalle['tipo']] ?? $detalle['tipo'] ?>
            </p>
            <?php if ($detalle['mesa']): ?>
            <p class="text-muted small mb-1"><i class="bi bi-door-open me-1 text-gold"></i><?= h($detalle['mesa']) ?></p>
            <?php endif; ?>
            <?php if ($detalle['direccion']): ?>
            <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-1 text-gold"></i><?= h($detalle['direccion']) ?></p>
            <?php endif; ?>
            <p class="text-muted small mt-2 mb-0"><i class="bi bi-clock me-1 text-gold"></i><?= date('d/m/Y H:i', strtotime($detalle['created_at'])) ?></p>
        </div>

        <!-- Cambiar estado -->
        <div class="rp-form-card">
            <p class="rp-eyebrow mb-3">Actualizar Estado</p>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $detalle['id'] ?>">
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($estadoLabels as $val => $lbl): ?>
                    <label class="d-flex align-items-center gap-2 p-2 rounded cursor-pointer"
                           style="border:1px solid <?= $detalle['estado']===$val?'var(--gold)':'var(--black-border)' ?>;background:<?= $detalle['estado']===$val?'rgba(201,168,76,.08)':'transparent' ?>;cursor:pointer;">
                        <input type="radio" name="estado" value="<?= $val ?>" class="d-none"
                               <?= $detalle['estado']===$val?'checked':'' ?>>
                        <span class="rp-badge rp-badge--<?= $estadoBadge[$val] ?>"><?= $lbl ?></span>
                        <?php if ($detalle['estado']===$val): ?>
                        <i class="bi bi-check-lg text-gold ms-auto"></i>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                    <button type="submit" class="rp-btn-gold btn mt-1">
                        <i class="bi bi-check-lg me-2"></i>Guardar Estado
                    </button>
                </div>
            </form>
            <!-- WhatsApp al cliente -->
            <a href="https://wa.me/<?= preg_replace('/\D/','',$detalle['telefono']) ?>?text=<?= rawurlencode('Hola '.$detalle['nombre'].', tu pedido #'.$detalle['codigo'].' en '.APP_NAME.' ya está '.($estadoLabels[$detalle['estado']]??$detalle['estado']).'. ¡Gracias!') ?>"
               target="_blank" class="btn rp-btn-outline w-100 mt-2">
                <i class="bi bi-whatsapp me-2"></i>Notificar por WhatsApp
            </a>
            <!-- Ver recibo -->
            <a href="<?= APP_URL ?>/pages/recibo.php?codigo=<?= h($detalle['codigo']) ?>"
               target="_blank" class="btn rp-btn-outline w-100 mt-2">
                <i class="bi bi-receipt me-2"></i>Ver E-Receipt
            </a>
            <!-- Reimprimir comanda -->
            <button type="button" class="btn rp-btn-outline w-100 mt-2"
                    onclick="reimprimirComanda(<?= $detalle['id'] ?>)">
                <i class="bi bi-printer me-2"></i>Reimprimir Comanda
            </button>
            <div id="printMsg" class="mt-2" style="display:none;"></div>

            <script>
            function reimprimirComanda(id) {
                const btn = document.querySelector('[onclick="reimprimirComanda(' + id + ')"]');
                const msg = document.getElementById('printMsg');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Imprimiendo…';

                fetch('<?= APP_URL ?>/admin/api/reprint_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id=' + id
                })
                .then(r => r.json())
                .then(data => {
                    msg.style.display = '';
                    msg.className = 'rp-alert rp-alert--' + (data.ok ? 'success' : 'error') + ' mt-2';
                    msg.textContent = data.message;
                    setTimeout(() => msg.style.display = 'none', 4000);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-printer me-2"></i>Reimprimir Comanda';
                });
            }
            </script>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════ -->
<!-- LISTA DE PEDIDOS                                       -->
<!-- ══════════════════════════════════════════════════════ -->

<!-- Filtros -->
<form method="GET" action="" class="row g-2 mb-4">
    <div class="col-12 col-md-5 col-lg-4">
        <input type="search" name="q" class="rp-form-control form-control"
               placeholder="Buscar nombre, código…" value="<?= h($search) ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <select name="estado" class="rp-form-control form-select">
            <option value="">Todos</option>
            <?php foreach ($estadoLabels as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $filterEstado===$val?'selected':'' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="rp-btn-gold btn"><i class="bi bi-search me-1"></i>Filtrar</button>
        <a href="<?= APP_URL ?>/admin/pedidos.php" class="rp-btn-outline btn ms-1">Limpiar</a>
    </div>
</form>

<div class="rp-form-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="rp-display fs-5 mb-0">
            Pedidos
            <?php if ($nuevos): ?>
            <span class="ms-2 badge rounded-pill" style="background:var(--gold);color:var(--black);font-size:.7rem;">
                <?= $nuevos ?> nuevo<?= $nuevos>1?'s':'' ?>
            </span>
            <?php endif; ?>
        </h2>
        <span class="text-muted small"><?= count($pedidos) ?> resultado(s)</span>
    </div>

    <?php if ($pedidos): ?>
    <div class="table-responsive">
        <table class="rp-table">
            <thead>
                <tr><th>Código</th><th>Cliente</th><th>Tipo</th><th>Items</th><th>Total</th><th>Estado</th><th>Hora</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p):
                    $nItems = $db->prepare('SELECT SUM(cantidad) FROM pedido_items WHERE pedido_id=:id');
                    $nItems->execute([':id'=>$p['id']]);
                    $cantItems = $nItems->fetchColumn();
                ?>
                <tr style="<?= $p['estado']==='nuevo'?'background:rgba(201,168,76,.04);':'' ?>">
                    <td>
                        <strong class="text-gold" style="letter-spacing:.08em;"><?= h($p['codigo']) ?></strong>
                        <?php if ($p['estado']==='nuevo'): ?>
                        <span class="ms-1" style="width:8px;height:8px;background:#ffc107;border-radius:50%;display:inline-block;animation:pulse 1.5s infinite;"></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= h($p['nombre']) ?></strong><br>
                        <small class="text-muted"><?= h($p['telefono']) ?></small>
                    </td>
                    <td><small><?= $tipoLabels[$p['tipo']] ?? h($p['tipo']) ?></small></td>
                    <td class="text-center"><?= $cantItems ?></td>
                    <td class="text-gold"><?= formatPrecio((float)$p['total']) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <select name="estado" class="rp-form-control form-select form-select-sm"
                                    onchange="this.form.submit()"
                                    style="width:auto;display:inline;font-size:.75rem;padding:.25rem .5rem;">
                                <?php foreach ($estadoLabels as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $p['estado']===$val?'selected':'' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><small class="text-muted"><?= date('H:i', strtotime($p['created_at'])) ?></small></td>
                    <td>
                        <a href="?ver=<?= $p['id'] ?>" class="btn btn-sm rp-btn-outline me-1" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="?action=delete&id=<?= $p['id'] ?>"
                           class="btn btn-sm" style="background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.3);color:#e05c5c;"
                           onclick="return confirmDelete('¿Eliminar pedido <?= h(addslashes($p['codigo'])) ?>?')">
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
        <i class="bi bi-bag-x text-gold" style="font-size:3rem;opacity:.4"></i>
        <p class="text-muted mt-3">No hay pedidos con esos filtros.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Auto-refresh si hay pedidos nuevos -->
<?php if ($nuevos): ?>
<p class="text-center text-muted small mt-3">
    <i class="bi bi-arrow-repeat me-1"></i>Actualizando automáticamente cada 30 seg.
</p>
<script>setTimeout(() => location.reload(), 30000);</script>
<?php endif; ?>

<style>
@keyframes pulse {
    0%,100% { opacity:1; } 50% { opacity:.3; }
}
</style>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
