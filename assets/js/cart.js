/**
 * RESTAURANT PREMIUM — cart.js
 * Carrito de compras con localStorage
 */
'use strict';

const Cart = (() => {
    const KEY = 'rp_cart';

    // ── Persistencia ──────────────────────────────────────
    function load() {
        try { return JSON.parse(localStorage.getItem(KEY)) || []; }
        catch { return []; }
    }

    function save(items) {
        localStorage.setItem(KEY, JSON.stringify(items));
    }

    // ── CRUD del carrito ──────────────────────────────────
    function getItems()  { return load(); }
    function clear()     { save([]); render(); updateBadge(); }

    function add(platillo) {
        const items = load();
        const idx   = items.findIndex(i => String(i.id) === String(platillo.id));
        if (idx >= 0) { items[idx].qty++; }
        else          { items.push({ ...platillo, qty: 1 }); }
        save(items);
        render();
        updateBadge(true);
    }

    function remove(id) {
        save(load().filter(i => String(i.id) !== String(id)));
        render();
        updateBadge();
    }

    function changeQty(id, delta) {
        const items = load();
        const idx   = items.findIndex(i => String(i.id) === String(id));
        if (idx < 0) return;
        items[idx].qty += delta;
        if (items[idx].qty <= 0) items.splice(idx, 1);
        save(items);
        render();
        updateBadge();
    }

    function total() {
        return load().reduce((sum, i) => {
            // precioBase = precio del platillo sin extras
            // extras = array de extras con su precio individual
            const base   = parseFloat(i.precioBase || i.precio) || 0;
            const extras = (i.extras || []).reduce((s, e) => s + (parseFloat(e.precio) || 0), 0);
            return sum + (base + extras) * i.qty;
        }, 0);
    }

    function count() {
        return load().reduce((sum, i) => sum + i.qty, 0);
    }

    // ── UI ────────────────────────────────────────────────
    function updateBadge(bump = false) {
        const badges = document.querySelectorAll('.rp-cart-count');
        const n = count();
        badges.forEach(b => {
            b.textContent = n;
            b.style.display = n > 0 ? '' : 'none';
            if (bump) {
                b.classList.add('bump');
                setTimeout(() => b.classList.remove('bump'), 300);
            }
        });
    }

    function fmtPrice(p) {
        return '$' + Number(p).toFixed(2);
    }

    function render() {
        const container = document.getElementById('cartItems');
        if (!container) return;

        const items = load();

        if (!items.length) {
            container.innerHTML = `
                <div class="rp-cart-empty">
                    <i class="bi bi-bag-x"></i>
                    <p>Tu carrito está vacío.<br>Agrega platillos desde el menú.</p>
                    <a href="${window.APP_URL}/pages/menu.php" class="btn rp-btn-gold btn-sm mt-2">
                        Ver Menú
                    </a>
                </div>`;
            updateTotalDisplay(0);
            return;
        }
        // Actualizar total ANTES de renderizar items para que el desglose se vea
        updateTotalDisplay(total());

        container.innerHTML = items.map(item => {
            // Extras y modificadores
            const extrasHtml = (item.extras && item.extras.length)
                ? `<div class="rp-cart-item__extras">
                    ${item.extras.map(e =>
                        `<span class="rp-cart-extra-tag">
                            ${e.nombre}${parseFloat(e.precio) > 0 ? ' <span style="color:var(--gold)">+$'+parseFloat(e.precio).toFixed(2)+'</span>' : ''}
                        </span>`
                    ).join('')}
                   </div>`
                : '';

            // Notas
            const notasHtml = item.notas
                ? `<p class="rp-cart-item__nota"><i class="bi bi-chat-dots me-1"></i>${item.notas}</p>`
                : '';

            // Precio base vs total
            const precioBaseHtml = item.precioBase && item.precioBase !== item.precio
                ? `<span class="rp-cart-item__precio-base">$${parseFloat(item.precioBase).toFixed(2)}</span> `
                : '';

            return `
            <div class="rp-cart-item" data-id="${item.id}">
                <img class="rp-cart-item__img"
                     src="${item.img || ''}"
                     alt="${item.nombre}"
                     onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2244%22><rect width=%2256%22 height=%2244%22 fill=%22%231c1c1c%22/><text x=%2228%22 y=%2228%22 fill=%22%23c9a84c%22 font-size=%2218%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22>${item.nombre.charAt(0)}</text></svg>'">
                <div class="flex-grow-1 overflow-hidden">
                    <p class="rp-cart-item__name text-truncate mb-0">${item.nombre}</p>
                    ${extrasHtml}
                    ${notasHtml}
                    <p class="rp-cart-item__price mb-0 mt-1">
                        ${precioBaseHtml}${fmtPrice(((parseFloat(item.precioBase || item.precio) || 0) + (item.extras || []).reduce((s,e) => s+(parseFloat(e.precio)||0),0)) * item.qty)}
                    </p>
                </div>
                <div class="rp-cart-item__qty">
                    <button class="rp-qty-btn" onclick="Cart.changeQty('${item.id}', -1)">−</button>
                    <span class="rp-qty-num">${item.qty}</span>
                    <button class="rp-qty-btn" onclick="Cart.changeQty('${item.id}', +1)">+</button>
                </div>
                <button class="rp-cart-item__remove" onclick="Cart.remove('${item.id}')" title="Eliminar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>`;
        }).join('');

        updateTotalDisplay(total());
    }

    function updateTotalDisplay(t) {
        const imp = window.RpImpuesto || { activo: false, pct: 0, nombre: 'IVA', incluido: true };

        let subtotal   = t;
        let impMonto   = 0;
        let totalFinal = t;

        if (imp.activo && imp.pct > 0) {
            if (imp.incluido) {
                subtotal   = t / (1 + imp.pct / 100);
                impMonto   = t - subtotal;
                totalFinal = t;
            } else {
                subtotal   = t;
                impMonto   = t * imp.pct / 100;
                totalFinal = t + impMonto;
            }
        }

        // ── Total principal ───────────────────────────
        document.querySelectorAll('.rp-cart-total-value').forEach(el => {
            el.textContent = '$' + totalFinal.toFixed(2) + ' MXN';
        });

        // ── Desglose: siempre actualizar, mostrar solo si hay impuesto activo ─
        const subtotalEl = document.getElementById('cartSubtotalVal');
        const taxLabel   = document.getElementById('cartTaxLabel');
        const taxVal     = document.getElementById('cartTaxVal');
        const breakdown  = document.getElementById('cartTaxBreakdown');

        if (breakdown) {
            if (imp.activo && imp.pct > 0) {
                // Mostrar siempre que impuesto esté activo (aunque t=0)
                breakdown.style.display = '';
                if (subtotalEl) subtotalEl.textContent = '$' + subtotal.toFixed(2);
                if (taxLabel)   taxLabel.textContent   = imp.nombre + ' ' + imp.pct + '%' + (imp.incluido ? ' (incluido)' : '');
                if (taxVal)     taxVal.textContent     = (imp.incluido ? '' : '+') + '$' + impMonto.toFixed(2);
            } else {
                breakdown.style.display = 'none';
            }
        }

        // ── Total checkout summary ────────────────────
        document.querySelectorAll('.rp-checkout-total-val').forEach(el => {
            el.textContent = '$' + totalFinal.toFixed(2) + ' MXN';
        });

        // ── Botón checkout ────────────────────────────
        const btn = document.getElementById('checkoutBtn');
        if (btn) btn.disabled = (count() === 0);
    }

    // ── Inicializar ───────────────────────────────────────
    function init() {
        render();
        updateBadge();

        // Re-render cuando se abre el offcanvas del carrito
        const cartCanvas = document.getElementById('cartOffcanvas');
        if (cartCanvas) {
            cartCanvas.addEventListener('show.bs.offcanvas', () => {
                render();
                updateTotalDisplay(total());
            });
        }

        // Botones "Agregar" en tarjetas de platillos
        document.querySelectorAll('[data-add-platillo]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const el = btn.closest('[data-platillo-json]');
                if (!el) return;
                try {
                    const platillo = JSON.parse(el.dataset.platilloJson);

                    // Verificar si el platillo tiene opciones
                    const res    = await fetch(`${window.APP_URL}/pages/api/opciones_platillo.php?id=${platillo.id}`);
                    const grupos = await res.json();

                    if (grupos && grupos.length > 0) {
                        // Abrir modal de opciones
                        OpcionesModal.abrir(platillo);
                    } else {
                        // Sin opciones → agregar directo
                        Cart.add(platillo);
                        btn.classList.add('added');
                        btn.innerHTML = '<i class="bi bi-check-lg"></i> Agregado';
                        setTimeout(() => {
                            btn.classList.remove('added');
                            btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar';
                        }, 1800);
                        if (window.innerWidth < 768) {
                            const oc = document.getElementById('cartOffcanvas');
                            if (oc && typeof bootstrap !== 'undefined') {
                                bootstrap.Offcanvas.getOrCreateInstance(oc).show();
                            }
                        }
                    }
                } catch(e) { console.error('Cart add error', e); }
            });
        });

        // Poplar checkout si estamos en esa página
        renderCheckoutSummary();
    }

    // ── Checkout summary ──────────────────────────────────
    function renderCheckoutSummary() {
        const el = document.getElementById('checkoutItemsPreview');
        if (!el) return;

        const items = load();
        if (!items.length) {
            window.location.href = window.APP_URL + '/pages/menu.php';
            return;
        }

        el.innerHTML = items.map(i => {
            const extrasHtml = (i.extras && i.extras.length)
                ? `<div class="rp-checkout-extras">
                    ${i.extras.map(e =>
                        `<span class="rp-checkout-extra-tag">${e.nombre}${parseFloat(e.precio) > 0 ? ' +$'+parseFloat(e.precio).toFixed(2) : ''}</span>`
                    ).join('')}
                   </div>`
                : '';
            const notaHtml = i.notas
                ? `<p class="rp-checkout-item__nota"><i class="bi bi-chat-dots me-1"></i>${i.notas}</p>`
                : '';
            return `
            <div class="rp-checkout-item" style="flex-direction:column;align-items:flex-start;">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <span class="rp-checkout-item__name">${i.nombre}</span>
                        <span class="rp-checkout-item__qty ms-2">×${i.qty}</span>
                    </div>
                    <span class="rp-checkout-item__price">${fmtPrice(((parseFloat(i.precioBase || i.precio)||0) + (i.extras||[]).reduce((s,e)=>s+(parseFloat(e.precio)||0),0)) * i.qty)}</span>
                </div>
                ${extrasHtml}
                ${notaHtml}
            </div>`;
        }).join('');

        // ── Desglose con impuesto en checkout ────────
        const imp = window.RpImpuesto || { activo: false, pct: 0, nombre: 'IVA', incluido: true };
        const t   = total();
        let subtotal   = t;
        let impMonto   = 0;
        let totalFinal = t;

        if (imp.activo && imp.pct > 0) {
            if (imp.incluido) {
                subtotal   = t / (1 + imp.pct / 100);
                impMonto   = t - subtotal;
                totalFinal = t;
            } else {
                subtotal   = t;
                impMonto   = t * imp.pct / 100;
                totalFinal = t + impMonto;
            }
        }

        // Actualizar total final
        document.querySelectorAll('.rp-checkout-total-val').forEach(el => {
            el.textContent = '$' + totalFinal.toFixed(2) + ' MXN';
        });

        // Desglose subtotal + impuesto en el resumen del checkout
        const taxSummary = document.getElementById('checkoutTaxSummary');
        if (taxSummary) {
            if (imp.activo && imp.pct > 0) {
                taxSummary.style.display = '';
                taxSummary.innerHTML = `
                    <div class="rp-checkout-item" style="border-top:1px dashed rgba(201,168,76,.2);margin-top:.25rem;padding-top:.5rem;">
                        <span class="rp-checkout-item__qty" style="color:var(--white-dim);font-size:.78rem;">Subtotal</span>
                        <span class="rp-checkout-item__price" style="color:var(--white-dim);font-size:.78rem;">$${subtotal.toFixed(2)}</span>
                    </div>
                    <div class="rp-checkout-item">
                        <span class="rp-checkout-item__qty" style="color:var(--gold);font-size:.78rem;">
                            ${imp.nombre} ${imp.pct}%${imp.incluido ? ' <span style="opacity:.65;font-size:.7rem;">(incluido)</span>' : ''}
                        </span>
                        <span class="rp-checkout-item__price" style="color:var(--gold);font-size:.78rem;font-weight:600;">
                            ${imp.incluido ? '' : '+'}$${impMonto.toFixed(2)}
                        </span>
                    </div>`;
            } else {
                taxSummary.style.display = 'none';
            }
        }

        // Inyectar items en input hidden para enviar al servidor
        const input = document.getElementById('cartDataInput');
        if (input) input.value = JSON.stringify(items);
    }

    return { add, remove, changeQty, clear, getItems, total, count, init, render };
})();

