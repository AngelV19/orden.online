/**
 * RESTAURANT PREMIUM — admin.js
 */
'use strict';

/* ── Sidebar mobile toggle ───────────────────────────────── */
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    sb.classList.toggle('open');
    if (ov) ov.style.setProperty('display', sb.classList.contains('open') ? 'block' : 'none', 'important');
}

function closeSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    sb.classList.remove('open');
    if (ov) ov.style.setProperty('display', 'none', 'important');
}

/* ── Confirm delete ──────────────────────────────────────── */
function confirmDelete(msg) {
    return confirm(msg || '¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.');
}

/* ── Flash message auto-hide ─────────────────────────────── */
const flashMsg = document.querySelector('.rp-flash');
if (flashMsg) {
    setTimeout(() => { flashMsg.style.opacity = '0'; flashMsg.style.transition = 'opacity .5s'; setTimeout(() => flashMsg.remove(), 500); }, 4000);
}

/* ── Preview image on file input ─────────────────────────── */
const imgInput   = document.getElementById('imageInput');
const imgPreview = document.getElementById('imagePreview');

if (imgInput && imgPreview) {
    imgInput.addEventListener('change', () => {
        const file = imgInput.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => { imgPreview.src = e.target.result; imgPreview.style.display = ''; };
            reader.readAsDataURL(file);
        }
    });
}

/* ── Inline status update for reservas ──────────────────────── */
document.querySelectorAll('.rp-status-select').forEach(sel => {
    sel.addEventListener('change', async () => {
        const id     = sel.dataset.id;
        const estado = sel.value;
        try {
            const res  = await fetch(window.ADMIN_URL + '/admin/api/update_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `id=${id}&estado=${estado}`
            });
            const data = await res.json();
            if (!data.ok) sel.value = sel.dataset.prev;
            else { sel.dataset.prev = estado; showToast(data.ok ? 'Estado actualizado' : 'Error al actualizar', data.ok ? 'success' : 'error'); }
        } catch { sel.value = sel.dataset.prev; }
    });
    sel.dataset.prev = sel.value;
});

/* ── Toast ───────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.75rem 1.25rem;border-radius:6px;font-size:.85rem;font-weight:500;transition:opacity .4s;`;
    t.style.background = type === 'success' ? 'rgba(46,160,67,.9)' : 'rgba(224,92,92,.9)';
    t.style.color = '#fff';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 2500);
}
