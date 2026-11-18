<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ONB.4 — penanda konfirmasi profil login pertama.
 *
 * Ditaruh di `users`, bukan di row roster: row roster lama (input manual HR,
 * self-register) tidak boleh tiba-tiba kena gerbang. Hanya import CSV yang
 * menyalakan flag ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_confirm_profile')->default(false)->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_confirm_profile');
        });
    }
};