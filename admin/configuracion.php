<?php
/**
 * RESTAURANT PREMIUM — Admin: Configuración del Sitio
 * Archivo: admin/configuracion.php
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/settings.php';
requireAdmin();

// Solo admins
if ($_SESSION['admin_rol'] !== 'admin') {
    header('Location: ' . APP_URL . '/admin/dashboard.php'); exit;
}

$adminTitle = 'Configuración del Sitio';
$db  = db();
$msg = '';

// ── GUARDAR ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupo = $_POST['grupo'] ?? 'general';

    // Si se seleccionó un tema preset, aplicar sus colores
    if (isset($_POST['cfg']['tema_preset'])) {
        $temas = getTemasPreset();
        $preset = $_POST['cfg']['tema_preset'];
        if (isset($temas[$preset])) {
            [,$gold,$black,$white] = $temas[$preset];
            $_POST['cfg']['color_primario']   = $gold;
            $_POST['cfg']['color_secundario'] = $black;
            $_POST['cfg']['color_texto']      = $white;
            $_POST['cfg']['color_navbar']     = $black;
            $_POST['cfg']['color_cards']      = adjustBrightness($black, 5);
        }
    }

    foreach ($_POST['cfg'] ?? [] as $clave => $valor) {
        // Sanitizar clave
        $clave = preg_replace('/[^a-z0-9_]/', '', $clave);
        if (!$clave) continue;

        // Si es array (hidden+checkbox), tomar el último valor (el checkbox gana sobre el hidden)
        if (is_array($valor)) {
            $valor = end($valor);
        }

        $db->prepare(
            'INSERT INTO configuracion (clave, valor) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE valor = :v2'
        )->execute([':k' => $clave, ':v' => trim($valor), ':v2' => trim($valor)]);
    }

    // Subir imágenes
    // $_FILES['img'] viene transpuesto: ['name'=>[clave=>val], 'error'=>[clave=>val]...]
    if (!empty($_FILES['img']['name']) && is_array($_FILES['img']['name'])) {
        foreach ($_FILES['img']['name'] as $clave => $nombre) {
            $file = [
                'name'     => $_FILES['img']['name'][$clave]     ?? '',
                'type'     => $_FILES['img']['type'][$clave]     ?? '',
                'tmp_name' => $_FILES['img']['tmp_name'][$clave] ?? '',
                'error'    => $_FILES['img']['error'][$clave]    ?? UPLOAD_ERR_NO_FILE,
                'size'     => $_FILES['img']['size'][$clave]     ?? 0,
            ];
            if ($file['error'] === UPLOAD_ERR_OK && $file['name'] !== '') {
                $subida = subirImagen($file, 'config/');
                if ($subida) {
                    $val = 'config/' . $subida;
                    $db->prepare(
                        'INSERT INTO configuracion (clave, valor) VALUES (:k,:v)
                         ON DUPLICATE KEY UPDATE valor = :v2'
                    )->execute([':k' => $clave, ':v' => $val, ':v2' => $val]);
                }
            }
        }
    }

    // Limpiar caché
    unset($_SERVER['_rp_settings']);
    $msg = 'Configuración guardada correctamente.';

    // Recargar settings
    $settings = getSettings();
}

$settings = getSettings();

// Grupos de configuración
$grupos = [
    'general'    => ['✦ General',           'bi-house'],
    'colores'    => ['🎨 Colores',           'bi-palette'],
    'tema'       => ['🎭 Tema y Estilo',     'bi-brush'],
    'tipografia' => ['🔤 Tipografía',        'bi-fonts'],
    'impuestos'  => ['🧾 Impuestos',         'bi-receipt'],
    'contacto'   => ['📞 Contacto',          'bi-telephone'],
    'redes'      => ['📱 Redes Sociales',    'bi-share'],
    'whatsapp'   => ['💬 WhatsApp',          'bi-whatsapp'],
    'maps'       => ['🗺️ Google Maps',       'bi-map'],
    'nosotros'   => ['👨‍🍳 Nosotros',          'bi-people'],
    'seo'        => ['🔍 SEO',               'bi-search'],
    'pagos'      => ['💳 Métodos de Pago',    'bi-credit-card'],
    'impresora'  => ['🖨️ Impresora',            'bi-printer'],
    'moneda'     => ['💰 Moneda',                'bi-cash-coin'],
];

$grupoActivo = $_GET['grupo'] ?? 'general';
if (!array_key_exists($grupoActivo, $grupos)) $grupoActivo = 'general';

// Obtener campos del grupo activo
$campos = $db->prepare('SELECT * FROM configuracion WHERE grupo=:g ORDER BY clave ASC');
$campos->execute([':g' => $grupoActivo]);
$camposGrupo = $campos->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
?>

<style>
.rp-config-nav { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:2rem; }
.rp-config-nav a {
    display:flex; align-items:center; gap:.5rem;
    padding:.55rem 1rem; border-radius:6px;
    border:1px solid var(--black-border);
    color:var(--white-dim); font-size:.82rem; font-weight:500;
    transition:all .2s; text-decoration:none;
}
.rp-config-nav a:hover { border-color:var(--gold); color:var(--gold); }
.rp-config-nav a.active { background:rgba(201,168,76,.1); border-color:var(--gold); color:var(--gold); }

.rp-color-preview {
    width:32px; height:32px; border-radius:4px;
    border:1px solid var(--black-border); flex-shrink:0;
}

.rp-img-preview {
    width:100%; max-height:160px; object-fit:cover;
    border-radius:6px; border:1px solid var(--black-border);
    margin-bottom:.75rem;
}

.rp-tema-card {
    border:2px solid var(--black-border); border-radius:8px;
    padding:1rem; cursor:pointer; transition:all .2s;
    text-align:center;
}
.rp-tema-card:hover { border-color:var(--gold); }
.rp-tema-card.selected { border-color:var(--gold); background:rgba(201,168,76,.06); }
.rp-tema-swatch { display:flex; height:28px; border-radius:4px; overflow:hidden; margin-bottom:.5rem; }
.rp-tema-swatch span { flex:1; }
</style>

<?php if ($msg): ?>
<div class="rp-alert rp-alert--success rp-flash mb-4">
    <i class="bi bi-check-circle me-2"></i><?= h($msg) ?>
    <a href="<?= APP_URL ?>/" target="_blank" class="ms-3 text-gold small">
        <i class="bi bi-eye me-1"></i>Ver sitio actualizado
    </a>
</div>
<?php endif; ?>

<!-- Nav de grupos -->
<div class="rp-config-nav">
    <?php foreach ($grupos as $key => [$label, $ico]): ?>
    <a href="?grupo=<?= $key ?>" class="<?= $grupoActivo === $key ? 'active' : '' ?>">
        <i class="bi <?= $ico ?>"></i><?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="?grupo=<?= $grupoActivo ?>" enctype="multipart/form-data">
    <input type="hidden" name="grupo" value="<?= $grupoActivo ?>">

    <div class="row gy-4">
        <div class="col-lg-8">
            <div class="rp-form-card">
                <h2 class="rp-display fs-5 mb-4"><?= $grupos[$grupoActivo][0] ?></h2>

                <?php if ($grupoActivo === 'tema'): ?>
                <!-- ── Selector de temas preset ──────────────── -->
                <div class="mb-4">
                    <label class="rp-form-label d-block mb-3">Tema predefinido</label>
                    <div class="row g-2">
                        <?php foreach (getTemasPreset() as $key => [$nombre,$gold,$bg,$text]): ?>
                        <div class="col-6 col-md-4">
                            <label>
                                <input type="radio" name="cfg[tema_preset]" value="<?= $key ?>"
                                       class="d-none rp-tema-radio"
                                       <?= cfg('tema_preset','negro-dorado') === $key ? 'checked' : '' ?>>
                                <div class="rp-tema-card <?= cfg('tema_preset','negro-dorado') === $key ? 'selected' : '' ?>">
                                    <div class="rp-tema-swatch">
                                        <span style="background:<?= $bg ?>"></span>
                                        <span style="background:<?= $gold ?>"></span>
                                        <span style="background:<?= $text ?>"></span>
                                    </div>
                                    <p class="small mb-0" style="font-size:.75rem;"><?= $nombre ?></p>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted small mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Seleccionar un tema aplica automáticamente sus colores. Puedes ajustarlos después en "Colores".
                    </p>
                </div>

                <!-- Border radius -->
                <div>
                    <label class="rp-form-label">Redondez de bordes</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <?php foreach (['0'=>'Cuadrado','4'=>'Suave','8'=>'Redondeado','12'=>'Muy redondeado'] as $val => $lbl): ?>
                        <label class="d-flex flex-column align-items-center gap-1" style="cursor:pointer;">
                            <input type="radio" name="cfg[tema_border_radius]" value="<?= $val ?>"
                                   class="d-none"
                                   <?= cfg('tema_border_radius','4') === $val ? 'checked' : '' ?>>
                            <div class="rp-color-preview" style="border-radius:<?= $val ?>px;width:48px;height:32px;background:var(--gold);"></div>
                            <span class="text-muted" style="font-size:.72rem;"><?= $lbl ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php elseif ($grupoActivo === 'moneda'): ?>
                <!-- ── Configuración de moneda ──────────────────── -->
                <?php
                require_once __DIR__ . '/../config/settings.php';
                $monedaActual  = getMonedaConfig();
                $monedasLista  = getMonedasDisponibles();
                ?>
                <div class="row gy-4">

                    <div class="col-12">
                        <p class="text-muted small mb-0">
                            Configura la moneda que usará todo el sistema: menú, carrito, checkout, recibos y reportes.
                        </p>
                    </div>

                    <!-- Selector de moneda -->
                    <div class="col-md-6">
                        <label class="rp-form-label">Moneda *</label>
                        <select name="cfg[moneda_codigo]" id="selMonedaCodigo"
                                class="rp-form-control form-select"
                                onchange="actualizarSimboloMoneda()">
                            <?php foreach ($monedasLista as $codigo => [$nombre, $simbolo]): ?>
                            <option value="<?= $codigo ?>"
                                    data-simbolo="<?= h($simbolo) ?>"
                                    <?= $monedaActual['codigo'] === $codigo ? 'selected' : '' ?>>
                                <?= h($nombre) ?> (<?= h($codigo) ?>) — <?= h($simbolo) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Símbolo personalizado -->
                    <div class="col-md-6">
                        <label class="rp-form-label">Símbolo a mostrar</label>
                        <input type="text" name="cfg[moneda_simbolo]" id="inputMonedaSimbolo"
                               class="rp-form-control form-control"
                               maxlength="5"
                               value="<?= h($monedaActual['simbolo']) ?>">
                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                            Se actualiza automáticamente al cambiar la moneda, pero puedes editarlo.
                        </p>
                    </div>

                    <!-- Posición del símbolo -->
                    <div class="col-md-4">
                        <label class="rp-form-label">Posición del símbolo</label>
                        <select name="cfg[moneda_posicion]" class="rp-form-control form-select">
                            <option value="antes"   <?= $monedaActual['posicion']==='antes'   ? 'selected' : '' ?>>Antes ($100.00)</option>
                            <option value="despues" <?= $monedaActual['posicion']==='despues' ? 'selected' : '' ?>>Después (100.00€)</option>
                        </select>
                    </div>

                    <!-- Decimales -->
                    <div class="col-md-4">
                        <label class="rp-form-label">Decimales</label>
                        <select name="cfg[moneda_decimales]" class="rp-form-control form-select">
                            <option value="0" <?= $monedaActual['decimales']===0 ? 'selected' : '' ?>>0 (100)</option>
                            <option value="2" <?= $monedaActual['decimales']===2 ? 'selected' : '' ?>>2 (100.00)</option>
                        </select>
                    </div>

                    <!-- Separador decimal -->
                    <div class="col-md-4">
                        <label class="rp-form-label">Separador decimal</label>
                        <select name="cfg[moneda_separador_decimal]" class="rp-form-control form-select">
                            <option value="." <?= $monedaActual['separadorDecimal']==='.' ? 'selected' : '' ?>>Punto (100.00)</option>
                            <option value="," <?= $monedaActual['separadorDecimal']===',' ? 'selected' : '' ?>>Coma (100,00)</option>
                        </select>
                    </div>

                    <!-- Separador de miles -->
                    <div class="col-md-6">
                        <label class="rp-form-label">Separador de miles</label>
                        <select name="cfg[moneda_separador_miles]" class="rp-form-control form-select">
                            <option value="," <?= $monedaActual['separadorMiles']===',' ? 'selected' : '' ?>>Coma (1,000.00)</option>
                            <option value="." <?= $monedaActual['separadorMiles']==='.' ? 'selected' : '' ?>>Punto (1.000,00)</option>
                            <option value=" " <?= $monedaActual['separadorMiles']===' ' ? 'selected' : '' ?>>Espacio (1 000.00)</option>
                            <option value=""  <?= $monedaActual['separadorMiles']===''  ? 'selected' : '' ?>>Ninguno (1000.00)</option>
                        </select>
                    </div>

                    <!-- Vista previa en vivo -->
                    <div class="col-12">
                        <label class="rp-form-label d-block mb-2">Vista previa</label>
                        <div class="p-4 rounded-3 text-center" style="background:var(--black);border:1px solid var(--black-border);">
                            <p class="text-muted small mb-2">Así se verán los precios en tu sitio:</p>
                            <p class="rp-display fs-2 text-gold mb-0" id="monedaPreview">
                                <?= formatMoneda(459.50) ?>
                            </p>
                            <p class="text-muted mt-2 mb-0" style="font-size:.72rem;">Ejemplo: Filete Wellington — $459.50</p>
                        </div>
                    </div>

                </div>

                <script>
                const monedasData = <?= json_encode($monedasLista) ?>;

                function actualizarSimboloMoneda() {
                    const sel = document.getElementById('selMonedaCodigo');
                    const opt = sel.options[sel.selectedIndex];
                    const simbolo = opt.dataset.simbolo;
                    document.getElementById('inputMonedaSimbolo').value = simbolo;
                    actualizarPreviewMoneda();
                }

                function actualizarPreviewMoneda() {
                    const simbolo   = document.getElementById('inputMonedaSimbolo').value;
                    const codigo    = document.getElementById('selMonedaCodigo').value;
                    const posicion  = document.querySelector('[name="cfg[moneda_posicion]"]').value;
                    const decimales = parseInt(document.querySelector('[name="cfg[moneda_decimales]"]').value);
                    const sepDec    = document.querySelector('[name="cfg[moneda_separador_decimal]"]').value;
                    const sepMil    = document.querySelector('[name="cfg[moneda_separador_miles]"]').value;

                    let num = 459.50.toFixed(decimales);
                    let [entero, decimal] = num.split('.');
                    entero = entero.replace(/\\B(?=(\\d{3})+(?!\\d))/g, sepMil || '');

                    let numeroFinal = decimal !== undefined && decimales > 0
                        ? entero + sepDec + decimal
                        : entero;

                    const resultado = posicion === 'antes'
                        ? simbolo + numeroFinal
                        : numeroFinal + ' ' + simbolo;

                    document.getElementById('monedaPreview').textContent = resultado + ' ' + codigo;
                }

                // Listeners para actualizar preview en vivo
                ['inputMonedaSimbolo'].forEach(id => {
                    document.getElementById(id)?.addEventListener('input', actualizarPreviewMoneda);
                });
                document.querySelectorAll('[name="cfg[moneda_posicion]"], [name="cfg[moneda_decimales]"], [name="cfg[moneda_separador_decimal]"], [name="cfg[moneda_separador_miles]"]')
                    .forEach(el => el.addEventListener('change', actualizarPreviewMoneda));
                </script>

                <?php elseif ($grupoActivo === 'impresora'): ?>
                <!-- ── Configuración de impresora ───────────────── -->
                <div class="row gy-4">

                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
                             style="background:var(--black);border:1px solid var(--black-border);">
                            <div>
                                <p class="fw-semibold mb-0">Impresión automática</p>
                                <p class="text-muted small mb-0">Imprimir comanda al recibir cada pedido</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="cfg[impresora_activa]" value="0">
                                <input class="form-check-input" type="checkbox"
                                       name="cfg[impresora_activa]" value="1"
                                       <?= cfg('impresora_activa','0') === '1' ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="rp-form-label">IP de la impresora en la red</label>
                        <input type="text" name="cfg[impresora_ip]"
                               class="rp-form-control form-control"
                               placeholder="192.168.1.100"
                               value="<?= h(cfg('impresora_ip','192.168.1.100')) ?>">
                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                            Encuéntrala en el menú de la impresora o en tu router. Epson TM generalmente usa el puerto 9100.
                        </p>
                    </div>

                    <div class="col-md-4">
                        <label class="rp-form-label">Puerto TCP</label>
                        <input type="number" name="cfg[impresora_puerto]"
                               class="rp-form-control form-control"
                               placeholder="9100"
                               value="<?= h(cfg('impresora_puerto','9100')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="rp-form-label">Copias por pedido</label>
                        <input type="number" name="cfg[impresora_copias]"
                               class="rp-form-control form-control"
                               min="1" max="5"
                               value="<?= h(cfg('impresora_copias','1')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="rp-form-label">Líneas en blanco al cortar</label>
                        <input type="number" name="cfg[impresora_lineas]"
                               class="rp-form-control form-control"
                               min="1" max="10"
                               value="<?= h(cfg('impresora_lineas','3')) ?>">
                    </div>

                    <div class="col-md-4 d-flex align-items-end pb-1">
                        <div class="form-check form-switch">
                            <input type="hidden" name="cfg[impresora_logo]" value="0">
                                <input class="form-check-input" type="checkbox"
                                   name="cfg[impresora_logo]" value="1"
                                   <?= cfg('impresora_logo','1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted small">
                                Imprimir nombre del restaurante
                            </label>
                        </div>
                    </div>

                    <!-- Info de conexión -->
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.15);">
                            <p class="fw-semibold small mb-2"><i class="bi bi-info-circle text-gold me-2"></i>Cómo encontrar la IP de tu Epson TM</p>
                            <ol class="text-muted small mb-0 ps-3" style="line-height:2;">
                                <li>Apaga la impresora</li>
                                <li>Mantén presionado el botón de avance de papel</li>
                                <li>Enciéndela mientras lo mantienes presionado</li>
                                <li>Se imprimirá una hoja con la IP de red</li>
                                <li>Ingresa esa IP arriba</li>
                            </ol>
                        </div>
                    </div>

                    <!-- PrintNode -->
                    <div class="col-12">
                        <hr class="border-rp">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-cloud-arrow-up text-gold fs-5"></i>
                            <h3 class="rp-display fs-6 mb-0">PrintNode (Impresión en la nube)</h3>
                        </div>
                        <p class="text-muted small mb-3">
                            Usa PrintNode para imprimir desde un servidor en la nube hacia tu impresora local.
                            Ideal para producción. Crea tu cuenta gratis en
                            <a href="https://printnode.com" target="_blank" class="text-gold">printnode.com</a>
                        </p>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
                             style="background:var(--black);border:1px solid var(--black-border);">
                            <div>
                                <p class="fw-semibold mb-0">Usar PrintNode</p>
                                <p class="text-muted small mb-0">Si está activo, ignora la IP local y usa PrintNode</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="cfg[printnode_activo]" value="0">
                                <input class="form-check-input" type="checkbox"
                                       name="cfg[printnode_activo]" value="1"
                                       <?= cfg('printnode_activo','0') === '1' ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="rp-form-label">API Key de PrintNode</label>
                        <input type="text" name="cfg[printnode_apikey]"
                               class="rp-form-control form-control"
                               placeholder="Tu API Key de printnode.com"
                               value="<?= h(cfg('printnode_apikey','')) ?>">
                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                            Encuéntrala en printnode.com → Account → API Keys
                        </p>
                    </div>

                    <div class="col-12">
                        <label class="rp-form-label">ID de la impresora en PrintNode</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="cfg[printnode_printer_id]"
                                   class="rp-form-control form-control"
                                   placeholder="Ej: 12345"
                                   value="<?= h(cfg('printnode_printer_id','')) ?>">
                            <button type="button" class="rp-btn-outline btn px-3"
                                    onclick="cargarImpresoras()"
                                    title="Ver impresoras disponibles">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <div id="printerList" class="mt-2" style="display:none;"></div>
                    </div>

                    <!-- Botones de prueba -->
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="button" class="rp-btn-outline btn"
                                onclick="probarImpresora()">
                            <i class="bi bi-wifi me-2"></i>Probar IP local
                        </button>
                        <button type="button" class="rp-btn-outline btn"
                                onclick="probarPrintNode()">
                            <i class="bi bi-cloud-check me-2"></i>Probar PrintNode
                        </button>
                        <div id="printerTestResult" class="w-100 mt-1" style="display:none;"></div>
                    </div>
                </div>

                <script>
                function testRequest(url, btnSelector, label) {
                    const btn = document.querySelector(btnSelector);
                    const res = document.getElementById('printerTestResult');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Probando…';
                    fetch(url, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'} })
                    .then(r => r.json())
                    .then(data => {
                        res.style.display = '';
                        res.className = 'rp-alert rp-alert--' + (data.ok ? 'success' : 'error');
                        res.textContent = data.message;
                    })
                    .catch(() => {
                        res.style.display = '';
                        res.className = 'rp-alert rp-alert--error';
                        res.textContent = 'Error de conexión.';
                    })
                    .finally(() => { btn.disabled = false; btn.innerHTML = label; });
                }

                function probarImpresora() {
                    testRequest(
                        '<?= APP_URL ?>/admin/api/test_printer.php',
                        '[onclick="probarImpresora()"]',
                        '<i class="bi bi-wifi me-2"></i>Probar IP local'
                    );
                }

                function probarPrintNode() {
                    testRequest(
                        '<?= APP_URL ?>/admin/api/test_printnode.php',
                        '[onclick="probarPrintNode()"]',
                        '<i class="bi bi-cloud-check me-2"></i>Probar PrintNode'
                    );
                }

                function cargarImpresoras() {
                    const list = document.getElementById('printerList');
                    list.style.display = '';
                    list.innerHTML = '<span class="spinner-border spinner-border-sm me-2 text-gold"></span><span class="text-muted small">Cargando impresoras…</span>';
                    fetch('<?= APP_URL ?>/admin/api/test_printnode.php?action=printers', {
                        headers: {'X-Requested-With':'XMLHttpRequest'}
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok && data.printers?.length) {
                            list.innerHTML = '<p class="text-muted small mb-1">Selecciona tu impresora:</p>' +
                                data.printers.map(p =>
                                    `<div class="d-flex align-items-center justify-content-between p-2 rounded mb-1"
                                          style="background:var(--black);border:1px solid var(--black-border);cursor:pointer;"
                                          onclick="document.querySelector('[name=\'cfg[printnode_printer_id]\']').value='${p.id}';this.style.borderColor='var(--gold)'">
                                        <span class="small">${p.name}</span>
                                        <span class="rp-badge rp-badge-confirmada text-gold small">ID: ${p.id}</span>
                                    </div>`
                                ).join('');
                        } else {
                            list.innerHTML = '<p class="text-muted small">' + (data.message || 'No se encontraron impresoras.') + '</p>';
                        }
                    })
                    .catch(() => { list.innerHTML = '<p class="text-muted small">Error al cargar impresoras.</p>'; });
                }
                </script>

                <?php elseif ($grupoActivo === 'pagos'): ?>
                <!-- ── Configuración de métodos de pago ─────────── -->
                <div class="row gy-4">

                    <!-- Info transferencia -->
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-bank text-gold fs-5"></i>
                            <h3 class="rp-display fs-6 mb-0">Datos para Transferencia Bancaria</h3>
                        </div>
                        <p class="text-muted small mb-0">
                            Esta información aparece en el checkout cuando el cliente selecciona "Transferencia".
                        </p>
                    </div>

                    <div class="col-md-6">
                        <label class="rp-form-label">Nombre del banco *</label>
                        <input type="text" name="cfg[pago_banco]"
                               class="rp-form-control form-control"
                               placeholder="BBVA, Banamex, Chase…"
                               value="<?= h(cfg('pago_banco','BBVA México')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="rp-form-label">Titular de la cuenta *</label>
                        <input type="text" name="cfg[pago_titular]"
                               class="rp-form-control form-control"
                               placeholder="Nombre del titular"
                               value="<?= h(cfg('pago_titular', cfg('site_nombre', APP_NAME))) ?>">
                    </div>

                    <div class="col-12">
                        <label class="rp-form-label">CLABE Interbancaria *</label>
                        <div class="position-relative">
                            <input type="text" name="cfg[pago_clabe]"
                                   id="inputClabe"
                                   class="rp-form-control form-control"
                                   placeholder="18 dígitos"
                                   maxlength="18"
                                   oninput="this.value=this.value.replace(/\D/g,'')"
                                   value="<?= h(cfg('pago_clabe','')) ?>">
                            <span id="clabeCount"
                                  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);
                                         font-size:.72rem;color:var(--white-dim);">
                                <?= strlen(cfg('pago_clabe','')) ?>/18
                            </span>
                        </div>
                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                            Solo números, exactamente 18 dígitos.
                        </p>
                    </div>

                    <div class="col-md-6">
                        <label class="rp-form-label">Número de cuenta (opcional)</label>
                        <input type="text" name="cfg[pago_cuenta]"
                               class="rp-form-control form-control"
                               placeholder="Ej: 0123456789"
                               value="<?= h(cfg('pago_cuenta','')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="rp-form-label">Concepto predefinido</label>
                        <input type="text" name="cfg[pago_concepto]"
                               class="rp-form-control form-control"
                               placeholder="Ej: Pago de pedido"
                               value="<?= h(cfg('pago_concepto','Pago de pedido')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="rp-form-label">Instrucciones adicionales</label>
                        <textarea name="cfg[pago_instrucciones]"
                                  class="rp-form-control form-control" rows="2"
                                  placeholder="Ej: Envía tu comprobante por WhatsApp…"><?= h(cfg('pago_instrucciones','Envía tu comprobante por WhatsApp para confirmar tu pedido.')) ?></textarea>
                    </div>

                    <!-- Preview -->
                    <div class="col-12">
                        <label class="rp-form-label d-block mb-2">Vista previa en checkout</label>
                        <div class="p-3 rounded-3" style="background:var(--black);border:1px solid var(--black-border);max-width:420px;">
                            <p class="rp-eyebrow mb-3" style="font-size:.6rem;">Datos bancarios</p>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Banco</span>
                                    <strong id="prev_banco"><?= h(cfg('pago_banco','BBVA México')) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Titular</span>
                                    <strong id="prev_titular"><?= h(cfg('pago_titular', cfg('site_nombre', APP_NAME))) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">CLABE</span>
                                    <strong id="prev_clabe" style="color:var(--gold);"><?= h(cfg('pago_clabe','012345678901234567')) ?></strong>
                                </div>
                                <?php if (cfg('pago_cuenta','')): ?>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Cuenta</span>
                                    <strong id="prev_cuenta"><?= h(cfg('pago_cuenta','')) ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Preview en vivo
                document.querySelector('[name="cfg[pago_banco]"]')?.addEventListener('input', e => {
                    document.getElementById('prev_banco').textContent = e.target.value || 'BBVA México';
                });
                document.querySelector('[name="cfg[pago_titular]"]')?.addEventListener('input', e => {
                    document.getElementById('prev_titular').textContent = e.target.value || 'Titular';
                });
                document.querySelector('[name="cfg[pago_clabe]"]')?.addEventListener('input', e => {
                    const val = e.target.value.replace(/\D/g,'');
                    document.getElementById('prev_clabe').textContent = val || '000000000000000000';
                    document.getElementById('clabeCount').textContent = val.length + '/18';
                    document.getElementById('clabeCount').style.color = val.length === 18 ? '#2ea043' : 'var(--white-dim)';
                });
                </script>

                <?php elseif ($grupoActivo === 'impuestos'): ?>
                <!-- ── Configuración de impuestos ───────────────── -->
                <?php
                require_once __DIR__ . '/../config/settings.php';
                $imp = getImpuesto();
                ?>
                <div class="row gy-4">
                    <!-- Toggle activo -->
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
                             style="background:var(--black);border:1px solid var(--black-border);">
                            <div>
                                <p class="fw-semibold mb-0">Cobrar impuesto</p>
                                <p class="text-muted small mb-0">Activa o desactiva el impuesto en todos los pedidos</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="cfg[impuesto_activo]" value="0">
                                <input class="form-check-input" type="checkbox"
                                       name="cfg[impuesto_activo]" id="chkImpActivo"
                                       value="1" <?= $imp['activo'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Nombre -->
                    <div class="col-md-6">
                        <label class="rp-form-label">Nombre del impuesto</label>
                        <input type="text" name="cfg[impuesto_nombre]"
                               class="rp-form-control form-control"
                               placeholder="IVA, ISR, Tax…"
                               value="<?= h(cfg('impuesto_nombre','IVA')) ?>">
                    </div>

                    <!-- Porcentaje -->
                    <div class="col-md-6">
                        <label class="rp-form-label">Porcentaje (%)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" name="cfg[impuesto_porcentaje]"
                                   class="rp-form-control form-control"
                                   min="0" max="100" step="0.01"
                                   id="impPct"
                                   value="<?= h(cfg('impuesto_porcentaje','16')) ?>"
                                   oninput="updateImpPreview()">
                            <span class="text-gold fw-semibold fs-5">%</span>
                        </div>
                    </div>

                    <!-- Tipo: incluido o adicional -->
                    <div class="col-12">
                        <label class="rp-form-label d-block mb-2">Tipo de impuesto</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label style="cursor:pointer;">
                                    <input type="radio" name="cfg[impuesto_incluido]"
                                           value="1" class="d-none rp-tax-radio"
                                           <?= cfg('impuesto_incluido','1') === '1' ? 'checked' : '' ?>>
                                    <div class="rp-form-card p-3 rp-tax-card <?= cfg('impuesto_incluido','1') === '1' ? 'selected' : '' ?>"
                                         style="border:2px solid <?= cfg('impuesto_incluido','1') === '1' ? 'var(--gold)' : 'var(--black-border)' ?>;">
                                        <p class="fw-semibold small mb-1">
                                            <i class="bi bi-check-circle-fill text-gold me-1"></i>
                                            Incluido en el precio
                                        </p>
                                        <p class="text-muted mb-0" style="font-size:.75rem;">
                                            Los precios ya incluyen el impuesto. Se muestra desglosado en el recibo pero no suma extra al total.
                                        </p>
                                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                                            Ej: Platillo $100 → Base $86.21 + IVA $13.79 = <strong>$100.00</strong>
                                        </p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label style="cursor:pointer;">
                                    <input type="radio" name="cfg[impuesto_incluido]"
                                           value="0" class="d-none rp-tax-radio"
                                           <?= cfg('impuesto_incluido','1') === '0' ? 'checked' : '' ?>>
                                    <div class="rp-form-card p-3 rp-tax-card <?= cfg('impuesto_incluido','1') === '0' ? 'selected' : '' ?>"
                                         style="border:2px solid <?= cfg('impuesto_incluido','1') === '0' ? 'var(--gold)' : 'var(--black-border)' ?>;">
                                        <p class="fw-semibold small mb-1">
                                            <i class="bi bi-plus-circle text-gold me-1"></i>
                                            Se suma al precio
                                        </p>
                                        <p class="text-muted mb-0" style="font-size:.75rem;">
                                            El impuesto se agrega encima del precio del platillo al momento del pago.
                                        </p>
                                        <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">
                                            Ej: Platillo $100 + IVA 16% = <strong>$116.00</strong>
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preview en vivo -->
                    <div class="col-12">
                        <div id="impPreview" class="p-3 rounded-3"
                             style="background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.2);">
                            <p class="rp-eyebrow mb-2">Vista previa con precio ejemplo de $100.00</p>
                            <div id="impPreviewContent"></div>
                        </div>
                    </div>
                </div>

                <script>
                function updateImpPreview() {
                    const pct      = parseFloat(document.getElementById('impPct').value) || 0;
                    const incluido = document.querySelector('[name="cfg[impuesto_incluido]"]:checked')?.value === '1';
                    const nombre   = document.querySelector('[name="cfg[impuesto_nombre]"]').value || 'IVA';
                    const precio   = 100;
                    let html = '';

                    if (!document.getElementById('chkImpActivo').checked || pct === 0) {
                        html = '<p class="text-muted small mb-0">Impuesto desactivado — precio final: <strong>$100.00</strong></p>';
                    } else if (incluido) {
                        const base = (precio / (1 + pct/100)).toFixed(2);
                        const imp  = (precio - base).toFixed(2);
                        html = `<div class="d-flex flex-column gap-1">
                            <div class="d-flex justify-content-between small"><span class="text-muted">Subtotal (sin ${nombre})</span><span>$${base}</span></div>
                            <div class="d-flex justify-content-between small"><span class="text-muted">${nombre} ${pct}% (incluido)</span><span style="color:var(--gold);">$${imp}</span></div>
                            <div class="d-flex justify-content-between fw-semibold" style="border-top:1px dashed rgba(201,168,76,.3);padding-top:.35rem;margin-top:.15rem;"><span>Total</span><span style="color:var(--gold);">$100.00</span></div>
                        </div>`;
                    } else {
                        const imp   = (precio * pct / 100).toFixed(2);
                        const total = (precio + parseFloat(imp)).toFixed(2);
                        html = `<div class="d-flex flex-column gap-1">
                            <div class="d-flex justify-content-between small"><span class="text-muted">Subtotal</span><span>$100.00</span></div>
                            <div class="d-flex justify-content-between small"><span class="text-muted">${nombre} ${pct}%</span><span style="color:var(--gold);">+$${imp}</span></div>
                            <div class="d-flex justify-content-between fw-semibold" style="border-top:1px dashed rgba(201,168,76,.3);padding-top:.35rem;margin-top:.15rem;"><span>Total</span><span style="color:var(--gold);">$${total}</span></div>
                        </div>`;
                    }
                    document.getElementById('impPreviewContent').innerHTML = html;
                }

                // Inicializar preview
                document.addEventListener('DOMContentLoaded', () => {
                    updateImpPreview();
                    // Eventos
                    document.querySelectorAll('.rp-tax-radio').forEach(r => {
                        r.addEventListener('change', () => {
                            document.querySelectorAll('.rp-tax-card').forEach(c => {
                                c.style.border = '2px solid var(--black-border)';
                            });
                            r.closest('label').querySelector('.rp-tax-card').style.border = '2px solid var(--gold)';
                            updateImpPreview();
                        });
                    });
                    document.getElementById('chkImpActivo').addEventListener('change', updateImpPreview);
                    document.querySelector('[name="cfg[impuesto_nombre]"]').addEventListener('input', updateImpPreview);
                });
                </script>

                <?php elseif ($grupoActivo === 'tipografia'): ?>
                <!-- ── Selector de fuentes ───────────────────── -->
                <?php
                $fontGroups = [
                    'fuente_display' => ['Fuente de Títulos y Encabezados', getFuentesDisplay()],
                    'fuente_body'    => ['Fuente de Texto General',          getFuentesBody()],
                ];
                foreach ($fontGroups as $clave => [$label, $opciones]):
                    $current = cfg($clave, array_key_first($opciones));
                ?>
                <div class="mb-4">
                    <label class="rp-form-label"><?= $label ?></label>
                    <div class="row g-2">
                        <?php foreach ($opciones as $val => $nombre): ?>
                        <div class="col-6 col-md-4">
                            <label>
                                <input type="radio" name="cfg[<?= $clave ?>]" value="<?= h($val) ?>"
                                       class="d-none" <?= $current === $val ? 'checked' : '' ?>>
                                <div class="rp-form-card p-3 text-center"
                                     style="cursor:pointer;border:2px solid <?= $current === $val ? 'var(--gold)' : 'var(--black-border)' ?>;
                                            font-family:'<?= $val ?>',serif;transition:all .2s;"
                                     onclick="this.closest('label').querySelector('input').checked=true;updateFontPreviews()">
                                    <p style="font-size:1.2rem;margin:0 0 .3rem;"><?= $val === 'Playfair Display' ? 'Aa Bb' : 'Aa Bb' ?></p>
                                    <p class="text-muted mb-0" style="font-size:.7rem;font-family:var(--font-body);"><?= $nombre ?></p>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                <!-- ── Campos genéricos ──────────────────────── -->
                <div class="row gy-3">
                <?php foreach ($camposGrupo as $campo):
                    $clave = $campo['clave'];
                    $val   = $settings[$clave] ?? $campo['valor'] ?? '';
                    $tipo  = $campo['tipo'];
                    $label = $campo['etiqueta'] ?? $clave;
                ?>
                    <div class="col-12 <?= $tipo === 'color' ? 'col-md-6' : '' ?>">
                        <label class="rp-form-label"><?= h($label) ?></label>

                        <?php if ($tipo === 'color'): ?>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="color" name="cfg[<?= h($clave) ?>]"
                                   value="<?= h($val ?: '#c9a84c') ?>"
                                   class="rp-form-control form-control form-control-color"
                                   style="height:44px;width:60px;padding:4px;cursor:pointer;">
                            <input type="text" id="hex_<?= $clave ?>"
                                   value="<?= h($val ?: '#c9a84c') ?>"
                                   class="rp-form-control form-control"
                                   maxlength="7"
                                   oninput="syncColor(this,'<?= $clave ?>')"
                                   style="width:110px;">
                            <div class="rp-color-preview" id="prev_<?= $clave ?>"
                                 style="background:<?= h($val) ?>;"></div>
                        </div>

                        <?php elseif ($tipo === 'imagen'): ?>
                        <?php
                        $imgPath = $val ? __DIR__ . '/../uploads/' . $val : '';
                        $imgUrl  = ($val && file_exists($imgPath)) ? APP_URL . '/uploads/' . h($val) : '';
                        ?>
                        <?php if ($imgUrl): ?>
                        <img src="<?= $imgUrl ?>" class="rp-img-preview" id="prev_img_<?= $clave ?>">
                        <?php else: ?>
                        <div id="prev_img_<?= $clave ?>"
                             style="width:100%;height:80px;background:var(--black);border:2px dashed var(--black-border);border-radius:6px;display:flex;align-items:center;justify-content:center;margin-bottom:.5rem;">
                            <i class="bi bi-image text-gold" style="font-size:1.5rem;opacity:.4"></i>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="img[<?= h($clave) ?>]"
                               accept="image/jpeg,image/png,image/webp,image/svg+xml"
                               class="rp-form-control form-control"
                               onchange="previewImg(this,'prev_img_<?= $clave ?>')">
                        <?php if ($val): ?>
                        <p class="text-muted" style="font-size:.72rem;margin-top:.3rem;">
                            Actual: <?= h(basename($val)) ?>
                        </p>
                        <?php endif; ?>

                        <?php elseif ($tipo === 'textarea'): ?>
                        <textarea name="cfg[<?= h($clave) ?>]"
                                  class="rp-form-control form-control" rows="3"><?= h($val) ?></textarea>

                        <?php elseif ($tipo === 'boolean'): ?>
                        <div class="form-check form-switch mt-1">
                            <input type="hidden" name="cfg[<?= h($clave) ?>]" value="0">
                                <input class="form-check-input" type="checkbox"
                                   name="cfg[<?= h($clave) ?>]" value="1"
                                   <?= $val ? 'checked' : '' ?>>
                        </div>

                        <?php else: ?>
                        <input type="text" name="cfg[<?= h($clave) ?>]"
                               class="rp-form-control form-control"
                               value="<?= h($val) ?>" maxlength="500">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-4 pt-3 border-top border-rp">
                    <button type="submit" class="rp-btn-gold btn px-5">
                        <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                    </button>
                    <a href="<?= APP_URL ?>/" target="_blank" class="rp-btn-outline btn ms-2">
                        <i class="bi bi-eye me-2"></i>Ver sitio
                    </a>
                </div>
            </div>
        </div>

        <!-- Preview rápido -->
        <div class="col-lg-4">
            <div class="rp-form-card" style="position:sticky;top:80px;">
                <h3 class="rp-display fs-6 mb-3">Vista Previa Rápida</h3>

                <!-- Mini preview del branding -->
                <div id="brandPreview"
                     style="background:<?= cfg('color_secundario','#0d0d0d') ?>;
                            border-radius:8px;padding:1.5rem;text-align:center;margin-bottom:1rem;">
                    <?php if (cfg('site_logo') && file_exists(__DIR__.'/../uploads/'.cfg('site_logo'))): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= h(cfg('site_logo')) ?>"
                         style="max-height:60px;max-width:100%;margin-bottom:.75rem;">
                    <?php else: ?>
                    <p style="font-family:<?= cfg('fuente_display','Playfair Display') ?>,serif;
                               font-size:1.4rem;color:<?= cfg('color_primario','#c9a84c') ?>;margin:0 0 .5rem;">
                        ✦ <?= h(cfg('site_nombre', APP_NAME)) ?>
                    </p>
                    <?php endif; ?>
                    <p style="font-size:.8rem;color:<?= cfg('color_texto','#f5f5f0') ?>;opacity:.7;margin:0;">
                        <?= h(cfg('site_slogan','Slogan del restaurante')) ?>
                    </p>
                    <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1rem;">
                        <span style="background:<?= cfg('color_primario','#c9a84c') ?>;
                                     color:<?= cfg('color_secundario','#0d0d0d') ?>;
                                     padding:.4rem 1rem;border-radius:<?= cfg('tema_border_radius','4') ?>px;
                                     font-size:.75rem;font-weight:700;">
                            Reservar
                        </span>
                        <span style="border:1px solid <?= cfg('color_primario','#c9a84c') ?>;
                                     color:<?= cfg('color_primario','#c9a84c') ?>;
                                     padding:.4rem 1rem;border-radius:<?= cfg('tema_border_radius','4') ?>px;
                                     font-size:.75rem;">
                            Ver Menú
                        </span>
                    </div>
                </div>

                <!-- Info del sitio actual -->
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Nombre:</span>
                        <strong><?= h(cfg('site_nombre', APP_NAME)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Tema:</span>
                        <strong><?= getTemasPreset()[cfg('tema_preset','negro-dorado')][0] ?? 'Personalizado' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Fuente títulos:</span>
                        <strong><?= h(cfg('fuente_display','Playfair Display')) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Color principal:</span>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:18px;height:18px;border-radius:3px;background:<?= cfg('color_primario','#c9a84c') ?>;"></div>
                            <strong><?= cfg('color_primario','#c9a84c') ?></strong>
                        </div>
                    </div>
                </div>

                <hr class="border-rp my-3">
                <a href="<?= APP_URL ?>/" target="_blank" class="btn rp-btn-gold w-100">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Abrir Sitio Completo
                </a>
            </div>
        </div>
    </div>
</form>

<script>
// Sync color picker <-> input hex
function syncColor(input, clave) {
    const hex = input.value;
    const picker = document.querySelector(`input[type="color"][name="cfg[${clave}]"]`);
    const preview = document.getElementById('prev_' + clave);
    if (picker && /^#[0-9a-fA-F]{6}$/.test(hex)) {
        picker.value = hex;
        if (preview) preview.style.background = hex;
    }
}

// Sync hex input cuando cambia el color picker
document.querySelectorAll('input[type="color"]').forEach(picker => {
    picker.addEventListener('input', () => {
        const clave = picker.name.replace('cfg[','').replace(']','');
        const hexInput = document.getElementById('hex_' + clave);
        const preview  = document.getElementById('prev_' + clave);
        if (hexInput) hexInput.value = picker.value;
        if (preview)  preview.style.background = picker.value;
    });
});

// Preview imagen antes de subir
function previewImg(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const el = document.getElementById(previewId);
        if (!el) return;
        el.style.cssText = '';
        el.outerHTML = `<img src="${e.target.result}" class="rp-img-preview" id="${previewId}">`;
    };
    reader.readAsDataURL(file);
}

// Resaltar tema seleccionado
document.querySelectorAll('.rp-tema-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.rp-tema-card').forEach(c => c.classList.remove('selected'));
        radio.closest('label').querySelector('.rp-tema-card').classList.add('selected');
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer_admin.php'; ?>
