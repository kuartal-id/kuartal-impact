/**
 * Hero "field": a sparse lattice of points drifting slowly, joined by faint
 * lines when close. Canvas 2D, ~70 points, capped at 30 fps, paused when off
 * screen or the tab is hidden, and never started under prefers-reduced-motion
 * (the static CSS grid stays). Colour follows the theme.
 */
export function initField(canvas) {
    if (!canvas || !canvas.getContext) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reduce.matches) return;
    const ctx = canvas.getContext('2d');
    let w = 0, h = 0, dpr = 1, pts = [], raf = 0, last = 0, visible = true;

    const colour = () => (document.documentElement.classList.contains('dark') ? '158,255,31' : '5,6,5');
    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = canvas.clientWidth; h = canvas.clientHeight;
        canvas.width = w * dpr; canvas.height = h * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        const n = Math.round(Math.min(80, (w * h) / 16000));
        pts = Array.from({ length: n }, () => ({ x: Math.random() * w, y: Math.random() * h, vx: (Math.random() - .5) * .18, vy: (Math.random() - .5) * .18 }));
    }
    function frame(t) {
        raf = requestAnimationFrame(frame);
        if (t - last < 33) return; last = t;
        const c = colour();
        ctx.clearRect(0, 0, w, h);
        for (const p of pts) {
            p.x += p.vx; p.y += p.vy;
            if (p.x < 0 || p.x > w) p.vx *= -1;
            if (p.y < 0 || p.y > h) p.vy *= -1;
        }
        for (let i = 0; i < pts.length; i++) {
            const a = pts[i];
            for (let j = i + 1; j < pts.length; j++) {
                const b = pts[j], dx = a.x - b.x, dy = a.y - b.y, d = dx * dx + dy * dy;
                if (d < 14000) { ctx.strokeStyle = `rgba(${c},${(1 - d / 14000) * .16})`; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke(); }
            }
            ctx.fillStyle = `rgba(${c},.55)`; ctx.fillRect(a.x - 1, a.y - 1, 2, 2);
        }
    }
    const start = () => { if (!raf && visible && !document.hidden) raf = requestAnimationFrame(frame); };
    const stop = () => { cancelAnimationFrame(raf); raf = 0; };
    resize();
    new IntersectionObserver(([e]) => { visible = e.isIntersecting; visible ? start() : stop(); }).observe(canvas);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    window.addEventListener('resize', () => { resize(); });
    reduce.addEventListener('change', (e) => { if (e.matches) { stop(); ctx.clearRect(0, 0, w, h); } });
    start();
}
