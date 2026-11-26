<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// B5: campaign invite/reminder assessment — link opsional ke assignment
// (sender pakai ini utk exclude karyawan yang sudah submit).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('engagement_campaigns', 'assessment_assignment_id')) {
            Schema::table('engagement_campaigns', function (Blueprint $table) {
                $table->foreignId('assessment_assignment_id')->nullable()
                    ->constrained('assessment_assignments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('engagement_campaigns', 'assessment_assignment_id')) {
            Schema::table('engagement_campaigns', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assessment_assignment_id');
            });
        }
    }
};
