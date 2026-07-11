<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Loop extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'day' => 'date:Y-m-d',
            'pattern' => 'array',
            'notes' => 'array',
        ];
    }

    /** Public shape for the wall + broadcasts. Never leaks ip_hash. */
    public function toWall(): array
    {
        return [
            'id' => $this->id,
            'day' => $this->day->format('Y-m-d'),
            'deal_no' => $this->deal_no,
            'card_index' => $this->card_index,
            'card_name' => $this->card_name,
            'handle' => $this->handle,
            'title' => $this->title,
            'key' => $this->key,
            'bpm' => $this->bpm,
            'vibe' => $this->vibe,
            'pattern' => $this->pattern,
            'notes' => $this->notes,
            'spins' => $this->spins,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
