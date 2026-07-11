/**
 * Minimal type-1 MIDI writer: turns a bounced Dealt pattern into a real
 * .mid file - drums on channel 10, bass / chords / lead on 1-3 - so a loop
 * made in the browser can be dragged straight into FL Studio, Ableton, or
 * Logic. The bridge from toy to DAW.
 */

const PPQ = 96;
const STEP_TICKS = PPQ / 4;

const varLen = (n) => {
    const bytes = [n & 0x7f];
    n >>= 7;
    while (n > 0) {
        bytes.unshift((n & 0x7f) | 0x80);
        n >>= 7;
    }
    return bytes;
};

const str = (s) => [...s].map((c) => c.charCodeAt(0));
const u32 = (n) => [(n >> 24) & 255, (n >> 16) & 255, (n >> 8) & 255, n & 255];
const u16 = (n) => [(n >> 8) & 255, n & 255];

function track(events) {
    // events: [{tick, data: [...]}] sorted by tick
    const out = [];
    let last = 0;
    for (const e of events) {
        out.push(...varLen(e.tick - last), ...e.data);
        last = e.tick;
    }
    out.push(...varLen(0), 0xff, 0x2f, 0x00);
    return [...str('MTrk'), ...u32(out.length), ...out];
}

const GM_DRUMS = { kick: 36, clap: 39, hat: 42 };

export function patternToMidi({ pattern, notes, bpm, title }) {
    const rows = ['kick', 'clap', 'hat', 'bass', 'chord', 'lead'];
    const tempoEvents = [
        { tick: 0, data: [0xff, 0x03, title.length, ...str(title)] },
        { tick: 0, data: [0xff, 0x51, 0x03, ...u32(Math.round(60000000 / bpm)).slice(1)] },
    ];

    const drumEvents = [];
    const tonalTracks = { bass: [], chord: [], lead: [] };

    pattern.forEach((row, r) => {
        const name = rows[r];
        row.forEach((cell, step) => {
            if (!cell) return;
            const tick = step * STEP_TICKS;
            const vel = cell === 2 ? 118 : 92;
            if (r <= 2) {
                const note = GM_DRUMS[name];
                drumEvents.push({ tick, data: [0x99, note, vel] });
                drumEvents.push({ tick: tick + STEP_TICKS / 2, data: [0x89, note, 0] });
            } else if (name === 'bass') {
                const note = notes.bass[(cell - 1) % notes.bass.length];
                tonalTracks.bass.push({ tick, data: [0x90, note, vel] });
                tonalTracks.bass.push({ tick: tick + STEP_TICKS, data: [0x80, note, 0] });
            } else if (name === 'chord') {
                const chord = notes.chords[(cell - 1) % notes.chords.length];
                chord.forEach((note) => {
                    tonalTracks.chord.push({ tick, data: [0x91, note, vel - 12] });
                    tonalTracks.chord.push({ tick: tick + STEP_TICKS * 2, data: [0x81, note, 0] });
                });
            } else {
                const note = notes.lead[(cell - 1) % notes.lead.length];
                tonalTracks.lead.push({ tick, data: [0x92, note, vel] });
                tonalTracks.lead.push({ tick: tick + STEP_TICKS, data: [0x82, note, 0] });
            }
        });
    });

    const sortT = (evs) => evs.sort((a, b) => a.tick - b.tick);
    const tracks = [
        track(tempoEvents),
        track(sortT(drumEvents)),
        track(sortT(tonalTracks.bass)),
        track(sortT(tonalTracks.chord)),
        track(sortT(tonalTracks.lead)),
    ];

    const header = [...str('MThd'), ...u32(6), ...u16(1), ...u16(tracks.length), ...u16(PPQ)];
    return new Blob([new Uint8Array([...header, ...tracks.flat()])], { type: 'audio/midi' });
}

export function downloadMidi(args) {
    const blob = patternToMidi(args);
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${args.title.replace(/[^\w-]+/g, '-').toLowerCase() || 'dealt-loop'}.mid`;
    a.click();
    URL.revokeObjectURL(a.href);
}
