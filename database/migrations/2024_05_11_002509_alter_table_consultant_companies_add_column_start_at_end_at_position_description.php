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
        Schema::table('consultant_companies', function (Blueprint $table) {
            $table->timestamptz('start_at')->nullable()->after('company_id');
            $table->timestamptz('end_at')->nullable()->after('start_at');
            $table->string('position')->nullable()->after('end_at');
            $table->text('description')->nullable()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultant_companies', function (Blueprint $table) {
            $table->dropColumn('start_at');
            $table->dropColumn('end_at');
            $table->dropColumn('position');
            $table->dropColumn('description');
        });
    }
};
