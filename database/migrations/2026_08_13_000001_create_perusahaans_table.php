<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vertikal Perusahaan (CAP) tenant. Mirror kliniks table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // legal name
            $table->string('brand');                    // white-label brand
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('associate_id')->nullable();
            $table->foreign('associate_id')->references('id')->on('users')->nullOnDelete();
            $table->dateTime('mou_at')->nullable();
            $table->unsignedInteger('pepm_rate')->default(15000);              // Rp / employee / month
            $table->unsignedTinyInteger('sessions_per_employee_month')->default(2);
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('nps_score', 4, 1)->nullable();                    // aggregate wellbeing score (dummy MVP)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaans');
    }
};
