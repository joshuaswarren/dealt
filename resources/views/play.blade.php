<x-shell :deal="$deal">
<div id="game-root" data-deal='@json($deal)'>

    {{-- ============ DEAL SCREEN ============ --}}
    <section id="deal-screen" class="grain relative">
        <div class="mx-auto max-w-3xl pt-6 text-center sm:pt-12">
            <p class="font-mono text-xs tracking-[0.3em] text-dim">DEALT #{{ $deal['number'] }} &middot; {{ \Carbon\Carbon::parse($deal['day'])->format('D M j') }}</p>
            <h1 class="mt-4 text-3xl font-bold sm:text-5xl">Three cards. One groove.<br>Thirty seconds of music.</h1>
            <p class="mx-auto mt-4 max-w-xl text-dim">
                The same three cards are on this table for everyone on Earth today.
                Pick one. The groove starts playing before you can overthink it.
            </p>
            <p class="mt-6 font-mono text-xs tracking-widest text-magenta">
                HOUSE DEALS CARD ONE IN <span id="deadman-count" class="lcd inline-block min-w-[3.4em] px-2 py-0.5 text-center">0:30</span>
            </p>
        </div>

        <div class="mx-auto mt-8 grid max-w-4xl gap-4 sm:mt-12 sm:grid-cols-3 sm:gap-6">
            @foreach ($deal['cards'] as $i => $card)
                <button
                    class="deal-card card-enter p-5 text-left {{ $card['vibe'] !== 'techno' ? 'is-lofi' : '' }}"
                    style="animation-delay: {{ $i * 0.15 }}s"
                    data-index="{{ $i }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="card-badge uppercase">{{ $card['type'] }}</span>
                        @if ($i === 0)
                            <svg class="deadman-ring h-8 w-8 -rotate-90" viewBox="0 0 56 56">
                                <circle cx="28" cy="28" r="26" fill="none" stroke="#262639" stroke-width="3"/>
                                <circle class="drain" cx="28" cy="28" r="26" fill="none" stroke="#ff3dae" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        @endif
                    </div>
                    <p class="mt-6 font-mono text-3xl font-bold leading-none text-ink">{{ $card['key'] }}</p>
                    <p class="mt-1 font-mono text-sm text-dim">{{ $card['bpm'] }} BPM &middot; {{ $card['vibe'] }}</p>
                    <p class="mt-5 text-lg font-bold leading-snug">{{ $card['name'] }}</p>
                    <p class="mt-1 text-sm leading-snug text-dim">{{ $card['why'] }}</p>
                    <div class="rule-plate mt-5 p-3">
                        <p class="font-mono text-[10px] tracking-[0.2em] text-dim">FINISH RULE</p>
                        <p class="mt-1 text-sm leading-snug">{{ $card['rule'] }}</p>
                    </div>
                </button>
            @endforeach
        </div>

        @if (count($wallToday))
            <p class="mt-10 text-center font-mono text-xs tracking-widest text-dim">
                <span class="text-acid">{{ count($wallToday) }}{{ count($wallToday) === 12 ? '+' : '' }}</span>
                LOOP{{ count($wallToday) === 1 ? '' : 'S' }} ON TODAY'S WALL &middot;
                <a href="{{ route('wall') }}" class="underline decoration-line hover:text-ink">LISTEN</a>
            </p>
        @endif
    </section>

    {{-- ============ SESSION ============ --}}
    <section id="session-screen" class="hidden">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <span class="card-badge uppercase" id="card-name">-</span>
            <span class="chip"><span id="card-key">-</span></span>
            <span class="chip"><span id="card-bpm">-</span> BPM</span>
            <span class="chip">clock <span id="session-clock" class="text-ink">0:00</span></span>
        </div>
        <p class="mt-3 font-mono text-sm text-amber" id="card-rule-line">
            <span class="text-dim tracking-[0.2em] text-[10px]">RULE&nbsp;</span><span id="card-rule">-</span>
        </p>

        <div class="mt-6 rounded-2xl border border-line bg-panel p-4 sm:p-6">
            <div id="grid" class="seq-grid grid gap-y-[6px]" style="grid-template-columns: 52px 1fr;"></div>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <button id="btn-play" class="btn-play" aria-label="play or stop">
                    <svg id="icon-play" class="h-5 w-5 fill-ink" viewBox="0 0 12 14"><path d="M0 0l12 7-12 7z"/></svg>
                    <svg id="icon-stop" class="hidden h-4 w-4 fill-acid" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>
                </button>
                <p class="font-mono text-[11px] leading-relaxed text-dim">
                    tap pads &middot; tap twice for accent<br>
                    tap a row name to mute &middot; space = play/stop
                </p>
                <button id="btn-bounce" class="btn-bounce ml-auto px-6 py-4 text-sm" disabled title="touch the grid, hear it loop once">
                    BOUNCE IT
                </button>
            </div>
        </div>
        <p class="mt-3 text-right font-mono text-[10px] tracking-widest text-dim">DONE BEATS GOOD.</p>
    </section>

    {{-- ============ BOUNCE MODAL ============ --}}
    <div id="bounce-modal" class="fixed inset-0 z-50 hidden bg-bg/80 backdrop-blur-sm">
        <form id="bounce-form" class="mx-auto mt-[18vh] w-[min(92vw,26rem)] rounded-2xl border border-line bg-panel p-6">
            <p class="font-mono text-xs tracking-[0.25em] text-dim">SIGN THE WALL</p>
            <label class="mt-5 block font-mono text-[11px] tracking-widest text-dim" for="handle-input">YOUR DJ NAME</label>
            <input id="handle-input" required minlength="2" maxlength="24" placeholder="DJ …"
                   class="mt-1 w-full rounded-lg border border-line bg-bg px-3 py-2.5 font-mono outline-none focus:border-acid">
            <label class="mt-4 block font-mono text-[11px] tracking-widest text-dim" for="title-input">LOOP TITLE <span class="opacity-50">(optional)</span></label>
            <input id="title-input" maxlength="60" placeholder="untitled banger"
                   class="mt-1 w-full rounded-lg border border-line bg-bg px-3 py-2.5 outline-none focus:border-acid">
            <p id="bounce-error" class="mt-3 hidden text-sm text-magenta"></p>
            <div class="mt-6 flex gap-3">
                <button type="button" id="bounce-cancel" class="flex-1 rounded-lg border border-line py-3 font-mono text-xs tracking-widest text-dim hover:text-ink">KEEP COOKING</button>
                <button type="submit" class="btn-bounce flex-1 py-3 text-xs">TO THE WALL</button>
            </div>
        </form>
    </div>

    {{-- ============ BOUNCED ============ --}}
    <section id="bounced-screen" class="hidden pt-10 text-center sm:pt-20">
        <div class="stamp mx-auto inline-block text-xl sm:text-2xl">ON THE WALL</div>
        <p id="bounced-streak" class="mt-8 text-lg text-ink"></p>
        <p class="mt-1 text-dim">New cards at midnight, Texas time.</p>
        <div class="mx-auto mt-8 flex max-w-sm flex-col gap-3 sm:flex-row sm:justify-center">
            <button id="btn-share" class="btn-bounce px-6 py-3 text-xs">SHARE</button>
            <button id="btn-midi" class="rounded-xl border border-line px-6 py-3 font-mono text-xs tracking-widest hover:border-acid" title="take it into your DAW">.MID FOR YOUR DAW</button>
            <a id="wall-link" href="/wall" class="rounded-xl border border-line px-6 py-3 font-mono text-xs tracking-widest hover:border-acid">SEE THE WALL</a>
        </div>
    </section>
</div>
</x-shell>
