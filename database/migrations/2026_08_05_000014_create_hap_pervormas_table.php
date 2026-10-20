<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B-only: hap_pervormas (Build-NEW #7, M6). 3-stage payout:
     * Stage1 flat @5 sesi, Stage2 1/3 sisa @25, Stage3 2/3 sisa @40 (90d rolling).
     * Immutable once paid. (Renamed dari hap_commissions — "pervorma" per brief.)
     */
    public function up()
    {
        Schema::create('hap_pervormas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinik_id')->constrained('kliniks')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('associate_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedTinyInteger('stage'); // 1 | 2 | 3
            $table->unsignedInteger('amount');
            $table->enum('status', ['due', 'paid'])->default('due');
            $table->timestamp('due_at')->nullable();   // payout SLA 7 hari kerja
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['klinik_id', 'stage']); // satu stage cair sekali per klinik
        });
    }

    public function down()
    {
        Schema::dropIfExists('hap_pervormas');
    }
};
