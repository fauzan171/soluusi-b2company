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
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('phone_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn('bank');
            $table->dropColumn('account_number');
            $table->dropColumn('phone_number');
        });
    }
};
