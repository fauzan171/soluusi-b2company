<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['kliniks', 'perusahaans', 'sekolahs'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('status', 20)->default('active')->after('is_active');
            });
        }
    }

    public function down(): void
    {
        foreach (['kliniks', 'perusahaans', 'sekolahs'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
