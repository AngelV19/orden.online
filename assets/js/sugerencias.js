/**
 * RESTAURANT PREMIUM — sugerencias.js
 * Modal de sugerencias antes del checkout
 */
'use strict';

const SugerenciasModal = (() => {

    let modal = null;

    // ── Abrir modal con sugerencias ───────────────────────
    async function abrir() {
        const items  = Cart.getItems ? Cart.getItems() : [];
        const cartIds = items.map(i => i.idOriginal || i.id);

        // Si carrito vacío, ir directo al checkout
        if (!items.length) {
            irCheckout();
            return;
        }

        const modalEl = document.getElementById('sugerenciasModal');
        if (!modalEl) { irCheckout(); return; }

        modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Actualizar total en el modal
        const totalEl = document.getElementById('sugerenciasTotal');
        if (totalEl && Cart.total) {
            const imp = window.RpImpuesto || { activo: false, pct: 0, incluido: true };
            let t = Cart.total();
            if (imp.activo && imp.pct > 0 && !imp.incluido) t = t * (1 + imp.pct / 100);
            totalEl.textContent = '· $' + t.toFixed(2) + ' MXN';
        }

        // Mostrar loading
        document.getElementById('sugerenciasGrid').innerHTML = `
            <div class="col-12 text-center py-4">
                <div class="spinner-border text-gold" role="status" style="width:2rem;height:2rem;"></div>
                <p class="text-muted small mt-2">Buscando sugerencias…</p>
            </div>`;

        modal.show();

        // Cargar sugerencias del servidor
        try {
            const idsParam = encodeURIComponent(JSON.stringify(cartIds));
            const res  = await fetch(`${window.APP_URL}/pages/api/sugerencias.php?ids=${idsParam}`);
            const data = await res.json();
            renderSugerencias(data);
        } catch(e) {
            // Si falla, ir directo al checkout
            modal.hide();
            irCheckout();
        }
    }

    // ── Renderizar sugerencias ────────────────────────────
    function renderSugerencias(platillos) {
        const grid = document.getElementById('sugerenciasGrid');

        if (!platillos.length) {
            // Sin sugerencias — ir directo
            modal?.hide();
            irCheckout();
            return;
        }

        grid.innerHTML = platillos.map(p => {
            const imgHtml = p.imagen
                ? `<img src="${p.imagen}" alt="${esc(p.nombre)}"
                        style="width:100%;height:130px;object-fit:cover;"
                        onerror="this.style.display='none'">`
                : `<div style="width:100%;height:130px;background:var(--black);
                               display:flex;align-items:center;justify-content:center;">
                       <span style="font-family:var(--font-display);font-size:2rem;color:var(--gold);">
                           ${esc(p.nombre.charAt(0))}
                       </span>
                   </div>`;

            return `
            <div class="col-6 col-md-3" data-platillo-json='${JSON.stringify({
                id:     p.id + '_' + Date.now(),
                idOriginal: p.id,
                nombre: p.nombre,
                precio: p.precio,
                img:    p.imagen || '',
                extras: [],
                notas:  '',
                precioBase: p.precio,
            }).replace(/'/g, "&#39;")}'>
                <div class="rp-sug-card" id="sugCard_${p.id}">
                    <div style="border-radius:8px;overflow:hidden;margin-bottom:.65rem;">
                        ${imgHtml}
                    </div>
                    <p class="rp-sug-categoria">${esc(p.categoria)}</p>
                    <p class="rp-sug-nombre">${esc(p.nombre)}</p>
                    ${p.descripcion ? `<p class="rp-sug-desc">${esc(p.descripcion)}…</p>` : ''}
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <span class="rp-sug-precio">$${p.precio.toFixed(2)}</span>
                        <button class="rp-sug-btn" id="sugBtn_${p.id}"
                                onclick="SugerenciasModal.agregarSugerencia(${p.id}, this)">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // ── Agregar sugerencia al carrito ─────────────────────
    function agregarSugerencia(platilloId, btn) {
        const card = document.querySelector(`[data-platillo-json]`);
        // Encontrar el card correcto
        const cards = document.querySelectorAll('[data-platillo-json]');
        let platillo = null;

        cards.forEach(c => {
            try {
                const data = JSON.parse(c.dataset.platilloJson.replace(/&#39;/g, "'"));
                if (data.idOriginal === platilloId) platillo = data;
            } catch(e) {}
        });

        if (!platillo) return;

        Cart.add(platillo);

        // Feedback visual
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        btn.style.background = '#2ea043';
        btn.style.borderColor = '#2ea043';
        btn.style.color = '#fff';
        btn.disabled = true;

        // Actualizar total en el modal
        const totalEl = document.getElementById('sugerenciasTotal');
        if (totalEl && Cart.total) {
            const imp = window.RpImpuesto || { activo: false, pct: 0, incluido: true };
            let t = Cart.total();
            if (imp.activo && imp.pct > 0 && !imp.incluido) t = t * (1 + imp.pct / 100);
            totalEl.textContent = '· $' + t.toFixed(2) + ' MXN';
        }
    }

    // ── Ir al checkout ────────────────────────────────────
    function irCheckout() {
        modal?.hide();
        window.location.href = window.APP_URL + '/pages/checkout.php';
    }

    // ── Helper ────────────────────────────────────────────
    function esc(s) {
        return String(s || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    return { abrir, agregarSugerencia, irCheckout };
})();

window.SugerenciasModal = SugerenciasModal;
