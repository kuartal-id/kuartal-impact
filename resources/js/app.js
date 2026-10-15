import './theme-toggle';
import './search-panel';

/**
 * Mobile nav toggle, shared across Kuartal properties (scrolling-panel fix from kuartal-id PR #5).
 * Trigger [data-nav-toggle], panel [data-nav-panel]. Desktop dropdowns: button[data-dd].
 */
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');
    if (trigger && panel) {
        const root = document.documentElement;
        const setOpen = (open) => {
            panel.classList.toggle('hidden', !open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            root.classList.toggle('k-nav-locked', open);
            if (open) panel.scrollTop = 0;
        };
        trigger.addEventListener('click', () => setOpen(panel.classList.contains('hidden')));
        panel.addEventListener('click', (e) => { if (e.target.closest('a[href]')) setOpen(false); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !panel.classList.contains('hidden')) { setOpen(false); trigger.focus(); }
        });
        window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => { if (e.matches) setOpen(false); });
    }

    document.querySelectorAll('[data-dd]').forEach((btn) => {
        const wrap = btn.closest('[data-dd-wrap]') || btn.parentElement;
        const menu = wrap.querySelector('[data-dd-panel]');
        if (!menu) return;
        const set = (open) => { menu.classList.toggle('hidden', !open); btn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
        btn.addEventListener('click', () => set(menu.classList.contains('hidden')));
        wrap.addEventListener('keydown', (e) => { if (e.key === 'Escape') { set(false); btn.focus(); } });
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) set(false); });
        wrap.addEventListener('focusout', (e) => { if (!wrap.contains(e.relatedTarget)) set(false); });
    });
});
