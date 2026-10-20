<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B-only: split_settlements (§15.10, Build-NEW #1). Ledger split:
     * net → Soluusi, margin → klinik. FK ke consultation_sessions.
     */
    public function up()
    {
        Schema::create('split_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('consultation_sessions')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('klinik_id')->constrained('kliniks')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('net_amount');      // → Soluusi
            $table->unsignedInteger('klinik_margin');   // → klinik
            $table->enum('status', ['pending', 'settled', 'reversed'])->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->unique('session_id'); // satu sesi = satu settlement (idempotent webhook)
        });
    }

    public function down()
    {
        Schema::dropIfExists('split_settlements');
    }
};
