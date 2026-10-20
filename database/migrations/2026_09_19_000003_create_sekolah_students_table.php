<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Siswa terdaftar program wellbeing sekolah. Mirror perusahaan_employees.
// CRITICAL (spec §6 sekolah): PII siswa dipegang Soluusi, TIDAK pernah tampil
// di dashboard guru. Parent consent wajib untuk siswa < 18 (UU PDP).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->cascadeOnDelete();
            // Kelas = free string (VII-A, X IPA 2...) — terlalu beragam untuk master table.
            $table->string('kelas')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            // Parent consent (UU PDP + child protection §6.1/§6.3).
            $table->string('parent_name')->nullable();
            $table->dateTime('parent_consent_at')->nullable();
            $table->dateTime('consent_at')->nullable();   // consent siswa sendiri (>= 17 th)
            $table->timestamps();
            $table->index(['sekolah_id', 'kelas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah_students');
    }
};
