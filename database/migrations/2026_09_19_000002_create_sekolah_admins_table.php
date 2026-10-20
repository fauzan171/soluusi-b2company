<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admin sekolah (Kepsek / BK coordinator). Mirror perusahaan_admins.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // sekolah_admin (kepsek) | bk (guru BK coordinator)
            $table->string('role')->default('bk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah_admins');
    }
};
