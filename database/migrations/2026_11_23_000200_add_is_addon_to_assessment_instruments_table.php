<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv1: label add-on berbayar (Ergonomic/DISC/Big Five) — LABEL SAJA,
// tidak mengubah akses (domain gate tetap berlaku seperti sebelumnya).
// Idempotent: guard hasColumn agar aman di DB yang sudah termigrate.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assessment_instruments', 'is_addon')) {
            return;
        }

        Schema::table('assessment_instruments', function (Blueprint $table) {
            $table->boolean('is_addon')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('assessment_instruments', 'is_addon')) {
            return;
        }

        Schema::table('assessment_instruments', function (Blueprint $table) {
            $table->dropColumn('is_addon');
        });
    }
};
