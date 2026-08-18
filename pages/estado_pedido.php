<?php
/**
 * RESTAURANT PREMIUM — Estado del Pedido
 * Archivo: pages/estado_pedido.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Estado de tu Pedido — ' . APP_NAME;

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
$pedido = null;
$items  = [];

if ($codigo) {
    $s = db()->prepare('SELECT * FROM pedidos WHERE codigo = :c');
    $s->execute([':c' => $codigo]);
    $pedido = $s->fetch();

    if ($pedido) {
        $s2 = db()->prepare('SELECT * FROM pedido_items WHERE pedido_id = :id');
        $s2->execute([':id' => $pedido['id']]);
        $items = $s2->fetchAll();
    }
}

// Pasos del estado
$pasos = [
    'nuevo'      => ['Recibido',      'bi-bag-check',     0],
    'preparando' => ['Preparando',    'bi-fire',          1],
    'listo'      => ['Listo',         'bi-bell',          2],
    'entregado'  => ['Entregado',     'bi-check-circle',  3],
];

$estadoActual = $pedido ? $pedido['estado'] : '';
$pasoActual   = $pasos[$estadoActual][2] ?? 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div style="padding-top:90px;background:var(--black-soft);border-bottom:1px solid var(--black-border);">
    <div class="container py-4 text-center">
        <p class="rp-eyebrow">Seguimiento</p>
        <div class="rp-divider mx-auto"></div>
        <h1 class="rp-display fs-1 mt-3">Estado de tu <em class="rp-display--italic text-gold">Pedido</em></h1>
    </div>
</div>

<section class="rp-section rp-section--dark">
    <div class="container">

    <!-- Búsqueda por código -->
    <form method="GET" action="" class="row justify-content-center mb-5">
        <div class="col-md-5">
            <label class="rp-form-label text-center d-block mb-2">Ingresa tu código de pedido</label>
            <div class="d-flex gap-2">
                <input type="text" name="codigo" class="rp-form-control form-control text-center"
                       placeholder="Ej: A1B2C3D4" maxlength="12"
                       value="<?= h($codigo) ?>"
                       style="letter-spacing:.15em;font-size:1.1rem;text-transform:uppercase;">
                <button type="submit" class="rp-btn-gold btn px-4">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </form>

    <?php if ($codigo && !$pedido): ?>
    <div class="text-center py-3">
        <div class="rp-alert rp-alert--error d-inline-block">
            <i class="bi bi-exclamation-circle me-2"></i>
            No encontramos un pedido con el código <strong><?= h($codigo) ?></strong>.
        </div>
    </div>

    <?php elseif ($pedido): ?>

    <?php if ($pedido['estado'] === 'cancelado'): ?>
    <div class="rp-alert rp-alert--error text-center mb-4">
        <i class="bi bi-x-circle me-2"></i>Este pedido fue cancelado.
    </div>
    <?php else: ?>

    <!-- Barra de progreso -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
            <div class="rp-order-status">
                <?php foreach ($pasos as $key => [$label, $ico, $paso]): 
                    if ($key === 'cancelado') continue;
                    $done   = $paso < $pasoActual;
                    $active = $paso === $pasoActual;
                    $cls    = $done ? 'done' : ($active ? 'active' : '');
                ?>
                <div class="rp-order-step <?= $cls ?>">
                    <div class="rp-order-step__icon">
                        <i class="bi <?= $ico ?>"></i>
                    </div>
                    <span class="rp-order-step__label"><?= $label ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Mensaje de estado -->
            <?php
            $mensajes = [
                'nuevo'      => ['Tu pedido fue recibido y está en cola.',         'bi-hourglass-split', '#ffc107'],
                'preparando' => ['¡Estamos preparando tu orden en cocina!',         'bi-fire',            '#fd7e14'],
                'listo'      => ['Tu pedido está listo. ¡Pasa a recogerlo!',       'bi-bell-fill',       '#2ea043'],
                'entregado'  => ['¡Pedido entregado! Esperamos que lo disfrutes.', 'bi-heart-fill',      '#c9a84c'],
            ];
            [$msgTxt, $msgIco, $msgColor] = $mensajes[$estadoActual] ?? ['Estado desconocido.', 'bi-question', '#888'];
            ?>
            <div class="text-center mt-3 p-3 rounded-3"
                 style="background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.15);">
                <i class="bi <?= $msgIco ?> me-2" style="color:<?= $msgColor ?>;"></i>
                <span class="text-muted"><?= $msgTxt ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detalle del pedido -->
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="bg-rp-card border border-rp rounded-3 overflow-hidden">
                <!-- Encabezado -->
                <div class="p-4 border-bottom border-rp">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <p class="text-muted small mb-1">Código de pedido</p>
                            <p class="rp-display fs-4 text-gold mb-0" style="letter-spacing:.1em;">
                                <?= h($pedido['codigo']) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <p class="text-muted small mb-1">Realizado el</p>
                            <p class="small mb-0"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></p>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <span class="rp-badge rp-badge--<?= $pedido['estado'] === 'nuevo' ? 'pendiente' : ($pedido['estado'] === 'entregado' ? 'confirmada' : 'pendiente') ?>">
                            <?php
                            $etiquetas = ['nuevo'=>'Nuevo','preparando'=>'Preparando','listo'=>'Listo','entregado'=>'Entregado','cancelado'=>'Cancelado'];
                            echo $etiquetas[$pedido['estado']] ?? ucfirst($pedido['estado']);
                            ?>
                        </span>
                        <span class="rp-badge rp-badge--pendiente">
                            <?php
                            $tipoLabels = ['salon'=>'En restaurante','llevar'=>'Para llevar','domicilio'=>'A domicilio'];
                            echo $tipoLabels[$pedido['tipo']] ?? ucfirst($pedido['tipo']);
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Cliente -->
                <div class="p-4 border-bottom border-rp">
                    <p class="rp-eyebrow mb-2">Cliente</p>
                    <p class="fw-semibold mb-1"><?= h($pedido['nombre']) ?></p>
                    <p class="text-muted small mb-0"><?= h($pedido['email']) ?> · <?= h($pedido['telefono']) ?></p>
                    <?php if ($pedido['mesa']): ?>
                    <p class="text-muted small mt-1"><i class="bi bi-door-open me-1 text-gold"></i><?= h($pedido['mesa']) ?></p>
                    <?php endif; ?>
                    <?php if ($pedido['direccion']): ?>
                    <p class="text-muted small mt-1"><i class="bi bi-geo-alt me-1 text-gold"></i><?= h($pedido['direccion']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <div class="p-4 border-bottom border-rp">
                    <p class="rp-eyebrow mb-3">Platillos ordenados</p>
                    <?php foreach ($items as $it):
                        $sOp = db()->prepare('SELECT * FROM pedido_item_opciones WHERE item_id=:id');
                        $sOp->execute([':id'=>$it['id']]);
                        $opIt = $sOp->fetchAll();
                    ?>
                    <div class="py-2 border-bottom border-rp">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="fw-semibold small"><?= h($it['nombre']) ?></span>
                                <span class="text-muted small ms-2">×<?= $it['cantidad'] ?></span>
                                <?php if ($opIt): ?>
                                <div style="display:flex;flex-wrap:wrap;gap:.2rem;margin-top:.3rem;">
                                    <?php foreach ($opIt as $op): ?>
                                    <span style="background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.15);
                                                 border-radius:100px;padding:.08rem .4rem;font-size:.65rem;color:#b0a99a;">
                                        <?= h($op['nombre']) ?>
                                        <?php if ($op['precio'] > 0): ?>
                                        <span style="color:#c9a84c;"> +$<?= number_format((float)$op['precio'],2) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($it['notas'])): ?>
                                <p style="font-size:.7rem;color:#b0a99a;font-style:italic;margin:.2rem 0 0;">
                                    <i class="bi bi-chat-dots me-1"></i><?= h($it['notas']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <span class="text-gold small fw-semibold ms-3"><?= formatPrecio((float)$it['subtotal']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total -->
                <div class="p-4 d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total</span>
                    <span class="rp-display fs-4 text-gold"><?= formatPrecio((float)$pedido['total']) ?></span>
                </div>

                <?php if ($pedido['notas']): ?>
                <div class="px-4 pb-4">
                    <p class="text-muted small"><i class="bi bi-chat-dots text-gold me-1"></i><?= h($pedido['notas']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Botones de acción -->
            <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
                <a href="<?= APP_URL ?>/pages/recibo.php?codigo=<?= urlencode($pedido['codigo']) ?>"
                   class="rp-btn-gold btn px-4">
                    <i class="bi bi-receipt me-2"></i>Ver Recibo
                </a>
                <a href="<?= APP_URL ?>/pages/menu.php" class="rp-btn-outline btn px-4">
                    <i class="bi bi-bag me-2"></i>Hacer otro pedido
                </a>
            </div>

            <!-- Refresh automático si está en progreso -->
            <?php if (in_array($pedido['estado'], ['nuevo','preparando','listo'])): ?>
            <p class="text-center text-muted small mt-3">
                <i class="bi bi-arrow-repeat me-1"></i>
                Esta página se actualiza automáticamente cada 30 segundos.
            </p>
            <script>
                setTimeout(() => location.reload(), 30000);
            </script>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
