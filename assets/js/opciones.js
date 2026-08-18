/**
 * RESTAURANT PREMIUM — opciones.js (reescrito)
 */
'use strict';

const OpcionesModal = (() => {

    let platillo  = null;   // platillo actual
    let grupos    = [];     // grupos cargados
    // selecciones: { grupo_id: [ {id, nombre, precio}, ... ] }
    let sel       = {};

    // ── Abrir modal ───────────────────────────────────────
    async function abrir(p) {
        platillo = p;
        sel      = {};
        grupos   = [];

        const modalEl = document.getElementById('opcionesModal');
        if (!modalEl) { Cart.add(platillo); return; }

        // Rellenar encabezado
        document.getElementById('modalPlatilloNombre').textContent = p.nombre;
        document.getElementById('modalPlatilloImg').src            = p.img || '';
        document.getElementById('modalPlatilloPrecio').textContent = '$' + p.precio.toFixed(2) + ' MXN';
        document.getElementById('modalNotaInput').value            = '';
        document.getElementById('modalError').style.display        = 'none';
        document.getElementById('modalGrupos').innerHTML =
            '<div class="rp-modal-loading"><div class="rp-modal-spinner"></div><span>Cargando opciones…</span></div>';

        actualizarTotal();

        // Mostrar modal
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        // Cargar opciones
        try {
            const res = await fetch(window.APP_URL + '/pages/api/opciones_platillo.php?id=' + p.id);
            grupos    = await res.json();
            renderGrupos();
        } catch(e) {
            document.getElementById('modalGrupos').innerHTML =
                '<p class="text-muted text-center py-3">Error al cargar opciones.</p>';
        }
    }

    // ── Renderizar grupos ─────────────────────────────────
    function renderGrupos() {
        const cont = document.getElementById('modalGrupos');

        if (!grupos.length) {
            cont.innerHTML =
                '<div class="text-center py-3">' +
                '<i class="bi bi-bag-check text-gold fs-2 mb-2 d-block" style="opacity:.5"></i>' +
                '<p class="text-muted">Sin opciones adicionales.</p></div>';
            return;
        }

        const tipoLabel = { extra:'Extra', complemento:'Complemento', modificador:'Modificador' };
        const tipoCss   = { extra:'rp-tipo-badge-extra', complemento:'rp-tipo-badge-complemento', modificador:'rp-tipo-badge-modificador' };

        cont.innerHTML = grupos.map(g => {
            const items = g.opciones.map(op => {
                const precioTxt = op.precio > 0
                    ? '+$' + parseFloat(op.precio).toFixed(2)
                    : 'Sin costo';
                return `
                <div class="rp-opcion-item-check ${g.multiple ? '' : 'radio'}"
                     id="op_${g.id}_${op.id}"
                     data-gid="${g.id}"
                     data-oid="${op.id}"
                     data-precio="${op.precio}"
                     data-nombre="${esc(op.nombre)}">
                    <div class="rp-opcion-check-icon"><i class="bi bi-check"></i></div>
                    <div class="flex-grow-1">
                        <span class="rp-opcion-nombre">${esc(op.nombre)}</span>
                        ${op.descripcion ? `<span class="rp-opcion-desc">${esc(op.descripcion)}</span>` : ''}
                    </div>
                    <span class="rp-opcion-precio ${op.precio > 0 ? '' : 'gratis'}">${precioTxt}</span>
                </div>`;
            }).join('');

            return `
            <div class="rp-opcion-grupo">
                <div class="rp-opcion-grupo__header">
                    <div>
                        <p class="rp-opcion-grupo__title">${esc(g.nombre)}</p>
                        ${g.descripcion ? `<p class="rp-opcion-grupo__sub">${esc(g.descripcion)}</p>` : ''}
                        <p class="rp-opcion-grupo__sub mt-1">
                            <span class="rp-tipo-badge ${tipoCss[g.tipo]}">${tipoLabel[g.tipo]}</span>
                            <span class="ms-1 text-muted" style="font-size:.68rem;">
                                ${g.multiple ? 'Varios' : 'Elige uno'}
                                ${g.max_sel > 0 ? ' · máx. ' + g.max_sel : ''}
                            </span>
                        </p>
                    </div>
                    ${g.requerido ? '<span class="rp-required-badge">Requerido</span>' : ''}
                </div>
                <div class="rp-opcion-items">${items}</div>
            </div>`;
        }).join('');

        // Adjuntar eventos con click directo en cada div
        cont.querySelectorAll('.rp-opcion-item-check').forEach(div => {
            div.addEventListener('click', function() {
                const gid    = parseInt(this.dataset.gid);
                const oid    = parseInt(this.dataset.oid);
                const precio = parseFloat(this.dataset.precio) || 0;
                const nombre = this.dataset.nombre;
                const grupo  = grupos.find(g => g.id === gid);
                if (!grupo) return;

                if (!sel[gid]) sel[gid] = [];

                const yaSeleccionado = sel[gid].some(s => s.id === oid);

                if (!grupo.multiple) {
                    // Radio: limpiar todos del grupo
                    sel[gid] = [];
                    cont.querySelectorAll(`.rp-opcion-item-check[data-gid="${gid}"]`)
                        .forEach(d => d.classList.remove('selected'));
                }

                if (yaSeleccionado && grupo.multiple) {
                    // Deseleccionar
                    sel[gid] = sel[gid].filter(s => s.id !== oid);
                    this.classList.remove('selected');
                } else if (!yaSeleccionado) {
                    // Verificar máximo
                    if (grupo.max_sel > 0 && sel[gid].length >= grupo.max_sel) {
                        const quitado = sel[gid].shift();
                        cont.querySelector(`.rp-opcion-item-check[data-gid="${gid}"][data-oid="${quitado.id}"]`)
                            ?.classList.remove('selected');
                    }
                    sel[gid].push({ id: oid, nombre, precio });
                    this.classList.add('selected');
                }

                actualizarTotal();
            });
        });
    }

    // ── Calcular total ────────────────────────────────────
    function actualizarTotal() {
        if (!platillo) return;

        let extras = 0;
        Object.values(sel).forEach(g => g.forEach(op => { extras += parseFloat(op.precio) || 0; }));

        const total = platillo.precio + extras;

        const elBase   = document.getElementById('modalPrecioBase');
        const elExtras = document.getElementById('modalPrecioExtras');
        const elTotal  = document.getElementById('modalPrecioTotal');

        if (elBase)   elBase.textContent   = 'Base: $' + platillo.precio.toFixed(2);
        if (elExtras) elExtras.textContent = extras > 0 ? 'Extras: +$' + extras.toFixed(2) : '';
        if (elTotal)  elTotal.textContent  = 'Total: $' + total.toFixed(2) + ' MXN';
    }

    // ── Confirmar pedido ──────────────────────────────────
    function confirmar() {
        if (!platillo) return;

        // Validar requeridos
        for (const g of grupos) {
            if (g.requerido && (!sel[g.id] || !sel[g.id].length)) {
                const errEl = document.getElementById('modalError');
                errEl.textContent    = 'Por favor selecciona una opción en "' + g.nombre + '"';
                errEl.style.display  = '';
                setTimeout(() => errEl.style.display = 'none', 3500);
                return;
            }
        }

        // Calcular precio final
        let extras = 0;
        const opcionesSeleccionadas = [];
        Object.values(sel).forEach(g => g.forEach(op => {
            extras += parseFloat(op.precio) || 0;
            opcionesSeleccionadas.push(op);
        }));

        const notas = (document.getElementById('modalNotaInput')?.value || '').trim();

        const item = {
            ...platillo,
            precio:     platillo.precio + extras,
            precioBase: platillo.precio,
            extras:     opcionesSeleccionadas,
            notas:      notas,
            // clave única para permitir el mismo platillo con distintas opciones
            id:         platillo.id + '_' + Date.now(),
            idOriginal: platillo.id,
        };

        Cart.add(item);

        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('opcionesModal'))?.hide();

        // Feedback en botón
        const btn = document.querySelector(`[data-add-platillo][data-pid="${platillo.id}"]`);
        if (btn) {
            btn.classList.add('added');
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Agregado';
            setTimeout(() => {
                btn.classList.remove('added');
                btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar';
            }, 1800);
        }
    }

    // ── Helper ────────────────────────────────────────────
    function esc(s) {
        return String(s || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { abrir, confirmar };
})();

window.OpcionesModal = OpcionesModal;
