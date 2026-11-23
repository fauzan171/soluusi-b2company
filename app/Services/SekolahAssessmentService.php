<?php

namespace App\Services;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\ScreeningAnswer;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahStudent;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Http\Request;

/**
 * B3: submit asesmen sekolah via engine generic (D2/D6/D8).
 * Menerima answers keyed by item code (engine shape) DAN indexed 0..n-1
 * (legacy WHO-5 shape) — indexed dipetakan posisional ke kode item urut ord.
 */
class SekolahAssessmentService
{
    public static function submit(SekolahStudent $person, AssessmentAssignment $assignment, AssessmentInstrument $instrument, Request $r): SekolahAssessment
    {
        $items = InstrumentScoring::items($instrument);

        $answers = self::normalize($r->input('answers'), $items);
        $rules = ['answers' => ['required', 'array', 'size:'.$items->count()]];
        foreach ($items as $item) {
            $max = (int) $item->weight;
            $rules["answers.{$item->code}"] = ['required', 'integer', 'min:0', "max:{$max}"];
        }
        $r->merge(['answers' => $answers])->validate($rules);

        $result = InstrumentScoring::score($instrument, $answers);

        $row = SekolahAssessment::create([
            'sekolah_id' => $person->sekolah_id,
            'student_id' => $person->id,
            'kelas' => $person->kelas,
            'instrument_code' => $instrument->code,
            'assessment_assignment_id' => $assignment->id,
            'answers' => $answers,
            'score' => $result['score'],
            'escalated' => $result['escalated'],
            'taken_at' => now(),
        ]);

        foreach ($answers as $qCode => $value) {
            ScreeningAnswer::create([
                'assessable_type' => SekolahAssessment::class,
                'assessable_id' => $row->id,
                'question_code' => $qCode,
                'value' => (string) $value,
                'answered_at' => now(),
            ]);
        }

        return $row;
    }

    /**
     * Indexed legacy [0,2,3..] → keyed [W1=>0,...] by item order.
     * Jika keys sudah non-numeric, teruskan apa adanya.
     *
     * @param  array  $answers
     * @return array<string, int>
     */
    private static function normalize(array $answers, $items): array
    {
        if (! array_is_list($answers)) {
            return $answers;
        }

        $out = [];
        foreach ($items->values() as $i => $item) {
            $out[$item->code] = $answers[$i] ?? null;
        }

        return $out;
    }
}
