<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B-only: kliniks (brand/domain/logo/gcal_id per mitra klinik).
     * White-label source of truth (PRD §15.4). Prefix domain baru — merge additive.
     */
    public function up()
    {
        Schema::create('kliniks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('brand');                        // white-label brand name
            $table->string('domain')->nullable();           // neutral/klinik CNAME (Phase 2 custom)
            $table->string('logo')->nullable();
            $table->string('from_email')->nullable();       // per-klinik sender (fallback config whitelabel)
            $table->string('gcal_id')->nullable();          // per-klinik calendar (fallback master)
            $table->foreignId('associate_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate(); // HAP owner
            $table->unsignedInteger('total_rate')->default(1000000); // HAP Rate set at MoU (M6)
            $table->timestamp('mou_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kliniks');
    }
};
