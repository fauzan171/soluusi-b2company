<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Status 'cancelled' ditolak SQLite.
 *
 * Migrasi 2026_08_18_080001 hanya mengubah enum MySQL dengan asumsi "SQLite
 * tidak enforce enum". Itu keliru: kolomnya dibuat sebagai
 * varchar + CHECK ("status" in ('scheduled','done','no_show')), sehingga
 * setiap pembatalan sesi perusahaan gagal dengan integrity constraint
 * violation — di produksi yang juga SQLite, bukan cuma di test.
 *
 * SQLite tak bisa mengubah CHECK, jadi tabel dibangun ulang. DDL disalin apa
 * adanya dari skema berjalan supaya tidak ada kolom/index/FK yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE perusahaan_sessions MODIFY status ENUM('scheduled', 'done', 'no_show', 'cancelled') NOT NULL DEFAULT 'scheduled'");

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement(<<<'SQL'
            CREATE TABLE perusahaan_sessions_new (
                "id" integer not null primary key autoincrement,
                "perusahaan_id" integer not null,
                "employee_id" integer,
                "departement_id" integer,
                "consultant_id" integer,
                "konsultan_type" varchar not null default 'psikolog',
                "topic_category" varchar,
                "status" varchar check ("status" in ('scheduled', 'done', 'no_show', 'cancelled')) not null default 'scheduled',
                "rating" integer,
                "completed_at" datetime,
                "scheduled_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                "schedule_id" integer,
                "ended_at" datetime,
                "quota_consumed" tinyint(1) not null default '1',
                "cancel_reason" text,
                "cancelled_at" datetime,
                "checked_in_at" datetime,
                "meeting_url" varchar,
                "location" varchar,
                "delivery_status" varchar not null default 'pending',
                "no_show_party" varchar,
                foreign key("perusahaan_id") references "perusahaans"("id") on delete cascade,
                foreign key("employee_id") references "perusahaan_employees"("id") on delete set null,
                foreign key("departement_id") references "departements"("id") on delete set null,
                foreign key("consultant_id") references "consultants"("id") on delete set null
            )
        SQL);

        DB::statement('INSERT INTO perusahaan_sessions_new SELECT * FROM perusahaan_sessions');
        DB::statement('DROP TABLE perusahaan_sessions');
        DB::statement('ALTER TABLE perusahaan_sessions_new RENAME TO perusahaan_sessions');
        DB::statement('CREATE INDEX "perusahaan_sessions_perusahaan_id_status_index" on "perusahaan_sessions" ("perusahaan_id", "status")');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Tidak dikembalikan: menghapus 'cancelled' dari CHECK akan merusak baris
        // yang sudah dibatalkan. Membiarkan nilai yang lebih longgar itu aman.
    }
};