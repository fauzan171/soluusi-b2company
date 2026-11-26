<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link consultants row ke sumber B2C (jerichoad/soluusi MySQL).
     * Nullable + unique: konsultan lokal (legacy/demo) tetap boleh NULL.
     */
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->unsignedInteger('b2c_consultant_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            // SQLite: drop column under a unique index errors — drop index first.
            $table->dropUnique('consultants_b2c_consultant_id_unique');
            $table->dropColumn('b2c_consultant_id');
        });
    }
};
