<x-shell :deal="$deal" title="THE STORY - DEALT">
<article class="mx-auto max-w-2xl">
    <p class="font-mono text-xs tracking-[0.3em] text-dim">THE STORY</p>
    <h1 class="mt-3 text-3xl font-bold sm:text-5xl">The blank page is the boss fight.</h1>

    <div class="mt-8 space-y-5 text-lg leading-relaxed text-ink/90">
        <p>
            In 1999 I made techno in FruityLoops under the name DJ Zip. On January 2, 2000,
            a 13-minute concept mix called "A Y2K Time Warp" won the first Pick Hit Gold of the
            new millennium on amp3.com. The prize was $1,000. I was a college kid with an Akai
            AX-60 and a drum machine, and for one week the internet liked my song.
        </p>
        <p>
            Then life happened. The site died, the files scattered, and for 25 years the music
            existed only as a story I told. This July, an AI agent found my old artist page in
            the Internet Archive's IUMA collection. All ten tracks, still there. Two days later
            we found the original CD-R master in a box. The disc was dying, so we ripped it
            nine times across two copies and rebuilt it sample by sample until every byte agreed.
        </p>
        <p>
            Here is the part that stings: I never stopped wanting to make music. I bought the
            gear, installed the software, and for 25 years I never started a project. Not once.
            The blank page won every single time.
        </p>
        <p>
            So this morning I built a fix. A card deck generated from my own catalog's DNA.
            Analysis of the recovered masters showed eleven of thirteen tracks sit in A minor and
            ten of thirteen at 140 BPM, so the deck deals real keys and real tempos from real
            songs, plus one rule that defines "done" before you start. You never face silence.
            The groove is already playing.
        </p>
        <p class="font-bold">
            Dealt is that deck, for everyone. Same three cards for the whole internet each day.
            Pick one, or hesitate 30 seconds and the house picks for you, because indecision is
            the enemy here. Twist the groove. Bounce it to the wall. Come back tomorrow.
        </p>
        <p>
            No AI touches your music. The cards are dealt by arithmetic, the synths are wired by
            hand in Web Audio, and every loop on the wall is exactly what a person tapped into a
            grid. You can even download your loop as a .mid file and finish it in a real DAW,
            which is the whole secret agenda: this is a gateway drug back to making things.
        </p>
    </div>

    <div class="mt-10 rounded-2xl border border-line bg-panel p-6">
        <p class="font-mono text-[10px] tracking-[0.25em] text-dim">THE RECEIPTS</p>
        <ul class="mt-3 space-y-2 text-sm text-dim">
            <li>&rarr; <a class="underline decoration-line hover:text-ink" href="https://archive.org/details/iuma-dj_zip">The original ten tracks, free, in the Internet Archive</a></li>
            <li>&rarr; <a class="underline decoration-line hover:text-ink" href="https://web.archive.org/web/2000*/amp3.com">amp3.com in the Wayback Machine, where the Pick Hit lived</a></li>
            <li>&rarr; <a class="underline decoration-line hover:text-ink" href="https://github.com/joshuaswarren/dealt">This whole thing, open source</a></li>
        </ul>
    </div>

    <div class="mt-10 text-center">
        <a href="{{ route('play') }}" class="btn-bounce inline-block px-8 py-4 text-sm">DEAL ME IN</a>
    </div>
</article>
</x-shell>
