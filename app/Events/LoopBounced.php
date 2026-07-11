<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Loop;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A loop landed on the wall. Broadcast immediately (no queue dependency):
 * the payload is the full public loop shape, so the wall can prepend and
 * play it without a round-trip.
 */
final class LoopBounced implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public readonly Loop $loop)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('wall')];
    }

    public function broadcastAs(): string
    {
        return 'loop.bounced';
    }

    public function broadcastWith(): array
    {
        return $this->loop->toWall();
    }
}
