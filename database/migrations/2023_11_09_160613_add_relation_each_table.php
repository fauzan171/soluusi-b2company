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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('consultants', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('consultant_packages', function (Blueprint $table) {
            $table->foreignId('consultant_id')->constrained('consultants')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('benefits', function (Blueprint $table) {
            $table->foreignId('consultant_package_id')->constrained('consultant_packages')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('consultant_id')->constrained('consultants')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('consultant_id')->constrained('consultants')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('consultant_id')->constrained('consultants')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });

        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('category_id');
        });

        Schema::table('consultant_packages', function (Blueprint $table) {
            $table->dropColumn('consultant_id');
        });

        Schema::table('benefits', function (Blueprint $table) {
            $table->dropColumn('consultant_package_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('consultant_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('consultant_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('consultant_id');
        });
    }
};
