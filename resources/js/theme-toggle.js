/**
 * Kuartal shared light/dark mode toggle.
 * No framework dependency — plain JS, same pattern used across the
 * WordPress builds. Persists to localStorage, respects system preference
 * on first visit, and applies the theme BEFORE paint (see the inline
 * snippet in layouts/app.blade.php) to avoid a flash of wrong theme.
 *
 * Usage: any element with [data-theme-toggle] flips the theme on click.
 */
(function () {
  const STORAGE_KEY = 'kuartal-theme';

  function getStoredTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      return null;
    }
  }

  function storeTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) {
      /* private-mode / storage disabled: theme just won't persist */
    }
  }

  function systemPrefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  function applyTheme(theme) {
    const root = document.documentElement;
    if (theme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    root.setAttribute('data-theme', theme);
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    });
  }

  function currentTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  function initToggleButtons() {
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const next = currentTheme() === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        storeTheme(next);
      });
    });
  }

  // Theme is applied instantly by the inline head script (see layout),
  // this just wires up the buttons and keeps state in sync across tabs.
  document.addEventListener('DOMContentLoaded', initToggleButtons);

  window.addEventListener('storage', function (e) {
    if (e.key === STORAGE_KEY && e.newValue) {
      applyTheme(e.newValue);
    }
  });

  // Expose for other scripts if needed (e.g. Chart.js re-theming).
  window.kuartalTheme = {
    get: currentTheme,
    set: function (theme) {
      applyTheme(theme);
      storeTheme(theme);
    },
  };
})();
