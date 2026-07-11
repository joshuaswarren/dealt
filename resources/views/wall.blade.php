<x-shell :deal="$deal" title="THE WALL - DEALT">
<div id="wall-root" data-groups='@json($groups)'>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold sm:text-4xl">The wall</h1>
            <p class="mt-2 text-dim">Every loop is synthesized live from its pattern when you press play.<br class="hidden sm:block">
            A full listen counts as a spin.</p>
        </div>
        <a href="{{ route('play') }}" class="btn-bounce px-5 py-3 text-xs">PLAY DEALT #{{ $deal['number'] }}</a>
    </div>

    @forelse ($groups as $day => $loops)
        <section class="mt-10" data-day="{{ $day }}" @if($day === $deal['day']) data-today @endif>
            <div class="flex items-baseline gap-3 border-b border-line pb-2">
                <h2 class="font-mono text-sm font-bold tracking-[0.2em] text-acid">
                    DEALT #{{ $loops->first()['deal_no'] }}
                </h2>
                <span class="font-mono text-xs text-dim">{{ \Carbon\Carbon::parse($day)->format('D M j') }}</span>
                @if ($day === $deal['day'])
                    <span class="font-mono text-xs text-dim">&middot; <span id="today-count">{{ count($loops) }}</span> loops</span>
                @endif
            </div>
            <div class="wall-grid mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
        </section>
    @empty
        <div class="mt-16 rounded-2xl border border-dashed border-line p-12 text-center">
            <p class="font-mono text-sm tracking-widest text-dim">THE WALL IS EMPTY.</p>
            <p class="mt-2 text-dim">Someone has to bounce the first loop of the day.</p>
            <a href="{{ route('play') }}" class="btn-bounce mt-6 inline-block px-6 py-3 text-xs">BE THE ONE</a>
        </div>
    @endforelse
</div>
</x-shell>
