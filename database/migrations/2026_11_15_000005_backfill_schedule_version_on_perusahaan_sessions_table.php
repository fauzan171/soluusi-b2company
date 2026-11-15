<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Backfill: sesi legacy yang sudah punya scheduled_at tapi schedule_version=0
// (kolom baru di 2026_11_15_000004) dianggap sudah terjadwal sekali → version=1.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('perusahaan_sessions')
            ->whereNotNull('scheduled_at')
            ->where('schedule_version', 0)
            ->update(['schedule_version' => 1]);
    }

    public function down(): void
    {
        // Data backfill tidak di-rollback (tidak bisa membedakan mana yang asli 1).
    }
};
