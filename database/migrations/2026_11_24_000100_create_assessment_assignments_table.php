<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A1 (D2): satu mekanisme assignment instrumen untuk Perusahaan + Sekolah.
// assignable morph: program=CompanyWellbeingProgram, perusahaan=Perusahaan,
// sekolah=Sekolah (alias via Relation::morphMap, AppServiceProvider).
// Precedence: assignment program aktif > assignment level perusahaan (legacy).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment_assignments')) {
            return;
        }

        Schema::create('assessment_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignable_type', 32); // program|perusahaan|sekolah
            $table->unsignedBigInteger('assignable_id');
            $table->string('instrument_code', 32)->index();
            $table->string('audience', 16)->default('employee'); // employee|student|staff
            $table->string('source', 16); // core|addon|legacy
            $table->decimal('addon_price', 14, 2)->nullable(); // hanya source=addon
            $table->unsignedSmallInteger('round')->default(1);
            $table->date('opens_at');
            $table->date('closes_at')->nullable(); // null = open-ended (legacy)
            $table->string('status', 16)->default('active'); // active|closed
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assignable_type', 'assignable_id', 'instrument_code', 'audience', 'round']);
            $table->index(['assignable_type', 'assignable_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_assignments');
    }
};
