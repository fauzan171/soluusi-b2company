<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('expired_at');
        });
    }

    public function down()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
