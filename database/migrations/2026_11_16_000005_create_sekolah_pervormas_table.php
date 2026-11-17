<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C1: pervorma (Build-NEW #7) untuk vertikal sekolah — mirror hap_pervormas
     * (SAP playbook: 300K/400K/800K @5/25/40 sesi; rates live di config C2).
     */
    public function up(): void
    {
        Schema::create('sekolah_pervormas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('associate_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedTinyInteger('stage'); // 1 | 2 | 3
            $table->unsignedInteger('amount');
            $table->enum('status', ['due', 'paid'])->default('due');
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('due_at')->nullable();   // payout SLA 7 hari kerja
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['sekolah_id', 'stage']); // satu stage cair sekali per tenant
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah_pervormas');
    }
};
