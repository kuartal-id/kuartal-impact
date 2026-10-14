/**
 * Kuartal Impact theme switcher (neon / dark / neutral).
 * The theme is applied before paint by the inlined theme-boot.js; this wires
 * up every [data-theme-set] button, persists the choice (see theme-boot.js for
 * the storage keys and how it syncs light/dark with other Kuartal sites) and
 * follows changes made in other tabs.
 */
import './theme-boot';

const T = window.kuartalImpactThemes;

function current() {
    const t = document.documentElement.getAttribute('data-theme');
    return T.THEMES.includes(t) ? t : 'dark';
}

function syncButtons() {
    const t = current();
    document.querySelectorAll('[data-theme-set]').forEach((btn) => {
        btn.setAttribute('aria-pressed', btn.dataset.themeSet === t ? 'true' : 'false');
    });
}

function set(theme) {
    if (!T.THEMES.includes(theme)) return;
    T.apply(document, theme);
    T.save(localStorage, theme);
    syncButtons();
    document.dispatchEvent(new CustomEvent('kuartal:theme', { detail: { theme } }));
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-set]').forEach((btn) => {
        btn.addEventListener('click', () => set(btn.dataset.themeSet));
    });
    syncButtons();
});

window.addEventListener('storage', (e) => {
    if (e.key === T.KEY || e.key === T.SHARED) {
        T.apply(document, T.resolve(localStorage));
        syncButtons();
        document.dispatchEvent(new CustomEvent('kuartal:theme', { detail: { theme: current() } }));
    }
});

window.kuartalTheme = { get: current, set };
