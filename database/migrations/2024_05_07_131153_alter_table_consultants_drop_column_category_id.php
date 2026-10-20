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
        // ponytail: SQLite can't dropForeign; guard MySQL-only op (prod identik B2C).
        $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
        Schema::table('consultants', function (Blueprint $table) use ($isMysql) {
            if ($isMysql) {
                $table->dropForeign(['category_id']);
            }
            $table->dropColumn('category_id');
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
            $table->foreignId('category_id')->constrained('categories');
        });
    }
};
