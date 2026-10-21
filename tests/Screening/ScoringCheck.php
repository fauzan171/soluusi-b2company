<?php

// Self-check mesin skor screening. Jalankan: php tests/Screening/ScoringCheck.php
require __DIR__.'/../../vendor/autoload.php';

use App\Services\Screening\ScoringService as S;

$fail = 0;
$check = function (string $label, $got, $want) use (&$fail) {
    $ok = $got === $want;
    if (! $ok) { $fail++; }
    printf("%-56s %s  got=%s want=%s\n", $label, $ok ? 'OK  ' : 'FAIL', json_encode($got), json_encode($want));
};

// --- batas band: 24 hijau, 25 kuning, 49 kuning, 50 oranye, 74 oranye, 75 merah
$check('band 0',  S::bandFor(0)['color'], 'low');
$check('band 24', S::bandFor(24)['color'], 'low');
$check('band 25', S::bandFor(25)['color'], 'medium');
$check('band 49', S::bandFor(49)['color'], 'medium');
$check('band 50', S::bandFor(50)['color'], 'high');
$check('band 74', S::bandFor(74)['color'], 'high');
$check('band 75', S::bandFor(75)['color'], 'critical');
$check('band 100', S::bandFor(100)['color'], 'critical');
$check('band konsultasi 75', S::bandFor(75)['consult'], 3);

// --- skor domain mental health: 9 item freq0-4 → maks 36 (penyebut dari SELURUH soal)
$codes = ['Q5' => false, 'Q6' => false, 'Q7' => false, 'Q8' => true, 'Q9' => true,
    'Q10' => false, 'Q11' => true, 'Q12' => true, 'Q13' => false];
$q = collect($codes)->map(fn ($rev, $code) => (object) [
    'code' => $code, 'type' => 'freq0-4', 'reverse' => $rev, 'ord' => 5, 'options' => null,
])->values();

$check('9 soal diskor, maks 36', S::scoreSection('mental_health', [], $q)['max'], 36);
$check('semua nol → 0', S::scoreSection('mental_health', [], $q)['score'], 0);
$check('hanya Q5=4 → 11 (4/36)', S::scoreSection('mental_health', ['Q5' => 4], $q)['score'], 11);
$check('reverse Q8=0 ikut penuh (4/36)', S::scoreSection('mental_health', ['Q8' => 0], $q)['score'], 11);
$check('reverse Q8=4 → nol kontribusi', S::scoreSection('mental_health', ['Q8' => 4], $q)['score'], 0);
$check('semua jawaban 4 → 56 (20/36)', S::scoreSection('mental_health', array_fill_keys(array_keys($codes), 4), $q)['score'], 56);
$check('semua jawaban 0 → 44 (16/36, 4 item reverse)', S::scoreSection('mental_health', array_fill_keys(array_keys($codes), 0), $q)['score'], 44);
$check('hanya item reverse dijawab 0 → 44', S::scoreSection('mental_health', array_fill_keys(['Q8', 'Q9', 'Q11', 'Q12'], 0), $q)['score'], 44);

// --- pertanyaan konteks tidak masuk skor (Q29 single)
$qOnly29 = collect([(object) ['code' => 'Q29', 'type' => 'single', 'reverse' => false, 'ord' => 29, 'options' => null]]);
$check('Q29 konteks dilewati → max 0', S::scoreSection('ergonomic', ['Q29' => 2], $qOnly29)['max'], 0);
$qErgo = collect([
    (object) ['code' => 'Q29', 'type' => 'single', 'reverse' => false, 'ord' => 29, 'options' => null],
    (object) ['code' => 'Q21', 'type' => 'pain4',  'reverse' => false, 'ord' => 21, 'options' => null],
]);
$check('Q21 pain4=3 → 100', S::scoreSection('ergonomic', ['Q21' => 3], $qErgo)['score'], 100);

// --- rekomendasi = TERBURUK antar domain, bukan rata-rata
$check('max antar domain', S::recommend(['mental_health' => 10, 'ergonomic' => 80])['consult'], 3);
$check('section terburuk', S::recommend(['mental_health' => 10, 'ergonomic' => 80])['section'], 'ergonomic');
$check('semua ringan → 0', S::recommend(['mental_health' => 5, 'nutrition_lifestyle' => 20])['consult'], 0);
$check('label warna', S::colorLabel('high'), 'Oranye');

// --- nutrisi: bobot tetap ikut skor
$qNut = collect([(object) ['code' => 'Q16', 'type' => 'single', 'reverse' => false, 'ord' => 16, 'options' => null]]);
$check('nutrisi Q16=2 → 100', S::scoreSection('nutrition_lifestyle', ['Q16' => 2], $qNut)['score'], 100);

exit($fail === 0 ? 0 : 1);