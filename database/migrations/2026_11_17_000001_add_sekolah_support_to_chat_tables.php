<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Sekolah parity: chat_messages + session_summaries + session_slot_proposals
// bisa menunjuk sekolah_session_id atau perusahaan_session_id (XOR).
// CHECK constraint exactly-one-non-null via raw SQL — di MySQL 8.0.16+ enforce,
// di SQLite dipasang saat rebuild tabel (ALTER ADD CONSTRAINT tidak didukung).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqlite();
        } else {
            $this->alterMysql();
        }
    }

    private function alterMysql(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('perusahaan_session_id')->nullable()->change();
            $table->foreignId('sekolah_session_id')->nullable()
                ->constrained('sekolah_sessions')->cascadeOnDelete();
            $table->index(['sekolah_session_id', 'id']);
        });

        Schema::table('session_summaries', function (Blueprint $table) {
            $table->dropUnique(['perusahaan_session_id']);
            $table->foreignId('perusahaan_session_id')->nullable()->change();
            $table->unique('perusahaan_session_id');
            $table->foreignId('sekolah_session_id')->nullable()->unique()
                ->constrained('sekolah_sessions')->cascadeOnDelete();
        });

        Schema::table('session_slot_proposals', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->change();
            $table->foreignId('sekolah_session_id')->nullable()
                ->constrained('sekolah_sessions')->cascadeOnDelete();
            $table->index('sekolah_session_id');
        });

        DB::statement('ALTER TABLE chat_messages ADD CONSTRAINT chat_messages_one_session_chk CHECK ((perusahaan_session_id IS NULL) <> (sekolah_session_id IS NULL))');
        DB::statement('ALTER TABLE session_summaries ADD CONSTRAINT session_summaries_one_session_chk CHECK ((perusahaan_session_id IS NULL) <> (sekolah_session_id IS NULL))');
        DB::statement('ALTER TABLE session_slot_proposals ADD CONSTRAINT session_slot_proposals_one_session_chk CHECK ((session_id IS NULL) <> (sekolah_session_id IS NULL))');
    }

    private function rebuildSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // chat_messages
        DB::statement(<<<'SQL'
            CREATE TABLE chat_messages_new (
                "id" integer not null primary key autoincrement,
                "perusahaan_session_id" integer,
                "sekolah_session_id" integer,
                "sender_id" integer not null,
                "body" text not null,
                "read_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                foreign key("perusahaan_session_id") references "perusahaan_sessions"("id") on delete cascade,
                foreign key("sekolah_session_id") references "sekolah_sessions"("id") on delete cascade,
                foreign key("sender_id") references "users"("id") on delete cascade,
                check ((perusahaan_session_id is null) <> (sekolah_session_id is null))
            )
        SQL);
        DB::statement('INSERT INTO chat_messages_new ("id","perusahaan_session_id","sender_id","body","read_at","created_at","updated_at") SELECT "id","perusahaan_session_id","sender_id","body","read_at","created_at","updated_at" FROM chat_messages');
        DB::statement('DROP TABLE chat_messages');
        DB::statement('ALTER TABLE chat_messages_new RENAME TO chat_messages');
        DB::statement('CREATE INDEX "chat_messages_perusahaan_session_id_id_index" on "chat_messages" ("perusahaan_session_id", "id")');
        DB::statement('CREATE INDEX "chat_messages_sekolah_session_id_id_index" on "chat_messages" ("sekolah_session_id", "id")');

        // session_summaries
        DB::statement(<<<'SQL'
            CREATE TABLE session_summaries_new (
                "id" integer not null primary key autoincrement,
                "perusahaan_session_id" integer,
                "sekolah_session_id" integer,
                "summary" text,
                "client_advice" text,
                "input_hash" varchar(64) not null,
                "model" varchar,
                "status" varchar(16) not null default 'pending',
                "error_code" varchar(64),
                "generated_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                foreign key("perusahaan_session_id") references "perusahaan_sessions"("id") on delete cascade,
                foreign key("sekolah_session_id") references "sekolah_sessions"("id") on delete cascade,
                check ((perusahaan_session_id is null) <> (sekolah_session_id is null))
            )
        SQL);
        DB::statement('INSERT INTO session_summaries_new ("id","perusahaan_session_id","summary","client_advice","input_hash","model","status","error_code","generated_at","created_at","updated_at") SELECT "id","perusahaan_session_id","summary","client_advice","input_hash","model","status","error_code","generated_at","created_at","updated_at" FROM session_summaries');
        DB::statement('DROP TABLE session_summaries');
        DB::statement('ALTER TABLE session_summaries_new RENAME TO session_summaries');
        DB::statement('CREATE UNIQUE INDEX "session_summaries_perusahaan_session_id_unique" on "session_summaries" ("perusahaan_session_id")');
        DB::statement('CREATE UNIQUE INDEX "session_summaries_sekolah_session_id_unique" on "session_summaries" ("sekolah_session_id")');

        // session_slot_proposals
        DB::statement(<<<'SQL'
            CREATE TABLE session_slot_proposals_new (
                "id" integer not null primary key autoincrement,
                "session_id" integer,
                "sekolah_session_id" integer,
                "consultant_id" integer not null,
                "proposed_at" datetime,
                "slots" text not null,
                "status" varchar not null default 'pending',
                "created_at" datetime,
                "updated_at" datetime,
                foreign key("session_id") references "perusahaan_sessions"("id") on delete cascade,
                foreign key("sekolah_session_id") references "sekolah_sessions"("id") on delete cascade,
                foreign key("consultant_id") references "consultants"("id") on delete cascade,
                check ((session_id is null) <> (sekolah_session_id is null))
            )
        SQL);
        DB::statement('INSERT INTO session_slot_proposals_new ("id","session_id","consultant_id","proposed_at","slots","status","created_at","updated_at") SELECT "id","session_id","consultant_id","proposed_at","slots","status","created_at","updated_at" FROM session_slot_proposals');
        DB::statement('DROP TABLE session_slot_proposals');
        DB::statement('ALTER TABLE session_slot_proposals_new RENAME TO session_slot_proposals');
        DB::statement('CREATE INDEX "session_slot_proposals_sekolah_session_id_index" on "session_slot_proposals" ("sekolah_session_id")');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Tidak di-reverse penuh: rollback = drop kolom sekolah_session_id.
        // FK lama tetap nullable karena mengembalikannya ke NOT NULL akan
        // merusak baris sekolah yang sudah ada. Pola ini konsisten dengan
        // 2026_10_21_000001_fix_perusahaan_sessions_status_check_on_sqlite.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE chat_messages DROP CHECK chat_messages_one_session_chk');
        DB::statement('ALTER TABLE session_summaries DROP CHECK session_summaries_one_session_chk');
        DB::statement('ALTER TABLE session_slot_proposals DROP CHECK session_slot_proposals_one_session_chk');

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sekolah_session_id');
        });
        Schema::table('session_summaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sekolah_session_id');
        });
        Schema::table('session_slot_proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sekolah_session_id');
        });
    }
};
