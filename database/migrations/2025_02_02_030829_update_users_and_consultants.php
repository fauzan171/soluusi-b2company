<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop kolom dari tabel consultants
        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'bank', 'account_number']);
        });

        // Tambahkan kolom baru ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank')->nullable()->after('lastname');
            $table->string('account_number')->nullable()->after('bank');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Kembalikan perubahan jika rollback
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('phone_number')->nullable();
            $table->string('bank')->nullable();
            $table->string('account_number')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank', 'account_number']);
        });
    }
};
