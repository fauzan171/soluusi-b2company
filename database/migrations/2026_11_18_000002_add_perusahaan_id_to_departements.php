<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ONB.1 — departemen jadi milik satu perusahaan.
 *
 * Nullable supaya row legacy (dibuat sebelum migrasi ini) dan
 * `consultant_education` (FK ke departements) tidak putus. Divisi legacy
 * tetap tampil untuk semua tenant lewat Departement::forPerusahaan().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->foreignId('perusahaan_id')->nullable()->after('id')
                ->constrained('perusahaans')->nullOnDelete();
            $table->index('perusahaan_id');
        });
    }

    public function down(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->dropForeign(['perusahaan_id']);
            $table->dropIndex(['perusahaan_id']);
            $table->dropColumn('perusahaan_id');
        });
    }
};