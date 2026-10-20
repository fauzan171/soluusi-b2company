<?php

namespace App\Services\Klinik;

/**
 * Markup + cap per price range (Build-NEW #8, Spec §2.2/§5.3).
 * margin = final − net; cap = fraksi maks margin terhadap final.
 */
class MarkupService
{
    /** Cap (fraksi 0-1) untuk harga final di range tertentu. */
    public static function capFor(int $finalPrice): float
    {
        $rules = config('klinik.markup_caps', []);
        foreach ($rules as $rule) {
            if ($finalPrice >= $rule['min'] && $finalPrice <= $rule['max']) {
                return $rule['cap'];
            }
        }
        // ponytail: final di bawah range terendah (mis. paket < Rp250k) →
        // pakai cap range termurah, bukan 0. Tanpa ini margin apapun ditolak
        // dan link gagal dibuat. Bila spesifikasi menetapkan harga minimum,
        // tambahkan range eksplisit di config/klinik.php alih-alih hardcode di sini.
        return collect($rules)->min('cap') ?: 0.0;
    }

    /** Harga final maksimum yang boleh untuk net tertentu (hint saat reject). */
    public static function maxFinalFor(int $netPrice): int
    {
        // cari cap berdasarkan tebakan final = net dulu, lalu iterasi naik
        // sampai stabil (cap naik per range). ponytail: brute-force +1rb, cukup
        // akurat untuk rupiah; ganti closed-form kalau range berubah sering.
        $best = $netPrice;
        for ($final = $netPrice; $final <= $netPrice * 2; $final += 1000) {
            if (static::isValid($netPrice, $final)) {
                $best = $final;
            }
        }
        return $best;
    }

    /** Validasi: margin/final ≤ cap range. */
    public static function isValid(int $netPrice, int $finalPrice): bool
    {
        if ($finalPrice < $netPrice || $finalPrice <= 0) {
            return false;
        }
        $margin = $finalPrice - $netPrice;
        return $margin <= static::capFor($finalPrice) * $finalPrice;
    }
}
