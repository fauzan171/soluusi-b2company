<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** B2B-only: pivot user ↔ klinik (Partner Admin / Partner Staff). */
    public function up()
    {
        Schema::create('klinik_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinik_id')->constrained('kliniks')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('role', ['admin', 'staff'])->default('staff'); // Partner Admin | Partner Staff (PRD §4.2)
            $table->timestamps();

            $table->unique(['klinik_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('klinik_admins');
    }
};
