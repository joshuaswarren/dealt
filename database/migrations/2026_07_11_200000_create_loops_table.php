<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loops', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('day');                       // which deal day this belongs to
            $table->unsignedInteger('deal_no');
            $table->unsignedTinyInteger('card_index'); // 0..2
            $table->string('card_name', 60);
            $table->string('handle', 24);
            $table->string('title', 60)->nullable();
            $table->string('key', 12);
            $table->unsignedSmallInteger('bpm');
            $table->string('vibe', 12);
            $table->jsonb('pattern');                  // 6x16 int grid
            $table->jsonb('notes');                    // note tables for playback
            $table->unsignedInteger('spins')->default(0);
            $table->string('ip_hash', 64);             // rate limiting, never displayed
            $table->timestampsTz();

            $table->index(['day', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loops');
    }
};
