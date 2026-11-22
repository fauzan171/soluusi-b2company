<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M6 (MASTER-PLAN K12): perluas mesin screening existing jadi definition-driven.
// instrument_code + version identifikasi instrumen (WHO-5/PHQ-9/...), weight bobot skor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screening_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('screening_questions', 'instrument_code')) {
                $table->string('instrument_code')->nullable()->after('code'); // e.g. WHO-5, PHQ-9
            }
            if (! Schema::hasColumn('screening_questions', 'version')) {
                $table->string('version')->nullable()->after('instrument_code');
            }
            if (! Schema::hasColumn('screening_questions', 'weight')) {
                $table->float('weight')->nullable()->after('version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('screening_questions', function (Blueprint $table) {
            foreach (['weight', 'version', 'instrument_code'] as $col) {
                if (Schema::hasColumn('screening_questions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
