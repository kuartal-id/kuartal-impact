/**
 * Admin helpers (no dependencies):
 *  - [data-rich-editor]: a small contenteditable editor that writes HTML
 *    into its <textarea> (the server sanitises it again on save).
 *  - [data-slug-source]/[data-slug-target]: suggest a slug from the title.
 *  - [data-copy]: copy a URL to the clipboard.
 */
function initEditor(wrap) {
    const textarea = wrap.querySelector('textarea');
    const area = wrap.querySelector('[data-editor-area]');
    const bar = wrap.querySelector('[data-editor-bar]');
    if (!textarea || !area || !bar) return;

    area.innerHTML = textarea.value;
    textarea.hidden = true;
    area.hidden = false;
    bar.hidden = false;

    const sync = () => { textarea.value = area.innerHTML; };
    area.addEventListener('input', sync);
    area.addEventListener('blur', sync);
    textarea.form?.addEventListener('submit', sync);

    bar.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-cmd]');
        if (!btn) return;
        e.preventDefault();
        area.focus();
        const cmd = btn.dataset.cmd;
        if (cmd === 'link') {
            const url = window.prompt('Link URL (https://…, mailto:… or /path)');
            if (url) document.execCommand('createLink', false, url);
        } else if (cmd === 'image') {
            const url = window.prompt('Image URL (copy it from the Media library)');
            if (url) document.execCommand('insertImage', false, url);
        } else if (cmd === 'source') {
            const showing = !textarea.hidden;
            if (showing) { area.innerHTML = textarea.value; }
            else { sync(); }
            textarea.hidden = showing;
            area.hidden = !showing;
            return;
        } else if (btn.dataset.value) {
            document.execCommand(cmd, false, btn.dataset.value);
        } else {
            document.execCommand(cmd, false, null);
        }
        sync();
    });
}

function slugify(s) {
    return s.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 150);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-rich-editor]').forEach(initEditor);

    const src = document.querySelector('[data-slug-source]');
    const target = document.querySelector('[data-slug-target]');
    if (src && target) {
        let touched = target.value !== '';
        target.addEventListener('input', () => { touched = true; });
        src.addEventListener('input', () => { if (!touched) target.placeholder = slugify(src.value); });
    }

    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(new URL(btn.dataset.copy, window.location.origin).href);
                const old = btn.textContent; btn.textContent = 'Copied'; setTimeout(() => { btn.textContent = old; }, 1500);
            } catch (e) { window.prompt('Copy this URL', btn.dataset.copy); }
        });
    });
});
