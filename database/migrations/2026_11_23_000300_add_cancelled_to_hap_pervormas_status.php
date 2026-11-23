<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// QA10-F3 claw-back: status 'cancelled' untuk stage HAP yang tak lagi tercapai
// setelah refund sesi. SQLite enum = varchar+check tanpa enforce; MySQL rebuild
// kolom enum (pola 2026_08_18_080001). Idempotent via hasColumn+driver guard.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('hap_pervormas')) {
            DB::statement("ALTER TABLE hap_pervormas MODIFY status ENUM('due', 'paid', 'cancelled') NOT NULL DEFAULT 'due'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('hap_pervormas')) {
            DB::statement("UPDATE hap_pervormas SET status = 'due' WHERE status = 'cancelled'");
            DB::statement("ALTER TABLE hap_pervormas MODIFY status ENUM('due', 'paid') NOT NULL DEFAULT 'due'");
        }
    }
};
