<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pool konsultan yang Soluusi assign ke tenant (spec sekolah §4.1 / CAP §5.2).
// Satu konsultan bisa di multi tenant; sekolah_id & perusahaan_id nullable
// (pool khusus salah satu vertikal).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultant_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolahs')->cascadeOnDelete();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaans')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['consultant_id', 'sekolah_id', 'perusahaan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_pools');
    }
};
