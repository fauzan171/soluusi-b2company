<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('program_consent_history', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('roster_id');
            $table->foreignId('actor_id')->constrained('users');
            $table->boolean('granted');
            $table->text('evidence');
            $table->timestamp('agreed_at');
            $table->timestamp('recorded_at');
            $table->index(['type', 'roster_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_consent_history');
    }
};
