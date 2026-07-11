/**
 * The Dealt sound engine. Six voices, all synthesized live in Web Audio -
 * no samples, no libraries. Tuned to read as late-90s FruityLoops techno:
 * a round 909-ish kick, snappy noise clap, tight hats, a saw bass with a
 * closing filter, detuned-saw chord stabs, and a delayed square lead.
 *
 * Scheduling uses the standard lookahead pattern: a 25ms JS tick books
 * audio events ~120ms ahead on the AudioContext clock, so the groove
 * survives tab jank.
 */

const LOOKAHEAD_MS = 25;
const HORIZON_S = 0.12;

const midiHz = (m) => 440 * Math.pow(2, (m - 69) / 12);

export class Engine {
    constructor() {
        this.ctx = null;
        this.playing = false;
        this.step = 0;
        this.nextTime = 0;
        this.timer = null;
        this.bpm = 140;
        this.swing = 0;
        this.pattern = [];
        this.notes = { bass: [], lead: [], chords: [] };
        this.mutes = new Set();
        this.vibe = 'techno';
        this.onStep = null;       // UI callback (step index, time delta)
        this.onLoop = null;       // called each time the pattern wraps
    }

    ensureContext() {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
            this.master = this.ctx.createDynamicsCompressor();
            this.master.threshold.value = -14;
            this.master.knee.value = 22;
            this.master.ratio.value = 5;
            this.master.attack.value = 0.004;
            this.master.release.value = 0.16;
            this.gainOut = this.ctx.createGain();
            this.gainOut.gain.value = 0.9;
            this.master.connect(this.gainOut).connect(this.ctx.destination);

            // shared lead delay line
            this.delay = this.ctx.createDelay(1.5);
            this.delayGain = this.ctx.createGain();
            this.delayGain.gain.value = 0.28;
            this.delayFilter = this.ctx.createBiquadFilter();
            this.delayFilter.type = 'lowpass';
            this.delayFilter.frequency.value = 3200;
            this.delay.connect(this.delayFilter).connect(this.delayGain).connect(this.master);
            this.delayGain.connect(this.delay); // feedback through the filter path
        }
        if (this.ctx.state === 'suspended') this.ctx.resume();
        return this.ctx;
    }

    load({ bpm, vibe, pattern, notes }) {
        this.bpm = bpm;
        this.vibe = vibe;
        this.pattern = pattern.map((r) => [...r]);
        this.notes = notes;
        this.swing = vibe === 'techno' ? 0 : 0.12;
    }

    setCell(row, step, value) { this.pattern[row][step] = value; }

    toggleMute(row) {
        this.mutes.has(row) ? this.mutes.delete(row) : this.mutes.add(row);
        return this.mutes.has(row);
    }

    stepDur() { return 60 / this.bpm / 4; }

    start() {
        this.ensureContext();
        if (this.playing) return;
        this.playing = true;
        this.step = 0;
        this.nextTime = this.ctx.currentTime + 0.06;
        this.delay.delayTime.value = this.stepDur() * 3;
        this.timer = setInterval(() => this.tick(), LOOKAHEAD_MS);
    }

    stop() {
        this.playing = false;
        clearInterval(this.timer);
        this.timer = null;
    }

    tick() {
        while (this.nextTime < this.ctx.currentTime + HORIZON_S) {
            this.scheduleStep(this.step, this.nextTime);
            const swingOffset = this.step % 2 === 1 ? this.stepDur() * this.swing : 0;
            const uiStep = this.step;
            const delta = (this.nextTime - this.ctx.currentTime) * 1000;
            setTimeout(() => this.onStep && this.playing && this.onStep(uiStep), Math.max(0, delta));
            this.nextTime += this.stepDur() + (this.step % 2 === 0 ? this.stepDur() * this.swing : -swingOffset);
            this.step = (this.step + 1) % 16;
            if (this.step === 0 && this.onLoop) this.onLoop();
        }
    }

    scheduleStep(step, t) {
        const p = this.pattern;
        if (!p.length) return;
        const lofi = this.vibe !== 'techno';
        if (!this.mutes.has(0) && p[0][step]) this.kick(t, p[0][step] === 2, lofi);
        if (!this.mutes.has(1) && p[1][step]) this.clap(t, p[1][step] === 2, lofi);
        if (!this.mutes.has(2) && p[2][step]) this.hat(t, p[2][step] === 2, lofi);
        if (!this.mutes.has(3) && p[3][step]) this.bass(t, this.notes.bass[(p[3][step] - 1) % this.notes.bass.length], lofi);
        if (!this.mutes.has(4) && p[4][step]) this.chord(t, this.notes.chords[(p[4][step] - 1) % this.notes.chords.length], lofi);
        if (!this.mutes.has(5) && p[5][step]) this.lead(t, this.notes.lead[(p[5][step] - 1) % this.notes.lead.length], lofi);
    }

    /* ---------------- voices ---------------- */

    kick(t, accent, lofi) {
        const ctx = this.ctx;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        const peak = accent ? 1.0 : 0.82;
        osc.type = 'sine';
        osc.frequency.setValueAtTime(lofi ? 120 : 155, t);
        osc.frequency.exponentialRampToValueAtTime(lofi ? 42 : 48, t + 0.055);
        g.gain.setValueAtTime(peak, t);
        g.gain.exponentialRampToValueAtTime(0.001, t + (lofi ? 0.34 : 0.24));
        osc.connect(g).connect(this.master);
        osc.start(t);
        osc.stop(t + 0.4);
        if (!lofi) {
            // click transient
            const click = this.noiseBurst(t, 0.012, 3000, 'highpass', accent ? 0.5 : 0.35);
            click.connect(this.master);
        }
    }

    clap(t, accent, lofi) {
        const amp = (accent ? 0.62 : 0.5) * (lofi ? 0.8 : 1);
        [0, 0.012, 0.026].forEach((off, i) => {
            const n = this.noiseBurst(t + off, i === 2 ? 0.14 : 0.02, 1500, 'bandpass', amp * (i === 2 ? 1 : 0.6));
            n.connect(this.master);
        });
    }

    hat(t, accent, lofi) {
        const n = this.noiseBurst(t, accent ? 0.09 : 0.04, lofi ? 6000 : 8200, 'highpass', accent ? 0.34 : 0.22);
        n.connect(this.master);
    }

    bass(t, midi, lofi) {
        const ctx = this.ctx;
        const osc = ctx.createOscillator();
        const f = ctx.createBiquadFilter();
        const g = ctx.createGain();
        osc.type = lofi ? 'triangle' : 'sawtooth';
        osc.frequency.value = midiHz(midi);
        f.type = 'lowpass';
        f.Q.value = 8;
        f.frequency.setValueAtTime(lofi ? 500 : 900, t);
        f.frequency.exponentialRampToValueAtTime(140, t + 0.22);
        g.gain.setValueAtTime(0.5, t);
        g.gain.exponentialRampToValueAtTime(0.001, t + 0.26);
        osc.connect(f).connect(g).connect(this.master);
        osc.start(t);
        osc.stop(t + 0.3);
    }

    chord(t, midis, lofi) {
        const ctx = this.ctx;
        const g = ctx.createGain();
        const f = ctx.createBiquadFilter();
        f.type = 'lowpass';
        f.frequency.setValueAtTime(lofi ? 1200 : 2600, t);
        f.frequency.exponentialRampToValueAtTime(500, t + 0.3);
        g.gain.setValueAtTime(lofi ? 0.16 : 0.2, t);
        g.gain.exponentialRampToValueAtTime(0.001, t + (lofi ? 0.6 : 0.33));
        f.connect(g).connect(this.master);
        midis.forEach((m) => {
            [-7, 7].forEach((cents) => {
                const o = ctx.createOscillator();
                o.type = lofi ? 'triangle' : 'sawtooth';
                o.frequency.value = midiHz(m);
                o.detune.value = cents;
                o.connect(f);
                o.start(t);
                o.stop(t + 0.7);
            });
        });
    }

    lead(t, midi, lofi) {
        const ctx = this.ctx;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = lofi ? 'sine' : 'square';
        osc.frequency.value = midiHz(midi);
        g.gain.setValueAtTime(lofi ? 0.16 : 0.14, t);
        g.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
        osc.connect(g);
        g.connect(this.master);
        g.connect(this.delay);
        osc.start(t);
        osc.stop(t + 0.25);
    }

    noiseBurst(t, dur, freq, type, amp) {
        const ctx = this.ctx;
        const len = Math.max(1, Math.floor(ctx.sampleRate * dur));
        const buf = ctx.createBuffer(1, len, ctx.sampleRate);
        const data = buf.getChannelData(0);
        for (let i = 0; i < len; i++) data[i] = (Math.random() * 2 - 1) * (1 - i / len);
        const src = ctx.createBufferSource();
        src.buffer = buf;
        const f = ctx.createBiquadFilter();
        f.type = type;
        f.frequency.value = freq;
        const g = ctx.createGain();
        g.gain.value = amp;
        src.connect(f).connect(g);
        src.start(t);
        return g;
    }
}
