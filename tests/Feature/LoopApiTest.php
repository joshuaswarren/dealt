<?php

declare(strict_types=1);

use App\Events\LoopBounced;
use App\Models\Loop;
use App\Support\Deal\DealService;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\post;

function validBounce(array $overrides = []): array
{
    $pattern = array_fill(0, 6, array_fill(0, 16, 0));
    $pattern[0][0] = 1; // one kick: not silent

    return array_merge([
        'card_index' => 1,
        'handle' => 'DJ Test',
        'title' => 'first bounce',
        'pattern' => $pattern,
    ], $overrides);
}

it('bounces a loop onto the wall with server-side card identity', function (): void {
    Event::fake([LoopBounced::class]);

    $response = post(route('loops.store'), validBounce())->assertCreated();

    $deal = app(DealService::class)->today();
    $loop = Loop::query()->firstOrFail();

    expect($loop->key)->toBe($deal['cards'][1]['key'])
        ->and($loop->bpm)->toBe($deal['cards'][1]['bpm'])
        ->and($loop->card_name)->toBe($deal['cards'][1]['name'])
        ->and($loop->deal_no)->toBe($deal['number'])
        ->and($response->json('loop.handle'))->toBe('DJ Test')
        ->and($response->json('loop'))->not->toHaveKey('ip_hash');

    Event::assertDispatched(LoopBounced::class, fn (LoopBounced $e) => $e->loop->id === $loop->id);
});

it('rejects a silent loop', function (): void {
    $pattern = array_fill(0, 6, array_fill(0, 16, 0));

    post(route('loops.store'), validBounce(['pattern' => $pattern]))
        ->assertStatus(422);

    expect(Loop::query()->count())->toBe(0);
});

it('rejects malformed patterns and handles', function (): void {
    post(route('loops.store'), validBounce(['pattern' => [[1, 2], [3]]]))->assertStatus(422);
    post(route('loops.store'), validBounce(['handle' => 'x']))->assertStatus(422);
    post(route('loops.store'), validBounce(['card_index' => 7]))->assertStatus(422);

    $bad = array_fill(0, 6, array_fill(0, 16, 0));
    $bad[0][0] = 99; // out-of-range cell
    post(route('loops.store'), validBounce(['pattern' => $bad]))->assertStatus(422);
});

it('counts a spin at most a few times per visitor', function (): void {
    Event::fake([LoopBounced::class]);
    post(route('loops.store'), validBounce())->assertCreated();
    $loop = Loop::query()->firstOrFail();

    foreach (range(1, 5) as $i) {
        post(route('loops.spin', $loop));
    }

    expect($loop->refresh()->spins)->toBe(3);
});

it('renders play, wall, and about', function (): void {
    Event::fake([LoopBounced::class]);
    post(route('loops.store'), validBounce())->assertCreated();

    $this->get(route('play'))->assertOk()->assertSee('DEALT', false);
    $this->get(route('wall'))->assertOk()->assertSee('DJ Test');
    $this->get(route('about'))->assertOk()->assertSee('Pick Hit Gold');
});
