<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B §15.13: tag vertical di categories. B2C rows default 'b2c' (merge
     * parity — kolom additive, skema domain tetap identik).
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('vertical')->default('b2c')->after('code_category'); // b2c | klinik | both
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('vertical');
        });
    }
};
