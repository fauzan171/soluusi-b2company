<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B-only: consultant_fees (§15.3 DECIDED). Flat net fee per sesi
     * completed-and-verified, dibayar Soluusi → konsultan. BUKAN revenue-share
     * (field B2C profit_sharing preserved tapi TIDAK dipakai B2B — §15.9).
     */
    public function up()
    {
        Schema::create('consultant_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('consultant_package_id')->nullable()->constrained('consultant_packages')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('net_fee'); // flat per sesi
            $table->timestamp('effective_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('consultant_fees');
    }
};
