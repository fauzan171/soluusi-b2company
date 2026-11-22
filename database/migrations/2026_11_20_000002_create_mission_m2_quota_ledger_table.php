<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M2 (MASTER-PLAN K2): ledger kuota append-only. Periode = calendar month
// (period_ym "YYYY-M-", tz Asia/Jakarta). UNIQUE(session, event) = guard idempotent.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultation_quota_ledger')) {
            Schema::create('consultation_quota_ledger', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_package_id')->constrained('employee_packages')->cascadeOnDelete();
                $table->foreignId('perusahaan_session_id')->nullable()->constrained('perusahaan_sessions')->cascadeOnDelete();
                $table->string('period_ym', 7); // "YYYY-MM"
                $table->string('event', 20);    // reserved|used|forfeited|refunded|released (TEXT, sqlite-safe)
                $table->text('reason')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['perusahaan_session_id', 'event']);
                $table->index(['employee_package_id', 'period_ym']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_quota_ledger');
    }
};
