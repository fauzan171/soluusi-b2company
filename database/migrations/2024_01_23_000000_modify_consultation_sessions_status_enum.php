<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // ponytail: raw ALTER ... MODIFY = MySQL-only. SQLite enum = varchar+check,
        // data mapping di bawah tetap jalan (no-op di fresh DB), MODIFY di-skip.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // First, update existing data to match new enum values
        DB::statement("UPDATE consultation_sessions SET status = 'scheduled' WHERE status = 'ongoing'");
        DB::statement("UPDATE consultation_sessions SET status = 'done' WHERE status = 'waiting_summary'");

        // Modify the enum column
        DB::statement("ALTER TABLE consultation_sessions MODIFY COLUMN status ENUM('waiting', 'scheduled', 'done', 'canceled') NOT NULL DEFAULT 'waiting'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert data mapping
        DB::statement("UPDATE consultation_sessions SET status = 'ongoing' WHERE status = 'scheduled'");
        DB::statement("UPDATE consultation_sessions SET status = 'waiting_summary' WHERE status = 'done' AND payment_status = 'verified'");
        
        // Revert the enum column
        DB::statement("ALTER TABLE consultation_sessions MODIFY COLUMN status ENUM('waiting', 'ongoing', 'done', 'waiting_summary') NOT NULL DEFAULT 'waiting'");
    }
};