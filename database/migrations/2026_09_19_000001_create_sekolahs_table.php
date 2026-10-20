<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vertikal Sekolah tenant. Mirror perusahaans (CAP).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // legal name / nama yayasan
            $table->string('brand');                    // white-label brand
            $table->string('logo')->nullable();
            $table->string('tingkat')->nullable();      // SMP|SMA|SMK|Kampus
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('sessions_per_student_month')->default(2);
            $table->unsignedBigInteger('associate_id')->nullable();
            $table->foreign('associate_id')->references('id')->on('users')->nullOnDelete();
            $table->dateTime('mou_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolahs');
    }
};
