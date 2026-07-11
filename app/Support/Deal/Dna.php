<?php

declare(strict_types=1);

namespace App\Support\Deal;

/**
 * The catalog DNA: 13 techno masters produced as DJ Zip (1999-2000), recovered
 * from a 25-year-old CD-R in July 2026 and analyzed losslessly. Eleven of
 * thirteen sit in A minor; ten of thirteen at ~140 BPM. That fingerprint -
 * A minor @ 140 - is where every day's Signature card comes from, and each
 * Ancestor card deals a real master's key and tempo back as a starting point.
 */
final class Dna
{
    /** @return list<array{title: string, key: string, bpm: int, why: string}> */
    public static function tracks(): array
    {
        return [
            ['title' => 'A Y2K Timewarp', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'The 13-minute concept mix that won the first Pick Hit Gold of the year 2000.'],
            ['title' => 'Cosmic Attack', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'Textbook arch: build, central peak, symmetric fade. Made for the floor.'],
            ['title' => 'Digital Angst', 'key' => 'G major', 'bpm' => 140,
                'why' => 'The dark one. Almost nothing above 1.1 kHz - a mixing choice, not an accident.'],
            ['title' => 'Entranced', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'Hypnotic mid-tempo pulse. The trance lane of the catalog.'],
            ['title' => 'Forever Eclipsed', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'The noisiest, brightest texture on the whole CD.'],
            ['title' => 'Forever Eclipsed (Lunear Eclipse Remix)', 'key' => 'G minor', 'bpm' => 126,
                'why' => 'The tempo outlier. Slower, moodier, remixed by the same kid.'],
            ['title' => 'Nameless Beats', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'No name, all groove.'],
            ['title' => 'Percussive Love', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'Drums first, everything else second.'],
            ['title' => 'Percussive Love (True Love Remix)', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'The most percussive track ever measured in the catalog. The title was accurate.'],
            ['title' => 'Plasma Dancer', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'Steady plateau energy. Groove over drama.'],
            ['title' => 'The Darkness & The Light', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'Two movements: a loud opening act, a long quiet valley, then the loudest stretch on the CD.'],
            ['title' => 'What If...', 'key' => 'A# minor', 'bpm' => 110,
                'why' => 'The downtempo lane, visited exactly once. Darkest track in the catalog.'],
            ['title' => 'X-1', 'key' => 'A minor', 'bpm' => 140,
                'why' => 'Ranked #9 of 820+ techno tracks on amp3.com. Confidence, not flash.'],
        ];
    }

    /** Web-adapted finish rules: the constraint that defines "done" before you start. */
    public static function finishRules(): array
    {
        return [
            'Thirty seconds max. When it loops clean, bounce it.',
            'Three instruments, tops. Mute the rest.',
            'Make it deliberately ugly. Bounce it anyway.',
            'Your first eight touches are final. Count them.',
            'One row must stay exactly as dealt.',
            'Strip it to kick and one voice. Earn every addition.',
        ];
    }

    /** Semitone pitch class for a note name ("A#" -> 10). */
    public static function pitchClass(string $note): int
    {
        $map = ['C' => 0, 'C#' => 1, 'D' => 2, 'D#' => 3, 'E' => 4, 'F' => 5,
            'F#' => 6, 'G' => 7, 'G#' => 8, 'A' => 9, 'A#' => 10, 'B' => 11];

        return $map[$note] ?? 9;
    }
}
