<?php

namespace App\Support;

/**
 * wv5: revenue-share calculator (view-only) per S&K Klinik §5 —
 * 4-tier MAXIMUM caps by consultation price. Rate adalah batas atas,
 * persentase final ditetapkan via HPA. Pure function, tanpa DB access.
 */
class RevenueShare
{
    public static function rate(int $price): ?float
    {
        return match (true) {
            $price >= 400_000 => 0.25,
            $price >= 350_000 => 0.20,
            $price >= 300_000 => 0.15,
            $price >= 250_000 => 0.10,
            default => null, // di bawah Rp250.000: no standard rate, perlu kesepakatan HPA.
        };
    }
}
