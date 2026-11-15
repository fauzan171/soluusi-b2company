<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->text('request_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            $table->dropColumn('request_notes');
        });
    }
};
