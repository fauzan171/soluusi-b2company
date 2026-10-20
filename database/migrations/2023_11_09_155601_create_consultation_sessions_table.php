<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->restrictOnDelete()->cascadeOnUpdate();
            // B2B CHANGE (Model C): customer = guest tanpa akun → user_id nullable.
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('consultant_packages')->constrained('consultant_packages')->restrictOnDelete()->cascadeOnUpdate();
            // B2B CHANGE (Fix E.2 #3): enum final langsung di Blueprint (values sama
            // dengan hasil alter B2C). status +scheduled +canceled; payment_status +failed.
            $table->enum('status', ['waiting', 'scheduled', 'done', 'canceled', 'no_show'])->default('waiting');
            $table->string('order_number');
            $table->string('payment');
            $table->string('va_number')->nullable();
            $table->enum('payment_status', ['waiting', 'verified', 'canceled', 'failed'])->default('waiting');
            $table->integer('rating')->nullable();
            $table->timestampTz('order_at');
            $table->timestampTz('payment_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultation_sessions');
    }
};
