<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// M8 (W4, K12): definisi instrumen assessment sebagai DATA. Instrumen baru =
// seeder, tanpa kode. Item instrumen hidup di screening_questions (kolom M6
// instrument_code/version/weight) — vertical 'perusahaan' saja untuk v1.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assessment_instruments')) {
            Schema::create('assessment_instruments', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();               // who5 | phq9 | gad7 | cbi
                $table->string('name');                         // "WHO-5 Well-Being Index"
                $table->string('version')->default('1');
                $table->string('scoring_type');                 // sum | sum_x4 | mean_x25
                $table->json('bands');                          // [{max, label, note}]
                $table->text('interpretation')->nullable();     // teks interpretasi umum
                $table->string('license_note')->nullable();
                $table->json('escalate_items')->nullable();     // kode item eskalasi, e.g. ["P9"] (PHQ-9 item 9)
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Link existing WHO-5 rows ke definition (backfill; K12 migration path).
        if (Schema::hasTable('perusahaan_assessments') && ! Schema::hasColumn('perusahaan_assessments', 'instrument_code')) {
            Schema::table('perusahaan_assessments', function (Blueprint $table) {
                $table->string('instrument_code')->nullable()->after('departement_id')->index();
                $table->boolean('escalated')->default(false)->after('instrument_code');
            });
            DB::table('perusahaan_assessments')->whereNull('instrument_code')->update(['instrument_code' => 'who5']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('perusahaan_assessments', 'instrument_code')) {
            Schema::table('perusahaan_assessments', function (Blueprint $table) {
                $table->dropIndex(['instrument_code']);
                $table->dropColumn(['instrument_code', 'escalated']);
            });
        }
        Schema::dropIfExists('assessment_instruments');
    }
};
