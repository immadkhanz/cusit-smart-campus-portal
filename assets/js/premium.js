/**
 * CUSIT Smart Campus Portal — Premium Effects
 * Custom cursor, Lenis smooth scroll, AOS init
 */
document.addEventListener('DOMContentLoaded', () => {

    /* ── Custom Cursor ── */
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        const cursor = document.createElement('div');
        cursor.classList.add('custom-cursor');
        const dot = document.createElement('div');
        dot.classList.add('cursor-dot');
        document.body.appendChild(cursor);
        document.body.appendChild(dot);

        let cx = 0, cy = 0, dx = 0, dy = 0;

        document.addEventListener('mousemove', e => {
            dx = e.clientX;
            dy = e.clientY;
            // Dot follows instantly
            dot.style.left = dx + 'px';
            dot.style.top = dy + 'px';
        });

        // Smooth cursor ring follow
        function animateCursor() {
            cx += (dx - cx) * 0.15;
            cy += (dy - cy) * 0.15;
            cursor.style.left = cx + 'px';
            cursor.style.top = cy + 'px';
            requestAnimationFrame(animateCursor);
        }
        animateCursor();

        // Hover effect on interactive elements
        const interactives = document.querySelectorAll('a, button, input, select, textarea, .stat-card, .event-card, .ann-card, .content-card, .btn-primary, .nav-item');
        interactives.forEach(el => {
            el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
            el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
        });
    }

    /* ── AOS (Animate on Scroll) ── */
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });
    }

    /* ── Lenis Smooth Scroll ── */
    if (typeof Lenis !== 'undefined') {
        const lenis = new Lenis({
            duration: 1.2,
            easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smooth: true
        });
        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);
    }

    /* ── Staggered Card Entrance ── */
    const cards = document.querySelectorAll('.stat-card, .content-card, .event-card, .ann-card');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + i * 80);
    });

});
