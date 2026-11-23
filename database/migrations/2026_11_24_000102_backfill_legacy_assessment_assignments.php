<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A1 (D8 legacy parity): backfill assignment WHO-5 dari data prod existing.
// - Perusahaan TANPA program → 1 assignment legacy (open-ended) per perusahaan.
// - Perusahaan DENGAN program → core assignments dari snapshot assessment_domains:
//   mh → dass21+who5; nutrition → nutrition; wellbeing → who5 (D3).
// - Sekolah → who5 legacy utk audience student + staff.
// - sekolah_assessments.instrument_code null → 'who5' (mirror backfill W4 perusahaan).
// Idempotent: insertOrIgnore + unique index (assignable,instrument,audience,round).
// down(): no-op — data migration; assignment legacy jangan dihapus saat rollback
// (prod data hidup di tabel assessments yang tidak di-rollback bersama ini).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assessment_assignments')) {
            return;
        }

        $now = now();

        // Perusahaan tanpa program → legacy who5 (employee), open-ended.
        $perusahaans = DB::table('perusahaans')
            ->whereNotIn('id', DB::table('company_wellbeing_programs')->select('perusahaan_id'))
            ->get(['id', 'created_at']);
        foreach ($perusahaans as $p) {
            DB::table('assessment_assignments')->insertOrIgnore([
                'assignable_type' => 'perusahaan',
                'assignable_id' => $p->id,
                'instrument_code' => 'who5',
                'audience' => 'employee',
                'source' => 'legacy',
                'round' => 1,
                'opens_at' => substr((string) $p->created_at, 0, 10) ?: '1970-01-01',
                'closes_at' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Perusahaan dengan program → core dari snapshot domains (D3).
        $programs = DB::table('company_wellbeing_programs')->get(['id', 'perusahaan_id', 'starts_at', 'ends_at', 'assessment_domains']);
        foreach ($programs as $program) {
            $domains = json_decode($program->assessment_domains ?? '[]', true) ?: [];
            $codes = match (true) {
                in_array('mh', $domains, true) => ['dass21', 'who5'],
                in_array('nutrition', $domains, true) => ['nutrition'],
                in_array('wellbeing', $domains, true) => ['who5'],
                default => [],
            };
            foreach ($codes as $code) {
                DB::table('assessment_assignments')->insertOrIgnore([
                    'assignable_type' => 'program',
                    'assignable_id' => $program->id,
                    'instrument_code' => $code,
                    'audience' => 'employee',
                    'source' => 'core',
                    'round' => 1,
                    'opens_at' => $program->starts_at,
                    'closes_at' => $program->ends_at,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Sekolah → who5 legacy utk student + staff (D9: who5 aman utk siswa).
        foreach (DB::table('sekolahs')->get(['id']) as $sekolah) {
            foreach (['student', 'staff'] as $audience) {
                DB::table('assessment_assignments')->insertOrIgnore([
                    'assignable_type' => 'sekolah',
                    'assignable_id' => $sekolah->id,
                    'instrument_code' => 'who5',
                    'audience' => $audience,
                    'source' => 'legacy',
                    'round' => 1,
                    'opens_at' => '1970-01-01',
                    'closes_at' => null,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasColumn('sekolah_assessments', 'instrument_code')) {
            DB::table('sekolah_assessments')->whereNull('instrument_code')->update(['instrument_code' => 'who5']);
        }
    }

    public function down(): void
    {
        // no-op: data migration — baris legacy/core dipertahankan saat rollback
        // skema (000101 drop FK nullOnDelete, tidak menghapus assignment).
    }
};
