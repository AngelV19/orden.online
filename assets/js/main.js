/**
 * RESTAURANT PREMIUM — main.js
 */
'use strict';

/* ── Navbar scroll effect ─────────────────────────────────────── */
const navbar = document.getElementById('mainNavbar');
if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 60);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

/* ── Hero background Ken Burns ───────────────────────────────── */
const heroBg = document.querySelector('.rp-hero__bg');
if (heroBg) {
    setTimeout(() => heroBg.classList.add('loaded'), 100);
}

/* ── Scroll reveal (IntersectionObserver) ───────────────────── */
const revealEls = document.querySelectorAll('.rp-reveal');
if (revealEls.length) {
    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    revealEls.forEach(el => io.observe(el));
}

/* ── Counter animation (stats) ───────────────────────────────── */
function animateCount(el) {
    const target = parseInt(el.dataset.count, 10);
    if (isNaN(target)) return;
    const dur    = 1800;
    const start  = performance.now();
    const suffix = el.dataset.suffix || '';
    const step   = (now) => {
        const p = Math.min((now - start) / dur, 1);
        const ease = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(ease * target) + suffix;
        if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

const statsSection = document.querySelector('.rp-stats');
if (statsSection) {
    const io2 = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            document.querySelectorAll('[data-count]').forEach(animateCount);
            io2.disconnect();
        }
    }, { threshold: 0.5 });
    io2.observe(statsSection);
}

/* ── Menu filter + search ────────────────────────────────────── */
const filterBtns  = document.querySelectorAll('.rp-filter-pill');
// Usar data-categoria para las cards (data-cat era compartido con los botones)
const menuCards   = document.querySelectorAll('[data-categoria]');
const searchInput = document.querySelector('#menuSearch');

function filterMenu() {
    const active = document.querySelector('.rp-filter-pill.active');
    const cat    = active ? active.dataset.cat : 'all';
    const q      = searchInput ? searchInput.value.toLowerCase().trim() : '';

    menuCards.forEach(card => {
        const matchCat  = (cat === 'all') || (card.dataset.categoria === cat);
        const name      = (card.dataset.name || '').toLowerCase();
        const desc      = (card.dataset.desc || '').toLowerCase();
        const matchQ    = !q || name.includes(q) || desc.includes(q);
        card.style.display = (matchCat && matchQ) ? '' : 'none';
    });

    // Empty state
    const visible = [...menuCards].filter(c => c.style.display !== 'none');
    const empty   = document.querySelector('#menuEmpty');
    if (empty) empty.style.display = visible.length ? 'none' : '';
}

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterMenu();
    });
});

if (searchInput) {
    searchInput.addEventListener('input', filterMenu);
}

/* ── GLightbox ───────────────────────────────────────────────── */
if (typeof GLightbox !== 'undefined') {
    GLightbox({ selector: '.glightbox', touchNavigation: true, loop: true, openEffect: 'fade', closeEffect: 'fade' });
}

/* ── Testimonials carousel (Bootstrap Carousel) ─────────────── */
const testCarousel = document.querySelector('#testimonialCarousel');
if (testCarousel && typeof bootstrap !== 'undefined') {
    new bootstrap.Carousel(testCarousel, { interval: 5000, ride: 'carousel' });
}

/* ── Reservation form ────────────────────────────────────────── */
const resForm = document.querySelector('#reservationForm');
if (resForm) {
    // Min date = today
    const dateInput = resForm.querySelector('[name="fecha"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }

    resForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = resForm.querySelector('[type="submit"]');
        const alertBox = document.querySelector('#formAlert');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';

        try {
            const resp = await fetch(resForm.action, {
                method: 'POST',
                body: new FormData(resForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();

            if (alertBox) {
                alertBox.className = 'rp-alert rp-alert--' + (data.ok ? 'success' : 'error');
                alertBox.textContent = data.message;
                alertBox.style.display = '';
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (data.ok) resForm.reset();
        } catch {
            if (alertBox) {
                alertBox.className = 'rp-alert rp-alert--error';
                alertBox.textContent = 'Error de conexión. Por favor intenta de nuevo.';
                alertBox.style.display = '';
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Confirmar Reservación';
        }
    });
}

/* ── Smooth scroll for anchor links ─────────────────────────── */
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = 90; // navbar height
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
            // Close mobile navbar if open
            const navCollapse = document.querySelector('.navbar-collapse');
            if (navCollapse && navCollapse.classList.contains('show')) {
                document.querySelector('.navbar-toggler')?.click();
            }
        }
    });
});

/* ── Active nav link on scroll ───────────────────────────────── */
const sections = document.querySelectorAll('section[id]');
const navLinks  = document.querySelectorAll('.navbar-nav .nav-link[href*="#"]');

if (sections.length && navLinks.length) {
    const ioNav = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                navLinks.forEach(link => {
                    link.classList.toggle('active', link.getAttribute('href').endsWith('#' + entry.target.id));
                });
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px' });
    sections.forEach(s => ioNav.observe(s));
}
