import './theme-toggle';
import { initField } from './field';

document.addEventListener('DOMContentLoaded', function () {
    // Mobile nav: [data-nav-toggle] opens [data-nav-panel]; the panel scrolls, the page is locked.
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

    // Desktop dropdowns: button[data-dd] toggles its sibling [data-dd-panel]; Esc / outside click closes.
    // Desktop dropdowns: button[data-dd] toggles the [data-dd-panel] in its <li>; Esc / outside click closes.
    document.querySelectorAll('[data-dd]').forEach((btn) => {
        const wrap = btn.closest('li') || btn.parentElement;
        const menu = wrap.querySelector('[data-dd-panel]');
        if (!menu) return;
        const set = (open) => { menu.classList.toggle('hidden', !open); btn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
        btn.addEventListener('click', () => set(menu.classList.contains('hidden')));
        wrap.addEventListener('keydown', (e) => { if (e.key === 'Escape') { set(false); btn.focus(); } });
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) set(false); });
        wrap.addEventListener('focusout', (e) => { if (!wrap.contains(e.relatedTarget)) set(false); });
    });

    document.querySelectorAll('[data-field]').forEach(initField);
});
