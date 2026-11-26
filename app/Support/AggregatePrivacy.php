<?php

namespace App\Support;

class AggregatePrivacy
{
    /**
     * Suppression agregat n>=min. Default 5 = perilaku legacy (semua caller
     * existing tidak berubah). Report program wellbeing pass $min = 10 (R10).
     */
    public static function safe($counts, int $min = 5): bool
    {
        return collect($counts)->every(fn ($count) => $count == 0 || $count >= $min);
    }

    public static function month(): array
    {
        $month = request('month', now()->format('Y-m'));
        validator(['month' => $month], ['month' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']])->validate();
        $start = \Illuminate\Support\Carbon::createFromFormat('!Y-m', $month);
        return [$month, $start, $start->copy()->addMonth()];
    }

    public static function csvText(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
