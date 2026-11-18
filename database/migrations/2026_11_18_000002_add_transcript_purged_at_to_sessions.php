<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// D.7: penanda transkrip yang sudah dipurge. Tanpa kolom ini "sudah dipurge"
// dan "belum pernah chat" tidak bisa dibedakan (dua-duanya 0 pesan).
return new class extends Migration
{
    private const TABLES = ['perusahaan_sessions', 'sekolah_sessions'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('transcript_purged_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('transcript_purged_at');
            });
        }
    }
};