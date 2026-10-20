<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Model C: user_id nullable (guest). Create migration sudah nullable untuk
     *  fresh install; ini untuk DB yang terlanjur di-migrate sebelumnya. */
    public function up()
    {
        // Fresh SQLite sudah nullable sejak migration create. Doctrine DBAL
        // pada Laravel 9 dapat membangun ulang tabel secara lossy saat change()
        // di sini (kolom consultant_id/package_id/amount ikut hilang).
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
