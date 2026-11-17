<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C1: pervorma (Build-NEW #7) untuk vertikal perusahaan — mirror hap_pervormas
     * (CAP playbook: 600K/800K/1.6M; triggers 1=mou_at/2=50 sesi/3=150 sesi; rates live di config C2).
     * associate_id: kolom sudah ada sejak create-table (kliniks.associate_id
     * ditambahkan belakangan — di sini FK dideklarasikan di create).
     */
    public function up(): void
    {
        Schema::create('perusahaan_pervormas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained('perusahaans')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('associate_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedTinyInteger('stage'); // 1 | 2 | 3
            $table->unsignedInteger('amount');
            $table->enum('status', ['due', 'paid'])->default('due');
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('due_at')->nullable();   // payout SLA 7 hari kerja
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['perusahaan_id', 'stage']); // satu stage cair sekali per tenant
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan_pervormas');
    }
};
