<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'DEALT - the daily loop' }}</title>
    <meta name="description" content="Three cards. One groove. Thirty seconds of music, every day. No blank pages.">
    <meta property="og:title" content="DEALT #{{ $deal['number'] }} - the daily loop">
    <meta property="og:description" content="Same three cards for everyone on Earth today. Pick one, twist the groove, bounce it to the wall.">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="sticky top-0 z-40 border-b border-line bg-bg/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="/" class="wordmark text-lg sm:text-xl">DEALT</a>
            <nav class="flex items-center gap-4 sm:gap-6 font-mono text-xs tracking-widest text-dim">
                <span class="chip">#{{ $deal['number'] }}</span>
                <span id="nav-streak" class="chip hidden text-acid border-acid/40"></span>
                <a href="{{ route('play') }}" class="hover:text-ink transition-colors {{ request()->routeIs('play') ? 'text-acid' : '' }}">PLAY</a>
                <a href="{{ route('wall') }}" class="hover:text-ink transition-colors {{ request()->routeIs('wall') ? 'text-acid' : '' }}">WALL</a>
                <a href="{{ route('about') }}" class="hover:text-ink transition-colors {{ request()->routeIs('about') ? 'text-acid' : '' }}">STORY</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-10 pt-16 sm:px-6">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-line pt-5 font-mono text-[11px] tracking-wide text-dim">
            <a href="https://github.com/joshuaswarren/dealt" class="hover:text-ink">open source</a>
            <a href="{{ route('about') }}" class="hover:text-ink">the 1999 story</a>
            <a href="https://archive.org/details/iuma-dj_zip" class="hover:text-ink">the original tracks</a>
            <span class="ml-auto">no AI &middot; no signup &middot; runs on <a class="underline decoration-line hover:text-ink" href="https://laravel.com/cloud">Laravel Cloud</a></span>
        </div>
    </footer>
</body>
</html>
