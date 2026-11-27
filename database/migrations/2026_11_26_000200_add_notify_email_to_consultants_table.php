<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Email notifikasi konsultan (real, dari B2C). User B2B konsultan sync pakai
// placeholder b2c-{id}@b2b.local yang tidak bisa menerima mail — kolom ini
// diisi B2cConsultantContact::refresh() tiap malam setelah consultants:sync-b2c.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('notify_email')->nullable()->after('b2c_consultant_id');
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn('notify_email');
        });
    }
};
