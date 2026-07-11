/**
 * The Wall: every loop bounced today (and before), playable in place -
 * playback is local synthesis from the stored pattern, so the wall weighs
 * kilobytes, not megabytes. New bounces arrive live over WebSockets.
 */

import { Engine } from './audio.js';

const $ = (sel, el = document) => el.querySelector(sel);

const engine = new Engine();
let playingCard = null;

function thumbSvg(pattern, accent) {
    const cell = 7;
    const gap = 2;
    const w = 16 * (cell + gap);
    const h = 6 * (cell + gap);
    let rects = '';
    pattern.forEach((row, r) => {
        row.forEach((v, s) => {
            const fill = v ? accent : '#20202e';
            const op = v === 2 ? 1 : v ? 0.75 : 1;
            rects += `<rect x="${s * (cell + gap)}" y="${r * (cell + gap)}" width="${cell}" height="${cell}" rx="1.5" fill="${fill}" opacity="${op}"/>`;
        });
    });
    return `<svg class="thumb" viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg">${rects}</svg>`;
}

const ACCENTS = { techno: '#c6ff3e', lofi: '#ffb454', downtempo: '#ff3dae' };

export function renderLoopCard(loop, { fresh = false } = {}) {
    const el = document.createElement('article');
    el.className = `wall-card p-4 flex flex-col gap-3 vibe-${loop.vibe}${fresh ? ' fresh' : ''}`;
    el.id = `loop-${loop.id}`;
    const accent = ACCENTS[loop.vibe] ?? ACCENTS.techno;
    el.innerHTML = `
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="font-bold truncate">${esc(loop.handle)}</p>
                ${loop.title ? `<p class="text-sm text-dim truncate">&ldquo;${esc(loop.title)}&rdquo;</p>` : ''}
            </div>
            <button class="btn-spin shrink-0 w-10 h-10 rounded-full border border-line grid place-items-center hover:border-acid transition-colors" aria-label="play loop">
                <svg class="i-play w-3.5 h-3.5" viewBox="0 0 12 14" fill="currentColor"><path d="M0 0l12 7-12 7z"/></svg>
                <svg class="i-stop w-3 h-3 hidden" viewBox="0 0 10 10" fill="currentColor"><rect width="10" height="10"/></svg>
            </button>
        </div>
        <div class="thumb-holder">${thumbSvg(loop.pattern, accent)}</div>
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="chip">${esc(loop.card_name)}</span>
            <span class="chip">${esc(loop.key)}</span>
            <span class="chip">${loop.bpm} BPM</span>
            <span class="chip spins" title="full plays">&#9654; <span class="n">${loop.spins}</span></span>
        </div>`;

    const btn = $('.btn-spin', el);
    btn.addEventListener('click', () => togglePlay(loop, el));
    return el;
}

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function togglePlay(loop, el) {
    const wasPlaying = playingCard === el;
    if (playingCard) stopVisual(playingCard);
    engine.stop();
    playingCard = null;
    if (wasPlaying) return;

    engine.ensureContext();
    engine.mutes = new Set();
    engine.load({ bpm: loop.bpm, vibe: loop.vibe, pattern: loop.pattern, notes: loop.notes });
    let loops = 0;
    engine.onLoop = () => {
        loops += 1;
        if (loops === 1) {
            fetch(`/api/loops/${loop.id}/spin`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            }).then((r) => r.ok && r.json()).then((d) => {
                if (d) $('.spins .n', el).textContent = d.spins;
            }).catch(() => {});
        }
    };
    engine.onStep = null;
    engine.start();
    playingCard = el;
    $('.i-play', el).classList.add('hidden');
    $('.i-stop', el).classList.remove('hidden');
}

function stopVisual(el) {
    $('.i-play', el).classList.remove('hidden');
    $('.i-stop', el).classList.add('hidden');
}

function initWall() {
    const root = $('#wall-root');
    if (!root) return;

    // hydrate server-rendered loop data
    const groups = JSON.parse(root.dataset.groups);
    Object.entries(groups).forEach(([day, loops]) => {
        const section = $(`[data-day="${day}"] .wall-grid`);
        if (!section) return;
        loops.forEach((loop) => section.appendChild(renderLoopCard(loop)));
    });

    // live arrivals
    if (window.Echo) {
        window.Echo.channel('wall').listen('.loop.bounced', (loop) => {
            const section = $('[data-today]');
            const grid = section ? $('.wall-grid', section) : $('.wall-grid');
            if (!grid) return;
            if (section) section.classList.remove('hidden');
            $('#wall-empty')?.remove();
            grid.prepend(renderLoopCard(loop, { fresh: true }));
            const counter = $('#today-count');
            if (counter) counter.textContent = Number(counter.textContent) + 1;
        });
    }

    // deep link
    if (location.hash.startsWith('#loop-')) {
        const target = $(location.hash);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('fresh');
        }
    }
}

document.addEventListener('DOMContentLoaded', initWall);
