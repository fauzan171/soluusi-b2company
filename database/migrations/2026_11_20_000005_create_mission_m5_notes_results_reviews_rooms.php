<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M5 (MASTER-PLAN K14): catatan konsultan (encrypted), hasil konsultasi, review
// rating per sesi (UNIQUE), dan ruang fisik 1-on-1 per perusahaan.
// rooms dibuat sebelum migrasi M4 room_id FK — urutan file M5 < M4? TIDAK:
// M4 file 000004, M5 000005 — FK constrained('rooms') di M4 gagal jika rooms
// belum ada. Solusi: FK didefinisikan ulang di M5 (dropColumn+add constrained
// akan rumit); pendekatan paling sederhana = rooms dibuat DI MIGRASI INI
// sebelum M4 dijalankan? Tidak bisa — migrations run berurut 000004 lalu 000005.
// Perbaikan: M4 tidak memasang FK; M5 membuat rooms lalu FK room_id.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
                $table->string('name');
                $table->string('status', 20)->default('active'); // active|inactive (TEXT, sqlite-safe)
                $table->timestamps();
            });
        }

        // FK room_id → rooms (kolom ditambahkan di M4 tanpa constraint).
        if (Schema::hasColumn('perusahaan_sessions', 'room_id') && ! $this->hasFk('perusahaan_sessions', 'perusahaan_sessions_room_id_foreign')) {
            Schema::table('perusahaan_sessions', function (Blueprint $table) {
                $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('consultation_notes')) {
            Schema::create('consultation_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perusahaan_session_id')->constrained('perusahaan_sessions')->cascadeOnDelete();
                $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
                $table->text('body'); // encrypted cast di model (K16 — PDP)
                $table->timestamps();
                $table->unique('perusahaan_session_id');
            });
        }

        if (! Schema::hasTable('consultation_results')) {
            Schema::create('consultation_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perusahaan_session_id')->constrained('perusahaan_sessions')->cascadeOnDelete();
                $table->text('summary');
                $table->text('recommendation')->nullable();
                $table->timestamps();
                $table->unique('perusahaan_session_id');
            });
        }

        if (! Schema::hasTable('consultation_reviews')) {
            Schema::create('consultation_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('perusahaan_session_id')->constrained('perusahaan_sessions')->cascadeOnDelete();
                $table->foreignId('perusahaan_employee_id')->constrained('perusahaan_employees')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating'); // 1-5, validasi app-level
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->unique('perusahaan_session_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_reviews');
        Schema::dropIfExists('consultation_results');
        Schema::dropIfExists('consultation_notes');

        if (Schema::hasColumn('perusahaan_sessions', 'room_id') && $this->hasFk('perusahaan_sessions', 'perusahaan_sessions_room_id_foreign')) {
            Schema::table('perusahaan_sessions', function (Blueprint $table) {
                $table->dropForeign(['room_id']);
            });
        }

        Schema::dropIfExists('rooms');
    }

    private function hasFk(string $table, string $name): bool
    {
        foreach (DB::select("pragma foreign_key_list({$table})") as $fk) {
            if (($fk->table ?? null) === 'rooms') {
                return true;
            }
        }

        return false;
    }
};
