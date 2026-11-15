<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ADR 001 (opsi A): total maksimal 3 jadwal per sesi (1 awal + 2 reschedule).
// schedule_version = jumlah scheduled_at yang pernah di-set; 0 = belum terjadwal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('schedule_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->dropColumn('schedule_version');
        });
    }
};
