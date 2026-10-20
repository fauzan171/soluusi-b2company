<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->string('payment_method')->nullable();
            $table->string('link_meeting')->nullable();
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('link_meeting');
            $table->dropColumn('note');
        });
    }
};
