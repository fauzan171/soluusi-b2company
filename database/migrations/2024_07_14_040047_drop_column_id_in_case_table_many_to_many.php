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
        // ponytail: sqlite tidak bisa drop PRIMARY KEY column; id sisa di pivot
        // harmless utk test. MySQL (prod) tetap drop.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        Schema::table('consultant_categories', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('category_skills', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('consultant_companies', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('consultant_education', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('consultant_skills', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('consultant_social_media', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultant_categories', function (Blueprint $table) {
            $table->id();
        });
        Schema::table('category_skills', function (Blueprint $table) {
            $table->id();
        });
        Schema::table('consultant_companies', function (Blueprint $table) {
            $table->id();
        });
        Schema::table('consultant_education', function (Blueprint $table) {
            $table->id();
        });
        Schema::table('consultant_skills', function (Blueprint $table) {
            $table->id();
        });
        Schema::table('consultant_social_media', function (Blueprint $table) {
            $table->id();
        });
    }
};
