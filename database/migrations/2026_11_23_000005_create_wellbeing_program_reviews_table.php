<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv2 (research2-infra §3): review kuartalan Impact — 4 rows dibuat saat assign
// (same transaction), due_at = starts_at + 3×period bulan, unique per period.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wellbeing_program_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('company_wellbeing_programs')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_no'); // 1–4
            $table->date('due_at');                    // starts_at + 3×period bulan
            $table->string('status', 20)->default('upcoming'); // upcoming|in_review|completed (TEXT, sqlite-safe)
            $table->text('summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['program_id', 'period_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wellbeing_program_reviews');
    }
};
