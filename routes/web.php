<?php

declare(strict_types=1);

use App\Http\Controllers\LoopController;
use App\Models\Loop;
use App\Support\Deal\DealService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (DealService $deals) {
    $deal = $deals->today();
    $wallToday = Loop::query()
        ->where('day', $deal['day'])
        ->latest()
        ->limit(12)
        ->get()
        ->map(fn (Loop $l) => $l->toWall());

    return view('play', ['deal' => $deal, 'wallToday' => $wallToday]);
})->name('play');

Route::get('/wall', function (DealService $deals) {
    $loops = Loop::query()
        ->latest()
        ->limit(120)
        ->get()
        ->map(fn (Loop $l) => $l->toWall())
        ->groupBy('day');

    return view('wall', ['groups' => $loops, 'deal' => $deals->today()]);
})->name('wall');

Route::get('/about', function () {
    return view('about', ['deal' => app(DealService::class)->today()]);
})->name('about');

Route::post('/api/loops', [LoopController::class, 'store'])->name('loops.store');
Route::post('/api/loops/{loop}/spin', [LoopController::class, 'spin'])->name('loops.spin');
