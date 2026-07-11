<?php

declare(strict_types=1);

namespace App\Support\Deal;

/**
 * Deterministic PRNG (mulberry32). The daily deal must be identical on every
 * server and every visit, so we cannot use mt_rand/random_int. Seed with the
 * date integer (Ymd) and the sequence is fixed forever.
 */
final class Prng
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed & 0xFFFFFFFF;
    }

    /** Next float in [0, 1). */
    public function next(): float
    {
        $this->state = ($this->state + 0x6D2B79F5) & 0xFFFFFFFF;
        $t = $this->state;
        $t = self::imul($t ^ ($t >> 15), $t | 1);
        $t ^= ($t + self::imul($t ^ ($t >> 7), $t | 61)) & 0xFFFFFFFF;
        $t = ($t ^ ($t >> 14)) & 0xFFFFFFFF;

        return $t / 4294967296;
    }

    /** Integer in [0, $maxExclusive). */
    public function int(int $maxExclusive): int
    {
        return (int) floor($this->next() * $maxExclusive);
    }

    /**
     * @template T
     * @param  list<T>  $items
     * @return T
     */
    public function pick(array $items): mixed
    {
        return $items[$this->int(count($items))];
    }

    /** 32-bit integer multiply with overflow semantics (JS Math.imul). */
    private static function imul(int $a, int $b): int
    {
        $a &= 0xFFFFFFFF;
        $b &= 0xFFFFFFFF;
        $aHi = ($a >> 16) & 0xFFFF;
        $aLo = $a & 0xFFFF;
        $bHi = ($b >> 16) & 0xFFFF;
        $bLo = $b & 0xFFFF;

        return (($aLo * $bLo) + ((((($aHi * $bLo) + ($aLo * $bHi)) & 0xFFFF) << 16))) & 0xFFFFFFFF;
    }
}
