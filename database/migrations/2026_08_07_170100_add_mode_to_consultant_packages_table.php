<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('consultant_packages', function (Blueprint $table) {
            $table->string('mode')->default('online')->after('name');
        });
    }

    public function down()
    {
        Schema::table('consultant_packages', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
