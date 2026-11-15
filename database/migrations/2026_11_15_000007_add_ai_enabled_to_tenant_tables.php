<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['kliniks', 'perusahaans', 'sekolahs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('ai_enabled')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        foreach (['kliniks', 'perusahaans', 'sekolahs'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('ai_enabled');
            });
        }
    }
};
