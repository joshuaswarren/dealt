<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\LoopBounced;
use App\Models\Loop;
use App\Support\Deal\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoopController extends Controller
{
    /**
     * Bounce: publish a finished loop to the wall. The card identity (key,
     * bpm, names, note tables) comes from the SERVER's deal for today -
     * clients only submit which card and which cells. Nothing user-supplied
     * decides what the wall plays except the pattern itself.
     */
    public function store(Request $request, DealService $deals): JsonResponse
    {
        $ipKey = 'bounce:'.sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            return response()->json(['error' => 'Easy, DJ. Try again in a minute.'], 429);
        }
        RateLimiter::hit($ipKey, 60);

        $data = $request->validate([
            'card_index' => 'required|integer|min:0|max:2',
            'handle' => ['required', 'string', 'min:2', 'max:24', 'regex:/^[\pL\pN _.\'-]+$/u'],
            'title' => 'nullable|string|max:60',
            'pattern' => 'required|array|size:6',
            'pattern.*' => 'required|array|size:16',
            'pattern.*.*' => 'required|integer|min:0|max:8',
        ]);

        $deal = $deals->today();
        $card = $deal['cards'][$data['card_index']];

        // A bounce must contain at least one sounding cell - silence is not a loop.
        $total = array_sum(array_map('array_sum', $data['pattern']));
        if ($total === 0) {
            return response()->json(['error' => 'That loop is silent. Touch the grid first.'], 422);
        }

        $loop = Loop::query()->create([
            'day' => $deal['day'],
            'deal_no' => $deal['number'],
            'card_index' => $data['card_index'],
            'card_name' => $card['name'],
            'handle' => trim($data['handle']),
            'title' => $data['title'] ? trim($data['title']) : null,
            'key' => $card['key'],
            'bpm' => $card['bpm'],
            'vibe' => $card['vibe'],
            'pattern' => $data['pattern'],
            'notes' => $card['notes'],
            'ip_hash' => sha1((string) $request->ip()),
        ]);

        event(new LoopBounced($loop));

        return response()->json(['loop' => $loop->toWall()], 201);
    }

    /** Spin: someone played a wall loop all the way through. */
    public function spin(Request $request, Loop $loop): JsonResponse
    {
        $key = 'spin:'.sha1($request->ip().$loop->id);
        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 300);
            $loop->increment('spins');
        }

        return response()->json(['spins' => $loop->spins]);
    }
}
