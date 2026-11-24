<?php

namespace App\Services;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\SekolahStudent;
use Illuminate\Support\Collection;

/**
 * A1: resolusi akses instrumen per orang — assignment-driven (D2).
 * Employee: program aktif menang atas legacy level perusahaan (D8).
 * Siswa: hanya STUDENT_ALLOWED (D9 safety). Klinis: bypass via InstrumentGrant (D5).
 */
class AssessmentAccess
{
    /** Assignment terbuka utk employee: program aktif > legacy perusahaan. */
    public static function forEmployee(PerusahaanEmployee $e): Collection
    {
        $program = WellbeingQuotaService::activeProgram($e->perusahaan);
        $assignable = $program
            ? ['type' => $program->getMorphClass(), 'id' => $program->id]
            : ['type' => $e->perusahaan->getMorphClass(), 'id' => $e->perusahaan_id];

        $codes = self::openAssignments($assignable['type'], $assignable['id'], AssessmentAssignment::AUDIENCE_EMPLOYEE)
            ->filter(fn (AssessmentAssignment $a) => AssessmentInstrument::where('code', $a->instrument_code)->value('is_active'))
            ->values();

        return $codes;
    }

    /** Assignment terbuka utk anggota sekolah (siswa difilter STUDENT_ALLOWED). */
    public static function forSekolahMember(SekolahStudent $s): Collection
    {
        $audience = $s->type === SekolahStudent::TYPE_STAFF
            ? AssessmentAssignment::AUDIENCE_STAFF
            : AssessmentAssignment::AUDIENCE_STUDENT;

        return self::openAssignments($s->sekolah->getMorphClass(), $s->sekolah_id, $audience)
            ->filter(fn (AssessmentAssignment $a) => $audience !== AssessmentAssignment::AUDIENCE_STUDENT
                || in_array($a->instrument_code, AssessmentAssignment::STUDENT_ALLOWED, true))
            ->filter(fn (AssessmentAssignment $a) => AssessmentInstrument::where('code', $a->instrument_code)->value('is_active'))
            ->values();
    }

    /** Kode instrumen clinical per-grant (bypass assignment, D5). */
    public static function grantedCodes(PerusahaanEmployee $e): array
    {
        return InstrumentGrant::where('employee_id', $e->id)->pluck('instrument_code')->all();
    }

    public static function resolveForEmployee(PerusahaanEmployee $e, string $code): ?AssessmentAssignment
    {
        $open = self::forEmployee($e)->where('instrument_code', $code)->sortBy('round');

        // Round terbuka terendah yang belum dikerjakan; fallback round terendah
        // (controller render "sudah dikerjakan").
        return $open->first(fn ($a) => !self::hasSubmitted($a, $e->id, 'perusahaan')) ?? $open->first();
    }

    public static function resolveForSekolahMember(SekolahStudent $s, string $code): ?AssessmentAssignment
    {
        $open = self::forSekolahMember($s)->where('instrument_code', $code)->sortBy('round');

        return $open->first(fn ($a) => !self::hasSubmitted($a, $s->id, 'sekolah')) ?? $open->first();
    }

    /** Apakah orang ini sudah submit utk assignment tsb (satu submission per window, D6). */
    public static function hasSubmitted(AssessmentAssignment $a, int $personId, string $tenant): bool
    {
        $table = $tenant === 'sekolah' ? 'sekolah_assessments' : 'perusahaan_assessments';
        $personColumn = $tenant === 'sekolah' ? 'student_id' : 'employee_id';

        // Legacy (open-ended who5): perilaku lama — boleh isi ulang tiap 30 hari;
        // row disimpan tanpa assessment_assignment_id (lihat storedAssignmentId).
        if ($a->source === AssessmentAssignment::SOURCE_LEGACY) {
            return \Illuminate\Support\Facades\DB::table($table)
                ->where($personColumn, $personId)
                ->where('instrument_code', $a->instrument_code)
                ->where('taken_at', '>=', now()->subDays(30))
                ->exists();
        }

        return (bool) \Illuminate\Support\Facades\DB::table($table)
            ->where('assessment_assignment_id', $a->id)
            ->where($personColumn, $personId)
            ->exists();
    }

    /** assessment_assignment_id utk row baru: legacy null (unique index hanya utk window). */
    public static function storedAssignmentId(?AssessmentAssignment $a): ?int
    {
        return $a && $a->source !== AssessmentAssignment::SOURCE_LEGACY ? $a->id : null;
    }

    /** @return Collection<AssessmentAssignment> */
    private static function openAssignments(string $type, int $id, string $audience): Collection
    {
        return AssessmentAssignment::query()
            ->where('assignable_type', $type)
            ->where('assignable_id', $id)
            ->where('audience', $audience)
            ->open()
            ->get();
    }
}
