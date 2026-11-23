<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// wv1 (research2-infra §1): library instrumen assessment.
// - domain: gating instrumen per program (assessment_domains snapshot kontrak);
//   null = instrumen umum (Big Five/DISC) — selalu tersedia.
// - dimensions: JSON item_code → def dimensi (multi-dimensi DASS-21/Big Five/DISC).
// - instrument_grants: MCMI-III clinical indication-based — akses hanya via
//   grant eksplisit superadmin per employee (NO mass-test).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assessment_instruments', 'domain')) {
            Schema::table('assessment_instruments', function (Blueprint $table) {
                // mh | wellbeing | nutrition | ergonomic | clinical | null
                $table->string('domain')->nullable()->after('code');
            });
            // Backfill legacy (seeder lama berjalan sebelum kolom ini ada).
            DB::table('assessment_instruments')->where('code', 'who5')->update(['domain' => 'wellbeing']);
            DB::table('assessment_instruments')->whereIn('code', ['phq9', 'gad7', 'cbi'])->update(['domain' => 'mh']);
        }

        if (! Schema::hasColumn('assessment_instruments', 'dimensions')) {
            Schema::table('assessment_instruments', function (Blueprint $table) {
                // {key: {label, items: [code...], reverse: [code...], bands: [{max,label,note}]}}
                $table->json('dimensions')->nullable()->after('escalate_items');
            });
        }

        if (! Schema::hasTable('instrument_grants')) {
            Schema::create('instrument_grants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('perusahaan_employees')->cascadeOnDelete();
                $table->string('instrument_code'); // e.g. mcmi3
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['employee_id', 'instrument_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_grants');
        if (Schema::hasColumn('assessment_instruments', 'dimensions')) {
            Schema::table('assessment_instruments', function (Blueprint $table) {
                $table->dropColumn('dimensions');
            });
        }
        if (Schema::hasColumn('assessment_instruments', 'domain')) {
            Schema::table('assessment_instruments', function (Blueprint $table) {
                $table->dropColumn('domain');
            });
        }
    }
};
