<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Wellbeing kontrak + ledger (MASTER-PLAN §1, arch §3.3+3.4): company_wellbeing_programs +
// wellbeing_quota_ledger. Snapshot kuota+band+harga saat assign (K4); 2 bucket
// expert_chat|deep_support tanpa period_ym (K6, kontrak-level total). Aditif.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_wellbeing_programs')) {
            Schema::create('company_wellbeing_programs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
                $table->foreignId('wellbeing_package_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pricing_band_id')->nullable()->constrained('wellbeing_pricing_bands')->nullOnDelete(); // snapshot band; null utk discovery
                $table->unsignedInteger('employee_band_size');   // snapshot jumlah karyawan saat assign
                $table->string('deep_support_mode', 10);         // 'mhcu'|'one_on_one' (K7, wajib utk growth/impact)
                $table->date('starts_at');
                $table->date('ends_at');                         // starts_at + duration_days - 1
                // KUOTA SNAPSHOT (kebal perubahan katalog, K4):
                $table->unsignedInteger('expert_chat_quota');    // bucket 1
                $table->unsignedInteger('deep_support_quota');   // bucket 2 (final: band MHCU jika option A)
                $table->unsignedTinyInteger('webinar_quota')->default(0);
                $table->unsignedTinyInteger('assessment_count')->default(0);
                $table->unsignedInteger('assessment_max_participants')->nullable();
                // snapshot tambahan (T4 invoice/scope, R4/R6 termination):
                $table->decimal('monthly_price', 14, 2)->nullable(); // harga band saat assign (K13, exclude pajak)
                $table->json('assessment_domains')->nullable();      // scope assessment saat assign
                $table->json('selected_employee_ids')->nullable();   // K12: discovery 8 karyawan terpilih; null = semua
                $table->timestamp('notice_requested_at')->nullable(); // K11 30-day notice
                $table->date('terminated_at')->nullable();
                $table->string('status', 20)->default('active'); // active|ended|cancelled (TEXT, sqlite-safe)
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // superadmin
                $table->timestamps();
                $table->index(['perusahaan_id', 'status']);
            });
        }

        if (! Schema::hasTable('wellbeing_quota_ledger')) {
            Schema::create('wellbeing_quota_ledger', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained('company_wellbeing_programs')->cascadeOnDelete();
                $table->string('bucket', 20);                    // expert_chat|deep_support (TEXT, sqlite-safe)
                $table->foreignId('perusahaan_session_id')->nullable()->constrained('perusahaan_sessions')->cascadeOnDelete();
                $table->foreignId('perusahaan_employee_id')->nullable()->constrained('perusahaan_employees')->cascadeOnDelete();
                $table->string('event', 20);                     // reserved|used|forfeited|refunded|released (mirror M2)
                $table->text('reason')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['perusahaan_session_id', 'event']);
                $table->index(['program_id', 'bucket']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wellbeing_quota_ledger');
        Schema::dropIfExists('company_wellbeing_programs');
    }
};
