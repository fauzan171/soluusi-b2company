<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Karyawan terdaftar di program wellbeing perusahaan.
// CRITICAL (spec §7/§16): PII karyawan dipegang Soluusi, TIDAK pernah tampil
// di HR dashboard. HR hanya lihat aggregate count.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaan_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
            $table->unsignedBigInteger('departement_id')->nullable();
            $table->foreign('departement_id')->references('id')->on('departements')->nullOnDelete();
            // Link ke login user (role employee) — nullable: karyawan bisa
            // terdaftar dulu sebelum dibuatkan akun.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->dateTime('consent_at')->nullable();   // UU PDP consent
            $table->timestamps();
            $table->index(['perusahaan_id', 'departement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan_employees');
    }
};
