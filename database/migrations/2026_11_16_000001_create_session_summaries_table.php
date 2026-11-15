<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ringkasan AI per sesi konsultasi (perusahaan vertical). Satu baris per sesi.
// summary = untuk konsultan; client_advice = untuk karyawan. HR tidak melihat per-sesi.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('session_summaries')) {
            return;
        }

        Schema::create('session_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_session_id')->unique()
                ->constrained('perusahaan_sessions')->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->text('client_advice')->nullable();
            $table->string('input_hash', 64);
            $table->string('model')->nullable();
            $table->string('status', 16)->default('pending'); // pending|completed|failed
            $table->string('error_code', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_summaries');
    }
};
