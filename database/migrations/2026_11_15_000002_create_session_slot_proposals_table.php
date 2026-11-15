<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_slot_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('perusahaan_sessions')->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
            $table->timestamp('proposed_at')->nullable();
            $table->json('slots'); // array of {start, end}
            $table->string('status')->default('pending'); // pending|accepted|rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_slot_proposals');
    }
};
