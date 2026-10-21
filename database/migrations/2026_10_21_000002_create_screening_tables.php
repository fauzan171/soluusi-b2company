<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Screening multi-domain (Mental Health / Nutrition & Lifestyle / Ergonomic) diadopsi
// dari flow kiosk TVS. Bank pertanyaan disimpan sebagai DATA ber-vertical, bukan kode,
// supaya segmen perusahaan & sekolah berbagi satu mesin skor.
//
// Asesmen yang sudah ada TIDAK diganti: WHO-5 lama tetap valid dan dipetakan ke
// section 'mental_health'. Riwayat per waktu (tren) dipertahankan — kiosk menyimpan satu
// snapshot, Soluusi menyimpan deret, jadi tidak ada unique(employee, section).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_questions', function (Blueprint $table) {
            $table->id();
            $table->string('vertical');                     // perusahaan | sekolah
            $table->string('section');                      // mental_health | nutrition_lifestyle | ergonomic
            $table->string('code');                         // Q5 … Q34 (kode form asli)
            $table->text('text');
            $table->string('type');                         // freq0-4 | pain4 | yesno | single | multi | scale1-10
            $table->json('options')->nullable();            // pilihan, untuk single/multi
            $table->boolean('reverse')->default(false);     // skor terbalik (item positif)
            $table->unsignedSmallInteger('ord');
            $table->timestamps();
            $table->unique(['vertical', 'code']);
            $table->index(['vertical', 'section', 'ord']);
        });

        Schema::create('screening_answers', function (Blueprint $table) {
            $table->id();
            $table->string('assessable_type');              // App\Models\Perusahaan\PerusahaanAssessment | …\Sekolah\SekolahAssessment
            $table->unsignedBigInteger('assessable_id');
            $table->string('question_code');
            $table->text('value')->nullable();              // mentah: int / string / JSON array
            $table->text('other_text')->nullable();         // isi "Other"
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['assessable_type', 'assessable_id', 'question_code'], 'screening_answers_unique');
        });

        // Perluas asesmen perusahaan: satu baris per (sesi, domain) alih-alih satu per check-up.
        Schema::table('perusahaan_assessments', function (Blueprint $table) {
            $table->string('section')->default('mental_health')->after('employee_id');
            $table->string('status')->default('completed')->after('section');   // pending | in_progress | completed
            $table->unsignedBigInteger('consultant_id')->nullable()->after('status');
            $table->text('notes')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->index(['perusahaan_id', 'section', 'taken_at']);
        });

        Schema::table('sekolah_assessments', function (Blueprint $table) {
            $table->string('section')->default('mental_health')->after('student_id');
            $table->string('status')->default('completed')->after('section');
            $table->unsignedBigInteger('consultant_id')->nullable()->after('status');
            $table->text('notes')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->index(['sekolah_id', 'section', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sekolah_assessments', function (Blueprint $table) {
            $table->dropIndex(['sekolah_id', 'section', 'taken_at']);
            $table->dropColumn(['section', 'status', 'consultant_id', 'notes', 'conclusion', 'concluded_at']);
        });

        Schema::table('perusahaan_assessments', function (Blueprint $table) {
            $table->dropIndex(['perusahaan_id', 'section', 'taken_at']);
            $table->dropColumn(['section', 'status', 'consultant_id', 'notes', 'conclusion', 'concluded_at']);
        });

        Schema::dropIfExists('screening_answers');
        Schema::dropIfExists('screening_questions');
    }
};