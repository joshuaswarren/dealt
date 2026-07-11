/**
 * The Dealt session: deal screen -> pick (or deadman) -> live grid -> bounce.
 * State machine is deliberately simple; all audio goes through Engine, all
 * persistence is localStorage (streaks, handle) + one POST (the bounce).
 */

import { Engine } from './audio.js';
import { downloadMidi } from './midi.js';

const $ = (sel, el = document) => el.querySelector(sel);
const $$ = (sel, el = document) => [...el.querySelectorAll(sel)];

const store = {
    get(key, fallback = null) {
        try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; }
    },
    set(key, value) { localStorage.setItem(key, JSON.stringify(value)); },
};

export function initGame(root) {
    const deal = JSON.parse(root.dataset.deal);
    const engine = new Engine();
    let card = null;
    let touched = 0;
    let loopsHeard = 0;
    let deadmanTimer = null;
    let sessionStart = null;

    /* ---------- deal screen ---------- */

    const dealScreen = $('#deal-screen');
    const sessionScreen = $('#session-screen');

    function startDeadman() {
        const el = $('#deadman-count');
        let left = 30;
        deadmanTimer = setInterval(() => {
            left -= 1;
            if (el) el.textContent = `0:${String(left).padStart(2, '0')}`;
            if (left <= 0) {
                clearInterval(deadmanTimer);
                pick(0, true);
            }
        }, 1000);
    }

    function pick(index, byHouse = false) {
        clearInterval(deadmanTimer);
        card = deal.cards[index];
        engine.ensureContext();
        engine.load(card);
        if (byHouse) {
            const stamp = document.createElement('div');
            stamp.className = 'stamp fixed left-1/2 top-1/3 -translate-x-1/2 z-50';
            stamp.textContent = 'HOUSE DEALS';
            document.body.appendChild(stamp);
            setTimeout(() => stamp.remove(), 1400);
        }
        renderSession();
        dealScreen.classList.add('hidden');
        sessionScreen.classList.remove('hidden');
        engine.start();
        sessionStart = Date.now();
        startClock();
    }

    $$('.deal-card', dealScreen).forEach((el) => {
        el.addEventListener('click', () => pick(Number(el.dataset.index)));
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(Number(el.dataset.index)); }
        });
    });

    startDeadman();

    /* ---------- session ---------- */

    const ROWS = ['KICK', 'CLAP', 'HAT', 'BASS', 'CHORD', 'LEAD'];
    const DRUM_ROWS = 3;

    function renderSession() {
        $('#card-name').textContent = card.name;
        $('#card-key').textContent = card.key;
        $('#card-bpm').textContent = `${card.bpm}`;
        $('#card-rule').textContent = card.rule;
        sessionScreen.className = sessionScreen.className.replace(/vibe-\w+/g, '');
        sessionScreen.classList.add(`vibe-${card.vibe}`);

        const grid = $('#grid');
        grid.innerHTML = '';
        card.pattern.forEach((row, r) => {
            const label = document.createElement('button');
            label.className = 'row-label text-right pr-2';
            label.textContent = ROWS[r];
            label.title = 'mute / unmute';
            label.addEventListener('click', () => {
                label.classList.toggle('muted', engine.toggleMute(r));
            });
            grid.appendChild(label);

            const rowEl = document.createElement('div');
            rowEl.className = 'seq-row grid grid-cols-16 gap-[3px] sm:gap-[5px]';
            row.forEach((cell, s) => {
                const pad = document.createElement('button');
                pad.className = 'pad';
                pad.dataset.row = r;
                pad.dataset.step = s;
                paint(pad, cell);
                pad.addEventListener('click', () => {
                    const cur = engine.pattern[r][s];
                    let next;
                    if (r < DRUM_ROWS) {
                        next = cur === 0 ? 1 : cur === 1 ? 2 : 0;
                    } else {
                        const max = r === 3 ? card.notes.bass.length
                            : r === 4 ? card.notes.chords.length
                            : card.notes.lead.length;
                        next = cur >= max ? 0 : cur + 1;
                    }
                    engine.setCell(r, s, next);
                    paint(pad, next);
                    touched += 1;
                    updateBounceState();
                });
                rowEl.appendChild(pad);
            });
            grid.appendChild(rowEl);
        });
    }

    function paint(pad, value) {
        pad.classList.toggle('on', value > 0);
        pad.classList.toggle('accent-hit', value === 2 && Number(pad.dataset.row) < DRUM_ROWS);
        const r = Number(pad.dataset.row);
        if (r >= DRUM_ROWS && value > 0) {
            pad.textContent = value;
            pad.style.fontSize = '9px';
            pad.style.fontFamily = 'var(--font-mono)';
            pad.style.color = '#0a0a10';
            pad.style.display = 'grid';
            pad.style.placeItems = 'center';
        } else {
            pad.textContent = '';
        }
    }

    engine.onStep = (step) => {
        $$('.playing-col').forEach((el) => el.classList.remove('playing-col'));
        $$(`.pad[data-step="${step}"]`).forEach((el) => el.parentElement.classList.add('playing-col'));
        // narrower: mark the column via per-pad brightness
        $$('.pad').forEach((el) => el.classList.toggle('now', Number(el.dataset.step) === step));
    };

    engine.onLoop = () => {
        loopsHeard += 1;
        updateBounceState();
    };

    $('#btn-play').addEventListener('click', () => {
        if (engine.playing) {
            engine.stop();
            $('#btn-play').classList.remove('playing');
            $('#icon-play').classList.remove('hidden');
            $('#icon-stop').classList.add('hidden');
        } else {
            engine.start();
            $('#btn-play').classList.add('playing');
            $('#icon-play').classList.add('hidden');
            $('#icon-stop').classList.remove('hidden');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space' && card && !e.target.closest('input,textarea')) {
            e.preventDefault();
            $('#btn-play').click();
        }
    });

    function startClock() {
        const el = $('#session-clock');
        setInterval(() => {
            const s = Math.floor((Date.now() - sessionStart) / 1000);
            el.textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        }, 1000);
        $('#btn-play').classList.add('playing');
        $('#icon-play').classList.add('hidden');
        $('#icon-stop').classList.remove('hidden');
    }

    function updateBounceState() {
        // done requires: you touched the grid, and you heard it loop at least once
        $('#btn-bounce').disabled = !(touched >= 1 && loopsHeard >= 1);
    }

    /* ---------- bounce ---------- */

    const modal = $('#bounce-modal');
    $('#btn-bounce').addEventListener('click', () => {
        $('#handle-input').value = store.get('dealt.handle', '');
        modal.classList.remove('hidden');
        $('#handle-input').focus();
    });
    $('#bounce-cancel').addEventListener('click', () => modal.classList.add('hidden'));

    $('#bounce-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const handle = $('#handle-input').value.trim();
        const title = $('#title-input').value.trim();
        if (handle.length < 2) return;
        store.set('dealt.handle', handle);

        const res = await fetch('/api/loops', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                card_index: card.index, handle, title, pattern: engine.pattern,
            }),
        });

        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            $('#bounce-error').textContent = body.error ?? 'The wall refused that one. Try again.';
            $('#bounce-error').classList.remove('hidden');
            return;
        }

        const { loop } = await res.json();
        bumpStreak();
        modal.classList.add('hidden');
        showBounced(loop, { handle, title });
    });

    function bumpStreak() {
        const s = store.get('dealt.streak', { last: null, run: 0, best: 0 });
        const today = deal.day;
        if (s.last !== today) {
            const yesterday = new Date(new Date(today + 'T12:00:00').getTime() - 86400000)
                .toISOString().slice(0, 10);
            s.run = s.last === yesterday ? s.run + 1 : 1;
            s.best = Math.max(s.best, s.run);
            s.last = today;
            store.set('dealt.streak', s);
        }
        return s;
    }

    function showBounced(loop, { handle, title }) {
        const s = store.get('dealt.streak', { run: 1, best: 1 });
        $('#bounced-screen').classList.remove('hidden');
        $('#session-screen').classList.add('hidden');
        $('#bounced-streak').textContent = s.run > 1
            ? `${s.run}-day streak. Same table tomorrow.`
            : 'Day one of the streak. Same table tomorrow.';

        $('#btn-midi').addEventListener('click', () => downloadMidi({
            pattern: engine.pattern,
            notes: card.notes,
            bpm: card.bpm,
            title: title || `dealt-${deal.number}-${handle}`,
        }));

        $('#btn-share').addEventListener('click', async () => {
            const glyphs = engine.pattern[0].map((v, i) =>
                (engine.pattern[0][i] || engine.pattern[3][i]) ? '▣' : '▢').join('');
            const text = `DEALT #${deal.number} - ${card.name}\n${card.key} @ ${card.bpm} BPM\n${glyphs}\n${location.origin}/wall#loop-${loop.id}`;
            await navigator.clipboard.writeText(text);
            $('#btn-share').textContent = 'COPIED';
            setTimeout(() => ($('#btn-share').textContent = 'SHARE'), 1600);
        });

        $('#wall-link').href = `/wall#loop-${loop.id}`;
    }

    /* streak display in nav */
    const streak = store.get('dealt.streak', null);
    if (streak && streak.run > 0) {
        $('#nav-streak').textContent = `▮ ${streak.run}`;
        $('#nav-streak').classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = $('#game-root');
    if (root) initGame(root);
});
