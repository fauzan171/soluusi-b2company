<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Idempotensi reminder H-1 hari / H-1 jam: flag per sesi — command reminder
// hanya kirim sekali per sesi per jarak (tanpa tabel notifications log).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->timestamp('reminded_day_at')->nullable()->after('schedule_version');
            $table->timestamp('reminded_hour_at')->nullable()->after('reminded_day_at');
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->dropColumn(['reminded_day_at', 'reminded_hour_at']);
        });
    }
};
