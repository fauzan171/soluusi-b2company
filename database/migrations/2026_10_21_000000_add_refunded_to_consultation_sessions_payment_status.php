<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah 'refunded' ke enum payment_status.
     *
     * Tanpa ini webhook refund gagal menyimpan status: MySQL memotong nilai di luar
     * enum jadi '' (strict mode = error), sehingga sesi tampak belum dibayar padahal
     * uangnya sudah dikembalikan.
     *
     * ponytail: raw ALTER ... MODIFY = MySQL-only, sama seperti migrasi enum sebelumnya.
     * SQLite pakai varchar tanpa CHECK untuk payment_status, jadi no-op di sana.
     */
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE consultation_sessions MODIFY COLUMN payment_status ENUM('waiting', 'verified', 'canceled', 'failed', 'refunded') NOT NULL DEFAULT 'waiting'");
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE consultation_sessions SET payment_status = 'canceled' WHERE payment_status = 'refunded'");
        DB::statement("ALTER TABLE consultation_sessions MODIFY COLUMN payment_status ENUM('waiting', 'verified', 'canceled', 'failed') NOT NULL DEFAULT 'waiting'");
    }
};