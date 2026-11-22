<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// W5 (K11, research-D): sender_role agar tampilan chat tak perlu join; backfill
// 'employee' (sender legacy = pemilik sesi karyawan; konsultan lama minor —
// command chat:encrypt-existing tidak menyentuh kolom ini, backfill best-effort).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_messages') && ! Schema::hasColumn('chat_messages', 'sender_role')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->string('sender_role', 20)->default('employee')->after('sender_id'); // employee|consultant
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chat_messages', 'sender_role')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn('sender_role');
            });
        }
    }
};
