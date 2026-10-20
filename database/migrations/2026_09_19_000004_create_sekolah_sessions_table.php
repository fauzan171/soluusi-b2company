<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sesi konseling siswa. Mirror perusahaan_sessions.
// CRITICAL: dashboard guru hanya GROUP BY kelas/topic/status — tidak pernah
// query student_id individual (spec §16.1: NO individual student data).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('sekolah_students')->nullOnDelete();
            $table->string('kelas')->nullable();               // snapshot kelas saat booking
            $table->unsignedBigInteger('consultant_id')->nullable();
            $table->foreign('consultant_id')->references('id')->on('consultants')->nullOnDelete();
            $table->string('konsultan_type')->default('psikolog'); // psikolog|karir (sekolah: anak/remaja + karir)
            $table->string('topic_category')->nullable();      // anonim: Stress, Bullying, Kecemasan...
            $table->string('mode')->default('online');         // online|offline (offline = ruang BK)
            $table->string('origin')->default('bk_referral');  // bk_referral|self_booking
            $table->enum('status', ['scheduled', 'done', 'no_show', 'cancelled'])->default('scheduled');
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5 — TIDAK tampil per-siswa di portal sekolah
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();
            $table->index(['sekolah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah_sessions');
    }
};
