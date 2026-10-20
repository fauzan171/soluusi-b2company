<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Status 'cancelled' untuk sesi yang dibatalkan karyawan — kuota kembali.
// SQLite tidak enforce enum; untuk MySQL rebuild kolom enum.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE perusahaan_sessions MODIFY status ENUM('scheduled', 'done', 'no_show', 'cancelled') NOT NULL DEFAULT 'scheduled'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE perusahaan_sessions MODIFY status ENUM('scheduled', 'done', 'no_show') NOT NULL DEFAULT 'scheduled'");
        }
    }
};
