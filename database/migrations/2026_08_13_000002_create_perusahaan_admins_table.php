<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pivot user ↔ perusahaan (mirror klinik_admins). Role HR admin perusahaan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaan_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['hr_admin', 'employee'])->default('hr_admin');
            $table->timestamps();
            $table->unique(['perusahaan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan_admins');
    }
};