// Exponer globalmente
window.Cart = Cart;

document.addEventListener('DOMContentLoaded', () => Cart.init());

/* ── Método de pago ──────────────────────────────────────── */
function seleccionarPago(el) {
    // Quitar selected de todos
    document.querySelectorAll('.rp-pago-opcion').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');

    const metodo = el.dataset.metodo;
    document.getElementById('metodoPagoInput').value = metodo;

    // Ocultar todos los paneles
    document.querySelectorAll('.rp-pago-panel').forEach(p => p.style.display = 'none');

    // Mostrar panel del método seleccionado
    const panel = document.getElementById('panel-' + metodo);
    if (panel) panel.style.display = '';
}

// Formatear número de tarjeta: 1234 5678 9012 3456
function formatCard(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = v.replace(/(.{4})/g, '$1 ').trim();

    // Detectar tipo de tarjeta por el número
    const icon = document.querySelector('#cardNumber ~ i');
    if (!icon) return;
    if      (/^4/.test(v))      icon.className = 'bi bi-credit-card-2-front position-absolute top-50 end-0 translate-middle-y me-3 text-gold';
    else if (/^5[1-5]/.test(v)) icon.className = 'bi bi-credit-card-fill position-absolute top-50 end-0 translate-middle-y me-3 text-gold';
    else if (/^3[47]/.test(v))  icon.className = 'bi bi-credit-card-2-back position-absolute top-50 end-0 translate-middle-y me-3 text-gold';
    else                         icon.className = 'bi bi-credit-card position-absolute top-50 end-0 translate-middle-y me-3 text-muted';
}

// Formatear fecha de vencimiento: MM / AA
function formatExpiry(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 4);
    if (v.length >= 2) v = v.substring(0, 2) + ' / ' + v.substring(2);
    input.value = v;
}

// Copiar CLABE al portapapeles
function copiarClabe() {
    const clabe = document.getElementById('clabeNum')?.textContent?.replace(/\s/g, '');
    if (!clabe) return;
    navigator.clipboard.writeText(clabe).then(() => {
        const btn = document.querySelector('[onclick="copiarClabe()"]');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            btn.style.color = '#2ea043';
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-copy"></i>';
                btn.style.color = 'var(--gold)';
            }, 2000);
        }
    });
}
