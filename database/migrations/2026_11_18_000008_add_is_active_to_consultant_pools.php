<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ONB.5: HR/BK menyalakan/mematikan konsultan yang sudah di-assign Soluusi.
// Default true supaya pool existing tidak hilang diam-diam.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_pools', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('perusahaan_id');
        });
    }

    public function down(): void
    {
        Schema::table('consultant_pools', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};