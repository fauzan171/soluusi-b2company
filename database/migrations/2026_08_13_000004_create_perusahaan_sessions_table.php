<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sesi konseling karyawan program wellbeing.
// CRITICAL: employee_id dipakai untuk Employee dashboard (riwayat sendiri).
// HR dashboard TIDAK query kolom ini — hanya GROUP BY departement/topic/status.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id')->references('id')->on('perusahaan_employees')->nullOnDelete();
            $table->unsignedBigInteger('departement_id')->nullable();
            $table->foreign('departement_id')->references('id')->on('departements')->nullOnDelete();
            $table->unsignedBigInteger('consultant_id')->nullable();
            $table->foreign('consultant_id')->references('id')->on('consultants')->nullOnDelete();
            $table->string('konsultan_type')->default('psikolog'); // psikolog|nutrisionis|fisioterapis|karir
            $table->string('topic_category')->nullable();          // anonim: Stress, Kecemasan, Karir...
            $table->enum('status', ['scheduled', 'done', 'no_show'])->default('scheduled');
            $table->unsignedTinyInteger('rating')->nullable();      // 1-5, NPS individual → TIDAK tampil di HR
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();
            $table->index(['perusahaan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan_sessions');
    }
};
