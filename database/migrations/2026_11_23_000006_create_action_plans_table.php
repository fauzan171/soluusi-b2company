<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv4: feature B — HR action plan tracker per area prioritas (paket Growth/Impact).
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('action_plans')) {
            return;
        }
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_wellbeing_program_id')->constrained()->cascadeOnDelete();
            $table->string('area'); // e.g. departement name / domain (mh, nutrition)
            $table->string('status')->default('planned'); // planned|in_progress|completed
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('action_plans')) {
            Schema::dropIfExists('action_plans');
        }
    }
};
