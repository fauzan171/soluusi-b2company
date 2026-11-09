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
        $schema = Schema::getConnection()->getSchemaBuilder();

        Schema::table('consultation_sessions', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('consultation_sessions', 'klinik_id')) {
                $table->foreignId('klinik_id')->nullable()->after('consultant_package_id')->constrained('kliniks')->restrictOnDelete()->cascadeOnUpdate();
            }
            if (! $schema->hasColumn('consultation_sessions', 'checkout_link_id')) {
                $table->foreignId('checkout_link_id')->nullable()->after('klinik_id')->constrained('checkout_links')->nullOnDelete()->cascadeOnUpdate();
            }

            // Guest customer PII (Model C, Spec §6) — transactional only
            $pii = ['customer_name' => fn() => $table->string('customer_name')->nullable(),
                'customer_email' => fn() => $table->string('customer_email')->nullable(),
                'customer_phone' => fn() => $table->string('customer_phone')->nullable(),
                'customer_dob' => fn() => $table->date('customer_dob')->nullable(),
                'customer_regency_id' => fn() => $table->unsignedBigInteger('customer_regency_id')->nullable(),
                'customer_gender' => fn() => $table->string('customer_gender')->nullable(),
            ];
            // Dual-confirm (Spec §9)
            $dual = ['konsultan_done_at' => fn() => $table->timestamp('konsultan_done_at')->nullable(),
                'customer_confirmed_at' => fn() => $table->timestamp('customer_confirmed_at')->nullable(),
                'completion_verified_at' => fn() => $table->timestamp('completion_verified_at')->nullable(),
                'needs_ops_review' => fn() => $table->boolean('needs_ops_review')->default(false),
            ];
            // Check-in + no-show (Build-NEW #11)
            $checkin = ['konsultan_checked_in_at' => fn() => $table->timestamp('konsultan_checked_in_at')->nullable(),
                'customer_checked_in_at' => fn() => $table->timestamp('customer_checked_in_at')->nullable(),
                'no_show_at' => fn() => $table->timestamp('no_show_at')->nullable(),
                'cancel_reason' => fn() => $table->string('cancel_reason')->nullable(),
            ];
            foreach ([$pii, $dual, $checkin] as $group) {
                foreach ($group as $col => $def) {
                    if (! $schema->hasColumn('consultation_sessions', $col)) {
                        $def();
                    }
                }
            }
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
