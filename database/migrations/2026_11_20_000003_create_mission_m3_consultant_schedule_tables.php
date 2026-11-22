<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 (MASTER-PLAN K6/K7): kolom kontrak di consultant_pools + template jam kerja
// mingguan per assignment + exception ketersediaan per konsultan (global semua
// perusahaan; NULL time = full-day block).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_pools', function (Blueprint $table) {
            if (! Schema::hasColumn('consultant_pools', 'contract_start')) {
                $table->date('contract_start')->nullable()->after('perusahaan_id');
            }
            if (! Schema::hasColumn('consultant_pools', 'contract_end')) {
                $table->date('contract_end')->nullable()->after('contract_start');
            }
            if (! Schema::hasColumn('consultant_pools', 'status')) {
                $table->string('status', 20)->default('active')->after('contract_end'); // active|ended (TEXT, sqlite-safe)
            }
        });

        if (! Schema::hasTable('consultant_work_hours')) {
            Schema::create('consultant_work_hours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consultant_pool_id')->constrained('consultant_pools')->cascadeOnDelete();
                $table->unsignedTinyInteger('weekday'); // 0=Minggu .. 6=Sabtu
                $table->time('start_time');
                $table->time('end_time');
                $table->json('consultation_types')->nullable(); // null = semua tipe
                $table->unsignedSmallInteger('slot_minutes')->default(60);
                $table->timestamps();
                $table->unique(['consultant_pool_id', 'weekday', 'start_time']);
            });
        }

        if (! Schema::hasTable('consultant_availability_exceptions')) {
            Schema::create('consultant_availability_exceptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
                $table->date('date');
                $table->time('start_time')->nullable(); // NULL = full-day block
                $table->time('end_time')->nullable();
                $table->timestamps();
                $table->unique(['consultant_id', 'date', 'start_time']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_availability_exceptions');
        Schema::dropIfExists('consultant_work_hours');

        Schema::table('consultant_pools', function (Blueprint $table) {
            foreach (['status', 'contract_end', 'contract_start'] as $col) {
                if (Schema::hasColumn('consultant_pools', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
