<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hasil skrining WHO-5 karyawan. Skor 0-100 (sum answers * 4).
// CRITICAL: HR dashboard hanya baca AGREGAT (latest score per employee → distribusi band).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaan_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id')->references('id')->on('perusahaan_employees')->nullOnDelete();
            $table->unsignedBigInteger('departement_id')->nullable();
            $table->foreign('departement_id')->references('id')->on('departements')->nullOnDelete();
            $table->json('answers');                          // 5 item WHO-5, nilai 0-5
            $table->unsignedTinyInteger('score');             // 0-100
            $table->dateTime('taken_at');
            $table->timestamps();
            $table->index(['perusahaan_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan_assessments');
    }
};
