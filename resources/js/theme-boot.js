/*
 * Kuartal Impact theme boot. Inlined in <head> (layouts/app.blade.php) so the
 * theme is applied before first paint, and loaded by theme-toggle.js.
 *
 * Three themes: 'neon' (lime site colour, black type), 'dark' (black canvas,
 * neon accents) and 'neutral' (warm cream + deep forest).
 *
 * Storage:
 *   kuartal-impact-theme  the exact choice on this site (neon | dark | neutral)
 *   kuartal-theme         the light/dark mode shared by every Kuartal property.
 *                         dark -> 'dark'; neon and neutral both write 'light'.
 * If another Kuartal site flipped the shared mode since our last visit, the
 * shared mode wins: dark -> 'dark', light -> the last light theme chosen here
 * (kuartal-impact-light) or 'neutral'. No stored choice at all -> 'dark'.
 */
(function (w) {
    var THEMES = ['neon', 'dark', 'neutral'];
    var KEY = 'kuartal-impact-theme', SHARED = 'kuartal-theme', LIGHT = 'kuartal-impact-light';

    function mode(t) { return t === 'dark' ? 'dark' : 'light'; }
    function valid(t) { return THEMES.indexOf(t) !== -1; }
    function get(store, k) { try { return store.getItem(k); } catch (e) { return null; } }
    function put(store, k, v) { try { store.setItem(k, v); } catch (e) { /* storage disabled */ } }

    function resolve(store) { return resolveValues(get(store, KEY), get(store, SHARED), get(store, LIGHT)); }

    function resolveValues(mine, shared, light) {
        if (valid(mine) && (shared !== 'dark' && shared !== 'light' || mode(mine) === shared)) return mine;
        if (shared === 'dark') return 'dark';
        if (shared === 'light') return valid(light) && light !== 'dark' ? light : 'neutral';
        return 'dark';
    }

    function save(store, theme) {
        if (!valid(theme)) return;
        put(store, KEY, theme);
        put(store, SHARED, mode(theme));
        if (theme !== 'dark') put(store, LIGHT, theme);
    }

    function apply(doc, theme) {
        var root = doc.documentElement;
        root.setAttribute('data-theme', theme);
        root.classList.toggle('dark', theme === 'dark');
        root.style.colorScheme = mode(theme);
        var meta = doc.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', { neon: '#9eff1f', dark: '#050505', neutral: '#f3eee3' }[theme]);
    }

    w.kuartalImpactThemes = { THEMES: THEMES, KEY: KEY, SHARED: SHARED, resolve: resolve, resolveValues: resolveValues, save: save, apply: apply, mode: mode };
})(typeof window !== 'undefined' ? window : globalThis);
