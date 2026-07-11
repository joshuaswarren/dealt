<?php

declare(strict_types=1);

namespace App\Support\Deal;

use Carbon\CarbonImmutable;

/**
 * Deals the day's three cards. Same date => same deal, everywhere, forever -
 * that is the Wordle contract: the whole internet gets the same constraints
 * and the same starting groove, and everything that diverges is you.
 *
 * Card anatomy: name, why, key, bpm, vibe, a finish rule (what "done" means,
 * decided before you start), and a seeded 6x16 pattern with note tables so
 * the session opens with sound instead of silence.
 */
final class DealService
{
    public const LAUNCH_DATE = '2026-07-11';

    public const ROWS = ['kick', 'clap', 'hat', 'bass', 'chord', 'lead'];

    public const STEPS = 16;

    public function today(): array
    {
        return $this->for(CarbonImmutable::now('America/Chicago'));
    }

    public function for(CarbonImmutable $date): array
    {
        $day = $date->format('Y-m-d');
        $number = (int) CarbonImmutable::parse(self::LAUNCH_DATE)->diffInDays($date->startOfDay()) + 1;
        $rng = new Prng((int) $date->format('Ymd'));

        $cards = [
            $this->ancestorCard($rng),
            $this->signatureCard(),
            $this->thirdCard($rng),
        ];

        $rulePool = Dna::finishRules();

        foreach ($cards as $i => &$card) {
            $card['rule'] = array_splice($rulePool, $rng->int(count($rulePool)), 1)[0];
            $card['pattern'] = $this->seedPattern($rng, $card['vibe']);
            $card['notes'] = $this->noteTables($card['key']);
            $card['index'] = $i;
        }

        return ['number' => $number, 'day' => $day, 'cards' => $cards];
    }

    private function ancestorCard(Prng $rng): array
    {
        $track = $rng->pick(Dna::tracks());

        return [
            'type' => 'ancestor',
            'name' => $track['title'],
            'why' => $track['why'],
            'key' => $track['key'],
            'bpm' => $track['bpm'],
            'vibe' => $track['bpm'] <= 116 ? 'downtempo' : 'techno',
        ];
    }

    private function signatureCard(): array
    {
        return [
            'type' => 'signature',
            'name' => 'Zip Prime',
            'why' => 'A minor at 140 - the statistical center of a 13-track catalog. The fingerprint.',
            'key' => 'A minor',
            'bpm' => 140,
            'vibe' => 'techno',
        ];
    }

    private function thirdCard(Prng $rng): array
    {
        if ($rng->next() < 0.5) {
            return [
                'type' => 'sidepath',
                'name' => 'Tape Hiss',
                'why' => 'Off the dance floor entirely. Slow, dusty, half-asleep.',
                'key' => $rng->pick(['A minor', 'D minor', 'F major']),
                'bpm' => 84,
                'vibe' => 'lofi',
            ];
        }

        $note = $rng->pick(['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'B']);

        return [
            'type' => 'wildcard',
            'name' => "{$note} Minor Detour",
            'why' => 'Off the map on purpose. Novelty fuel.',
            'key' => "{$note} minor",
            'bpm' => $rng->pick([128, 132, 145, 150]),
            'vibe' => 'techno',
        ];
    }

    /**
     * Seed a playable 6x16 pattern. Drum cells: 0 off, 1 on, 2 accent.
     * Tonal cells: 0 off, N = 1-based index into that row's note table.
     *
     * @return list<list<int>>
     */
    private function seedPattern(Prng $rng, string $vibe): array
    {
        $p = array_fill(0, count(self::ROWS), array_fill(0, self::STEPS, 0));

        if ($vibe === 'techno') {
            foreach ([0, 4, 8, 12] as $s) {
                $p[0][$s] = $s === 0 ? 2 : 1;                      // four-on-floor kick
            }
            $p[1][4] = 1;
            $p[1][12] = 1;                                          // clap backbeat
            foreach ([2, 6, 10, 14] as $s) {
                $p[2][$s] = 1;                                      // offbeat hats
            }
            if ($rng->next() < 0.5) {
                $p[2][15] = 2;                                      // pushed accent hat
            }
            foreach ([0, 3, 6, 10, 12] as $s) {
                $p[3][$s] = $rng->pick([1, 1, 2]);                  // rolling bass, mostly root
            }
            $p[4][0] = 1;
            $p[4][10] = $rng->pick([1, 2]);                         // chord stabs
            $leadSteps = [2, 5, 8, 11, 14];
        } else {
            // lofi / downtempo: lazier skeleton
            $p[0][0] = 2;
            $p[0][7] = 1;
            $p[0][10] = 1;
            $p[1][4] = 1;
            $p[1][12] = 1;
            foreach ([2, 6, 10, 14] as $s) {
                $p[2][$s] = $rng->next() < 0.7 ? 1 : 0;
            }
            $p[3][0] = 1;
            $p[3][8] = 2;
            $p[3][14] = $rng->next() < 0.5 ? 1 : 0;
            $p[4][0] = 1;
            $p[4][8] = 2;
            $leadSteps = [4, 9, 12];
        }

        foreach ($leadSteps as $s) {
            if ($rng->next() < 0.75) {
                $p[5][$s] = 1 + $rng->int(6);                       // pentatonic pick
            }
        }

        return $p;
    }

    /**
     * Note tables per tonal row, as MIDI notes derived from the key root.
     * bass: low register; lead: minor pentatonic; chords: i and bVI triads.
     */
    private function noteTables(string $key): array
    {
        [$note, $quality] = array_pad(explode(' ', $key), 2, 'minor');
        $pc = Dna::pitchClass($note);
        $bassRoot = 36 + (($pc + 3) % 12) - 3;                      // keep bass around C2..B2
        $leadRoot = $bassRoot + 24;
        $third = $quality === 'major' ? 4 : 3;

        return [
            'bass' => [$bassRoot, $bassRoot + 7, $bassRoot + 5, $bassRoot + 12],
            'lead' => [
                $leadRoot, $leadRoot + $third, $leadRoot + 5,
                $leadRoot + 7, $leadRoot + 10, $leadRoot + 12,
            ],
            'chords' => [
                [$bassRoot + 12, $bassRoot + 12 + $third, $bassRoot + 19],
                [$bassRoot + 8, $bassRoot + 12, $bassRoot + 15],
            ],
        ];
    }
}
