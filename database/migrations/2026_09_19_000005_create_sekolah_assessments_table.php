<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hasil skrining WHO-5 siswa. Mirror perusahaan_assessments.
// CRITICAL: portal guru hanya baca AGREGAT per kelas (distribusi band),
// n<5 per kelas di-suppress. Individual flag hanya psikolog + BK (confidential).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('sekolah_students')->nullOnDelete();
            $table->string('kelas')->nullable();
            $table->json('answers');                          // 5 item WHO-5, nilai 0-5
            $table->unsignedTinyInteger('score');             // 0-100
            $table->dateTime('taken_at');
            $table->timestamps();
            $table->index(['sekolah_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah_assessments');
    }
};
