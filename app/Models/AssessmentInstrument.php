<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Definisi instrumen assessment (W4, K12): code/name/version/scoring/bands sebagai data.
 * Instrumen baru = baris + item di screening_questions — tanpa kode baru.
 */
class AssessmentInstrument extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'bands' => 'array',
        'escalate_items' => 'array',
        'is_active' => 'bool',
    ];

    public function questions()
    {
        return ScreeningQuestion::where('instrument_code', $this->code)
            ->where('vertical', 'perusahaan')
            ->orderBy('ord');
    }

    /** Band label untuk skor (bands sorted ascending by max). */
    public function bandLabelFor(int $score): string
    {
        foreach ($this->bands ?? [] as $band) {
            if ($score <= $band['max']) {
                return $band['label'];
            }
        }
        return $this->bands ? end($this->bands)['label'] : '';
    }

    /** Interpretasi/note band untuk skor. */
    public function bandNoteFor(int $score): string
    {
        foreach ($this->bands ?? [] as $band) {
            if ($score <= $band['max']) {
                return $band['note'] ?? '';
            }
        }
        return '';
    }
}
