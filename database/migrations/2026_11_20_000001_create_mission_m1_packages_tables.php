<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M1 (MASTER-PLAN §2): packages + employee_packages.
// Package kuota bulanan per karyawan; assignment aktif 1 per employee dijaga
// app-level (partial unique tidak portable di sqlite) — skema sengaja simple.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedTinyInteger('monthly_quota')->default(2); // sesi/karyawan/bulan
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_packages')) {
            Schema::create('employee_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perusahaan_employee_id')->constrained('perusahaan_employees')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->string('status', 20)->default('active'); // active|ended (TEXT, sqlite-safe)
                $table->timestamps();
                $table->index(['perusahaan_id', 'status']);
                $table->index(['perusahaan_employee_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_packages');
        Schema::dropIfExists('packages');
    }
};
