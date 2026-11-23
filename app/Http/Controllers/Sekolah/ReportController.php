<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\SekolahContext;

class ReportController extends Controller
{
    public function index()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah && $sekolah->is_active, 403);
        return view('sekolah.report', ['sekolah' => $sekolah, 'instrumentSections' => $this->instrumentSections($sekolah)] + ProgramAggregate::monthly($sekolah, 'sekolah'));
    }

    /**
     * B4 (D9): agregat per instrumen yang di-assign ke sekolah, per audience.
     * Threshold 5; breakdown kelas hanya utk who5; siswa hanya instrumen
     * STUDENT_ALLOWED; legacy rows (NULL assignment_id, kode match) ikut
     * dihitung; tanpa identitas siswa.
     */
    private function instrumentSections($sekolah): array
    {
        $sections = \App\Models\AssessmentAssignment::query()
            ->where('assignable_type', $sekolah->getMorphClass())
            ->where('assignable_id', $sekolah->id)
            ->whereIn('audience', [\App\Models\AssessmentAssignment::AUDIENCE_STUDENT, \App\Models\AssessmentAssignment::AUDIENCE_STAFF])
            ->orderBy('instrument_code')->orderBy('audience')->orderBy('round')
            ->get()
            ->filter(function ($a) {
                if ($a->audience === \App\Models\AssessmentAssignment::AUDIENCE_STUDENT
                    && ! in_array($a->instrument_code, \App\Models\AssessmentAssignment::STUDENT_ALLOWED, true)) {
                    return false;
                }
                return (bool) \App\Models\AssessmentInstrument::where('code', $a->instrument_code)->value('is_active');
            })
            ->map(function ($assignment) use ($sekolah) {
                $instrument = \App\Models\AssessmentInstrument::where('code', $assignment->instrument_code)->firstOrFail();
                $isStudent = $assignment->audience === \App\Models\AssessmentAssignment::AUDIENCE_STUDENT;

                $registered = $isStudent ? $sekolah->students()->count() : $sekolah->staff()->count();

                // Rows assignment tsb + legacy NULL rows kode sama, tenant sama (D8).
                $query = \App\Models\Sekolah\SekolahAssessment::query()
                    ->where('sekolah_id', $sekolah->id)
                    ->where('instrument_code', $assignment->instrument_code)
                    ->where('status', 'completed')
                    ->where(function ($q) use ($assignment) {
                        $q->where('assessment_assignment_id', $assignment->id);
                        // row tanpa assignment_id hanya milik assignment legacy (bukan program/round).
                        if ($assignment->source === \App\Models\AssessmentAssignment::SOURCE_LEGACY) {
                            $q->orWhereNull('assessment_assignment_id');
                        }
                    });
                $isStudent ? $query->studentRoster() : $query->staffRoster();

                $latest = $query->get()
                    ->groupBy('student_id')
                    ->map(fn ($rows) => $rows->sortByDesc('taken_at')->first());

                $assessed = $latest->count();
                $bandCounts = [];
                foreach ($latest as $row) {
                    $label = $instrument->bandLabelFor((int) $row->score);
                    $bandCounts[$label] = ($bandCounts[$label] ?? 0) + 1;
                }

                // who5: kelas breakdown (legacy behavior) — only for students.
                $kelas = null;
                $kelasCounts = [];
                if ($assignment->instrument_code === 'who5' && $isStudent) {
                    foreach ($latest as $row) {
                        $kelasCounts[$row->kelas ?? 'Tanpa kelas'] = ($kelasCounts[$row->kelas ?? 'Tanpa kelas'] ?? 0) + 1;
                    }
                    $kelas = \App\Support\AggregatePrivacy::safe(array_values($kelasCounts), 5) ? $kelasCounts : null;
                }

                $section = [
                    'code' => $assignment->instrument_code,
                    'instrument' => $instrument,
                    'name' => $instrument->name,
                    'audience' => $assignment->audience,
                    'assessed' => $assessed,
                    'registered' => $registered,
                    'pct' => $registered > 0 ? round($assessed / $registered * 100) : 0,
                    'bands' => \App\Support\AggregatePrivacy::safe(array_values($bandCounts), 5) ? $bandCounts : null,
                    'escalatedCount' => \App\Support\AggregatePrivacy::safe(array_values($bandCounts), 5)
                        ? (int) $latest->where('escalated', true)->count() : null,
                ];
                // Key kelas hanya utk who5: non-who5 tanpa key, who5 selalu
                // ada (null saat tersupresi threshold).
                if ($assignment->instrument_code === 'who5' && $isStudent) {
                    $section['kelas'] = $kelas;
                }

                return $section;
            })
            ->values();

        return $sections->all();
    }
}
