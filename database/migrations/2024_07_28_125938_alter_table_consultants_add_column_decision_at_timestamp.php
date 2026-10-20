<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->timestamp('decision_at')->nullable()->after('status');
            $table->timestamp('interview_at')->nullable()->after('decision_at');
            $table->string('interview_link')->nullable()->after('interview_at');
        });
        // ponytail: raw MODIFY = MySQL-only; SQLite enum = varchar (skip).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE consultants MODIFY COLUMN status ENUM('accept', 'reject', 'pending', 'interview', 'payment') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn('decision_at');
            DB::statement("ALTER TABLE consultants MODIFY COLUMN status ENUM('accept', 'reject', 'pending', 'interview') DEFAULT 'pending'");
        });
    }
};
