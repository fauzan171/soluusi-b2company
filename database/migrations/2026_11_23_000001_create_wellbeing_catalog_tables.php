<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Wellbeing catalog (MASTER-PLAN §1, arch §3.1+3.2): wellbeing_packages +
// wellbeing_pricing_bands. Katalog = DATA (seeder WellbeingPackageSeeder, K3),
// editable runtime via admin UI. Aditif: 0 tabel/kolom existing berubah.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wellbeing_packages')) {
            Schema::create('wellbeing_packages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();          // discovery|foundation|growth|impact
                $table->string('name');
                $table->unsignedSmallInteger('duration_days'); // 14|90|180|365
                $table->boolean('is_free')->default(false);    // discovery = true
                // fitur matrix (nullable = tidak termasuk)
                $table->unsignedTinyInteger('assessment_count')->default(0);       // 0|1|1|2|2
                $table->unsignedInteger('assessment_max_participants')->nullable(); // 40 (discovery), null = semua
                $table->json('assessment_domains')->nullable();                     // ["mh"] / ["mh","nutrition"]
                $table->unsignedTinyInteger('expert_chat_experts')->default(0);     // 0|1|2|2
                $table->unsignedInteger('expert_chat_quota')->default(0);           // bucket 1 (K6)
                $table->unsignedInteger('deep_support_quota')->default(0);          // bucket 2 (K6)
                $table->string('deep_support_mode', 10)->nullable();                // null|'mhcu'|'one_on_one'
                $table->unsignedTinyInteger('webinar_count')->default(0);           // 0|1|3|6
                $table->boolean('quarterly_review')->default(false);                // impact only
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wellbeing_pricing_bands')) {
            Schema::create('wellbeing_pricing_bands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wellbeing_package_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('band_min');              // 50|151|401|601|801
                $table->unsignedInteger('band_max');              // 150|400|600|800|1000
                $table->decimal('monthly_price', 14, 2);          // Rp/bulan, exclude pajak (K13)
                $table->unsignedInteger('mhcu_quota')->nullable(); // 5..25 growth / 10..50 impact; null = default katalog
                $table->timestamps();
                $table->unique(['wellbeing_package_id', 'band_min']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wellbeing_pricing_bands');
        Schema::dropIfExists('wellbeing_packages');
    }
};
