<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E.1: pembeda orang di program sekolah. `sekolah_students` dipakai bersama
 * oleh siswa dan staf sekolah (guru/karyawan) sebagai "person row" sesi &
 * asesmen. Default 'student' supaya baris lama tidak berubah arti.
 *
 * Agregat (DashboardMetrics, ProgramAggregate, AiInsightService) memakai
 * Sekolah::students() yang kini filter type='student' — staf tidak boleh
 * masuk hitungan terdaftar/kuota/kelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah_students', function (Blueprint $table) {
            $table->string('type')->default('student')->after('sekolah_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('sekolah_students', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};