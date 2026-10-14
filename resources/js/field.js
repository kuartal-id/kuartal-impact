/**
 * Hero contour map: topographic lines (marching squares over a slow-moving
 * height field) drawn on a canvas on top of the tech grid. Lines that land on
 * the grid's major levels are drawn heavier, like index contours on a survey map.
 *
 * Lightweight: coarse 18px sampling, ~15 fps, paused off-screen / in hidden tabs.
 * prefers-reduced-motion: one still frame, no animation. Colours come from the
 * theme tokens (--k-motif / --k-mark) and refresh when the theme changes.
 */
export function initField(canvas) {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const CELL = 18, LEVELS = 11, FRAME_MS = 66;
    let w = 0, h = 0, cols = 0, rows = 0, grid = new Float32Array(0), raf = 0, last = 0, t = 0, visible = true;
    let line = 'rgba(158,255,31,.3)', mark = '#9eff1f';

    const readColours = () => {
        const cs = getComputedStyle(canvas);
        line = cs.getPropertyValue('--k-motif').trim() || line;
        mark = cs.getPropertyValue('--k-mark').trim() || mark;
    };

    const height = (x, y, time) => {
        const u = x / 260, v = y / 260;
        return Math.sin(u * 1.1 + time * 0.9) * Math.cos(v * 0.9 - time * 0.4)
            + 0.6 * Math.sin(u * 0.5 - v * 1.3 + 1.7 + time * 0.5)
            + 0.35 * Math.cos(u * 2.1 + v * 1.7 - time * 0.7)
            + 0.5 * Math.sin((u + v) * 0.35 + 0.4);
    };

    const resize = () => {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = canvas.clientWidth; h = canvas.clientHeight;
        canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        cols = Math.ceil(w / CELL) + 1; rows = Math.ceil(h / CELL) + 1;
        grid = new Float32Array(cols * rows);
        draw();
    };

    // Interpolated crossing point on a cell edge.
    const lerp = (a, b, va, vb, lv) => a + (b - a) * ((lv - va) / (vb - va || 1e-6));

    function draw() {
        for (let j = 0; j < rows; j++) for (let i = 0; i < cols; i++) grid[j * cols + i] = height(i * CELL, j * CELL, t);
        ctx.clearRect(0, 0, w, h);
        ctx.lineJoin = 'round';
        for (let l = 0; l < LEVELS; l++) {
            const lv = -1.8 + (3.6 * (l + 0.5)) / LEVELS;
            const index = l % 4 === 2;
            ctx.strokeStyle = index ? mark : line;
            ctx.globalAlpha = index ? 0.5 : 1;
            ctx.lineWidth = index ? 1.25 : 0.9;
            ctx.beginPath();
            for (let j = 0; j < rows - 1; j++) {
                for (let i = 0; i < cols - 1; i++) {
                    const a = grid[j * cols + i], b = grid[j * cols + i + 1], c = grid[(j + 1) * cols + i + 1], d = grid[(j + 1) * cols + i];
                    const code = (a > lv ? 8 : 0) | (b > lv ? 4 : 0) | (c > lv ? 2 : 0) | (d > lv ? 1 : 0);
                    if (code === 0 || code === 15) continue;
                    const x = i * CELL, y = j * CELL;
                    const top = [lerp(x, x + CELL, a, b, lv), y], right = [x + CELL, lerp(y, y + CELL, b, c, lv)];
                    const bottom = [lerp(x, x + CELL, d, c, lv), y + CELL], left = [x, lerp(y, y + CELL, a, d, lv)];
                    const seg = (p, q) => { ctx.moveTo(p[0], p[1]); ctx.lineTo(q[0], q[1]); };
                    switch (code) {
                        case 1: case 14: seg(left, bottom); break;
                        case 2: case 13: seg(bottom, right); break;
                        case 3: case 12: seg(left, right); break;
                        case 4: case 11: seg(top, right); break;
                        case 5: seg(left, top); seg(bottom, right); break;
                        case 6: case 9: seg(top, bottom); break;
                        case 7: case 8: seg(left, top); break;
                        case 10: seg(left, bottom); seg(top, right); break;
                    }
                }
            }
            ctx.stroke();
        }
        ctx.globalAlpha = 1;
    }

    function frame(now) {
        raf = requestAnimationFrame(frame);
        if (now - last < FRAME_MS) return;
        last = now; t += 0.0035;
        draw();
    }
    const start = () => { if (!raf && visible && !document.hidden && !reduce.matches) raf = requestAnimationFrame(frame); };
    const stop = () => { cancelAnimationFrame(raf); raf = 0; };

    readColours();
    resize();
    new IntersectionObserver(([e]) => { visible = e.isIntersecting; visible ? start() : stop(); }).observe(canvas);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    document.addEventListener('kuartal:theme', () => { readColours(); draw(); });
    let rt = 0;
    window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(resize, 120); });
    reduce.addEventListener('change', (e) => { if (e.matches) { stop(); draw(); } else start(); });
    start();
}
