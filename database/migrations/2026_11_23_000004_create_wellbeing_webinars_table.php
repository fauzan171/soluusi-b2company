<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv2 (research2-infra §2): entitas webinar per kontrak Wellbeing — 1 webinar
// selesai = 1 ledger row bucket 'webinar' (no attendance table v1).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wellbeing_webinars')) {
            return; // REV MED-4: idempotent vs stale migration names
        }
        Schema::create('wellbeing_webinars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('company_wellbeing_programs')->cascadeOnDelete();
            $table->string('title');
            $table->timestamp('scheduled_at');
            $table->string('status', 20)->default('planned'); // planned|done|cancelled (TEXT, sqlite-safe)
            $table->foreignId('consultant_id')->nullable()->constrained('consultants')->nullOnDelete(); // speaker
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wellbeing_webinars');
    }
};
