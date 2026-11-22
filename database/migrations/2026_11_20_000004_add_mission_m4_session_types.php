<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// M4 (MASTER-PLAN K8/K9/K10): feature flag lock request konsultan per perusahaan
// + tipe konsultasi (TERPISAH dari konsultan_type) + meeting_url override + room 1-on-1.
// Backfill consultation_type='chat' untuk row existing. TEXT, tanpa CHECK baru (sqlite-safe).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaans', 'consultant_request_enabled')) {
                $table->boolean('consultant_request_enabled')->default(false)->after('is_active');
            }
        });

        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaan_sessions', 'consultation_type')) {
                $table->string('consultation_type', 30)->default('chat')->after('konsultan_type'); // chat|online_meeting|one_on_one
            }
            if (! Schema::hasColumn('perusahaan_sessions', 'meeting_url')) {
                $table->string('meeting_url')->nullable()->after('consultation_type'); // override manual per sesi
            }
            if (! Schema::hasColumn('perusahaan_sessions', 'room_id')) {
                // FK dipasang di M5 setelah tabel rooms dibuat.
                $table->unsignedBigInteger('room_id')->nullable()->after('meeting_url');
            }
        });

        // Backfill row existing (kolom baru default 'chat' hanya untuk insert baru).
        if (Schema::hasColumn('perusahaan_sessions', 'consultation_type')) {
            DB::table('perusahaan_sessions')->whereNull('consultation_type')->update(['consultation_type' => 'chat']);
        }
    }

    public function down(): void
    {
        Schema::table('perusahaan_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan_sessions', 'room_id')) {
                $table->dropColumn('room_id');
            }
            if (Schema::hasColumn('perusahaan_sessions', 'meeting_url')) {
                $table->dropColumn('meeting_url');
            }
            if (Schema::hasColumn('perusahaan_sessions', 'consultation_type')) {
                $table->dropColumn('consultation_type');
            }
        });

        Schema::table('perusahaans', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaans', 'consultant_request_enabled')) {
                $table->dropColumn('consultant_request_enabled');
            }
        });
    }
};
