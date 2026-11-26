<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A1 (D6): satu submission per (assignment, orang) — unique index, bukan
// retake window. NULL assessment_assignment_id = baris lama (legacy WHO-5);
// SQLite & MySQL sama-sama membiarkan duplikat NULL (baris lama tak tersentuh).
// Nama index eksplisit pendek (default Laravel > 64 char terpotong di MySQL).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perusahaan_assessments', 'assessment_assignment_id')) {
            Schema::table('perusahaan_assessments', function (Blueprint $table) {
                $table->foreignId('assessment_assignment_id')->nullable()
                    ->constrained('assessment_assignments')->nullOnDelete();
            });
        }
        $this->addUnique('perusahaan_assessments', 'pa_assignment_employee_uq', ['assessment_assignment_id', 'employee_id']);

        if (! Schema::hasColumn('sekolah_assessments', 'instrument_code')) {
            Schema::table('sekolah_assessments', function (Blueprint $table) {
                $table->string('instrument_code', 32)->nullable()->after('kelas');
            });
        }
        if (! Schema::hasColumn('sekolah_assessments', 'escalated')) {
            Schema::table('sekolah_assessments', function (Blueprint $table) {
                $table->boolean('escalated')->default(false)->after('score');
            });
        }
        if (! Schema::hasColumn('sekolah_assessments', 'assessment_assignment_id')) {
            Schema::table('sekolah_assessments', function (Blueprint $table) {
                $table->foreignId('assessment_assignment_id')->nullable()
                    ->constrained('assessment_assignments')->nullOnDelete();
            });
        }
        $this->addUnique('sekolah_assessments', 'sa_assignment_student_uq', ['assessment_assignment_id', 'student_id']);

        if (! Schema::hasColumn('instrument_grants', 'reason')) {
            Schema::table('instrument_grants', function (Blueprint $table) {
                // D5: indikasi klinis untuk grant MCMI-III (max 500).
                $table->string('reason', 500)->nullable();
            });
        }
    }

    /** Idempotent: unique index hanya bila belum ada (SQLite & MySQL). */
    private function addUnique(string $table, string $indexName, array $cols): void
    {
        $driver = Schema::getConnection()->getConfig('driver');
        $exists = $driver === 'sqlite'
            ? DB::selectOne("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$indexName])
            : DB::selectOne(
                "SELECT index_name FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=?",
                [$table, $indexName]
            );
        if (! $exists) {
            Schema::table($table, function (Blueprint $table) use ($indexName, $cols) {
                $table->unique($cols, $indexName);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('perusahaan_assessments', 'assessment_assignment_id')) {
            Schema::table('perusahaan_assessments', function (Blueprint $table) {
                $table->dropForeign(['assessment_assignment_id']);
                $table->dropUnique('pa_assignment_employee_uq');
                $table->dropColumn('assessment_assignment_id');
            });
        }
        if (Schema::hasColumn('sekolah_assessments', 'assessment_assignment_id')) {
            Schema::table('sekolah_assessments', function (Blueprint $table) {
                $table->dropForeign(['assessment_assignment_id']);
                $table->dropUnique('sa_assignment_student_uq');
                $table->dropColumn(['assessment_assignment_id', 'instrument_code', 'escalated']);
            });
        }
        if (Schema::hasColumn('instrument_grants', 'reason')) {
            Schema::table('instrument_grants', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
