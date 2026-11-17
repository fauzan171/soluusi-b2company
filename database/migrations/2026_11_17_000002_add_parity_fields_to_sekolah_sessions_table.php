<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Parity fields untuk SekolahSession — mirror perusahaan_sessions
// (lihat 2026_11_15_000003 + 2026_11_15_000004).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah_sessions', function (Blueprint $table) {
            $table->text('request_notes')->nullable();
            $table->unsignedInteger('schedule_version')->default(1);
            $table->foreignId('requested_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('requested_for_role')->default('self'); // self|bk|parent
        });
    }

    public function down(): void
    {
        Schema::table('sekolah_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by_user_id');
            $table->dropColumn(['request_notes', 'schedule_version', 'requested_for_role']);
        });
    }
};
