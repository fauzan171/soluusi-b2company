<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B additive alter consultation_sessions:
     * - Model C guest PII (customer tanpa akun — PII transaksional di sesi)
     * - klinik_id + checkout_link_id (link-based booking)
     * - dual-confirm anti-fraud (Build-NEW #4) + no-show/check-in (#11)
     * Kolom B2C verbatim tidak disentuh (merge parity).
     */
    public function up()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->foreignId('klinik_id')->nullable()->after('consultant_package_id')->constrained('kliniks')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('checkout_link_id')->nullable()->after('klinik_id')->constrained('checkout_links')->nullOnDelete()->cascadeOnUpdate();

            // Guest customer PII (Model C, Spec §6) — transactional only
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->date('customer_dob')->nullable();
            $table->unsignedBigInteger('customer_regency_id')->nullable();
            $table->string('customer_gender')->nullable();

            // Dual-confirm (Spec §9): konsultan mark-done → verify → release
            $table->timestamp('konsultan_done_at')->nullable();
            $table->timestamp('customer_confirmed_at')->nullable();
            $table->timestamp('completion_verified_at')->nullable();
            $table->boolean('needs_ops_review')->default(false); // auto-confirm fallback flag

            // Check-in + no-show (Build-NEW #11)
            $table->timestamp('konsultan_checked_in_at')->nullable();
            $table->timestamp('customer_checked_in_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->string('cancel_reason')->nullable();
        });
    }

    public function down()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            foreach (['klinik_id', 'checkout_link_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_dob', 'customer_regency_id', 'customer_gender', 'konsultan_done_at', 'customer_confirmed_at', 'completion_verified_at', 'needs_ops_review', 'konsultan_checked_in_at', 'customer_checked_in_at', 'no_show_at', 'cancel_reason'] as $col) {
                Schema::table('consultation_sessions', function (Blueprint $t) use ($col) {
                    $t->dropColumn($col);
                });
            }
        });
    }
};
