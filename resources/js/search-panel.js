/**
 * Search panel (as on kuartal.id): a rounded slide-over from the right, opened by
 * [data-search-toggle]. Closes on [data-search-close], backdrop click or Escape.
 * Searches the links listed in the panel's browse state (site pages + funds).
 */
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.querySelector('[data-search-panel]');
    const backdrop = document.querySelector('[data-search-backdrop]');
    if (!panel || !backdrop) return;
    const input = panel.querySelector('[data-search-input]');
    const browse = panel.querySelector('[data-search-browse]');
    const results = panel.querySelector('[data-search-results]');
    const empty = panel.querySelector('[data-search-empty]');
    const toggles = document.querySelectorAll('[data-search-toggle]');
    const index = Array.from(browse.querySelectorAll('a[href]')).map((a) => ({
        title: a.textContent.trim(), url: a.getAttribute('href'), category: a.dataset.category || '',
    }));
    let last = null;

    const open = () => {
        last = document.activeElement;
        backdrop.classList.remove('hidden');
        panel.classList.remove('hidden');
        requestAnimationFrame(() => panel.classList.remove('translate-x-[calc(100%+0.75rem)]'));
        panel.setAttribute('aria-hidden', 'false');
        toggles.forEach((t) => t.setAttribute('aria-expanded', 'true'));
        document.documentElement.classList.add('k-nav-locked');
        setTimeout(() => input && input.focus(), 50);
    };
    const close = () => {
        panel.classList.add('translate-x-[calc(100%+0.75rem)]');
        panel.setAttribute('aria-hidden', 'true');
        backdrop.classList.add('hidden');
        toggles.forEach((t) => t.setAttribute('aria-expanded', 'false'));
        document.documentElement.classList.remove('k-nav-locked');
        setTimeout(() => panel.classList.add('hidden'), 300);
        if (last) last.focus();
    };
    toggles.forEach((t) => t.addEventListener('click', open));
    panel.querySelectorAll('[data-search-close]').forEach((b) => b.addEventListener('click', close));
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.classList.contains('hidden')) close(); });

    const esc = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    input && input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        browse.classList.toggle('hidden', q !== '');
        if (!q) { results.classList.add('hidden'); empty.classList.add('hidden'); return; }
        const hits = index.filter((i) => (i.title + ' ' + i.category).toLowerCase().includes(q));
        results.innerHTML = hits.map((h) => '<a href="' + esc(h.url) + '" class="block rounded-lg px-3 py-2 hover:bg-gray-bg dark:hover:bg-white/5">'
            + '<span class="block font-subhead text-sm text-black dark:text-white">' + esc(h.title) + '</span>'
            + '<span class="block text-xs text-gray-muted dark:text-white/55">' + esc(h.category) + '</span></a>').join('');
        results.classList.toggle('hidden', hits.length === 0);
        empty.classList.toggle('hidden', hits.length !== 0);
    });
});
