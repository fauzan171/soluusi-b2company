<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase G.1: mode konsultasi perusahaan (online|offline), mirror sekolah_sessions.mode.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->string('mode')->default('online');
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};