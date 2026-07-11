<?php

declare(strict_types=1);

use App\Support\Deal\DealService;
use Carbon\CarbonImmutable;

it('deals the same cards for the same date, always', function (): void {
    $svc = new DealService();
    $date = CarbonImmutable::parse('2026-07-12', 'America/Chicago');

    expect($svc->for($date))->toBe($svc->for($date));
});

it('deals different cards on different days', function (): void {
    $svc = new DealService();
    $a = $svc->for(CarbonImmutable::parse('2026-07-12', 'America/Chicago'));
    $b = $svc->for(CarbonImmutable::parse('2026-07-13', 'America/Chicago'));

    expect($a['cards'])->not->toBe($b['cards']);
});

it('numbers deals from launch day', function (): void {
    $svc = new DealService();

    expect($svc->for(CarbonImmutable::parse(DealService::LAUNCH_DATE))['number'])->toBe(1)
        ->and($svc->for(CarbonImmutable::parse('2026-07-12'))['number'])->toBe(2);
});

it('deals three complete, playable cards', function (): void {
    $deal = (new DealService())->for(CarbonImmutable::parse('2026-07-15'));

    expect($deal['cards'])->toHaveCount(3);
    foreach ($deal['cards'] as $card) {
        expect($card)->toHaveKeys(['type', 'name', 'why', 'key', 'bpm', 'vibe', 'rule', 'pattern', 'notes', 'index'])
            ->and($card['bpm'])->toBeGreaterThanOrEqual(84)->toBeLessThanOrEqual(150)
            ->and($card['pattern'])->toHaveCount(6);
        foreach ($card['pattern'] as $row) {
            expect($row)->toHaveCount(16);
            foreach ($row as $cell) {
                expect($cell)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(8);
            }
        }
        // every card starts with sound: at least a kick and one tonal event
        expect(array_sum($card['pattern'][0]))->toBeGreaterThan(0);
        expect(array_sum($card['pattern'][3]) + array_sum($card['pattern'][4]))->toBeGreaterThan(0);
        // note tables present and MIDI-sane
        expect($card['notes']['bass'])->toHaveCount(4)
            ->and($card['notes']['lead'])->toHaveCount(6)
            ->and($card['notes']['chords'])->toHaveCount(2);
        foreach (array_merge($card['notes']['bass'], $card['notes']['lead']) as $midi) {
            expect($midi)->toBeGreaterThanOrEqual(21)->toBeLessThanOrEqual(96);
        }
    }
    // fixed card archetypes
    expect($deal['cards'][0]['type'])->toBe('ancestor')
        ->and($deal['cards'][1]['type'])->toBe('signature')
        ->and($deal['cards'][1]['key'])->toBe('A minor')
        ->and($deal['cards'][1]['bpm'])->toBe(140)
        ->and(in_array($deal['cards'][2]['type'], ['sidepath', 'wildcard'], true))->toBeTrue();
});

it('ancestor cards carry real catalog keys and tempos', function (): void {
    $svc = new DealService();
    $titles = array_column(\App\Support\Deal\Dna::tracks(), 'title');

    foreach (range(1, 20) as $d) {
        $deal = $svc->for(CarbonImmutable::parse('2026-07-11')->addDays($d));
        expect(in_array($deal['cards'][0]['name'], $titles, true))->toBeTrue();
    }
});

it('deals three distinct finish rules every day', function (): void {
    $svc = new DealService();
    foreach (range(0, 60) as $d) {
        $deal = $svc->for(CarbonImmutable::parse('2026-07-11')->addDays($d));
        $rules = array_column($deal['cards'], 'rule');
        expect(array_unique($rules))->toHaveCount(3);
    }
});
