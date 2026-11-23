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
        'dimensions' => 'array',
        'is_active' => 'bool',
        'is_addon' => 'bool',
    ];

    /**
     * wv1: gate akses berdasar domain program aktif. Null domain = instrumen
     * umum (Big Five/DISC) — selalu diizinkan. Program tanpa snapshot domains
     * (null) juga tidak menggate (parity kontrak lama).
     */
    public function allowedForProgram(?array $programDomains): bool
    {
        return $this->domain === null
            || $programDomains === null
            || in_array($this->domain, $programDomains, true);
    }

    /** Subskor per dimensi (wv1): def {label, items[], multiplier?, bands[]}. */
    public function dimensionDefinition(string $key): ?array
    {
        return $this->dimensions[$key] ?? null;
    }

    /** Band label dimensi untuk skor (bands ascending by max). */
    public function dimensionBandFor(string $key, int $score): array
    {
        $bands = $this->dimensions[$key]['bands'] ?? [];
        foreach ($bands as $band) {
            if ($score <= $band['max']) {
                return ['label' => $band['label'], 'note' => $band['note'] ?? ''];
            }
        }
        return $bands ? ['label' => end($bands)['label'], 'note' => end($bands)['note'] ?? ''] : ['label' => '', 'note' => ''];
    }

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
